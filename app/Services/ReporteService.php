<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CajaRepository;
use App\Repositories\ReporteRepository;

class ReporteService
{
    private ReporteRepository $repo;
    private CajaRepository $cajaRepo;

    public function __construct()
    {
        $this->repo     = new ReporteRepository();
        $this->cajaRepo = new CajaRepository();
    }

    public function getResumenAdmin(): array
    {
        return [
            'cartera'        => $this->repo->getCarteraActiva(),
            'aging'          => $this->repo->getAgingVencidos(),
            'multi_creditos' => $this->repo->getClientesMultiCredito(),
        ];
    }

    public function getCobranza(string $desde, string $hasta): array
    {
        return $this->repo->getCobranzaPorCobrador($desde, $hasta);
    }

    public function getVentas(string $desde, string $hasta): array
    {
        return $this->repo->getPerformanceVendedores($desde, $hasta);
    }

    public function getComisiones(string $desde, string $hasta): array
    {
        return $this->repo->getComisionesPeriodo($desde, $hasta);
    }

    public function getFlujoCaja(int $dias = 30): array
    {
        return $this->repo->getFlujoCajaProyectado($dias);
    }

    public function getCuotasHoy(string $fecha): array
    {
        return $this->repo->getCuotasVencenFecha($fecha);
    }

    public function getClientesAtraso(): array
    {
        return $this->repo->getClientesConAtraso();
    }

    public function getProximosVencimientos(int $dias = 30): array
    {
        return $this->repo->getProximosVencimientos($dias);
    }

    public function getCapitalVsRecuperado(string $desde, string $hasta): array
    {
        return $this->repo->getCapitalVsRecuperado($desde, $hasta);
    }

    public function getRendicionesConDiferencia(string $desde, string $hasta): array
    {
        return $this->repo->getRendicionesConDiferencia($desde, $hasta);
    }

    public function getReporteFinanciero(string $desde, string $hasta): array
    {
        $cobradoRango  = $this->repo->getTotalCobradoEnRango($desde, $hasta);
        $prestadoRango = $this->repo->getTotalPrestadoEnRango($desde, $hasta);
        $cajaManuales  = $this->cajaRepo->getTotalesEnRango($desde, $hasta);
        $diferencia    = $cobradoRango - $prestadoRango
                       + (float)$cajaManuales['ingresos']
                       - (float)$cajaManuales['egresos'];

        $historicas   = $this->repo->getMetricasHistoricas();
        $ingresos     = $this->cajaRepo->getTotalIngresos();
        $egresos      = $this->cajaRepo->getTotalEgresos();
        $saldoCaja    = $historicas['cobrado_total'] - $historicas['prestado_total'] + $ingresos - $egresos;

        return [
            'entre_fechas' => [
                'cobrado'    => $cobradoRango,
                'prestado'   => $prestadoRango,
                'diferencia' => $diferencia,
            ],
            'historicas' => [
                'saldo_caja'       => $saldoCaja,
                'capital_activo'   => $historicas['capital_activo'],
                'cobrado_total'    => $historicas['cobrado_total'],
                'pendientes_cobro' => $historicas['pendientes_cobro'],
            ],
            'movimientos' => $this->repo->getHistorialMovimientos($desde, $hasta),
        ];
    }

    // ─── Exportadores Excel ───────────────────────────────────────────────────

    public function exportAtrasoExcel(): void
    {
        $data = $this->repo->getClientesConAtraso();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes con Atraso');

        $headers = ['#', 'Cliente', 'DNI', 'Teléfono', 'Dirección', 'Crédito', 'Cuotas Vencidas', 'Deuda Vencida', 'Días Atraso', 'Cobrador', 'Zona'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $i => $item) {
            $sheet->setCellValueByColumnAndRow(1,  $row, $i + 1);
            $sheet->setCellValueByColumnAndRow(2,  $row, $item['cliente_nombre']);
            $sheet->setCellValueByColumnAndRow(3,  $row, $item['dni']);
            $sheet->setCellValueByColumnAndRow(4,  $row, $item['telefono'] ?? '');
            $sheet->setCellValueByColumnAndRow(5,  $row, $item['direccion'] ?? '');
            $sheet->setCellValueByColumnAndRow(6,  $row, $item['credito_codigo']);
            $sheet->setCellValueByColumnAndRow(7,  $row, $item['cuotas_vencidas']);
            $sheet->setCellValueByColumnAndRow(8,  $row, (float)$item['deuda_vencida']);
            $sheet->setCellValueByColumnAndRow(9,  $row, $item['dias_atraso']);
            $sheet->setCellValueByColumnAndRow(10, $row, $item['cobrador_nombre'] ?? '');
            $sheet->setCellValueByColumnAndRow(11, $row, $item['zona_nombre'] ?? '');
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="clientes_atraso_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportCobranzaExcel(string $desde, string $hasta): void
    {
        $data = $this->repo->getCobranzaPorCobrador($desde, $hasta);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cobranza');

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'Cobrador');
        $sheet->setCellValue('C1', 'Cantidad Pagos');
        $sheet->setCellValue('D1', 'Total Cobrado');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $i => $item) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $item['cobrador']);
            $sheet->setCellValue('C' . $row, $item['cantidad_pagos']);
            $sheet->setCellValue('D' . $row, $item['total_cobrado']);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cobranza_' . $desde . '_' . $hasta . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── Exportadores PDF ─────────────────────────────────────────────────────

