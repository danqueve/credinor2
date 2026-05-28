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
            body { font-size: 9px; color: #111111; margin: 0; padding: 0; }

            /* ── Cabecera ── */
            .pdf-header { width: 100%; border-bottom: 2px solid #111111; padding-bottom: 10px; margin-bottom: 16px; }
            .pdf-header td { vertical-align: middle; padding: 0; }
            .company { font-size: 20px; font-weight: bold; color: #111111; letter-spacing: 2px; text-transform: uppercase; }
            .report-title { font-size: 10px; font-weight: bold; color: #111111; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.8px; }
            .report-sub { font-size: 8px; color: #6b7280; margin-top: 3px; }
            .date-block { text-align: right; }
            .date-label { font-size: 7px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
            .date-value { font-size: 10px; font-weight: bold; color: #111111; margin-top: 2px; }

            /* ── Tabla ── */
            table.data { border-collapse: collapse; width: 100%; }
            table.data thead tr { background-color: #111111; }
            table.data thead th {
                padding: 8px 6px;
                text-align: left;
                font-weight: bold;
                font-size: 7.5px;
                color: #ffffff;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: none;
            }
            table.data tbody tr.odd  { background-color: #ffffff; }
            table.data tbody tr.even { background-color: #f8fafc; }
            table.data tbody td {
                padding: 7px 6px;
                font-size: 10px;
                color: #111111;
                border-bottom: 1px solid #e5e7eb;
                vertical-align: middle;
            }
            table.data tfoot tr.tot-line { background-color: #f3f4f6; }
            table.data tfoot td {
                padding: 6px 6px;
                font-size: 8.5px;
                font-weight: bold;
                color: #111111;
                border-top: 2px solid #111111;
                border-bottom: none;
            }

            /* ── Utilidades ── */
            .num    { color: #9ca3af; text-align: center; font-size: 7.5px; }
            .right  { text-align: right; }
            .center { text-align: center; }
            .ingreso      { color: #166534; font-weight: bold; }
            .egreso       { color: #991b1b; font-weight: bold; }
            .tipo-ingreso { color: #166534; font-weight: bold; }
            .tipo-egreso  { color: #991b1b; font-weight: bold; }
            .label-tot    { color: #6b7280; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        </style>';

        $header = '<table class="pdf-header" cellspacing="0" cellpadding="0"><tr>
            <td>
                <div class="company">Credinor</div>
                <div class="report-title">Caja &mdash; Movimientos Manuales</div>
                <div class="report-sub">Período: ' . $periodo . ' &nbsp;&middot;&nbsp; ' . $registros . ' registros</div>
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
                <tfoot><tr class="tot-line">
                    <td colspan="4" class="right label-tot">TOTAL INGRESOS</td>
                    <td class="right ingreso">$ ' . number_format((float)$totales['ingresos'], 2, ',', '.') . '</td>
                    <td></td>
                </tr><tr class="tot-line">
                    <td colspan="4" class="right label-tot">TOTAL EGRESOS</td>
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
            '<table width="100%" style="border-top:1px solid #111111;padding-top:4px;">
                <tr>
                    <td style="font-size:7px;color:#111111;font-family:DejaVu Sans,sans-serif;">CREDINOR &mdash; Caja &mdash; Movimientos Manuales</td>
                    <td style="font-size:7px;color:#111111;text-align:right;font-family:DejaVu Sans,sans-serif;">P&aacute;gina {PAGENO} de {nb}</td>
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
