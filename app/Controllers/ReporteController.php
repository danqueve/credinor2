<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\PersonalRepository;
use App\Repositories\ZonaRepository;
use App\Services\ReporteService;

class ReporteController
{
    private ReporteService $service;
    private PersonalRepository $personalRepo;
    private ZonaRepository $zonaRepo;

    public function __construct()
    {
        $this->service      = new ReporteService();
        $this->personalRepo = new PersonalRepository();
        $this->zonaRepo     = new ZonaRepository();
    }

    public function index(): void
    {
        Auth::requireAdminReadOnly();

        $desde = Sanitizer::clean($_GET['desde'] ?? date('Y-m-01'));
        $hasta = Sanitizer::clean($_GET['hasta'] ?? date('Y-m-d'));

        $reporte = $this->service->getReporteFinanciero($desde, $hasta);

        $cobradores = array_values(array_filter(
            $this->personalRepo->findAllActive(),
            fn($p) => $p->rol_operativo === 'cobrador'
        ));

        View::render('reportes/index', [
            'titulo'      => 'Reportes y Analíticas',
            'filtros'     => ['desde' => $desde, 'hasta' => $hasta],
            'entreFechas' => $reporte['entre_fechas'],
            'historicas'  => $reporte['historicas'],
            'movimientos' => $reporte['movimientos'],
            'cobradores'  => $cobradores,
            'zonas'       => $this->zonaRepo->findAll(),
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
        $format = $_GET['format'] ?? 'pdf';

        match ($format) {
            'xlsx' => $this->service->exportClientesExcel($search),
            'csv'  => $this->service->exportClientesCsv($search),
            default => $this->service->exportClientesPdf($search),
        };
    }

    public function exportClientesPorCobrador(): void
    {
        Auth::requireAdminReadOnly();
        $idCobrador = (int)($_GET['id_cobrador'] ?? 0);
        $format     = $_GET['format'] ?? 'pdf';
        $filtros    = [
            'id_zona'     => !empty($_GET['id_zona']) ? (int)$_GET['id_zona'] : null,
            'solo_atraso' => !empty($_GET['solo_atraso']),
        ];

        match ($format) {
            'xlsx' => $this->service->exportClientesPorCobradorExcel($idCobrador, $filtros),
            'csv'  => $this->service->exportClientesPorCobradorCsv($idCobrador, $filtros),
            default => $this->service->exportClientesPorCobradorPdf($idCobrador, $filtros),
        };
    }

    public function exportHojaRuta(): void
    {
        Auth::requireAdminReadOnly();
        $idCobrador = (int)($_GET['id_cobrador'] ?? 0);
        $desde      = Sanitizer::clean($_GET['desde'] ?? date('Y-m-d'));
        $hasta      = Sanitizer::clean($_GET['hasta'] ?? $desde);
        $format     = $_GET['format'] ?? 'pdf';
        $filtros    = ['id_zona' => !empty($_GET['id_zona']) ? (int)$_GET['id_zona'] : null];

        match ($format) {
            'xlsx' => $this->service->exportHojaRutaExcel($idCobrador, $desde, $hasta, $filtros),
            'csv'  => $this->service->exportHojaRutaCsv($idCobrador, $desde, $hasta, $filtros),
            default => $this->service->exportHojaRutaPdf($idCobrador, $desde, $hasta, $filtros),
        };
    }

    public function exportCreditos(): void
    {
        Auth::requireAdminReadOnly();
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $estado = Sanitizer::clean($_GET['estado'] ?? '');
        $format = $_GET['format'] ?? 'pdf';

        match ($format) {
            'xlsx' => $this->service->exportCreditosExcel($search, $estado),
            'csv'  => $this->service->exportCreditosCsv($search, $estado),
            default => $this->service->exportCreditosPdf($search, $estado),
        };
    }

    public function exportCobros(): void
    {
        Auth::requireAdminReadOnly();
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $desde  = Sanitizer::clean($_GET['desde'] ?? '');
        $hasta  = Sanitizer::clean($_GET['hasta'] ?? '');
        $format = $_GET['format'] ?? 'pdf';

        match ($format) {
            'xlsx' => $this->service->exportCobrosExcel($search, $desde, $hasta),
            'csv'  => $this->service->exportCobrosCsv($search, $desde, $hasta),
            default => $this->service->exportCobrosPdf($search, $desde, $hasta),
        };
    }
}
