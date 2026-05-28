<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Services\CajaService;

class CajaController
{
    private CajaService $service;

    public function __construct()
    {
        $this->service = new CajaService();
    }

    public function index(): void
    {
        Auth::requireAdminReadOnly();

        $desde = Sanitizer::clean($_GET['desde'] ?? date('Y-m-01'));
        $hasta = Sanitizer::clean($_GET['hasta'] ?? date('Y-m-d'));

        $movimientos = $this->service->getEnRango($desde, $hasta);

        View::render('caja/index', [
            'titulo'      => 'Caja — Movimientos',
            'movimientos' => $movimientos,
            'filtros'     => ['desde' => $desde, 'hasta' => $hasta],
        ]);
    }

    public function exportPdf(): void
    {
        Auth::requireAdminReadOnly();

        $desde = Sanitizer::clean($_GET['desde'] ?? date('Y-m-01'));
        $hasta = Sanitizer::clean($_GET['hasta'] ?? date('Y-m-d'));

        $this->service->exportPdf($desde, $hasta);
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $user = Auth::user();

        try {
            $this->service->registrar($_POST, (int)$user['id']);
            $_SESSION['flash_success'] = 'Movimiento registrado correctamente.';
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        $appUrl = $_ENV['APP_URL'] ?? '';
        header('Location: ' . $appUrl . '/caja');
        exit;
    }

    public function delete(): void
    {
        Auth::requireAdmin();

        $id = (int)($_POST['id_movimiento'] ?? 0);
        if ($id > 0) {
            $this->service->eliminar($id);
            $_SESSION['flash_success'] = 'Movimiento eliminado.';
        }

        $appUrl = $_ENV['APP_URL'] ?? '';
        header('Location: ' . $appUrl . '/caja');
        exit;
    }
}
