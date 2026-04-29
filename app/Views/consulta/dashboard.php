<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();

$cuotasPendientes = count(array_filter($cuotasHoy, fn($c) => $c['estado'] !== 'pagada'));
$porcentaje = count($cuotasHoy) > 0
    ? round(($cuotasPagas / count($cuotasHoy)) * 100)
    : 0;
?>

<!-- Resumen del día -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card bg-slate-800 border-0 text-center p-3">
            <div class="text-secondary small">Cuotas hoy</div>
            <div class="h2 text-light fw-bold mb-0"><?= count($cuotasHoy) ?></div>
        </div>
    </div>
    <div class="col-6">
        <div class="card bg-slate-800 border-0 text-center p-3">
            <div class="text-secondary small">Cobradas</div>
            <div class="h2 text-success fw-bold mb-0"><?= $cuotasPagas ?></div>
        </div>
    </div>
    <div class="col-6">
        <div class="card bg-slate-800 border-0 text-center p-3">
            <div class="text-secondary small">Pendientes</div>
            <div class="h2 text-warning fw-bold mb-0"><?= $cuotasPendientes ?></div>
        </div>
    </div>
    <div class="col-6">
        <div class="card bg-slate-800 border-0 text-center p-3">
            <div class="text-secondary small">Total esperado</div>
            <div class="fw-bold text-info" style="font-size: 1.1rem;">$<?= number_format($totalEsperado, 0, ',', '.') ?></div>
        </div>
    </div>
</div>

<!-- Barra de progreso -->
<?php if (count($cuotasHoy) > 0): ?>
<div class="card bg-slate-800 border-0 p-3 mb-4">
    <div class="d-flex justify-content-between small text-secondary mb-2">
        <span>Progreso del día</span>
        <span><?= $porcentaje ?>%</span>
    </div>
    <div class="progress" style="height: 10px; border-radius: 8px;">
        <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%; border-radius: 8px;"></div>
    </div>
</div>
<?php endif; ?>

<!-- Lista de cuotas del día -->
<h6 class="text-secondary text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: .08em;">
    Hoja de ruta — <?= date('d/m/Y', strtotime($hoy)) ?>
</h6>

<?php if (empty($cuotasHoy)): ?>
    <div class="text-center py-5 text-secondary">
        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
        <div>Sin cuotas pendientes para hoy.</div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-2">
    <?php foreach ($cuotasHoy as $c): ?>
        <?php $pagada = $c['estado'] === 'pagada'; ?>
        <div class="card border-0 <?= $pagada ? 'opacity-50' : 'bg-slate-800' ?> list-item-touch px-3 py-2">
            <div class="d-flex align-items-center gap-3">
                <div class="<?= $pagada ? 'text-success' : 'text-warning' ?>" style="font-size: 1.5rem;">
                    <i class="bi bi-<?= $pagada ? 'check-circle-fill' : 'circle' ?>"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <a href="<?= $appUrl ?>/consulta/cliente?id=<?= $c['id_credito'] ?>"
                       class="text-light text-decoration-none fw-semibold d-block text-truncate">
                        <?= htmlspecialchars($c['cliente_nombre']) ?>
                    </a>
                    <div class="small text-secondary text-truncate">
                        DNI <?= htmlspecialchars($c['cliente_dni']) ?>
                        <?php if ($c['zona_nombre']): ?> · <?= htmlspecialchars($c['zona_nombre']) ?><?php endif; ?>
                    </div>
                    <?php if ($c['cliente_direccion']): ?>
                    <div class="small text-secondary text-truncate">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($c['cliente_direccion']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold text-light">$<?= number_format((float)$c['monto_esperado'], 0, ',', '.') ?></div>
                    <div class="small text-secondary">cuota #<?= $c['numero_cuota'] ?></div>
                    <?php if ($c['cliente_telefono']): ?>
                    <a href="tel:<?= htmlspecialchars($c['cliente_telefono']) ?>"
                       class="btn btn-sm btn-outline-success mt-1 py-0 px-2" style="font-size: 0.7rem;">
                        <i class="bi bi-telephone"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
