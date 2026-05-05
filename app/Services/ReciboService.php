<?php

declare(strict_types=1);

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ReciboService
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = defined('ROOT_PATH')
            ? ROOT_PATH . '/storage/recibos'
            : dirname(__DIR__, 2) . '/storage/recibos';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Genera el PDF de un recibo y devuelve la ruta relativa al archivo.
     *
     * @param array{
     *   numero_recibo: string,
     *   codigo_credito: string,
     *   cliente_nombre: string,
     *   cliente_dni: string,
     *   monto: float,
     *   forma_pago: string,
     *   fecha_pago_real: string,
     *   fecha_registro: string,
     *   cobrador_nombre: string,
     *   cuotas_aplicadas: string,
     *   referencia_externa?: string|null
     * } $datos
     * @return string  ruta relativa desde ROOT_PATH  (ej. storage/recibos/R-2026-00001.pdf)
     */
    public function generar(array $datos): string
    {
        $numero    = $datos['numero_recibo'];
        $filename  = preg_replace('/[^A-Za-z0-9\-_]/', '_', $numero) . '.pdf';
        $fullPath  = $this->storageDir . '/' . $filename;

        $appUrl = $_ENV['APP_URL'] ?? '';

        $html = $this->buildHtml($datos);
        error_log("ReciboService: HTML construido");

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A5',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 12,
            'margin_bottom' => 12,
        ]);
        $mpdf->SetTitle('Recibo ' . $numero);
        error_log("ReciboService: Iniciando WriteHTML...");
        $mpdf->WriteHTML($html);
        error_log("ReciboService: WriteHTML completado");

        error_log("ReciboService: Guardando en " . $fullPath);
        $mpdf->Output($fullPath, 'F');
        error_log("ReciboService: mPDF Output completado");

        return 'storage/recibos/' . $filename;
    }

    /**
     * Envía el PDF directamente al navegador para descarga.
     */
    public function descargar(string $rutaRelativa): void
    {
        $fullPath = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/' . $rutaRelativa;

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo 'Recibo no encontrado.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
    }

    private function buildHtml(array $d): string
    {
        $monto          = '$' . number_format((float)$d['monto'], 2, ',', '.');
        $fechaPago      = date('d/m/Y', strtotime($d['fecha_pago_real']));
        $fechaReg       = date('d/m/Y H:i', strtotime($d['fecha_registro']));
        $formaLabel     = match ($d['forma_pago']) {
            'efectivo'      => 'Efectivo',
            'transferencia' => 'Transferencia',
            'mp'            => 'Mercado Pago',
            default         => ucfirst($d['forma_pago']),
        };
        $refExt = !empty($d['referencia_externa']) ? htmlspecialchars($d['referencia_externa']) : '—';

        return <<<HTML
        <style>
            * { font-family: DejaVu Sans, sans-serif; }
            body { font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; }

            .header { background: #1e3a5f; color: #fff; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; }
            .header-row { width: 100%; }
            .header-row td { padding: 0; border: none; vertical-align: middle; }
            .company { font-size: 20px; font-weight: bold; letter-spacing: 1.5px; color: #ffffff; }
            .recibo-num { font-size: 10px; color: #93c5fd; margin-top: 3px; }
            .recibo-badge { text-align: right; font-size: 8px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.5px; }

            .monto-box { border: 2px solid #16a34a; border-radius: 6px; text-align: center; padding: 12px 0; margin-bottom: 16px; }
            .monto-label { font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
            .monto-value { font-size: 26px; font-weight: bold; color: #16a34a; }

            table.detalle { width: 100%; border-collapse: collapse; }
            table.detalle td { padding: 6px 4px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; vertical-align: middle; }
            table.detalle td.label { color: #6b7280; width: 42%; font-size: 9.5px; }
            table.detalle td.value { font-weight: bold; color: #111827; }

            .divider { border: none; border-top: 1px solid #e5e7eb; margin: 14px 0; }

            .footer { font-size: 8px; color: #9ca3af; text-align: center; margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 8px; line-height: 1.5; }
        </style>

        <div class="header">
            <table class="header-row" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <div class="company">CREDINOR</div>
                        <div class="recibo-num">Recibo N&ordm; {$d['numero_recibo']}</div>
                    </td>
                    <td class="recibo-badge">Comprobante<br>de pago</td>
                </tr>
            </table>
        </div>

        <div class="monto-box">
            <div class="monto-label">Monto abonado</div>
            <div class="monto-value">{$monto}</div>
        </div>

        <table class="detalle" cellspacing="0" cellpadding="0">
            <tr><td class="label">Cliente</td><td class="value">{$d['cliente_nombre']}</td></tr>
            <tr><td class="label">DNI</td><td class="value">{$d['cliente_dni']}</td></tr>
            <tr><td class="label">Crédito</td><td class="value">{$d['codigo_credito']}</td></tr>
            <tr><td class="label">Cuotas</td><td class="value">{$d['cuotas_aplicadas']}</td></tr>
            <tr><td class="label">Forma de pago</td><td class="value">{$formaLabel}</td></tr>
            <tr><td class="label">Referencia</td><td class="value">{$refExt}</td></tr>
            <tr><td class="label">Fecha de pago</td><td class="value">{$fechaPago}</td></tr>
            <tr><td class="label">Cobrador</td><td class="value">{$d['cobrador_nombre']}</td></tr>
            <tr><td class="label">Registrado</td><td class="value">{$fechaReg}</td></tr>
        </table>

        <div class="footer">
            Este recibo es comprobante v&aacute;lido de pago<br>
            Credinor &mdash; San Miguel de Tucum&aacute;n
        </div>
        HTML;
    }
}
