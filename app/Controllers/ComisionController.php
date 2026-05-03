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
        Auth::requireAdminReadOnly();

        // ── Rango por defecto: lunes → sábado de la semana actual ──
        $hoy    = new \DateTime();
        $dow    = (int)$hoy->format('N'); // 1=Lun … 7=Dom
        $lunes  = (clone $hoy)->modify('-' . ($dow - 1) . ' days');
        $sabado = (clone $lunes)->modify('+5 days');

        $desde = $_GET['desde'] ?? $lunes->format('Y-m-d');
        $hasta = $_GET['hasta'] ?? $sabado->format('Y-m-d');

        // Validar fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $lunes->format('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $sabado->format('Y-m-d');
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

        $periodo  = $desde . '_' . $hasta;

        // ── Modo: en tiempo real (preview) o liquidación guardada ──
        $liquidacion = $this->service->getLiquidacion($periodo);
        $esPreview   = empty($liquidacion);
        if ($esPreview) {
            $liquidacion = $this->service->getComisionesPorRango($desde, $hasta);
        }

        $periodos = $this->service->getPeriodosLiquidados();

        // Totales por tipo
        $totales = ['venta' => 0.0, 'cobranza' => 0.0, 'total' => 0.0];
        foreach ($liquidacion as $row) {
            $tipo = $row['tipo'] ?? 'total';
            if (isset($totales[$tipo])) $totales[$tipo] += (float)$row['monto_comision'];
            $totales['total'] += (float)$row['monto_comision'];
        }

        View::render('comisiones/index', [
            'titulo'      => 'Comisiones',
            'desde'       => $desde,
            'hasta'       => $hasta,
            'periodo'     => $periodo,
            'liquidacion' => $liquidacion,
            'esPreview'   => $esPreview,
            'periodos'    => $periodos,
            'totales'     => $totales,
        ]);
    }

    public function liquidar(): void
    {
        Auth::requireAdmin();

        $desde = trim($_POST['desde'] ?? '');
        $hasta = trim($_POST['hasta'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            http_response_code(400);
            echo 'Rango de fechas inválido';
            return;
        }

        $resumen = $this->service->liquidar($desde, $hasta);
        $_SESSION['flash_success'] = "Liquidación {$desde} → {$hasta} generada: {$resumen['filas']} registros — Total $" . number_format($resumen['total_comision'], 2, ',', '.');
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/comisiones?desde=' . $desde . '&hasta=' . $hasta);
        exit;
    }

    public function marcarPagada(): void
    {
        Auth::requireAdmin();

        $idComision = (int)($_POST['id_comision'] ?? 0);
        $desde      = $_POST['desde'] ?? '';
        $hasta      = $_POST['hasta'] ?? '';

        if ($idComision > 0) {
            $this->service->marcarPagada($idComision);
        }

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/comisiones?desde=' . $desde . '&hasta=' . $hasta);
        exit;
    }
}
