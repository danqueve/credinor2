<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Models\Credito;
use Mpdf\Mpdf;

class CreditoPdfService
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = defined('ROOT_PATH')
            ? ROOT_PATH . '/storage/creditos'
            : dirname(__DIR__, 2) . '/storage/creditos';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function generar(Credito $credito, ?Cliente $cliente, array $pagos): string
    {
        $filename = 'credito_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $credito->codigo) . '_' . date('Ymd_His') . '.pdf';
        $fullPath = $this->storageDir . '/' . $filename;

        $html = $this->buildHtml($credito, $cliente, $pagos);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 12,
            'margin_bottom' => 12,
            'tempDir'       => (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/cache/mpdf',
        ]);

        $mpdf->SetTitle('Cronograma y pagos - ' . $credito->codigo);
        $mpdf->WriteHTML($this->css() . $html);
        $mpdf->Output($fullPath, 'F');

        return 'storage/creditos/' . $filename;
    }

    public function descargar(string $rutaRelativa): void
    {
        $fullPath = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/' . $rutaRelativa;

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo 'PDF no disponible.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
    }

    public function buildHtml(Credito $credito, ?Cliente $cliente, array $pagos): string
    {
        $porcentaje = $credito->monto_total > 0
            ? round((($credito->monto_total - $credito->saldo_pendiente) / $credito->monto_total) * 100, 1)
            : 0;

        $capitalFormatted = '$' . number_format($credito->capital, 2, ',', '.');
        $montoTotalFormatted = '$' . number_format($credito->monto_total, 2, ',', '.');
        $saldoPendienteFormatted = '$' . number_format($credito->saldo_pendiente, 2, ',', '.');
        $valorCuotaFormatted = '$' . number_format($credito->valor_cuota, 2, ',', '.');
        $fechaInicio = $credito->fecha_inicio ? date('d/m/Y', strtotime($credito->fecha_inicio)) : '—';
        $fechaFin = $credito->fecha_fin_estimada ? date('d/m/Y', strtotime($credito->fecha_fin_estimada)) : '—';
        $frecuencia = ucfirst($credito->frecuencia);
        $clienteNombre = $cliente ? htmlspecialchars($cliente->nombre) : '—';
        $clienteDni = $cliente ? htmlspecialchars($cliente->dni) : '—';
        $fechaGeneracion = date('d/m/Y H:i');

        $cuotasHtml = '';
        if (!empty($credito->cuotas)) {
            foreach ($credito->cuotas as $q) {
                $saldoCuota = '$' . number_format($q->getSaldo(), 2, ',', '.');
                $cuotasHtml .= '<tr>'
                    . '<td>' . htmlspecialchars((string)$q->numero_cuota) . '</td>'
                    . '<td>' . date('d/m/Y', strtotime($q->fecha_vencimiento)) . '</td>'
                    . '<td>' . '$' . number_format($q->monto_esperado, 2, ',', '.') . '</td>'
                    . '<td>' . '$' . number_format($q->monto_pagado, 2, ',', '.') . '</td>'
                    . '<td>' . $saldoCuota . '</td>'
                    . '<td>' . htmlspecialchars($q->estadoLabel()) . '</td>'
                    . '<td>' . ($q->fecha_pagada ? date('d/m/Y', strtotime($q->fecha_pagada)) : '—') . '</td>'
                    . '</tr>';
            }
        } else {
            $cuotasHtml = '<tr><td colspan="7" class="empty-row">Sin cuotas registradas.</td></tr>';
        }

        $pagosHtml = '';
        if (!empty($pagos)) {
            foreach ($pagos as $p) {
                $cuotasAplicadas = !empty($p->cuotasAplicadas)
                    ? implode(', ', array_map(fn ($c) => '#' . $c['numero_cuota'], $p->cuotasAplicadas))
                    : '—';
                $estadoPago = $p->anulado ? 'Anulado' : 'Vigente';

                $pagosHtml .= '<tr>'
                    . '<td>' . date('d/m/Y', strtotime($p->fecha_pago_real)) . '</td>'
                    . '<td>' . '$' . number_format($p->monto_pagado, 2, ',', '.') . '</td>'
                    . '<td>' . htmlspecialchars($p->formasPagoLabel()) . '</td>'
                    . '<td>' . htmlspecialchars($cuotasAplicadas) . '</td>'
                    . '<td>' . htmlspecialchars($p->cobrador_nombre ?? '—') . '</td>'
                    . '<td>' . $estadoPago . '</td>'
                    . '</tr>';
            }
        } else {
            $pagosHtml = '<tr><td colspan="6" class="empty-row">Sin pagos registrados.</td></tr>';
        }

        return <<<HTML
        <div class="header">
            <div>
                <div class="brand">CREDINOR</div>
                <div class="title">Cronograma y pagos del crédito <span class="credit-code">{$credito->codigo}</span></div>
            </div>
            <div class="meta">
                <div>Generado: {$fechaGeneracion}</div>
                <div>Estado: {$credito->estadoLabel()}</div>
            </div>
        </div>

        <table class="info-table">
            <tbody>
                <tr>
                    <td class="info-label">Cliente</td>
                    <td class="info-value">{$clienteNombre}</td>
                    <td class="info-label">Crédito</td>
                    <td class="info-value">{$credito->codigo}</td>
                </tr>
                <tr>
                    <td class="info-label">DNI</td>
                    <td class="info-value">{$clienteDni}</td>
                    <td class="info-label">Estado</td>
                    <td class="info-value">{$credito->estadoLabel()}</td>
                </tr>
                <tr>
                    <td class="info-label">Inicio</td>
                    <td class="info-value">{$fechaInicio}</td>
                    <td class="info-label">Fin estimado</td>
                    <td class="info-value">{$fechaFin}</td>
                </tr>
                <tr>
                    <td class="info-label">Frecuencia</td>
                    <td class="info-value">{$frecuencia}</td>
                    <td class="info-label">Cuotas</td>
                    <td class="info-value">{$credito->cantidad_cuotas}</td>
                </tr>
                <tr>
                    <td class="info-label">Capital</td>
                    <td class="info-value">{$capitalFormatted}</td>
                    <td class="info-label">Total a devolver</td>
                    <td class="info-value">{$montoTotalFormatted}</td>
                </tr>
                <tr>
                    <td class="info-label">Pendiente</td>
                    <td class="info-value">{$saldoPendienteFormatted}</td>
                    <td class="info-label">Valor cuota</td>
                    <td class="info-value">{$valorCuotaFormatted}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-header">Cronograma de cuotas</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vencimiento</th>
                    <th>Esperado</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Fecha pago</th>
                </tr>
            </thead>
            <tbody>
                {$cuotasHtml}
            </tbody>
        </table>

        <div class="section-header">Historial de pagos</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Forma</th>
                    <th>Cuotas</th>
                    <th>Cobrador</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                {$pagosHtml}
            </tbody>
        </table>
        HTML;
    }

    private function css(): string
    {
        return '<style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 10px;
                color: #111827;
                margin: 0;
                padding: 0;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 16px;
                padding-bottom: 10px;
                border-bottom: 2px solid #dbeafe;
            }
            .brand {
                font-size: 24px;
                font-weight: 700;
                letter-spacing: 0.08em;
                color: #0f172a;
                margin-bottom: 4px;
            }
            .title {
                font-size: 13px;
                font-weight: 600;
                color: #334155;
            }
            .credit-code {
                color: #0f172a;
                font-weight: 700;
            }
            .meta {
                text-align: right;
                font-size: 9px;
                color: #475569;
                line-height: 1.5;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 18px;
            }
            .summary-grid div {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 10px;
            }
            .summary-label {
                font-size: 8.5px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #64748b;
                margin-bottom: 4px;
            }
            .summary-value {
                font-size: 12px;
                font-weight: 700;
                color: #0f172a;
            }
            .section-header {
                margin: 18px 0 8px;
                font-size: 12px;
                font-weight: 700;
                color: #0f172a;
                border-left: 4px solid #2563eb;
                padding-left: 8px;
                background: #eff6ff;
            }
            .data-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10px;
                margin-bottom: 12px;
            }
            .data-table th,
            .data-table td {
                border: 1px solid #cbd5e1;
                padding: 8px 6px;
            }
            .data-table th {
                background: #e2e8f0;
                color: #0f172a;
                font-weight: 700;
                text-align: left;
            }
            .data-table tbody tr:nth-child(odd) {
                background: #fbfbfd;
            }
            .data-table tbody tr:nth-child(even) {
                background: #ffffff;
            }
            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 18px;
                font-size: 10.5px;
            }
            .info-table td {
                border: 1px solid #cbd5e1;
                padding: 10px 12px;
                vertical-align: middle;
            }
            .info-label {
                width: 18%;
                background: #f1f5f9;
                color: #475569;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .info-value {
                width: 32%;
                background: #ffffff;
                color: #0f172a;
                font-weight: 600;
            }
            .empty-row {
                text-align: center;
                color: #64748b;
                padding: 18px 0;
            }
        </style>';
    }
}
