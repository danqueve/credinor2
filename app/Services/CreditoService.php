<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Audit;
use App\Helpers\Database;
use App\Models\Credito;
use App\Repositories\CreditoRepository;

class CreditoService
{
    private CreditoRepository      $repo;
    private CuotaCalendarioService $calendario;

    public function __construct()
    {
        $this->repo      = new CreditoRepository();
        $this->calendario = new CuotaCalendarioService();
    }

    /**
     * Calcula monto_total, interes_implicito e interes_implicito_pct para preview en vivo.
     *
     * @return array{monto_total: float, interes_implicito: float, interes_implicito_pct: float}
     */
    public function calcularPreview(float $capital, int $cantidadCuotas, float $valorCuota, float $gastosAdmin = 0.0): array
    {
        $montoTotal        = round($cantidadCuotas * $valorCuota, 2);
        $interesImplicito  = round($montoTotal - $capital - $gastosAdmin, 2);
        $interesImplicitoPct = $capital > 0
            ? round(($interesImplicito / $capital) * 100, 2)
            : 0.0;

        return [
            'monto_total'           => $montoTotal,
            'interes_implicito'     => $interesImplicito,
            'interes_implicito_pct' => $interesImplicitoPct,
        ];
    }

    /**
     * Crea un crédito completo (crédito + cuotas) dentro de una transacción.
     *
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string, id_credito?: int, codigo?: string, errors?: string[]}
     */
    public function crear(array $data, int $usuarioId): array
    {
        // ── Validación ────────────────────────────────────────────────────────
        $errors = [];

        $capital        = (float)($data['capital']        ?? 0);
        $cantidadCuotas = (int)($data['cantidad_cuotas']  ?? 0);
        $valorCuota     = (float)($data['valor_cuota']    ?? 0);
        $frecuencia     = trim($data['frecuencia']        ?? '');
        $fechaInicio    = trim($data['fecha_inicio']      ?? '');
        $idCliente      = (int)($data['id_cliente']       ?? 0);
        $gastosAdmin    = max(0.0, (float)($data['gastos_admin'] ?? 0));

        if ($capital <= 0)        $errors[] = 'El capital debe ser mayor a cero.';
        if ($cantidadCuotas < 1)  $errors[] = 'La cantidad de cuotas debe ser al menos 1.';
        if ($valorCuota <= 0)     $errors[] = 'El valor de cuota debe ser mayor a cero.';
        if ($idCliente <= 0)      $errors[] = 'Debe seleccionar un cliente.';

        if (!in_array($frecuencia, ['diaria', 'semanal', 'quincenal', 'mensual'], true)) {
            $errors[] = 'Frecuencia inválida.';
        }

        $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fechaInicio) {
            $errors[] = 'Fecha de inicio inválida (formato esperado: YYYY-MM-DD).';
        }

        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        // ── Cálculos ──────────────────────────────────────────────────────────
        $calculos   = $this->calcularPreview($capital, $cantidadCuotas, $valorCuota, $gastosAdmin);
        $cuotas     = $this->calendario->generar($fechaInicio, $cantidadCuotas, $frecuencia, $valorCuota);
        $fechaFin   = !empty($cuotas) ? end($cuotas)['fecha_vencimiento'] : null;

        // ── Transacción ───────────────────────────────────────────────────────
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $credito = new Credito();
            $credito->codigo              = $this->repo->generateCodigo();
            $credito->id_cliente          = $idCliente;
            $credito->id_vendedor         = !empty($data['id_vendedor']) ? (int)$data['id_vendedor'] : null;
            $credito->id_cobrador         = !empty($data['id_cobrador']) ? (int)$data['id_cobrador'] : null;
            $credito->capital             = $capital;
            $credito->cantidad_cuotas     = $cantidadCuotas;
            $credito->valor_cuota         = $valorCuota;
            $credito->monto_total         = $calculos['monto_total'];
            $credito->interes_implicito   = $calculos['interes_implicito'];
            $credito->interes_implicito_pct = $calculos['interes_implicito_pct'];
            $credito->gastos_admin        = $gastosAdmin;
            $credito->frecuencia          = $frecuencia;
            $credito->fecha_inicio        = $fechaInicio;
            $credito->fecha_fin_estimada  = $fechaFin;
            $credito->saldo_pendiente     = $calculos['monto_total'];
            $credito->destino_opcional    = !empty($data['destino_opcional']) ? trim($data['destino_opcional']) : null;
            $credito->id_credito_origen   = !empty($data['id_credito_origen']) ? (int)$data['id_credito_origen'] : null;
            $credito->observaciones       = !empty($data['observaciones']) ? trim($data['observaciones']) : null;
            $credito->created_by          = $usuarioId;

            $idCredito = $this->repo->insert($credito);
            $this->repo->insertCuotas($idCredito, $cuotas);

            $db->commit();

            Audit::log('credito.create', 'creditos', $idCredito, null, [
                'codigo'          => $credito->codigo,
                'id_cliente'      => $idCliente,
                'capital'         => $capital,
                'monto_total'     => $calculos['monto_total'],
                'cantidad_cuotas' => $cantidadCuotas,
                'frecuencia'      => $frecuencia,
            ]);

