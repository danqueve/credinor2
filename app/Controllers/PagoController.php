<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PersonalRepository;
use App\Services\PagoService;
use App\Services\ReciboService;

class PagoController
{
    private PagoRepository    $pagoRepo;
    private CreditoRepository $creditoRepo;
    private PersonalRepository $personalRepo;
    private PagoService       $pagoService;

    public function __construct()
    {
        $this->pagoRepo     = new PagoRepository();
        $this->creditoRepo  = new CreditoRepository();
        $this->personalRepo = new PersonalRepository();
        $this->pagoService  = new PagoService();
    }

    // ── Historial de pagos ────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAdminReadOnly();

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $desde  = Sanitizer::clean($_GET['desde'] ?? '');
        $hasta  = Sanitizer::clean($_GET['hasta'] ?? '');
        $limit  = 30;
        $offset = ($page - 1) * $limit;

        $pagos      = $this->pagoRepo->findAll($limit, $offset, $search, $desde, $hasta);
        $total      = $this->pagoRepo->countAll($search, $desde, $hasta);
        $totalPages = (int)ceil($total / $limit);

        View::render('pagos/index', [
            'titulo'     => 'Historial de Pagos',
            'pagos'      => $pagos,
            'search'     => $search,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

    // ── Formulario nuevo pago ─────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAdmin();

        $personal = $this->personalRepo->findAllActive();

        // Pre-seleccionar crédito si viene por URL
        $credito          = null;
        $cuotasPendientes = [];
        $idCreditoGet     = (int)($_GET['id_credito'] ?? 0);

        if ($idCreditoGet > 0) {
            $credito = $this->creditoRepo->findById($idCreditoGet);
            if ($credito && in_array($credito->estado, ['activo'], true)) {
                $cuotasPendientes = $this->pagoRepo->getCuotasPendientes($idCreditoGet);
            }
        }

        View::render('pagos/form_pago', [
            'titulo'           => 'Registrar Pago',
            'personal'         => $personal,
            'credito'          => $credito,
            'cuotasPendientes' => $cuotasPendientes,
        ]);
    }

    // ── Procesar pago ─────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAdmin();

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $data = [
            'id_credito'         => (int)($_POST['id_credito']        ?? 0),
            'monto_pagado'       => $_POST['monto_pagado']            ?? '0',
            'forma_pago'         => Sanitizer::clean($_POST['forma_pago']         ?? ''),
            'fecha_pago_real'    => Sanitizer::clean($_POST['fecha_pago_real']    ?? ''),
            'id_cobrador'        => !empty($_POST['id_cobrador']) ? (int)$_POST['id_cobrador'] : null,
            'referencia_externa' => Sanitizer::clean($_POST['referencia_externa'] ?? ''),
            'observaciones'      => Sanitizer::clean($_POST['observaciones']      ?? ''),
        ];

        $result = $this->pagoService->registrar($data, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/pagos/nuevo?id_credito=' . $data['id_credito']);
            exit;
        }

        $_SESSION['flash_success'] = $result['message'];
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $data['id_credito']);
        exit;
    }

    // ── Descargar recibo PDF ──────────────────────────────────────────────────

    public function descargarRecibo(): void
    {
        Auth::requireAdminReadOnly();

        $idPago = (int)($_GET['id_pago'] ?? 0);
        $recibo = $this->pagoRepo->findReciboPorPago($idPago);

        if (!$recibo || empty($recibo['pdf_path'])) {
            http_response_code(404);
            echo 'Recibo no disponible.';
            exit;
        }

        (new ReciboService())->descargar($recibo['pdf_path']);
        exit;
    }

    // ── Anular pago (POST) ────────────────────────────────────────────────────

    public function anular(): void
    {
        Auth::requireAdmin();

        $idPago    = (int)($_POST['id_pago']   ?? 0);
        $motivo    = Sanitizer::clean($_POST['motivo'] ?? '');
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        // Para redireccionar de vuelta a la ficha del crédito
        $pago = $this->pagoRepo->findById($idPago);
        $idCredito = $pago ? $pago->id_credito : 0;

        $result = $this->pagoService->anular($idPago, $motivo, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_success'] = $result['message'];
        }

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $idCredito);
        exit;
    }
}
