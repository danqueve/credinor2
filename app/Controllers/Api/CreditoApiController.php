<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Repositories\CreditoRepository;
use App\Services\CreditoService;
use App\Services\CuotaCalendarioService;

class CreditoApiController
{
    private CreditoService        $service;
    private CuotaCalendarioService $calendario;
    private CreditoRepository     $repo;

    public function __construct()
    {
        $this->service    = new CreditoService();
        $this->calendario = new CuotaCalendarioService();
        $this->repo       = new CreditoRepository();
    }

    /**
     * GET /api/creditos/preview
     * Calcula monto_total, interes_implicito e interes_implicito_pct en vivo.
     */
    public function preview(): void
    {
        Auth::requireLogin();

        $capital        = (float)str_replace(',', '.', $_GET['capital']       ?? '0');
        $cantidadCuotas = (int)($_GET['cantidad_cuotas'] ?? 0);
        $valorCuota     = (float)str_replace(',', '.', $_GET['valor_cuota']   ?? '0');
        $gastosAdmin    = (float)str_replace(',', '.', $_GET['gastos_admin']  ?? '0');

        if ($capital <= 0 || $cantidadCuotas < 1 || $valorCuota <= 0) {
            Response::json(false, null, ['Valores inválidos.']);
            return;
        }

        $data = $this->service->calcularPreview($capital, $cantidadCuotas, $valorCuota, $gastosAdmin);
        Response::json(true, $data);
    }

    /**
     * GET /api/creditos/calendario_preview
     * Genera el calendario de cuotas para previsualización (sin guardar).
     */
    public function calendarioPreview(): void
    {
        Auth::requireLogin();

        $fechaInicio    = $_GET['fecha_inicio']    ?? '';
        $cantidadCuotas = (int)($_GET['cantidad_cuotas'] ?? 0);
        $frecuencia     = $_GET['frecuencia']      ?? '';
        $valorCuota     = (float)str_replace(',', '.', $_GET['valor_cuota'] ?? '0');

        $fechaDt = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fechaDt || $cantidadCuotas < 1 || !in_array($frecuencia, ['diaria', 'semanal', 'quincenal', 'mensual'], true)) {
            Response::json(false, null, ['Parámetros inválidos.']);
            return;
        }

        $cuotas = $this->calendario->generar($fechaInicio, $cantidadCuotas, $frecuencia, $valorCuota);
        Response::json(true, ['cuotas' => $cuotas]);
    }

    /**
     * GET /api/creditos/activos_cliente?id_cliente=X
     * Devuelve créditos activos del cliente para el aviso en el formulario.
     */
    public function activosByCliente(): void
    {
        Auth::requireLogin();

        $idCliente = (int)($_GET['id_cliente'] ?? 0);
        if ($idCliente <= 0) {
            Response::json(false, null, ['id_cliente requerido.']);
            return;
        }

        $creditos = $this->repo->findActivosByCliente($idCliente);
        $data = array_map(fn($c) => [
            'id_credito'     => $c->id_credito,
            'codigo'         => $c->codigo,
            'capital'        => $c->capital,
            'saldo_pendiente' => $c->saldo_pendiente,
            'frecuencia'     => $c->frecuencia,
            'cobrador'       => $c->cobrador_nombre,
        ], $creditos);

        Response::json(true, ['creditos' => $data, 'total' => count($data)]);
    }
}