    public function exportAtrasoPdf(): void
    {
        $data = $this->repo->getClientesConAtraso();
        $filas = '';
        $totalDeuda = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $deuda = (float)$item['deuda_vencida'];
            $totalDeuda += $deuda;
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($item['cliente_nombre']) . '</td>
                <td>' . htmlspecialchars($item['dni']) . '</td>
                <td>' . htmlspecialchars($item['telefono'] ?? '—') . '</td>
                <td>' . htmlspecialchars($item['credito_codigo']) . '</td>
                <td class="center">' . $item['cuotas_vencidas'] . '</td>
                <td class="right">$ ' . number_format($deuda, 0, ',', '.') . '</td>
                <td class="center">' . $item['dias_atraso'] . ' d</td>
                <td>' . htmlspecialchars($item['cobrador_nombre'] ?? '—') . '</td>
            </tr>';
            $i++;
        }

        $html = $this->pdfHeader('Clientes con Cuotas Atrasadas', 'Total de registros: ' . ($i - 1)) . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:5%">#</th>
                    <th style="width:22%">Cliente</th>
                    <th style="width:10%">DNI</th>
                    <th style="width:11%">Teléfono</th>
                    <th style="width:10%">Crédito</th>
                    <th style="width:7%">Cuotas V.</th>
                    <th style="width:11%">Deuda</th>
                    <th style="width:7%">Atraso</th>
                    <th style="width:17%">Cobrador</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="6" class="right">TOTAL DEUDA VENCIDA:</td>
                    <td class="right">$ ' . number_format($totalDeuda, 0, ',', '.') . '</td>
                    <td colspan="2"></td>
                </tr></tfoot>
            </table>';

