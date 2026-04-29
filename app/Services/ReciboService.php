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

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => [148, 105],   // A6 apaisado, tamaño recibo
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->SetTitle('Recibo ' . $numero);
        $mpdf->WriteHTML($html);
        $mpdf->Output($fullPath, 'F');

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
            body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 0; }
            .header { background: #0f172a; color: #fff; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px; }
            .header h1 { margin: 0; font-size: 16px; letter-spacing: 1px; }
            .header .num { font-size: 12px; color: #94a3b8; margin-top: 2px; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
            td.label { color: #6b7280; width: 45%; }
            td.value { font-weight: bold; }
            .monto { font-size: 20px; color: #16a34a; font-weight: bold; text-align: center; padding: 8px 0; }
            .footer { font-size: 9px; color: #9ca3af; text-align: center; margin-top: 10px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
        </style>
        <div class="header">
            <h1>CREDINOR</h1>
            <div class="num">Recibo Nº {$d['numero_recibo']}</div>
        </div>
        <div class="monto">{$monto}</div>
        <table>
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
        <div class="footer">Este recibo es comprobante válido de pago — Credinor San Miguel de Tucumán</div>
        HTML;
    }
}
