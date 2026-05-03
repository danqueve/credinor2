<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Services\PagoService;

class PagoApiController
{
    private PagoService       $service;
    private PagoRepository    $pagoRepo;
    private CreditoRepository $creditoRepo;

    public function __construct()
    {
        $this->service     = new PagoService();
        $this->pagoRepo    = new PagoRepository();
        $this->creditoRepo = new CreditoRepository();
    }

    /**
     * GET /api/pagos/preview_fifo
     * Devuelve cómo se distribuiría un monto entre las cuotas pendientes (preview sin guardar).
     */
    public function previewFifo(): void
    {
        Auth::requireLogin();

        $idCredito = (int)($_GET['id_credito'] ?? 0);
        $monto     = (float)str_replace(',', '.', $_GET['monto'] ?? '0');

        if ($idCredito <= 0 || $monto <= 0) {
            Response::json(false, null, ['Parámetros inválidos.']);
            return;
        }

        $credito = $this->creditoRepo->findById($idCredito);
        if (!$credito) {
            Response::json(false, null, ['Crédito no encontrado.']);
            return;
        }

        $cuotas = $this->pagoRepo->getCuotasPendientes($idCredito);
        $fifo   = $this->service->calcularFifo($monto, $cuotas);

        Response::json(true, [
            'aplicaciones'   => $fifo['aplicaciones'],
            'monto_restante' => $fifo['monto_restante'],
            'saldo_credito'  => $credito->saldo_pendiente,
        ]);
    }

    /**
     * GET /api/pagos/cuotas_credito?id_credito=X
     * Devuelve cuotas pendientes de un crédito (para el formulario de pago AJAX).
     */
    public function cuotasCredito(): void
    {
        Auth::requireLogin();

        $idCredito = (int)($_GET['id_credito'] ?? 0);
        if ($idCredito <= 0) {
            Response::json(false, null, ['id_credito requerido.']);
            return;
        }

        $credito = $this->creditoRepo->findById($idCredito);
        if (!$credito) {
            Response::json(false, null, ['Crédito no encontrado.']);
            return;
        }

        $cuotas = $this->pagoRepo->getCuotasPendientes($idCredito);

        Response::json(true, [
            'credito' => [
                'id_credito'      => $credito->id_credito,
                'codigo'          => $credito->codigo,
                'cliente_nombre'  => $credito->cliente_nombre,
                'cliente_dni'     => $credito->cliente_dni,
                'saldo_pendiente' => $credito->saldo_pendiente,
                'estado'          => $credito->estado,
                'id_cobrador'     => $credito->id_cobrador,
            ],
            'cuotas' => $cuotas,
        ]);
    }
}
