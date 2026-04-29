<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Audit;
use App\Helpers\Database;
use App\Models\Pago;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Services\ReciboService;

class PagoService
{
    private PagoRepository    $pagoRepo;
    private CreditoRepository $creditoRepo;
    private ClienteRepository $clienteRepo;

    public function __construct()
    {
        $this->pagoRepo    = new PagoRepository();
        $this->creditoRepo = new CreditoRepository();
        $this->clienteRepo = new ClienteRepository();
    }

    /**
     * Calcula (sin guardar) cómo se distribuiría un monto entre las cuotas pendientes (FIFO).
     *
     * @param array<int, array{id_cuota:int, numero_cuota:int, fecha_vencimiento:string, monto_esperado:float, monto_pagado:float, monto_recargo:float}> $cuotasPendientes
     * @return array{aplicaciones: array[], monto_restante: float}
     */
    public function calcularFifo(float $monto, array $cuotasPendientes): array
    {
        $restante    = round($monto, 2);
        $aplicaciones = [];

        foreach ($cuotasPendientes as $q) {
            if ($restante <= 0) break;

            $saldo    = round((float)$q['monto_esperado'] + (float)$q['monto_recargo'] - (float)$q['monto_pagado'], 2);
            $aplicar  = round(min($saldo, $restante), 2);

            if ($aplicar <= 0) continue;

            $aplicaciones[] = [
                'id_cuota'          => (int)$q['id_cuota'],
                'numero_cuota'      => (int)$q['numero_cuota'],
                'fecha_vencimiento' => $q['fecha_vencimiento'],
                'monto_esperado'    => (float)$q['monto_esperado'],
                'monto_pagado_prev' => (float)$q['monto_pagado'],
                'monto_aplicado'    => $aplicar,
                'saldo_nuevo'       => round($saldo - $aplicar, 2),
            ];
            $restante = round($restante - $aplicar, 2);
        }

        return ['aplicaciones' => $aplicaciones, 'monto_restante' => $restante];
    }

