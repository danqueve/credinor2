<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Services\ReporteService;

class ReporteController
{
    private ReporteService $service;

    public function __construct()
    {
        $this->service = new ReporteService();
    }

    public function index(): void
    {
        Auth::requireAdminReadOnly();

        $desde = Sanitizer::clean($_GET['desde'] ?? date('Y-m-01'));
        $hasta = Sanitizer::clean($_GET['hasta'] ?? date('Y-m-d'));

        $reporte = $this->service->getReporteFinanciero($desde, $hasta);

        View::render('reportes/index', [
            'titulo'      => 'Reportes y Analíticas',
            'filtros'     => ['desde' => $desde, 'hasta' => $hasta],
            'entreFechas' => $reporte['entre_fechas'],
            'historicas'  => $reporte['historicas'],
            'movimientos' => $reporte['movimientos'],
        ]);
    }

    public function vencimientos(): void
    {
        Auth::requireAdminReadOnly();
        $dias = max(1, min(365, (int)($_GET['dias'] ?? 30)));

        View::render('reportes/vencimientos', [
            'titulo'      => 'Próximos Vencimientos',
            'vencimientos' => $this->service->getProximosVencimientos($dias),
            'dias'        => $dias,
        ]);
    }

    public function exportCobranza(): void
    {
        Auth::requireAdminReadOnly();
        $desde  = Sanitizer::clean($_GET['desde']  ?? date('Y-m-01'));
        $hasta  = Sanitizer::clean($_GET['hasta']  ?? date('Y-m-d'));
        $format = $_GET['format'] ?? 'excel';

        if ($format === 'pdf') {
            $this->service->exportCobranzaPdf($desde, $hasta);
        } else {
            $this->service->exportCobranzaExcel($desde, $hasta);
        }
    }

    public function exportAtraso(): void
    {
        Auth::requireAdminReadOnly();
        $format = $_GET['format'] ?? 'excel';

        if ($format === 'pdf') {
            $this->service->exportAtrasoPdf();
        } else {
            $this->service->exportAtrasoExcel();
        }
    }

    public function exportClientes(): void
    {
        Auth::requireAdminReadOnly();
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $this->service->exportClientesPdf($search);
    }

    public function exportCreditos(): void
    {
        Auth::requireAdminReadOnly();
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $estado = Sanitizer::clean($_GET['estado'] ?? '');
        $this->service->exportCreditosPdf($search, $estado);
    }

    public function exportCobros(): void
    {
        Auth::requireAdminReadOnly();
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $desde  = Sanitizer::clean($_GET['desde'] ?? '');
        $hasta  = Sanitizer::clean($_GET['hasta'] ?? '');
        $this->service->exportCobrosPdf($search, $desde, $hasta);
    }
}
