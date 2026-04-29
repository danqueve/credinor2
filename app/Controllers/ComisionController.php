<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\View;
use App\Services\ComisionService;

class ComisionController
{
    private ComisionService $service;

    public function __construct()
    {
        $this->service = new ComisionService();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $periodo    = $_GET['periodo'] ?? date('Y-m');
        $liquidacion = $this->service->getLiquidacion($periodo);
        $periodos    = $this->service->getPeriodosLiquidados();

        // Totales por tipo
        $totales = ['venta' => 0.0, 'cobranza' => 0.0, 'total' => 0.0];
        foreach ($liquidacion as $row) {
            $totales[$row['tipo']]  += (float)$row['monto_comision'];
            $totales['total']       += (float)$row['monto_comision'];
        }

        View::render('comisiones/index', [
            'titulo'      => 'Comisiones',
            'periodo'     => $periodo,
            'liquidacion' => $liquidacion,
            'periodos'    => $periodos,
            'totales'     => $totales,
        ]);
    }

    public function liquidar(): void
    {
        Auth::requireLogin();

        $periodo = trim($_POST['periodo'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            http_response_code(400);
            echo 'Período inválido';
            return;
        }

        $resumen = $this->service->liquidar($periodo);

        $_SESSION['flash_success'] = "Liquidación {$resumen['periodo']} generada: {$resumen['filas']} registros — Total $" . number_format($resumen['total_comision'], 2, ',', '.');
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/comisiones?periodo=' . $periodo);
        exit;
    }

    public function marcarPagada(): void
    {
        Auth::requireLogin();

        $idComision = (int)($_POST['id_comision'] ?? 0);
        $periodo    = $_POST['periodo'] ?? date('Y-m');

        if ($idComision > 0) {
            $this->service->marcarPagada($idComision);
        }

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/comisiones?periodo=' . $periodo);
        exit;
    }
}
