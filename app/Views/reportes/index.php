<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
$d = $filtros['desde'];
$h = $filtros['hasta'];

$fmt  = fn($n) => '$' . number_format((float)$n, 2, ',', '.');
$fmtK = fn($n) => '$' . number_format((float)$n, 0, ',', '.');

$ef  = $entreFechas;
$hi  = $historicas;

$tipoBadge = [
    'cobranza' => ['class' => 'bg-success',         'icon' => 'bi-arrow-down-circle', 'label' => 'Cobranza'],
    'prestamo' => ['class' => 'bg-warning text-dark','icon' => 'bi-arrow-up-circle',   'label' => 'Préstamo'],
    'ingreso'  => ['class' => 'bg-info text-dark',  'icon' => 'bi-plus-circle',        'label' => 'Ingreso Caja'],
    'egreso'   => ['class' => 'bg-danger',           'icon' => 'bi-dash-circle',        'label' => 'Egreso Caja'],
];
?>

<!-- Encabezado + filtro -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="h3 mb-0 text-white fw-bold">
        <i class="bi bi-bar-chart-line me-2 text-info"></i>Reportes y Analíticas
    </h2>
    <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
        <a href="<?= $appUrl ?>/reportes/exportar/clientes?format=pdf" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i>Clientes
        </a>
        <a href="<?= $appUrl ?>/reportes/exportar/creditos?format=pdf" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i>Creditos
        </a>
        <a href="<?= $appUrl ?>/reportes/exportar/cobros?format=pdf&desde=<?= urlencode($d) ?>&hasta=<?= urlencode($h) ?>" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i>Cobros
        </a>
    <form action="<?= $appUrl ?>/reportes" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
        <input type="date" name="desde" class="form-control form-control-sm bg-slate-800 border-secondary text-light" value="<?= $d ?>">
        <span class="text-secondary small">—</span>
        <input type="date" name="hasta" class="form-control form-control-sm bg-slate-800 border-secondary text-light" value="<?= $h ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </form>
    </div>
</div>

<!-- ① Sección Entre Fechas -->
<p class="text-secondary small text-uppercase fw-semibold mb-2" style="letter-spacing:.07em;">
    <i class="bi bi-calendar-range me-1"></i>Entre Fechas
    <span class="text-muted ms-2"><?= date('d/m/Y', strtotime($d)) ?> — <?= date('d/m/Y', strtotime($h)) ?></span>
</p>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-arrow-down-circle text-success me-1"></i>Total Cobrado</div>
            <div class="h4 text-success fw-bold mb-0"><?= $fmtK($ef['cobrado']) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-arrow-up-circle text-warning me-1"></i>Total Prestado</div>
            <div class="h4 text-warning fw-bold mb-0"><?= $fmtK($ef['prestado']) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <?php $dif = $ef['diferencia']; $difClass = $dif >= 0 ? 'text-success' : 'text-danger'; ?>
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1">
                <i class="bi bi-arrow-left-right text-info me-1"></i>Diferencia
                <span class="text-muted">(+ mov. manuales)</span>
            </div>
            <div class="h4 <?= $difClass ?> fw-bold mb-0">
                <?= ($dif >= 0 ? '+' : '') . $fmtK($dif) ?>
            </div>
        </div>
    </div>
</div>

<!-- ② Métricas Históricas -->
<p class="text-secondary small text-uppercase fw-semibold mb-2" style="letter-spacing:.07em;">
    <i class="bi bi-infinity me-1"></i>Métricas Históricas
</p>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <?php $sc = $hi['saldo_caja']; $scClass = $sc >= 0 ? 'text-success' : 'text-danger'; ?>
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-safe2-fill text-success me-1"></i>Saldo Actual de Caja</div>
            <div class="h5 <?= $scClass ?> fw-bold mb-0"><?= $fmtK($sc) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-cash-stack text-warning me-1"></i>Capital Prestado (activo)</div>
            <div class="h5 text-warning fw-bold mb-0"><?= $fmtK($hi['capital_activo']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-graph-up text-info me-1"></i>Total Cobrado Histórico</div>
            <div class="h5 text-info fw-bold mb-0"><?= $fmtK($hi['cobrado_total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-0 p-3 text-center">
            <div class="small text-secondary mb-1"><i class="bi bi-hourglass-split text-danger me-1"></i>Pendientes de Cobro</div>
            <div class="h5 text-danger fw-bold mb-0"><?= $fmtK($hi['pendientes_cobro']) ?></div>
        </div>
    </div>
</div>

<!-- ③ Historial de Movimientos -->
<p class="text-secondary small text-uppercase fw-semibold mb-2" style="letter-spacing:.07em;">
    <i class="bi bi-clock-history me-1"></i>Historial de Movimientos
    <span class="text-muted ms-2"><?= date('d/m/Y', strtotime($d)) ?> — <?= date('d/m/Y', strtotime($h)) ?></span>
</p>
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
            <thead>
                <tr class="text-secondary small text-uppercase">
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Detalle</th>
                    <th class="text-end">Monto</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $mv):
                $tb = $tipoBadge[$mv['tipo']] ?? ['class'=>'bg-secondary','icon'=>'bi-circle','label'=>$mv['tipo']];
                $esEntrada = in_array($mv['tipo'], ['cobranza','ingreso']);
            ?>
                <tr>
                    <td class="text-light text-nowrap"><?= date('d/m/Y', strtotime($mv['fecha'])) ?></td>
                    <td>
                        <span class="badge <?= $tb['class'] ?>">
                            <i class="bi <?= $tb['icon'] ?> me-1"></i><?= $tb['label'] ?>
                        </span>
                    </td>
                    <td class="text-light"><?= htmlspecialchars($mv['detalle']) ?></td>
                    <td class="text-end fw-bold <?= $esEntrada ? 'text-success' : 'text-danger' ?>">
                        <?= ($esEntrada ? '+' : '−') . $fmtK($mv['monto']) ?>
                    </td>
                    <td class="text-secondary small"><?= htmlspecialchars($mv['usuario']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($movimientos)): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4">
                    <i class="bi bi-inbox me-2"></i>Sin movimientos en el período seleccionado.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($movimientos) >= 200): ?>
    <div class="card-footer bg-transparent border-secondary text-secondary small text-center">
        Mostrando los últimos 200 movimientos del período. Ajustá el rango de fechas para ver más.
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
