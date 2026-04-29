<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
$d = $filtros['desde'];
$h = $filtros['hasta'];
$dias = $filtros['dias'];

// Helpers de formato
$fmt = fn($n) => '$' . number_format((float)$n, 2, ',', '.');
$fmtK = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
?>

<!-- Encabezado + filtro -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="h3 mb-0 text-white fw-bold"><i class="bi bi-bar-chart-line me-2 text-info"></i>Reportes y Analíticas</h2>
    <form action="<?= $appUrl ?>/reportes" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
        <input type="date" name="desde" class="form-control form-control-sm bg-slate-800 border-secondary text-light" value="<?= $d ?>">
        <span class="text-secondary small">—</span>
        <input type="date" name="hasta" class="form-control form-control-sm bg-slate-800 border-secondary text-light" value="<?= $h ?>">
        <select name="dias" class="form-select form-select-sm bg-slate-800 border-secondary text-light" style="width:auto;">
            <option value="30"  <?= $dias == 30 ? 'selected' : '' ?>>Flujo 30d</option>
            <option value="60"  <?= $dias == 60 ? 'selected' : '' ?>>Flujo 60d</option>
            <option value="90"  <?= $dias == 90 ? 'selected' : '' ?>>Flujo 90d</option>
        </select>
        <button class="btn btn-sm btn-primary">Filtrar</button>
    </form>
</div>

