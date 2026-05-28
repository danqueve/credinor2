<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CajaRepository;

class CajaService
{
    private CajaRepository $repo;

    public function __construct()
    {
        $this->repo = new CajaRepository();
    }

    public function registrar(array $data, int $userId): void
    {
        $tipo    = $data['tipo'] ?? '';
        $monto   = (float)($data['monto'] ?? 0);
        $concepto = trim($data['concepto'] ?? '');
        $fecha   = $data['fecha'] ?? date('Y-m-d');
        $obs     = trim($data['observaciones'] ?? '') ?: null;

        if (!in_array($tipo, ['ingreso', 'egreso'])) {
            throw new \InvalidArgumentException('Tipo inválido.');
        }
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }
        if ($concepto === '') {
            throw new \InvalidArgumentException('El concepto es obligatorio.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException('Fecha inválida.');
        }

        $this->repo->insertar($tipo, $monto, $concepto, $fecha, $obs, $userId);
    }

    public function eliminar(int $id): void
    {
        $this->repo->softDelete($id);
    }

    public function getRecientes(int $limit = 50): array
    {
        return $this->repo->getRecientes($limit);
    }

    public function getEnRango(string $desde, string $hasta): array
    {
        return $this->repo->getEnRango($desde, $hasta);
    }

    public function exportPdf(string $desde, string $hasta): void
    {
        $movimientos = $this->repo->getEnRango($desde, $hasta);
        $totales     = $this->repo->getTotalesEnRango($desde, $hasta);

        $filas   = '';
        $i       = 1;
        foreach ($movimientos as $m) {
            $esIngreso = $m['tipo'] === 'ingreso';
            $clase     = ($i % 2 === 0) ? 'even' : 'odd';
            $concepto  = htmlspecialchars($m['concepto']);
            if (!empty($m['observaciones'])) {
                $concepto .= '<br><small style="color:#6b7280">' . htmlspecialchars($m['observaciones']) . '</small>';
            }
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td class="center">' . date('d/m/Y', strtotime($m['fecha'])) . '</td>
                <td class="center tipo-' . $m['tipo'] . '">' . ($esIngreso ? 'Ingreso' : 'Egreso') . '</td>
                <td>' . $concepto . '</td>
                <td class="right ' . ($esIngreso ? 'ingreso' : 'egreso') . '">$ ' . number_format((float)$m['monto'], 2, ',', '.') . '</td>
                <td>' . htmlspecialchars($m['usuario_nombre'] ?? '—') . '</td>
            </tr>';
            $i++;
        }

        $periodo   = date('d/m/Y', strtotime($desde)) . ' — ' . date('d/m/Y', strtotime($hasta));
        $registros = count($movimientos);

        $css = '<style>
            * { font-family: DejaVu Sans, sans-serif; }
            body { font-size: 9px; color: #1a1a2e; margin: 0; padding: 0; }
            .pdf-header { width: 100%; border-bottom: 3px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 14px; }
            .pdf-header td { vertical-align: bottom; padding: 0; }
            .company { font-size: 17px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
            .report-title { font-size: 12px; font-weight: bold; color: #374151; margin-top: 3px; }
            .report-sub { font-size: 8px; color: #6b7280; margin-top: 3px; }
            .date-block { text-align: right; }
            .date-label { font-size: 7px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
            .date-value { font-size: 10px; font-weight: bold; color: #1e3a5f; margin-top: 2px; }
            table.data { border-collapse: collapse; width: 100%; margin-top: 0; }
            table.data thead tr { background-color: #1e3a5f; }
            table.data thead th { padding: 7px 5px; text-align: left; font-weight: bold; font-size: 7.5px; color: #fff; letter-spacing: 0.3px; border: none; }
            table.data tbody tr.odd  { background-color: #ffffff; }
            table.data tbody tr.even { background-color: #f0f4f8; }
            table.data tbody td { padding: 5px 5px; font-size: 8px; color: #1f2937; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
            table.data tfoot tr { background-color: #1e3a5f; }
            table.data tfoot td { padding: 7px 5px; font-size: 8.5px; font-weight: bold; color: #fff; border: none; }
            .num    { color: #9ca3af; text-align: center; font-size: 7.5px; }
            .right  { text-align: right; }
            .center { text-align: center; }
            .ingreso { color: #15803d; }
            .egreso  { color: #b91c1c; }
            .tipo-ingreso { color: #15803d; font-weight: bold; }
            .tipo-egreso  { color: #b91c1c; font-weight: bold; }
        </style>';

        $header = '<table class="pdf-header" cellspacing="0" cellpadding="0"><tr>
            <td>
                <div class="company">CREDINOR</div>
                <div class="report-title">Caja &mdash; Movimientos Manuales</div>
                <div class="report-sub">Período: ' . $periodo . ' &mdash; ' . $registros . ' registros</div>
            </td>
            <td class="date-block">
                <div class="date-label">Generado el</div>
                <div class="date-value">' . date('d/m/Y H:i') . '</div>
            </td>
        </tr></table>';

        $html = $header . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:4%">#</th>
                    <th style="width:10%">Fecha</th>
                    <th style="width:10%">Tipo</th>
                    <th style="width:44%">Concepto</th>
                    <th style="width:16%" class="right">Monto</th>
                    <th style="width:16%">Usuario</th>
                </tr></thead>
                <tbody>' . ($filas ?: '<tr><td colspan="6" class="center" style="padding:12px;color:#9ca3af;">Sin movimientos en el período seleccionado.</td></tr>') . '</tbody>
                <tfoot><tr>
                    <td colspan="4" class="right">INGRESOS:</td>
                    <td class="right ingreso">$ ' . number_format((float)$totales['ingresos'], 2, ',', '.') . '</td>
                    <td></td>
                </tr><tr>
                    <td colspan="4" class="right">EGRESOS:</td>
                    <td class="right egreso">$ ' . number_format((float)$totales['egresos'], 2, ',', '.') . '</td>
                    <td></td>
                </tr></tfoot>
            </table>';

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
                    <td style="font-size:7px;color:#9ca3af;font-family:DejaVu Sans,sans-serif;">CREDINOR &mdash; Caja</td>
                    <td style="font-size:7px;color:#9ca3af;text-align:right;font-family:DejaVu Sans,sans-serif;">P&aacute;gina {PAGENO} de {nb}</td>
                </tr>
            </table>'
        );
        $mpdf->WriteHTML($css . $html);
        $mpdf->Output('caja_' . $desde . '_' . $hasta . '.pdf', 'I');
        exit;
    }

    public function getSaldoHistorico(float $cobradoTotal, float $prestadoTotal): float
    {
        $ingresos = $this->repo->getTotalIngresos();
        $egresos  = $this->repo->getTotalEgresos();
        return $cobradoTotal - $prestadoTotal + $ingresos - $egresos;
    }

    public function getTotalesEnRango(string $desde, string $hasta): array
    {
        return $this->repo->getTotalesEnRango($desde, $hasta);
    }
}
