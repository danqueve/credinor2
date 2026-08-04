<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Export;
use App\Helpers\Url;
use App\Repositories\CajaRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\ReporteRepository;

class ReporteService
{
    private ReporteRepository $repo;
    private CajaRepository $cajaRepo;
    private PersonalRepository $personalRepo;

    public function __construct()
    {
        $this->repo         = new ReporteRepository();
        $this->cajaRepo     = new CajaRepository();
        $this->personalRepo = new PersonalRepository();
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

        $headers = ['#', 'Cliente', 'DNI', 'Teléfono', 'Dirección', 'Crédito', 'Cuotas Vencidas', 'Deuda Vencida', 'Días Atraso', 'Cobrador', 'Zona'];
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                $i + 1,
                $item['cliente_nombre'],
                $item['dni'],
                $item['telefono'] ?? '',
                $item['direccion'] ?? '',
                $item['credito_codigo'],
                $item['cuotas_vencidas'],
                (float)$item['deuda_vencida'],
                $item['dias_atraso'],
                $item['cobrador_nombre'] ?? '',
                $item['zona_nombre'] ?? '',
            ];
        }

        Export::xlsx($headers, $rows, 'clientes_atraso_' . date('Y-m-d') . '.xlsx', 'Clientes con Atraso', [7 => '#,##0.00']);
    }

    public function exportCobranzaExcel(string $desde, string $hasta): void
    {
        $data = $this->repo->getCobranzaPorCobrador($desde, $hasta);

        $headers = ['#', 'Cobrador', 'Cantidad Pagos', 'Total Cobrado'];
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [$i + 1, $item['cobrador'], $item['cantidad_pagos'], (float)$item['total_cobrado']];
        }

        Export::xlsx($headers, $rows, 'cobranza_' . $desde . '_' . $hasta . '.xlsx', 'Cobranza', [3 => '#,##0.00']);
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
        $sub = $search !== '' ? 'Filtro: ' . $search : 'Todos los clientes';
        $html = $this->clientesPdfHtml($data, 'Clientes', $sub);
        $this->renderPdf($html, 'clientes_' . date('Y-m-d') . '.pdf');
    }

    public function exportClientesExcel(string $search = ''): void
    {
        $data = $this->repo->exportClientes($search);
        Export::xlsx(
            $this->clientesExportHeaders(),
            $this->clientesExportRows($data),
            'clientes_' . date('Y-m-d') . '.xlsx',
            'Clientes',
            [8 => '#,##0.00', 9 => '#,##0.00']
        );
    }

    public function exportClientesCsv(string $search = ''): void
    {
        $data = $this->repo->exportClientes($search);
        Export::csv($this->clientesExportHeaders(), $this->clientesExportRows($data), 'clientes_' . date('Y-m-d') . '.csv');
    }

    /**
     * Cartera de un cobrador. $filtros admite: id_zona (int), solo_atraso (bool).
     */
    public function exportClientesPorCobradorPdf(int $idCobrador, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarCarteraCobrador($idCobrador, $filtros);
        $html = $this->clientesPdfHtml($data, 'Cartera de Clientes', 'Cobrador: ' . $cobrador->nombre);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        $this->renderPdf($html, 'cartera_' . $nombreArchivo . '_' . date('Y-m-d') . '.pdf');
    }

    public function exportClientesPorCobradorExcel(int $idCobrador, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarCarteraCobrador($idCobrador, $filtros);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        Export::xlsx(
            $this->clientesExportHeaders(),
            $this->clientesExportRows($data),
            'cartera_' . $nombreArchivo . '_' . date('Y-m-d') . '.xlsx',
            'Cartera',
            [8 => '#,##0.00', 9 => '#,##0.00']
        );
    }

    public function exportClientesPorCobradorCsv(int $idCobrador, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarCarteraCobrador($idCobrador, $filtros);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        Export::csv($this->clientesExportHeaders(), $this->clientesExportRows($data), 'cartera_' . $nombreArchivo . '_' . date('Y-m-d') . '.csv');
    }

    /**
     * @return array{0: \App\Models\Personal, 1: array} [$cobrador, $data]
     */
    private function cargarCarteraCobrador(int $idCobrador, array $filtros): array
    {
        $cobrador = $this->personalRepo->findById($idCobrador);
        if (!$cobrador) {
            $_SESSION['flash_error'] = 'Cobrador no encontrado.';
            Url::redirect('/personal');
        }

        return [$cobrador, $this->repo->exportClientesPorCobrador($idCobrador, $filtros)];
    }

    /** "Apellido, Nombre" — cae a solo nombre si no hay apellido cargado. */
    private function nombreCompleto(array $item): string
    {
        $apellido = trim((string)($item['apellido'] ?? ''));
        $nombre   = trim((string)($item['nombre'] ?? ''));
        return $apellido !== '' ? $apellido . ', ' . $nombre : $nombre;
    }

    /** "18|35" (numero_cuota|cantidad_cuotas, tal como lo arma el SQL) → "Cuota 18/35". */
    private function formatCuotaLabel(?string $raw): string
    {
        if (empty($raw) || !str_contains($raw, '|')) {
            return '';
        }
        [$numero, $total] = explode('|', $raw, 2);
        return 'Cuota ' . $numero . '/' . $total;
    }

    /** Tabla HTML compartida entre "Clientes" y "Cartera de un cobrador" — mismas columnas. */
    private function clientesPdfHtml(array $data, string $titulo, string $subtitulo): string
    {
        $filas = '';
        $totalCuota = 0.0;
        $totalSaldo = 0.0;
        $i = 1;

        foreach ($data as $item) {
            $cuota = (float)$item['cuota_a_pagar'];
            $saldo = (float)$item['saldo_total'];
            $totalCuota += $cuota;
            $totalSaldo += $saldo;
            $cuotaLabel = $this->formatCuotaLabel($item['cuota_label'] ?? null);
            $direccion = trim((string)($item['direccion'] ?? ''));
            $barrio    = trim((string)($item['barrio'] ?? ''));
            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($this->nombreCompleto($item)) . '<br><small>DNI ' . htmlspecialchars($item['dni'] ?? '') . '</small></td>
                <td>' . htmlspecialchars($item['telefono'] ?? '—') . '</td>
                <td>' . htmlspecialchars($direccion !== '' ? $direccion : '—') . ($barrio !== '' ? '<br><small>' . htmlspecialchars($barrio) . '</small>' : '') . '</td>
                <td>' . htmlspecialchars($item['zona_nombre'] ?? '—') . '</td>
                <td class="right">' . ($cuotaLabel !== '' ? '<small>' . htmlspecialchars($cuotaLabel) . '</small><br>' : '') . '$ ' . number_format($cuota, 0, ',', '.') . '</td>
                <td class="right">$ ' . number_format($saldo, 0, ',', '.') . '</td>
                <td class="center">' . (!empty($item['proxima_cuota']) ? date('d/m/Y', strtotime($item['proxima_cuota'])) : '—') . '</td>
            </tr>';
            $i++;
        }

        return $this->pdfHeader($titulo, $subtitulo . ' — ' . ($i - 1) . ' registros') . '
            <table class="data" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:4%">#</th>
                    <th style="width:20%">Cliente</th>
                    <th style="width:10%">Teléfono</th>
                    <th style="width:20%">Dirección</th>
                    <th style="width:9%">Zona</th>
                    <th style="width:14%">Cuota a pagar</th>
                    <th style="width:12%">Saldo</th>
                    <th style="width:11%">Próx. cuota</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="5" class="right">TOTALES:</td>
                    <td class="right">$ ' . number_format($totalCuota, 0, ',', '.') . '</td>
                    <td class="right">$ ' . number_format($totalSaldo, 0, ',', '.') . '</td>
                    <td></td>
                </tr></tfoot>
            </table>';
    }

    /** @return string[] */
    private function clientesExportHeaders(): array
    {
        return ['#', 'Apellido y Nombre', 'DNI', 'Teléfono', 'Dirección', 'Barrio', 'Zona', 'N° Cuota', 'Cuota a Pagar', 'Saldo', 'Próx. Cuota'];
    }

    private function clientesExportRows(array $data): array
    {
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                $i + 1,
                $this->nombreCompleto($item),
                $item['dni'] ?? '',
                $item['telefono'] ?? '',
                $item['direccion'] ?? '',
                $item['barrio'] ?? '',
                $item['zona_nombre'] ?? '',
                str_replace('|', '/', (string)($item['cuota_label'] ?? '')),
                (float)$item['cuota_a_pagar'],
                (float)$item['saldo_total'],
                !empty($item['proxima_cuota']) ? date('d/m/Y', strtotime($item['proxima_cuota'])) : '',
            ];
        }
        return $rows;
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

    public function exportCreditosExcel(string $search = '', string $estado = ''): void
    {
        $data = $this->repo->exportCreditos($search, $estado);
        $headers = ['#', 'Código', 'Cliente', 'DNI', 'Capital', 'Monto Total', 'Saldo', 'Estado', 'Cobrador', 'Inicio'];
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                $i + 1, $item['codigo'], $item['cliente_nombre'], $item['cliente_dni'],
                (float)$item['capital'], (float)$item['monto_total'], (float)$item['saldo_pendiente'],
                ucfirst((string)$item['estado']), $item['cobrador_nombre'] ?? '',
                date('d/m/Y', strtotime($item['fecha_inicio'])),
            ];
        }
        Export::xlsx($headers, $rows, 'creditos_' . date('Y-m-d') . '.xlsx', 'Créditos', [4 => '#,##0.00', 5 => '#,##0.00', 6 => '#,##0.00']);
    }

    public function exportCreditosCsv(string $search = '', string $estado = ''): void
    {
        $data = $this->repo->exportCreditos($search, $estado);
        $headers = ['#', 'Código', 'Cliente', 'DNI', 'Capital', 'Monto Total', 'Saldo', 'Estado', 'Cobrador', 'Inicio'];
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                $i + 1, $item['codigo'], $item['cliente_nombre'], $item['cliente_dni'],
                (float)$item['capital'], (float)$item['monto_total'], (float)$item['saldo_pendiente'],
                ucfirst((string)$item['estado']), $item['cobrador_nombre'] ?? '',
                date('d/m/Y', strtotime($item['fecha_inicio'])),
            ];
        }
        Export::csv($headers, $rows, 'creditos_' . date('Y-m-d') . '.csv');
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

    public function exportCobrosExcel(string $search = '', string $desde = '', string $hasta = ''): void
    {
        $data = $this->repo->exportCobros($search, $desde, $hasta);
        Export::xlsx($this->cobrosExportHeaders(), $this->cobrosExportRows($data), 'cobros_' . date('Y-m-d') . '.xlsx', 'Cobros', [4 => '#,##0.00']);
    }

    public function exportCobrosCsv(string $search = '', string $desde = '', string $hasta = ''): void
    {
        $data = $this->repo->exportCobros($search, $desde, $hasta);
        Export::csv($this->cobrosExportHeaders(), $this->cobrosExportRows($data), 'cobros_' . date('Y-m-d') . '.csv');
    }

    /** @return string[] */
    private function cobrosExportHeaders(): array
    {
        return ['#', 'Fecha', 'Cliente', 'DNI', 'Monto', 'Crédito', 'Forma de pago', 'Cobrador', 'Estado'];
    }

    private function cobrosExportRows(array $data): array
    {
        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                $i + 1,
                date('d/m/Y', strtotime($item['fecha_pago_real'])),
                $item['cliente_nombre'],
                $item['cliente_dni'],
                (float)$item['monto_pagado'],
                $item['credito_codigo'],
                ucfirst(str_replace('_', ' ', (string)$item['forma_pago'])),
                $item['cobrador_nombre'] ?? '',
                (bool)$item['anulado'] ? 'Anulado' : 'Vigente',
            ];
        }
        return $rows;
    }

    // ─── Hoja de ruta ───────────────────────────────────────────────────────────

    /**
     * Hoja de ruta diaria de un cobrador: cuotas por cobrar en el rango de
     * fechas, ordenadas por zona para armar el recorrido a pie.
     * $filtros admite: id_zona (int|null).
     */
    public function exportHojaRutaPdf(int $idCobrador, string $desde, string $hasta, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarHojaRuta($idCobrador, $desde, $hasta, $filtros);
        $html = $this->buildHojaRutaHtml($data, [
            'cobrador_nombre' => $cobrador->nombre,
            'desde'           => $desde,
            'hasta'           => $hasta,
        ]);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        $this->renderPdf($html, 'hoja_ruta_' . $nombreArchivo . '_' . date('Y-m-d', strtotime($desde)) . '.pdf');
    }

    public function exportHojaRutaExcel(int $idCobrador, string $desde, string $hasta, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarHojaRuta($idCobrador, $desde, $hasta, $filtros);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        Export::xlsx(
            $this->hojaRutaExportHeaders(),
            $this->hojaRutaExportRows($data),
            'hoja_ruta_' . $nombreArchivo . '_' . date('Y-m-d', strtotime($desde)) . '.xlsx',
            'Hoja de Ruta',
            [10 => '#,##0.00']
        );
    }

    public function exportHojaRutaCsv(int $idCobrador, string $desde, string $hasta, array $filtros = []): void
    {
        [$cobrador, $data] = $this->cargarHojaRuta($idCobrador, $desde, $hasta, $filtros);
        $nombreArchivo = preg_replace('/[^a-z0-9]+/i', '_', $cobrador->nombre);
        Export::csv($this->hojaRutaExportHeaders(), $this->hojaRutaExportRows($data), 'hoja_ruta_' . $nombreArchivo . '_' . date('Y-m-d', strtotime($desde)) . '.csv');
    }

    /**
     * Arma el HTML de la hoja de ruta: agrupado por zona, con subtotal por
     * zona y total general. Público — mismo patrón testeable que
     * CreditoPdfService::buildHtml(), ver tests/Unit/HojaRutaHtmlTest.php.
     *
     * @param array $meta  ['cobrador_nombre' => string, 'desde' => string, 'hasta' => string]
     */
    public function buildHojaRutaHtml(array $data, array $meta): string
    {
        $filas = '';
        $totalGeneral = 0.0;
        $i = 1;
        $zonaActual = false; // distinto de null: fuerza el primer encabezado de zona
        $subtotalZona = 0.0;

        $cerrarZona = function () use (&$filas, &$zonaActual, &$subtotalZona) {
            if ($zonaActual !== false) {
                $filas .= '<tr class="subtotal-zona"><td colspan="6" class="right">Subtotal ' . htmlspecialchars($zonaActual) . ':</td>'
                    . '<td class="right">$ ' . number_format($subtotalZona, 0, ',', '.') . '</td><td></td></tr>';
            }
        };

        foreach ($data as $item) {
            $zona = $item['zona_nombre'] ?? 'Sin zona';
            if ($zona !== $zonaActual) {
                $cerrarZona();
                $filas .= '<tr class="zona-header"><td colspan="8">' . htmlspecialchars($zona) . '</td></tr>';
                $zonaActual = $zona;
                $subtotalZona = 0.0;
            }

            $aCobrar = (float)$item['a_cobrar'];
            $subtotalZona += $aCobrar;
            $totalGeneral += $aCobrar;

            $apellido  = trim((string)($item['cliente_apellido'] ?? ''));
            $nombre    = $apellido !== '' ? $apellido . ', ' . $item['cliente_nombre'] : $item['cliente_nombre'];
            $direccion = trim((string)($item['cliente_direccion'] ?? ''));
            $barrio    = trim((string)($item['cliente_barrio'] ?? ''));

            $clase = ($i % 2 === 0) ? 'even' : 'odd';
            $filas .= '<tr class="' . $clase . '">
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($nombre) . '</td>
                <td>' . htmlspecialchars($direccion !== '' ? $direccion : '—') . ($barrio !== '' ? '<br><small>' . htmlspecialchars($barrio) . '</small>' : '') . '</td>
                <td>' . htmlspecialchars($item['cliente_telefono'] ?? '—') . '</td>
                <td>' . htmlspecialchars($item['credito_codigo']) . '<br><small>Cuota ' . (int)$item['numero_cuota'] . '/' . (int)$item['total_cuotas'] . '</small></td>
                <td class="center">' . date('d/m/Y', strtotime($item['fecha_vencimiento'])) . '</td>
                <td class="right">$ ' . number_format($aCobrar, 0, ',', '.') . '</td>
                <td class="firma"></td>
            </tr>';
            $i++;
        }
        $cerrarZona();

        $totalRegistros = $i - 1;
        if ($totalRegistros === 0) {
            $filas = '<tr><td colspan="8" class="center">Sin cuotas por cobrar en el período seleccionado.</td></tr>';
        }

        $subtitulo = 'Cobrador: ' . $meta['cobrador_nombre'] . ' — '
            . date('d/m/Y', strtotime($meta['desde'])) . ' al ' . date('d/m/Y', strtotime($meta['hasta']));

        return $this->pdfHeader('Hoja de Ruta', $subtitulo . ' — ' . $totalRegistros . ' cuotas') . '
            <table class="data ruta" cellspacing="0" cellpadding="0">
                <thead><tr>
                    <th style="width:4%">#</th>
                    <th style="width:19%">Cliente</th>
                    <th style="width:22%">Dirección</th>
                    <th style="width:11%">Teléfono</th>
                    <th style="width:14%">Crédito</th>
                    <th style="width:9%">Vence</th>
                    <th style="width:11%">A cobrar</th>
                    <th style="width:10%">Firma</th>
                </tr></thead>
                <tbody>' . $filas . '</tbody>
                <tfoot><tr>
                    <td colspan="6" class="right">TOTAL A COBRAR:</td>
                    <td class="right">$ ' . number_format($totalGeneral, 0, ',', '.') . '</td>
                    <td></td>
                </tr></tfoot>
            </table>';
    }

    /**
     * @return array{0: \App\Models\Personal, 1: array} [$cobrador, $data]
     */
    private function cargarHojaRuta(int $idCobrador, string $desde, string $hasta, array $filtros): array
    {
        $cobrador = $this->personalRepo->findById($idCobrador);
        if (!$cobrador) {
            $_SESSION['flash_error'] = 'Cobrador no encontrado.';
            Url::redirect('/personal');
        }

        $idZona = !empty($filtros['id_zona']) ? (int)$filtros['id_zona'] : null;
        return [$cobrador, $this->repo->getHojaRutaCobrador($idCobrador, $desde, $hasta, $idZona)];
    }

    /** @return string[] */
    private function hojaRutaExportHeaders(): array
    {
        return ['#', 'Cliente', 'DNI', 'Dirección', 'Barrio', 'Teléfono', 'Zona', 'Crédito', 'Cuota', 'Vence', 'A cobrar'];
    }

    private function hojaRutaExportRows(array $data): array
    {
        $rows = [];
        foreach ($data as $i => $item) {
            $apellido = trim((string)($item['cliente_apellido'] ?? ''));
            $nombre   = $apellido !== '' ? $apellido . ', ' . $item['cliente_nombre'] : $item['cliente_nombre'];
            $rows[] = [
                $i + 1,
                $nombre,
                $item['cliente_dni'],
                $item['cliente_direccion'] ?? '',
                $item['cliente_barrio'] ?? '',
                $item['cliente_telefono'] ?? '',
                $item['zona_nombre'] ?? '',
                $item['credito_codigo'],
                $item['numero_cuota'] . '/' . $item['total_cuotas'],
                date('d/m/Y', strtotime($item['fecha_vencimiento'])),
                (float)$item['a_cobrar'],
            ];
        }
        return $rows;
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

            /* ── Hoja de ruta: agrupado por zona ── */
            table.ruta tr.zona-header td {
                background-color: #dbeafe;
                color: #1e3a5f;
                font-weight: bold;
                font-size: 8px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                padding: 5px 5px;
                border-bottom: 1px solid #93c5fd;
            }
            table.ruta tr.subtotal-zona td {
                background-color: #f0f4f8;
                font-weight: bold;
                font-size: 8px;
                color: #1e3a5f;
                padding: 4px 5px;
                border-bottom: 2px solid #cbd5e1;
            }
            table.ruta td.firma { border-bottom: 1px solid #9ca3af; }
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
            'tempDir'       => (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/cache/mpdf',
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