        $this->renderPdf($html, 'clientes_atraso_' . date('Y-m-d') . '.pdf');
    }

    public function exportCobranzaPdf(string $desde, string $hasta): void
    {
        $data = $this->repo->getCobranzaPorCobrador($desde, $hasta);
        $filas = '';
        $total = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $cobrado = (float)$item['total_cobrado'];
            $total += $cobrado;
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($item['cobrador']) . '</td>
                <td class="center">' . $item['cantidad_pagos'] . '</td>
                <td class="right">$ ' . number_format($cobrado, 0, ',', '.') . '</td>
            </tr>';
            $i++;
        }

        $periodo = date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
        $html = $this->pdfHeader('Reporte de Cobranza', 'Período: ' . $periodo) . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:8%">#</th>
                    <th style="width:52%">Cobrador</th>
                    <th style="width:20%">Cant. Pagos</th>
                    <th style="width:20%">Total Cobrado</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="3" class="right">TOTAL:</td>
                    <td class="right">$ ' . number_format($total, 0, ',', '.') . '</td>
                </tr></tfoot>
            </table>';

        $this->renderPdf($html, 'cobranza_' . $desde . '_' . $hasta . '.pdf');
    }

    public function exportClientesPdf(string $search = ''): void
    {
        $data = $this->repo->exportClientes($search);
        $filas = '';
        $totalSaldo = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $saldo = (float)$item['saldo_total'];
            $totalSaldo += $saldo;
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($item['nombre'] ?? '') . '</td>
                <td>' . htmlspecialchars($item['dni'] ?? '') . '</td>
                <td>' . htmlspecialchars($item['telefono'] ?? '—') . '</td>
                <td>' . htmlspecialchars($item['zona_nombre'] ?? '—') . '</td>
                <td class="center">' . (int)$item['creditos_activos'] . '</td>
                <td class="right">$ ' . number_format($saldo, 0, ',', '.') . '</td>
                <td class="center">' . (!empty($item['proxima_cuota']) ? date('d/m/Y', strtotime($item['proxima_cuota'])) : '—') . '</td>
            </tr>';
            $i++;
        }

        $sub = $search !== '' ? 'Filtro: ' . $search : 'Todos los clientes';
        $html = $this->pdfHeader('Clientes', $sub . ' — ' . ($i - 1) . ' registros') . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:5%">#</th>
                    <th style="width:25%">Cliente</th>
                    <th style="width:11%">DNI</th>
                    <th style="width:13%">Teléfono</th>
                    <th style="width:13%">Zona</th>
                    <th style="width:8%">Créditos</th>
                    <th style="width:13%">Saldo</th>
                    <th style="width:12%">Próx. cuota</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="6" class="right">TOTAL SALDO:</td>
                    <td class="right">$ ' . number_format($totalSaldo, 0, ',', '.') . '</td>
                    <td></td>
                </tr></tfoot>
            </table>';

        $this->renderPdf($html, 'clientes_' . date('Y-m-d') . '.pdf');
    }

    public function exportCreditosPdf(string $search = '', string $estado = ''): void
    {
        $data = $this->repo->exportCreditos($search, $estado);
        $filas = '';
        $totalCapital = 0.0;
        $totalSaldo = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $capital = (float)$item['capital'];
            $saldo   = (float)$item['saldo_pendiente'];
            $totalCapital += $capital;
            $totalSaldo   += $saldo;
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($item['codigo']) . '</td>
                <td>' . htmlspecialchars($item['cliente_nombre']) . '<br><small>DNI ' . htmlspecialchars($item['cliente_dni']) . '</small></td>
                <td class="right">$ ' . number_format($capital, 0, ',', '.') . '</td>
                <td class="right">$ ' . number_format((float)$item['monto_total'], 0, ',', '.') . '</td>
                <td class="right">$ ' . number_format($saldo, 0, ',', '.') . '</td>
                <td class="center">' . htmlspecialchars(ucfirst((string)$item['estado'])) . '</td>
                <td>' . htmlspecialchars($item['cobrador_nombre'] ?? '—') . '</td>
                <td class="center">' . date('d/m/Y', strtotime($item['fecha_inicio'])) . '</td>
            </tr>';
            $i++;
        }

        $sub = trim(($search !== '' ? 'Filtro: ' . $search . ' ' : '') . ($estado !== '' ? '— Estado: ' . $estado : ''));
        $html = $this->pdfHeader('Créditos', ($sub !== '' ? $sub . ' — ' : '') . ($i - 1) . ' registros') . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:4%">#</th>
                    <th style="width:9%">Código</th>
                    <th style="width:22%">Cliente</th>
                    <th style="width:11%">Capital</th>
                    <th style="width:11%">Total</th>
                    <th style="width:11%">Saldo</th>
                    <th style="width:9%">Estado</th>
                    <th style="width:15%">Cobrador</th>
                    <th style="width:8%">Inicio</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="3" class="right">TOTALES:</td>
                    <td class="right">$ ' . number_format($totalCapital, 0, ',', '.') . '</td>
                    <td></td>
                    <td class="right">$ ' . number_format($totalSaldo, 0, ',', '.') . '</td>
                    <td colspan="3"></td>
                </tr></tfoot>
            </table>';

        $this->renderPdf($html, 'creditos_' . date('Y-m-d') . '.pdf');
    }

    public function exportCobrosPdf(string $search = '', string $desde = '', string $hasta = ''): void
    {
        $data = $this->repo->exportCobros($search, $desde, $hasta);
        $filas = '';
        $total = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $monto = (float)$item['monto_pagado'];
            if (!(bool)$item['anulado']) {
                $total += $monto;
            }
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $anulado = (bool)$item['anulado'];
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td class="center">' . date('d/m/Y', strtotime($item['fecha_pago_real'])) . '</td>
                <td>' . htmlspecialchars($item['cliente_nombre']) . '<br><small>DNI ' . htmlspecialchars($item['cliente_dni']) . '</small></td>
                <td>' . htmlspecialchars($item['credito_codigo']) . '</td>
                <td class="right">$ ' . number_format($monto, 0, ',', '.') . '</td>
                <td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$item['forma_pago']))) . '</td>
                <td>' . htmlspecialchars($item['cobrador_nombre'] ?? '—') . '</td>
                <td class="center' . ($anulado ? ' anulado' : '') . '">' . ($anulado ? 'Anulado' : 'Vigente') . '</td>
            </tr>';
            $i++;
        }

        $periodo = ($desde !== '' || $hasta !== '')
            ? 'Período: ' . ($desde !== '' ? date('d/m/Y', strtotime($desde)) : 'inicio') . ' al ' . ($hasta !== '' ? date('d/m/Y', strtotime($hasta)) : 'hoy')
            : 'Todos los cobros';
        $sub = trim($periodo . ($search !== '' ? ' — Filtro: ' . $search : ''));
        $html = $this->pdfHeader('Cobros', $sub . ' — ' . ($i - 1) . ' registros') . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:4%">#</th>
                    <th style="width:9%">Fecha</th>
                    <th style="width:22%">Cliente</th>
                    <th style="width:10%">Crédito</th>
                    <th style="width:11%">Monto</th>
                    <th style="width:13%">Forma pago</th>
                    <th style="width:18%">Cobrador</th>
                    <th style="width:8%">Estado</th>
                    <th style="width:5%"></th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="4" class="right">TOTAL VIGENTE:</td>
                    <td class="right">$ ' . number_format($total, 0, ',', '.') . '</td>
                    <td colspan="4"></td>
                </tr></tfoot>
            </table>';

        $this->renderPdf($html, 'cobros_' . date('Y-m-d') . '.pdf');
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function pdfCss(): string
    {
        return '<style>
            * { font-family: DejaVu Sans, sans-serif; }
            body { font-size: 9px; color: #1a1a2e; margin: 0; padding: 0; }

            /* ── Cabecera ── */
            .pdf-header { width: 100%; border-bottom: 3px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 14px; }
            .pdf-header td { vertical-align: bottom; padding: 0; }
            .company { font-size: 17px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
            .report-title { font-size: 12px; font-weight: bold; color: #374151; margin-top: 3px; }
            .report-sub { font-size: 8px; color: #6b7280; margin-top: 3px; }
            .date-block { text-align: right; }
            .date-label { font-size: 7px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
            .date-value { font-size: 10px; font-weight: bold; color: #1e3a5f; margin-top: 2px; }

            /* ── Tabla ── */
            table.data { border-collapse: collapse; width: 100%; margin-top: 0; }
            table.data thead tr { background-color: #1e3a5f; }
            table.data thead th {
                padding: 7px 5px;
                text-align: left;
                font-weight: bold;
                font-size: 7.5px;
                color: #ffffff;
                letter-spacing: 0.3px;
                border: none;
            }
            table.data tbody tr.odd  { background-color: #ffffff; }
            table.data tbody tr.even { background-color: #f0f4f8; }
            table.data tbody td {
                padding: 5px 5px;
                font-size: 8px;
                color: #1f2937;
                border-bottom: 1px solid #e2e8f0;
                vertical-align: middle;
            }
            table.data tfoot tr { background-color: #1e3a5f; }
            table.data tfoot td {
                padding: 7px 5px;
                font-size: 8.5px;
                font-weight: bold;
                color: #ffffff;
                border: none;
            }

            /* ── Utilidades ── */
            .num    { color: #9ca3af; text-align: center; font-size: 7.5px; }
            .right  { text-align: right; }
            .center { text-align: center; }
            .anulado { color: #b45309; }
            small { font-size: 6.5px; color: #6b7280; }
        </style>';
    }

    private function pdfHeader(string $titulo, string $subtitulo): string
    {
        return '<table class="pdf-header" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width:65%;">
                    <div class="company">CREDINOR</div>
                    <div class="report-title">' . htmlspecialchars($titulo) . '</div>
                    <div class="report-sub">' . htmlspecialchars($subtitulo) . '</div>
                </td>
                <td style="width:35%;" class="date-block">
                    <div class="date-label">Generado el</div>
                    <div class="date-value">' . date('d/m/Y H:i') . '</div>
                </td>
            </tr>
        </table>';
    }

    private function renderPdf(string $html, string $filename): void
    {
        $mpdf = new \Mpdf\Mpdf([
            'format'        => 'A4',
            'margin_top'    => 14,
            'margin_bottom' => 16,
            'margin_left'   => 14,
            'margin_right'  => 14,
        ]);

        $mpdf->SetHTMLFooter(
            '<table width="100%" style="border-top:1px solid #e2e8f0;padding-top:4px;">
                <tr>
                    <td style="font-size:7px;color:#9ca3af;font-family:DejaVu Sans,sans-serif;">CREDINOR &mdash; ' . date('d/m/Y') . '</td>
                    <td style="font-size:7px;color:#9ca3af;text-align:right;font-family:DejaVu Sans,sans-serif;">P&aacute;gina {PAGENO} de {nb}</td>
                </tr>
            </table>'
        );

        $mpdf->WriteHTML($this->pdfCss() . $html);
        $mpdf->Output($filename, 'I');
        exit;
    }
}
