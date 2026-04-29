<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();

$creditosActivos    = array_filter($creditos, fn($c) => $c->estado === 'activo');
$saldoTotal         = array_sum(array_map(fn($c) => $c->saldo_pendiente, $creditosActivos));
?>

<!-- Header cliente -->
<div class="card bg-slate-800 border-0 p-3 mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-info d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:52px;height:52px;font-size:1.4rem;">
            <span class="text-dark fw-bold"><?= mb_strtoupper(mb_substr($cliente->nombre, 0, 1)) ?></span>
        </div>
        <div class="flex-grow-1">
            <div class="text-light fw-bold" style="font-size: 1.1rem;">
                <?= htmlspecialchars($cliente->nombre) ?>
            </div>
            <div class="text-secondary small">DNI <?= htmlspecialchars($cliente->dni) ?></div>
            <?php if ($cliente->zona_nombre): ?>
            <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($cliente->zona_nombre) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contacto rápido -->
    <?php if ($cliente->telefono): ?>
    <div class="d-flex gap-2 mt-3">
        <a href="tel:<?= htmlspecialchars($cliente->telefono) ?>" class="btn btn-outline-success flex-fill">
            <i class="bi bi-telephone me-1"></i> Llamar
        </a>
        <a href="https://wa.me/549<?= preg_replace('/\D/', '', $cliente->telefono) ?>?text=<?= urlencode('Hola ' . $cliente->nombre . ', te contactamos de Credinor.') ?>"
           class="btn btn-success flex-fill" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Saldo total -->
<?php if (count($creditosActivos) > 0): ?>
<div class="card bg-slate-800 border-warning border-opacity-50 p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Saldo total activo</span>
        <span class="fw-bold text-warning fs-5">$<?= number_format($saldoTotal, 2, ',', '.') ?></span>
    </div>
    <div class="text-secondary small mt-1"><?= count($creditosActivos) ?> crédito(s) activo(s)</div>
</div>
<?php endif; ?>

<!-- Créditos -->
<h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: .08em;">
    Todos los Créditos
</h6>

<?php if (empty($creditos)): ?>
    <div class="text-center py-4 text-secondary small">Sin créditos registrados.</div>
<?php else: ?>
    <div class="d-flex flex-column gap-2">
    <?php foreach ($creditos as $cr): ?>
        <a href="<?= $appUrl ?>/consulta/credito?id=<?= $cr->id_credito ?>"
           class="card bg-slate-800 border-0 text-decoration-none px-3 py-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="font-monospace text-light small"><?= htmlspecialchars($cr->codigo) ?></span>
                    <span class="badge bg-<?= $cr->estadoBadge() ?> ms-2"><?= $cr->estadoLabel() ?></span>
                    <div class="text-secondary small mt-1">
                        <?= $cr->cantidad_cuotas ?> cuotas · <?= ucfirst($cr->frecuencia) ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold <?= $cr->saldo_pendiente > 0 ? 'text-warning' : 'text-success' ?>">
                        $<?= number_format($cr->saldo_pendiente, 0, ',', '.') ?>
                    </div>
                    <div class="text-secondary small">saldo</div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