            return [
                'ok'         => true,
                'message'    => "Crédito {$credito->codigo} creado exitosamente.",
                'id_credito' => $idCredito,
                'codigo'     => $credito->codigo,
            ];

        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error interno al crear el crédito. Intente de nuevo.'];
        }
    }

    /**
     * Edita un credito. Si ya tiene pagos, solo permite datos operativos para
     * preservar cuotas, saldos e historial contable.
     *
     * @param array<string, mixed> $data
     * @return array{ok: bool, message: string, id_credito?: int, errors?: string[]}
     */
    public function editar(int $idCredito, array $data, int $usuarioId): array
    {
        $creditoActual = $this->repo->findById($idCredito);
        if (!$creditoActual) {
            return ['ok' => false, 'message' => 'Credito no encontrado.'];
        }
        if ($creditoActual->estado !== 'activo') {
            return ['ok' => false, 'message' => 'Solo se pueden editar creditos activos.'];
        }

        $tienePagos = $this->repo->hasPagos($idCredito);
        $errors = [];

        $capital        = (float)($data['capital']        ?? $creditoActual->capital);
        $cantidadCuotas = (int)($data['cantidad_cuotas']  ?? $creditoActual->cantidad_cuotas);
        $valorCuota     = (float)($data['valor_cuota']    ?? $creditoActual->valor_cuota);
        $frecuencia     = trim((string)($data['frecuencia'] ?? $creditoActual->frecuencia));
        $fechaInicio    = trim((string)($data['fecha_inicio'] ?? $creditoActual->fecha_inicio));
        $gastosAdmin    = max(0.0, (float)($data['gastos_admin'] ?? $creditoActual->gastos_admin));

        if (!$tienePagos) {
            if ($capital <= 0)        $errors[] = 'El capital debe ser mayor a cero.';
            if ($cantidadCuotas < 1)  $errors[] = 'La cantidad de cuotas debe ser al menos 1.';
            if ($valorCuota <= 0)     $errors[] = 'El valor de cuota debe ser mayor a cero.';
            if (!in_array($frecuencia, ['diaria', 'semanal', 'quincenal', 'mensual'], true)) {
                $errors[] = 'Frecuencia invalida.';
            }

            $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
            if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fechaInicio) {
                $errors[] = 'Fecha de inicio invalida (formato esperado: YYYY-MM-DD).';
            }
        }

        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        $credito = clone $creditoActual;
        $credito->id_vendedor      = !empty($data['id_vendedor']) ? (int)$data['id_vendedor'] : null;
        $credito->id_cobrador      = !empty($data['id_cobrador']) ? (int)$data['id_cobrador'] : null;
        $credito->destino_opcional = !empty($data['destino_opcional']) ? trim((string)$data['destino_opcional']) : null;
        $credito->observaciones    = !empty($data['observaciones']) ? trim((string)$data['observaciones']) : null;
        $credito->updated_by       = $usuarioId;

        $cuotas = [];
        if (!$tienePagos) {
            $calculos = $this->calcularPreview($capital, $cantidadCuotas, $valorCuota, $gastosAdmin);
            $cuotas   = $this->calendario->generar($fechaInicio, $cantidadCuotas, $frecuencia, $valorCuota);
            $fechaFin = !empty($cuotas) ? end($cuotas)['fecha_vencimiento'] : null;

            $credito->capital               = $capital;
            $credito->cantidad_cuotas       = $cantidadCuotas;
            $credito->valor_cuota           = $valorCuota;
            $credito->monto_total           = $calculos['monto_total'];
            $credito->interes_implicito     = $calculos['interes_implicito'];
            $credito->interes_implicito_pct = $calculos['interes_implicito_pct'];
            $credito->gastos_admin          = $gastosAdmin;
            $credito->frecuencia            = $frecuencia;
            $credito->fecha_inicio          = $fechaInicio;
            $credito->fecha_fin_estimada    = $fechaFin;
            $credito->saldo_pendiente       = $calculos['monto_total'];
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $this->repo->updateEditable($credito, !$tienePagos);
            if (!$tienePagos) {
                $this->repo->replaceCuotasSinPagos($idCredito, $cuotas);
            }

            Audit::log('credito.update', 'creditos', $idCredito, [
                'capital' => $creditoActual->capital,
                'cantidad_cuotas' => $creditoActual->cantidad_cuotas,
                'valor_cuota' => $creditoActual->valor_cuota,
                'id_cobrador' => $creditoActual->id_cobrador,
            ], [
                'capital' => $credito->capital,
                'cantidad_cuotas' => $credito->cantidad_cuotas,
                'valor_cuota' => $credito->valor_cuota,
                'id_cobrador' => $credito->id_cobrador,
                'solo_operativo' => $tienePagos,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error interno al editar el credito. Intente de nuevo.'];
        }

        return [
            'ok' => true,
            'message' => $tienePagos
                ? 'Credito actualizado. Como tiene pagos, solo se modificaron datos operativos.'
                : 'Credito actualizado y calendario recalculado.',
            'id_credito' => $idCredito,
        ];
    }

    /**
     * Refinancia un crédito activo: cierra el original y genera uno nuevo con el saldo como capital.
     *
     * @param array<string, mixed> $data [cantidad_cuotas, valor_cuota, frecuencia, fecha_inicio, id_cobrador?, id_vendedor?, gastos_admin?]
     * @return array{ok: bool, message: string, id_credito?: int, codigo?: string, errors?: string[]}
     */
    public function refinanciar(int $idCredito, array $data, int $usuarioId): array
    {
        $creditoOrigen = $this->repo->findById($idCredito);
        if (!$creditoOrigen) {
            return ['ok' => false, 'message' => 'Crédito no encontrado.'];
        }
        if ($creditoOrigen->estado !== 'activo') {
            return ['ok' => false, 'message' => 'Solo se pueden refinanciar créditos en estado activo.'];
        }
        if ($creditoOrigen->saldo_pendiente <= 0) {
            return ['ok' => false, 'message' => 'El crédito ya está saldado; no hay monto a refinanciar.'];
        }

        $data['capital']          = $creditoOrigen->saldo_pendiente;
        $data['id_cliente']       = $creditoOrigen->id_cliente;
        $data['id_credito_origen'] = $idCredito;

        $errors = [];
        $cantidadCuotas = (int)($data['cantidad_cuotas'] ?? 0);
        $valorCuota     = (float)($data['valor_cuota']   ?? 0);
        $frecuencia     = trim($data['frecuencia']       ?? '');
        $fechaInicio    = trim($data['fecha_inicio']     ?? '');

        if ($cantidadCuotas < 1) $errors[] = 'La cantidad de cuotas debe ser al menos 1.';
        if ($valorCuota <= 0)    $errors[] = 'El valor de cuota debe ser mayor a cero.';
        if (!in_array($frecuencia, ['diaria', 'semanal', 'quincenal', 'mensual'], true)) {
            $errors[] = 'Frecuencia inválida.';
        }
        $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fechaInicio) {
            $errors[] = 'Fecha de inicio inválida.';
        }
        if (!empty($errors)) {
            return ['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Cerrar crédito original
            $this->repo->updateEstado($idCredito, 'refinanciado', $usuarioId);
            Audit::log('credito.refinanciar_origen', 'creditos', $idCredito,
                ['estado' => 'activo'],
                ['estado' => 'refinanciado']
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Error al cerrar el crédito original.'];
        }

        // Crear nuevo crédito (usar crear() que tiene su propia transacción)
        $result = $this->crear($data, $usuarioId);
        if (!$result['ok']) {
            // Revertir el estado del crédito origen si falla
            $this->repo->updateEstado($idCredito, 'activo', $usuarioId);
        }
        return $result;
    }

    /**
     * Marca un crédito como incobrable (requiere motivo ≥ 10 chars).
     *
     * @return array{ok: bool, message: string}
     */
    public function marcarIncobrable(int $idCredito, string $motivo, int $usuarioId): array
    {
        if (mb_strlen(trim($motivo)) < 10) {
            return ['ok' => false, 'message' => 'El motivo debe tener al menos 10 caracteres.'];
        }

        $credito = $this->repo->findById($idCredito);
        if (!$credito) {
            return ['ok' => false, 'message' => 'Crédito no encontrado.'];
        }
        if (!in_array($credito->estado, ['activo'], true)) {
            return ['ok' => false, 'message' => 'Solo se pueden marcar como incobrables créditos activos.'];
        }

        $this->repo->updateEstado($idCredito, 'incobrable', $usuarioId);

        // Actualizar score del cliente (RN-10)
        $this->repo->decrementarScoreCliente($credito->id_cliente);

        Audit::log('credito.incobrable', 'creditos', $idCredito,
            ['estado' => 'activo'],
            ['estado' => 'incobrable', 'motivo' => $motivo]
        );

        return ['ok' => true, 'message' => 'Crédito marcado como incobrable.'];
    }

    /**
     * Anula un crédito activo (requiere motivo ≥ 10 caracteres).
     *
     * @return array{ok: bool, message: string}
     */
    public function anular(int $idCredito, string $motivo, int $usuarioId): array
    {
        if (mb_strlen(trim($motivo)) < 10) {
            return ['ok' => false, 'message' => 'El motivo debe tener al menos 10 caracteres.'];
        }

        $credito = $this->repo->findById($idCredito);
        if (!$credito) {
            return ['ok' => false, 'message' => 'Crédito no encontrado.'];
        }
        if ($credito->estado !== 'activo') {
            return ['ok' => false, 'message' => 'Solo se pueden anular créditos en estado activo.'];
        }

        $this->repo->updateEstado($idCredito, 'anulado', $usuarioId);

        Audit::log('credito.anular', 'creditos', $idCredito, ['estado' => 'activo'], [
            'estado' => 'anulado',
            'motivo' => $motivo,
        ]);

        return ['ok' => true, 'message' => 'Crédito anulado correctamente.'];
    }
}
