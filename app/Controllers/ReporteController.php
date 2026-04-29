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
        Auth::requireAdmin();

        $desde = Sanitizer::clean($_GET['desde'] ?? date('Y-m-01'));
        $hasta = Sanitizer::clean($_GET['hasta'] ?? date('Y-m-d'));
        $dias  = max(7, min(90, (int)($_GET['dias'] ?? 30)));

        View::render('reportes/index', [
            'titulo'         => 'Reportes y Analíticas',
            'filtros'        => ['desde' => $desde, 'hasta' => $hasta, 'dias' => $dias],
            'resumen'        => $this->service->getResumenAdmin(),
            'cobranza'       => $this->service->getCobranza($desde, $hasta),
            'ventas'         => $this->service->getVentas($desde, $hasta),
            'comisiones'     => $this->service->getComisiones($desde, $hasta),
            'flujoCaja'      => $this->service->getFlujoCaja($dias),
            'cuotasHoy'      => $this->service->getCuotasHoy(date('Y-m-d')),
            'atraso'         => $this->service->getClientesAtraso(),
            'capitalRec'     => $this->service->getCapitalVsRecuperado($desde, $hasta),
            'rendDiferencia' => $this->service->getRendicionesConDiferencia($desde, $hasta),
        ]);
    }

    public function exportCobranza(): void
    {
        Auth::requireAdmin();
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
        Auth::requireAdmin();
        $format = $_GET['format'] ?? 'excel';

        if ($format === 'pdf') {
            $this->service->exportAtrasoPdf();
        } else {
            $this->service->exportAtrasoExcel();
        }
    }
}
