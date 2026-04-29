<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReporteRepository;

class ReporteService
{
    private ReporteRepository $repo;

    public function __construct()
    {
        $this->repo = new ReporteRepository();
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

    public function getCapitalVsRecuperado(string $desde, string $hasta): array
    {
        return $this->repo->getCapitalVsRecuperado($desde, $hasta);
    }

    public function getRendicionesConDiferencia(string $desde, string $hasta): array
    {
        return $this->repo->getRendicionesConDiferencia($desde, $hasta);
    }

    public function exportAtrasoExcel(): void
    {
        $data = $this->repo->getClientesConAtraso();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes con Atraso');

        $headers = ['Cliente', 'DNI', 'Teléfono', 'Dirección', 'Crédito', 'Cuotas Vencidas', 'Deuda Vencida', 'Días Atraso', 'Cobrador', 'Zona'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValueByColumnAndRow(1, $row, $item['cliente_nombre']);
            $sheet->setCellValueByColumnAndRow(2, $row, $item['dni']);
            $sheet->setCellValueByColumnAndRow(3, $row, $item['telefono'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, $item['direccion'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row, $item['credito_codigo']);
            $sheet->setCellValueByColumnAndRow(6, $row, $item['cuotas_vencidas']);
            $sheet->setCellValueByColumnAndRow(7, $row, (float)$item['deuda_vencida']);
            $sheet->setCellValueByColumnAndRow(8, $row, $item['dias_atraso']);
            $sheet->setCellValueByColumnAndRow(9, $row, $item['cobrador_nombre'] ?? '');
            $sheet->setCellValueByColumnAndRow(10, $row, $item['zona_nombre'] ?? '');
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="clientes_atraso_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportAtrasoPdf(): void
    {
        $data = $this->repo->getClientesConAtraso();
        $filas = '';
        $totalDeuda = 0;
        foreach ($data as $item) {
            $deuda = (float)$item['deuda_vencida'];
            $totalDeuda += $deuda;
            $filas .= '<tr>
                <td>' . htmlspecialchars($item['cliente_nombre']) . '</td>
                <td>' . htmlspecialchars($item['dni']) . '</td>
                <td>' . htmlspecialchars($item['telefono'] ?? '—') . '</td>
                <td>' . htmlspecialchars($item['credito_codigo']) . '</td>
                <td align="center">' . $item['cuotas_vencidas'] . '</td>
                <td align="right">$ ' . number_format($deuda, 2, ',', '.') . '</td>
                <td align="center">' . $item['dias_atraso'] . ' días</td>
                <td>' . htmlspecialchars($item['cobrador_nombre'] ?? '—') . '</td>
            </tr>';
        }
        $html = '<h2 style="text-align:center;">Clientes con Cuotas Atrasadas</h2>
            <p style="text-align:center;">Generado: ' . date('d/m/Y H:i') . '</p>
            <table border="1" width="100%" cellpadding="6" style="border-collapse:collapse;font-size:10px;">
                <thead><tr style="background:#f2f2f2;">
                    <th>Cliente</th><th>DNI</th><th>Teléfono</th><th>Crédito</th>
                    <th>Cuotas V.</th><th>Deuda</th><th>Atraso</th><th>Cobrador</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr style="font-weight:bold;">
                    <td colspan="5" align="right">TOTAL DEUDA VENCIDA:</td>
                    <td align="right">$ ' . number_format($totalDeuda, 2, ',', '.') . '</td>
                    <td colspan="2"></td>
                </tr></tfoot>
            </table>';
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('clientes_atraso_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    public function exportCobranzaExcel(string $desde, string $hasta): void
    {
        $data = $this->repo->getCobranzaPorCobrador($desde, $hasta);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cobranza');

        // Encabezados
        $sheet->setCellValue('A1', 'Cobrador');
        $sheet->setCellValue('B1', 'Cantidad Pagos');
        $sheet->setCellValue('C1', 'Total Cobrado');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['cobrador']);
            $sheet->setCellValue('B' . $row, $item['cantidad_pagos']);
            $sheet->setCellValue('C' . $row, $item['total_cobrado']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="cobranza_' . $desde . '_' . $hasta . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportCobranzaPdf(string $desde, string $hasta): void
    {
        $data = $this->repo->getCobranzaPorCobrador($desde, $hasta);
        $html = '
            <h2 style="text-align: center;">Reporte de Cobranza</h2>
            <p style="text-align: center;">Periodo: ' . $desde . ' al ' . $hasta . '</p>
            <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th>Cobrador</th>
                        <th>Cantidad Pagos</th>
                        <th>Total Cobrado</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total = 0;
        foreach ($data as $item) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($item['cobrador']) . '</td>
                        <td align="center">' . $item['cantidad_pagos'] . '</td>
                        <td align="right">$ ' . number_format((float)$item['total_cobrado'], 2, ',', '.') . '</td>
                    </tr>';
            $total += (float)$item['total_cobrado'];
        }
        
        $html .= '</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f9f9f9;">
                        <td colspan="2" align="right">TOTAL:</td>
                        <td align="right">$ ' . number_format($total, 2, ',', '.') . '</td>
                    </tr>
                </tfoot>
            </table>';

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        $mpdf->Output('cobranza_' . $desde . '_' . $hasta . '.pdf', 'D');
        exit;
    }
}
