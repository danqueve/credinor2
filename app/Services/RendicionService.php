<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Audit;
use App\Helpers\Database;
use App\Models\Pago;
use App\Models\Rendicion;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Repositories\RendicionRepository;

class RendicionService
{
    private RendicionRepository $repo;
    private PagoRepository      $pagoRepo;
    private CreditoRepository   $creditoRepo;
    private ClienteRepository   $clienteRepo;
    private PagoService         $pagoService;

    public function __construct()
    {
        $this->repo        = new RendicionRepository();
        $this->pagoRepo    = new PagoRepository();
        $this->creditoRepo = new CreditoRepository();
        $this->clienteRepo = new ClienteRepository();
        $this->pagoService = new PagoService();
    }

    /**
     * Procesa una rendición completa con carga bulk en una sola transacción.
     *
     * $header = [id_cobrador, fecha_rendicion, total_efectivo_declarado, total_transferencias_declarado, observaciones]
     * $filas  = [[ id_credito, monto, forma_pago, fecha_pago_real ], ...]
     *
     * @param array<string, mixed>              $header
     * @param array<int, array<string, mixed>>  $filas
     * @return array{ok: bool, message: string, id_rendicion?: int, errors?: array<int, string>}
     */
    public function crear(array $header, array $filas, int $usuarioId): array
    {
        // ── Validar cabecera ──────────────────────────────────────────────────
        $errors = [];

        $idCobrador      = (int)($header['id_cobrador']      ?? 0);
        $fechaRendicion  = trim($header['fecha_rendicion']   ?? '');
        $totalEfectivo   = round(max(0.0, (float)str_replace(',', '.', $header['total_efectivo_declarado']   ?? '0')), 2);
        $totalTransf     = round(max(0.0, (float)str_replace(',', '.', $header['total_transferencias_declarado'] ?? '0')), 2);
        $observaciones   = !empty($header['observaciones']) ? trim($header['observaciones']) : null;

        if ($idCobrador <= 0)   $errors[] = 'Debe seleccionar un cobrador.';
        $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaRendicion);
        if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fechaRendicion) {
            $errors[] = 'Fecha de rendición inválida.';
        }
        if (empty($filas))      $errors[] = 'La rendición no tiene filas cargadas.';

        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        // ── Validar y pre-calcular cada fila ──────────────────────────────────
        $filasValidadas = [];
        $filaErrors     = [];

        foreach ($filas as $idx => $fila) {
            $nFila    = $idx + 1;
            $idCred   = (int)($fila['id_credito']    ?? 0);
            $monto    = round((float)str_replace(',', '.', $fila['monto'] ?? '0'), 2);
            $forma    = trim($fila['forma_pago']     ?? 'efectivo');
            $fechaReal = trim($fila['fecha_pago_real'] ?? $fechaRendicion);

            if ($idCred <= 0)   { $filaErrors[$idx] = "Fila {$nFila}: crédito inválido."; continue; }
            if ($monto <= 0)    { $filaErrors[$idx] = "Fila {$nFila}: el monto debe ser mayor a cero."; continue; }
            if (!in_array($forma, ['efectivo','transferencia','mp','otro'], true)) {
                $filaErrors[$idx] = "Fila {$nFila}: forma de pago inválida.";
                continue;
            }
            $fDt = \DateTime::createFromFormat('Y-m-d', $fechaReal);
            if (!$fDt || $fDt->format('Y-m-d') !== $fechaReal) {
                $filaErrors[$idx] = "Fila {$nFila}: fecha de pago real inválida.";
                continue;
            }

            $credito = $this->creditoRepo->findById($idCred);
            if (!$credito || $credito->estado !== 'activo') {
                $filaErrors[$idx] = "Fila {$nFila}: el crédito {$idCred} no está activo.";
                continue;
            }
            if ($monto > round($credito->saldo_pendiente + 0.005, 2)) {
                $filaErrors[$idx] = "Fila {$nFila} ({$credito->codigo}): monto \${$monto} supera el saldo \${$credito->saldo_pendiente}.";
                continue;
            }

            $filasValidadas[] = [
                'id_credito'      => $idCred,
                'monto'           => $monto,
                'forma_pago'      => $forma,
                'fecha_pago_real' => $fechaReal,
                'credito'         => $credito,
            ];
        }

        if (!empty($filaErrors)) {
            return [
                'ok'      => false,
                'message' => 'Hay errores en algunas filas. Corríjalas antes de confirmar.',
                'errors'  => $filaErrors,
            ];
        }

        // ── Transacción única ─────────────────────────────────────────────────
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Crear cabecera de la rendición (borrador)
            $rendicion = new Rendicion();
            $rendicion->id_cobrador                    = $idCobrador;
            $rendicion->fecha_rendicion                = $fechaRendicion;
            $rendicion->total_efectivo_declarado       = $totalEfectivo;
            $rendicion->total_transferencias_declarado = $totalTransf;
            $rendicion->total_registrado               = 0.0;
            $rendicion->estado                         = 'borrador';
            $rendicion->observaciones                  = $observaciones;
            $rendicion->created_by                     = $usuarioId;

            $idRendicion      = $this->repo->insert($rendicion);
            $totalRegistrado  = 0.0;
            $recibosPendientes = [];

            // 2. Procesar cada fila (FIFO + inserción) dentro de la misma transacción
            foreach ($filasValidadas as $fila) {
                $idCred    = $fila['id_credito'];
                $monto     = $fila['monto'];
                $forma     = $fila['forma_pago'];
                $fechaReal = $fila['fecha_pago_real'];

                // Cuotas pendientes en el momento de procesar esta fila
                $cuotasPendientes = $this->pagoRepo->getCuotasPendientes($idCred);
                $fifo = $this->pagoService->calcularFifo($monto, $cuotasPendientes);

                // Insertar pago con id_rendicion
                $pago = new Pago();
                $pago->id_credito        = $idCred;
                $pago->id_cobrador       = $idCobrador;
                $pago->monto_pagado      = $monto;
                $pago->forma_pago        = $forma;
                $pago->fecha_pago_real   = $fechaReal;
                $pago->id_rendicion      = $idRendicion;
                $pago->created_by        = $usuarioId;

                $idPago = $this->pagoRepo->insert($pago);

                // Aplicar FIFO a cuotas
                foreach ($fifo['aplicaciones'] as $ap) {
                    $nuevoMonto = round($ap['monto_pagado_prev'] + $ap['monto_aplicado'], 2);

                    // Obtener monto total de la cuota para determinar estado
                    $cuotaRow = null;
                    foreach ($cuotasPendientes as $q) {
                        if ((int)$q['id_cuota'] === $ap['id_cuota']) { $cuotaRow = $q; break; }
                    }
                    $montoTotal = (float)($cuotaRow['monto_esperado'] ?? 0) + (float)($cuotaRow['monto_recargo'] ?? 0);

                    if ($nuevoMonto >= $montoTotal) {
                        $nuevoEstado      = 'pagada';
                        $nuevaFechaPagada = $fechaReal;
                    } else {
                        $nuevoEstado      = 'parcial';
                        $nuevaFechaPagada = null;
                    }

                    $this->pagoRepo->updateCuota($ap['id_cuota'], $nuevoMonto, $nuevoEstado, $nuevaFechaPagada);
                    $this->pagoRepo->insertPagoCuota($idPago, $ap['id_cuota'], $ap['monto_aplicado']);
                }

                // Recalcular saldo del crédito
                $nuevoSaldo   = $this->pagoRepo->recalcularSaldoCredito($idCred);
                $estadoCred   = round($nuevoSaldo, 2) <= 0 ? 'finalizado' : 'activo';
                $this->pagoRepo->updateCreditoSaldoYEstado($idCred, $nuevoSaldo, $estadoCred);

                // Generar recibo (pdf_path se actualiza post-commit)
                $numRecibo = $this->pagoRepo->generateNumeroRecibo();
                $idRecibo  = $this->pagoRepo->insertRecibo($idPago, $numRecibo, null);

                // Guardar datos para generar PDF después del commit
                $recibosPendientes[] = [
                    'id_recibo'    => $idRecibo,
                    'num_recibo'   => $numRecibo,
                    'id_pago'      => $idPago,
                    'id_credito'   => $idCred,
                    'monto'        => $monto,
                    'forma'        => $forma,
                    'fecha_real'   => $fechaReal,
                    'cuotas_fifo'  => $fifo['aplicaciones'],
                    'credito'      => $fila['credito'],
                ];

                $totalRegistrado = round($totalRegistrado + $monto, 2);
            }

            // 3. Actualizar totales y estado de la rendición
            $totalDeclarado = round($totalEfectivo + $totalTransf, 2);
            $diferencia     = round($totalDeclarado - $totalRegistrado, 2);
            $estadoFinal    = abs($diferencia) < 0.005 ? 'conciliada' : 'con_diferencia';

            $this->repo->updateTotales($idRendicion, $totalRegistrado, $estadoFinal);

            $db->commit();

            // 3b. Generar PDFs fuera de la transacción
            foreach ($recibosPendientes as $rp) {
                try {
                    $credito = $rp['credito'];
                    $cliente = $this->clienteRepo->findById($credito->id_cliente);
                    $cuotasStr = implode(', ', array_map(
                        fn($ap) => '#' . $ap['numero_cuota'],
                        $rp['cuotas_fifo']
                    ));
                    $pdfPath = (new \App\Services\ReciboService())->generar([
                        'numero_recibo'      => $rp['num_recibo'],
                        'codigo_credito'     => $credito->codigo,
                        'cliente_nombre'     => $cliente ? trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) : '—',
                        'cliente_dni'        => $cliente->dni ?? '—',
                        'monto'              => $rp['monto'],
                        'forma_pago'         => $rp['forma'],
                        'fecha_pago_real'    => $rp['fecha_real'],
                        'fecha_registro'     => date('Y-m-d H:i:s'),
                        'cobrador_nombre'    => $credito->cobrador_nombre ?? '—',
                        'cuotas_aplicadas'   => $cuotasStr,
                        'referencia_externa' => null,
                    ]);
                    $this->pagoRepo->updateReciboPdfPath($rp['id_recibo'], $pdfPath);
                } catch (\Throwable) {
                    // PDF fallido no revierte la rendición confirmada
                }
            }

            Audit::log('rendicion.create', 'rendiciones', $idRendicion, null, [
                'id_cobrador'     => $idCobrador,
                'fecha'           => $fechaRendicion,
                'total_declarado' => $totalDeclarado,
                'total_registrado' => $totalRegistrado,
                'diferencia'      => $diferencia,
                'estado'          => $estadoFinal,
                'filas'           => count($filasValidadas),
            ]);

            return [
                'ok'           => true,
                'message'      => "Rendición creada ({$estadoFinal}). {$totalRegistrado} registrado / {$totalDeclarado} declarado.",
                'id_rendicion' => $idRendicion,
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error interno al guardar la rendición: ' . $e->getMessage()];
        }
    }
}