    /**
     * Registra un pago con aplicación FIFO en una sola transacción.
     *
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string, id_pago?: int, numero_recibo?: string, errors?: string[]}
     */
    public function registrar(array $data, int $usuarioId): array
    {
        // ── Validaciones ──────────────────────────────────────────────────────
        $errors = [];

        $idCredito      = (int)($data['id_credito']    ?? 0);
        $monto          = round((float)str_replace(',', '.', $data['monto_pagado'] ?? '0'), 2);
        $formaPago      = trim($data['forma_pago']     ?? '');
        $fechaPagoReal  = trim($data['fecha_pago_real'] ?? '');
        $idCobrador     = !empty($data['id_cobrador'])  ? (int)$data['id_cobrador']  : null;
        $refExterna     = !empty($data['referencia_externa']) ? trim($data['referencia_externa']) : null;
        $observaciones  = !empty($data['observaciones'])      ? trim($data['observaciones'])      : null;

        if ($idCredito <= 0)        $errors[] = 'Debe seleccionar un crédito.';
        if ($monto <= 0)            $errors[] = 'El monto debe ser mayor a cero.';
        if (!in_array($formaPago, ['efectivo', 'transferencia', 'mp', 'otro'], true)) {
            $errors[] = 'Forma de pago inválida.';
        }

        $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaPagoReal);
        if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fechaPagoReal) {
            $errors[] = 'Fecha de pago real inválida.';
        }

        // RN-02: fecha_pago_real ≤ hoy
        if ($fechaDt && $fechaDt > new \DateTime('today')) {
            $errors[] = 'La fecha de pago real no puede ser futura.';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        // ── Cargar crédito y validar estado ───────────────────────────────────
        $credito = $this->creditoRepo->findById($idCredito);
        if (!$credito) {
            return ['ok' => false, 'message' => 'Crédito no encontrado.'];
        }
        if ($credito->estado === 'finalizado') {
            return ['ok' => false, 'message' => 'El crédito ya está finalizado y no acepta nuevos pagos.'];
        }
        if (!in_array($credito->estado, ['activo', 'incobrable'], true)) {
            return ['ok' => false, 'message' => "El crédito en estado '{$credito->estado}' no acepta pagos."];
        }

        // RN-03: fecha_pago_real ≥ fecha_inicio del crédito
        $fechaInicioDt = \DateTime::createFromFormat('Y-m-d', $credito->fecha_inicio);
        if ($fechaDt < $fechaInicioDt) {
            return ['ok' => false, 'message' => "La fecha de pago no puede ser anterior al inicio del crédito ({$credito->fecha_inicio})."];
        }

        // RN-04: monto ≤ saldo pendiente
        if ($monto > round($credito->saldo_pendiente + 0.005, 2)) {
            return ['ok' => false, 'message' => "El monto ($monto) supera el saldo pendiente ({$credito->saldo_pendiente})."];
        }

        // ── Distribución FIFO ──────────────────────────────────────────────────
        $cuotasPendientes = $this->pagoRepo->getCuotasPendientes($idCredito);
        $fifo = $this->calcularFifo($monto, $cuotasPendientes);

        if (empty($fifo['aplicaciones'])) {
            return ['ok' => false, 'message' => 'No hay cuotas pendientes para aplicar el pago.'];
        }

        // ── Transacción ────────────────────────────────────────────────────────
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Insertar pago
            $pago = new Pago();
            $pago->id_credito        = $idCredito;
            $pago->id_cobrador       = $idCobrador;
            $pago->monto_pagado      = $monto;
            $pago->forma_pago        = $formaPago;
            $pago->referencia_externa = $refExterna;
            $pago->fecha_pago_real   = $fechaPagoReal;
            $pago->observaciones     = $observaciones;
            $pago->created_by        = $usuarioId;

            $idPago = $this->pagoRepo->insert($pago);

            // 2. Aplicar FIFO: actualizar cuotas y registrar en pago_cuotas
            foreach ($fifo['aplicaciones'] as $ap) {
                $nuevoMontoPagado = round($ap['monto_pagado_prev'] + $ap['monto_aplicado'], 2);

                // Determinar nuevo estado de la cuota
                $cuotaRow = null;
                foreach ($cuotasPendientes as $q) {
                    if ((int)$q['id_cuota'] === $ap['id_cuota']) {
                        $cuotaRow = $q;
                        break;
                    }
                }
                $montoTotal = (float)($cuotaRow['monto_esperado'] ?? 0) + (float)($cuotaRow['monto_recargo'] ?? 0);
                $nuevaFechaPagada = null;
                if ($nuevoMontoPagado >= $montoTotal) {
                    $nuevoEstado      = 'pagada';
                    $nuevaFechaPagada = $fechaPagoReal;
                } else {
                    $nuevoEstado = 'parcial';
                }

                $this->pagoRepo->updateCuota($ap['id_cuota'], $nuevoMontoPagado, $nuevoEstado, $nuevaFechaPagada);
                $this->pagoRepo->insertPagoCuota($idPago, $ap['id_cuota'], $ap['monto_aplicado']);
            }

            // 3. Recalcular saldo del crédito
            $nuevoSaldo   = $this->pagoRepo->recalcularSaldoCredito($idCredito);
            $nuevoEstadoCr = round($nuevoSaldo, 2) <= 0 ? 'finalizado' : 'activo';
            $this->pagoRepo->updateCreditoSaldoYEstado($idCredito, $nuevoSaldo, $nuevoEstadoCr);

            // 4. Generar número de recibo y reservar fila (pdf_path se actualiza post-commit)
            $numeroRecibo = $this->pagoRepo->generateNumeroRecibo();
            $idRecibo     = $this->pagoRepo->insertRecibo($idPago, $numeroRecibo, null);

            $db->commit();

            // 5. Generar PDF fuera de la transacción (fallo de PDF no revierte el pago)
            $pdfPath = null;
            try {
                $cliente = $this->clienteRepo->findById($credito->id_cliente);
                $cuotasStr = implode(', ', array_map(
                    fn($ap) => '#' . $ap['numero_cuota'],
                    $fifo['aplicaciones']
                ));
                $pdfPath = (new ReciboService())->generar([
                    'numero_recibo'      => $numeroRecibo,
                    'codigo_credito'     => $credito->codigo,
                    'cliente_nombre'     => $cliente ? trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) : '—',
                    'cliente_dni'        => $cliente->dni ?? '—',
                    'monto'              => $monto,
                    'forma_pago'         => $formaPago,
                    'fecha_pago_real'    => $fechaPagoReal,
                    'fecha_registro'     => date('Y-m-d H:i:s'),
                    'cobrador_nombre'    => $idCobrador ? '—' : '—',
                    'cuotas_aplicadas'   => $cuotasStr,
                    'referencia_externa' => $refExterna,
                ]);
                $this->pagoRepo->updateReciboPdfPath($idRecibo, $pdfPath);
            } catch (\Throwable) {
                // PDF fallido no impide el pago ya confirmado
            }

            Audit::log('pago.create', 'pagos', $idPago, null, [
                'id_credito'       => $idCredito,
                'monto'            => $monto,
                'forma_pago'       => $formaPago,
                'fecha_pago_real'  => $fechaPagoReal,
                'cuotas_afectadas' => count($fifo['aplicaciones']),
                'numero_recibo'    => $numeroRecibo,
            ]);

            return [
                'ok'            => true,
                'message'       => "Pago registrado. Recibo: {$numeroRecibo}.",
                'id_pago'       => $idPago,
                'numero_recibo' => $numeroRecibo,
                'pdf_path'      => $pdfPath,
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error interno al registrar el pago. Intente de nuevo.'];
        }
    }

    /**
     * Anula un pago: revierte las cuotas y recalcula el saldo del crédito.
     *
     * @return array{ok: bool, message: string}
     */
    public function anular(int $idPago, string $motivo, int $usuarioId): array
    {
        if (mb_strlen(trim($motivo)) < 10) {
            return ['ok' => false, 'message' => 'El motivo debe tener al menos 10 caracteres.'];
        }

        $pago = $this->pagoRepo->findById($idPago);
        if (!$pago) {
            return ['ok' => false, 'message' => 'Pago no encontrado.'];
        }
        if ($pago->anulado) {
            return ['ok' => false, 'message' => 'El pago ya está anulado.'];
        }

        $aplicaciones = $this->pagoRepo->getCuotasAplicadas($idPago);
        $cuotasPendientes = $this->pagoRepo->getCuotasPendientes($pago->id_credito);

        // Necesitamos también las cuotas pagadas para revertir
        $db = Database::getInstance();
        $stmtAll = $db->prepare("SELECT * FROM cuotas WHERE id_credito = ? ORDER BY numero_cuota ASC");
        $stmtAll->execute([$pago->id_credito]);
        $todasCuotas = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);

        $db->beginTransaction();
        try {
            // Revertir cada aplicación
            foreach ($aplicaciones as $ap) {
                $cuota = null;
                foreach ($todasCuotas as $c) {
                    if ((int)$c['id_cuota'] === (int)$ap['id_cuota']) {
                        $cuota = $c;
                        break;
                    }
                }
                if (!$cuota) continue;

                $nuevoMontoPagado = max(0.0, round((float)$cuota['monto_pagado'] - (float)$ap['monto_aplicado'], 2));
                $montoTotal = (float)$cuota['monto_esperado'] + (float)$cuota['monto_recargo'];

                if ($nuevoMontoPagado <= 0) {
                    $nuevoEstado = (new \DateTime($cuota['fecha_vencimiento']) < new \DateTime('today'))
                        ? 'vencida' : 'pendiente';
                    $nuevaFechaPagada = null;
                } elseif ($nuevoMontoPagado < $montoTotal) {
                    $nuevoEstado      = 'parcial';
                    $nuevaFechaPagada = $cuota['fecha_pagada'];
                } else {
                    $nuevoEstado      = 'pagada';
                    $nuevaFechaPagada = $cuota['fecha_pagada'];
                }

                $this->pagoRepo->updateCuota((int)$ap['id_cuota'], $nuevoMontoPagado, $nuevoEstado, $nuevaFechaPagada);
            }

            // Anular el pago
            $this->pagoRepo->anular($idPago, $motivo, $usuarioId);

            // Recalcular saldo del crédito
            $nuevoSaldo   = $this->pagoRepo->recalcularSaldoCredito($pago->id_credito);
            $credito      = $this->creditoRepo->findById($pago->id_credito);
            $estadoActual = $credito ? $credito->estado : 'activo';
            // Si el crédito estaba finalizado por este pago, reabrirlo
            $nuevoEstadoCr = ($estadoActual === 'finalizado') ? 'activo' : $estadoActual;
            $this->pagoRepo->updateCreditoSaldoYEstado($pago->id_credito, $nuevoSaldo, $nuevoEstadoCr);

            $db->commit();

            Audit::log('pago.anular', 'pagos', $idPago,
                ['anulado' => false, 'monto' => $pago->monto_pagado],
                ['anulado' => true, 'motivo' => $motivo]
            );

            return ['ok' => true, 'message' => 'Pago anulado correctamente.'];

        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error interno al anular el pago.'];
        }
    }
}