<!-- ① KPIs Cartera -->
<?php $c = $resumen['cartera']; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary">Créditos activos</div>
            <div class="h3 text-info fw-bold mb-0"><?= number_format((int)($c['total_creditos'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary">Capital prestado</div>
            <div class="h5 text-light fw-bold mb-0"><?= $fmtK($c['capital_total'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary">Saldo pendiente</div>
            <div class="h5 text-warning fw-bold mb-0"><?= $fmtK($c['saldo_actual'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary">Cuotas vencidas hoy</div>
            <div class="h3 <?= count($cuotasHoy) > 0 ? 'text-danger' : 'text-success' ?> fw-bold mb-0"><?= count($cuotasHoy) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- ② Aging Vencidos -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-light"><i class="bi bi-hourglass-split text-danger me-2"></i>Aging Cartera Vencida</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Tramo</th><th class="text-center">Cuotas</th><th class="text-end">Saldo Vencido</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($resumen['aging'] as $ag): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($ag['tramo']) ?></td>
                            <td class="text-center text-warning"><?= $ag['cantidad_cuotas'] ?></td>
                            <td class="text-end text-danger fw-bold"><?= $fmtK($ag['saldo_vencido']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resumen['aging'])): ?>
                        <tr><td colspan="3" class="text-center text-secondary py-3">Sin cuotas vencidas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ③ Multi-crédito -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-layers text-warning me-2"></i>Clientes con Múltiples Créditos</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Cliente</th><th>DNI</th><th class="text-center">Créditos</th><th class="text-end">Saldo Total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($resumen['multi_creditos'] as $m): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($m['nombre']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($m['dni']) ?></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= $m['creditos_activos'] ?></span></td>
                            <td class="text-end text-warning fw-bold"><?= $fmtK($m['saldo_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resumen['multi_creditos'])): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">Ningún cliente con múltiples créditos.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ④ Cobranza por cobrador -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-light"><i class="bi bi-cash-coin text-success me-2"></i>Cobranza por Cobrador</h6>
                <div class="d-flex gap-1">
                    <a href="<?= $appUrl ?>/reportes/exportar/cobranza?desde=<?= $d ?>&hasta=<?= $h ?>&format=excel" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:.75rem;"><i class="bi bi-file-earmark-spreadsheet"></i> XLS</a>
                    <a href="<?= $appUrl ?>/reportes/exportar/cobranza?desde=<?= $d ?>&hasta=<?= $h ?>&format=pdf" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.75rem;"><i class="bi bi-file-pdf"></i> PDF</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Cobrador</th><th class="text-center">Pagos</th><th class="text-end">Total Cobrado</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($cobranza as $cb): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($cb['cobrador']) ?></td>
                            <td class="text-center text-secondary"><?= $cb['cantidad_pagos'] ?></td>
                            <td class="text-end text-success fw-bold"><?= $fmtK($cb['total_cobrado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cobranza)): ?>
                        <tr><td colspan="3" class="text-center text-secondary py-3">Sin datos en el periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑤ Performance vendedores -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-person-check text-info me-2"></i>Performance Vendedores</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Vendedor</th><th class="text-center">Créditos</th><th class="text-end">Capital</th><th class="text-end">Volumen</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($v['vendedor']) ?></td>
                            <td class="text-center"><?= $v['cantidad_creditos'] ?></td>
                            <td class="text-end text-secondary"><?= $fmtK($v['capital_prestado']) ?></td>
                            <td class="text-end text-info fw-bold"><?= $fmtK($v['volumen_negocio']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ventas)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">Sin datos en el periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑥ Capital vs Recuperado -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-arrow-left-right text-warning me-2"></i>Capital Prestado vs Recuperado</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Mes</th><th class="text-center">Créd.</th><th class="text-end">Capital</th><th class="text-end">Recuperado</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($capitalRec as $cr): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($cr['mes'] ?? '') ?></td>
                            <td class="text-center text-secondary"><?= $cr['creditos_otorgados'] ?? 0 ?></td>
                            <td class="text-end text-warning"><?= $fmtK($cr['capital_prestado'] ?? 0) ?></td>
                            <td class="text-end text-success fw-bold"><?= $fmtK($cr['total_recuperado'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($capitalRec)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">Sin datos en el periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑦ Rendiciones con diferencia -->
    <div class="col-12 col-lg-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Rendiciones con Diferencia</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Fecha</th><th>Cobrador</th><th class="text-end">Declarado</th><th class="text-end">Registrado</th><th class="text-end">Dif.</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rendDiferencia as $rd): ?>
                        <tr>
                            <td class="text-secondary"><?= date('d/m/Y', strtotime($rd['fecha_rendicion'])) ?></td>
                            <td class="text-light"><?= htmlspecialchars($rd['cobrador_nombre']) ?></td>
                            <td class="text-end"><?= $fmtK($rd['total_declarado']) ?></td>
                            <td class="text-end"><?= $fmtK($rd['total_registrado']) ?></td>
                            <td class="text-end fw-bold <?= (float)$rd['diferencia'] < 0 ? 'text-danger' : 'text-warning' ?>">
                                <?= $fmt($rd['diferencia']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rendDiferencia)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-3">Sin diferencias en el periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑧ Clientes con atraso -->
    <div class="col-12">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-light"><i class="bi bi-clock-history text-danger me-2"></i>Clientes con Cuotas Atrasadas</h6>
                <div class="d-flex gap-1">
                    <a href="<?= $appUrl ?>/reportes/exportar/atraso?format=excel" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:.75rem;"><i class="bi bi-file-earmark-spreadsheet"></i> XLS</a>
                    <a href="<?= $appUrl ?>/reportes/exportar/atraso?format=pdf" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.75rem;"><i class="bi bi-file-pdf"></i> PDF</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Cliente</th><th>DNI</th><th>Teléfono</th><th>Crédito</th>
                        <th class="text-center">C.Venc.</th><th class="text-end">Deuda</th>
                        <th class="text-center">Días</th><th>Cobrador</th><th>Zona</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach (array_slice($atraso, 0, 50) as $at): ?>
                        <tr>
                            <td class="text-light">
                                <a href="<?= $appUrl ?>/clientes/ficha?id=<?= $at['id_cliente'] ?>" class="text-info text-decoration-none">
                                    <?= htmlspecialchars($at['cliente_nombre']) ?>
                                </a>
                            </td>
                            <td class="text-secondary"><?= htmlspecialchars($at['dni']) ?></td>
                            <td>
                                <?php if ($at['telefono']): ?>
                                <a href="tel:<?= htmlspecialchars($at['telefono']) ?>" class="text-success text-decoration-none">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($at['telefono']) ?>
                                </a>
                                <?php else: echo '—'; endif; ?>
                            </td>
                            <td class="font-monospace text-secondary" style="font-size:.8rem;">
                                <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $at['id_credito'] ?>" class="text-secondary text-decoration-none">
                                    <?= htmlspecialchars($at['credito_codigo']) ?>
                                </a>
                            </td>
                            <td class="text-center"><span class="badge bg-danger"><?= $at['cuotas_vencidas'] ?></span></td>
                            <td class="text-end text-danger fw-bold"><?= $fmtK($at['deuda_vencida']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $at['dias_atraso'] > 30 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= $at['dias_atraso'] ?>d
                                </span>
                            </td>
                            <td class="text-secondary"><?= htmlspecialchars($at['cobrador_nombre'] ?? '—') ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($at['zona_nombre'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($atraso)): ?>
                        <tr><td colspan="9" class="text-center text-secondary py-4">
                            <i class="bi bi-check-circle text-success me-2"></i>Sin clientes con atraso.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($atraso) > 50): ?>
            <div class="card-footer bg-transparent border-secondary text-secondary small text-center">
                Mostrando 50 de <?= count($atraso) ?> registros. Exportar para ver todos.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ⑨ Flujo de caja proyectado -->
    <div class="col-12">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-calendar-week text-primary me-2"></i>Flujo de Caja Proyectado (próximos <?= $dias ?> días)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Fecha</th><th>Día</th><th class="text-center">Cuotas</th><th class="text-end">Monto Esperado</th>
                    </tr></thead>
                    <tbody>
                    <?php
                    $totalFlujoCaja = 0;
                    foreach ($flujoCaja as $fc):
                        $totalFlujoCaja += (float)$fc['monto_esperado'];
                    ?>
                        <tr>
                            <td class="text-light"><?= date('d/m/Y', strtotime($fc['fecha'])) ?></td>
                            <td class="text-secondary"><?= strftime('%A', strtotime($fc['fecha'])) ?></td>
                            <td class="text-center text-secondary"><?= $fc['cuotas'] ?></td>
                            <td class="text-end text-info fw-bold"><?= $fmtK($fc['monto_esperado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($flujoCaja)): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">Sin cuotas proyectadas.</td></tr>
                    <?php else: ?>
                        <tr class="border-top border-secondary">
                            <td colspan="3" class="text-end text-secondary fw-bold">TOTAL <?= $dias ?>d</td>
                            <td class="text-end text-success fw-bold fs-6"><?= $fmtK($totalFlujoCaja) ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑩ Cuotas que vencen hoy -->
    <div class="col-12">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light">
                    <i class="bi bi-calendar-check text-warning me-2"></i>
                    Cuotas que Vencen Hoy — <?= date('d/m/Y') ?>
                    <span class="badge bg-warning text-dark ms-2"><?= count($cuotasHoy) ?></span>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Cliente</th><th>DNI</th><th>Teléfono</th><th>Crédito</th>
                        <th class="text-center">#Cuota</th><th class="text-end">Monto</th>
                        <th>Estado</th><th>Cobrador</th><th>Zona</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($cuotasHoy as $ch): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($ch['cliente_nombre']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($ch['cliente_dni']) ?></td>
                            <td>
                                <?php if ($ch['cliente_telefono']): ?>
                                <a href="tel:<?= htmlspecialchars($ch['cliente_telefono']) ?>" class="text-success text-decoration-none small">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($ch['cliente_telefono']) ?>
                                </a>
                                <?php else: echo '—'; endif; ?>
                            </td>
                            <td class="font-monospace text-secondary" style="font-size:.8rem;">
                                <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $ch['id_credito'] ?>" class="text-secondary text-decoration-none">
                                    <?= htmlspecialchars($ch['credito_codigo']) ?>
                                </a>
                            </td>
                            <td class="text-center text-secondary"><?= $ch['numero_cuota'] ?></td>
                            <td class="text-end text-warning fw-bold"><?= $fmtK($ch['monto_esperado']) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= ucfirst($ch['estado']) ?></span></td>
                            <td class="text-secondary"><?= htmlspecialchars($ch['cobrador_nombre'] ?? '—') ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($ch['zona_nombre'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cuotasHoy)): ?>
                        <tr><td colspan="9" class="text-center text-secondary py-4">
                            <i class="bi bi-check-circle text-success me-2"></i>Sin cuotas para hoy.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ⑪ Comisiones sugeridas -->
    <div class="col-12">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-light"><i class="bi bi-percent text-secondary me-2"></i>Comisiones Sugeridas del Periodo</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead><tr class="text-secondary small text-uppercase">
                        <th>Personal</th><th>Tipo</th><th class="text-end">Base</th><th class="text-end">%</th><th class="text-end">Comisión</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($comisiones as $cm): ?>
                        <tr>
                            <td class="text-light"><?= htmlspecialchars($cm['nombre']) ?></td>
                            <td><span class="badge bg-<?= $cm['tipo'] === 'venta' ? 'info' : 'success' ?>"><?= ucfirst($cm['tipo']) ?></span></td>
                            <td class="text-end text-secondary"><?= $fmtK($cm['monto_base']) ?></td>
                            <td class="text-end text-secondary"><?= number_format((float)$cm['pct'], 2) ?>%</td>
                            <td class="text-end text-success fw-bold"><?= $fmtK($cm['monto_comision']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($comisiones)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-3">Sin comisiones en el periodo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
