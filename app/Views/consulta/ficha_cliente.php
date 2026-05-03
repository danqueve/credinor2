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

    <!-- Contacto + reporte -->
    <?php
    $adminWa   = $_ENV['ADMIN_WHATSAPP'] ?? '';
    $resumen   = 'Cliente: ' . $cliente->nombre . ' | DNI: ' . $cliente->dni;
    if ($cliente->telefono) $resumen .= ' | Tel: ' . $cliente->telefono;
    if (count($creditosActivos) > 0) $resumen .= ' | Saldo: $' . number_format($saldoTotal, 0, ',', '.');
    if ($cliente->direccion) $resumen .= ' | Dir: ' . $cliente->direccion;
    ?>
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <?php if ($cliente->telefono): ?>
        <a href="tel:<?= htmlspecialchars($cliente->telefono) ?>" class="btn btn-outline-success flex-fill">
            <i class="bi bi-telephone me-1"></i> Llamar
        </a>
        <a href="https://wa.me/549<?= preg_replace('/\D/', '', $cliente->telefono) ?>?text=<?= urlencode('Hola ' . $cliente->nombre . ', te contactamos de Credinor.') ?>"
           class="btn btn-success flex-fill" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
        </a>
        <?php endif; ?>
        <?php if ($adminWa): ?>
        <a href="https://wa.me/<?= htmlspecialchars($adminWa) ?>?text=<?= urlencode('📋 Reporte cliente — ' . $resumen) ?>"
           class="btn btn-outline-info w-100" target="_blank" rel="noopener">
            <i class="bi bi-send me-1"></i> Reportar al supervisor
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Saldo total -->
<?php if (count($creditosActivos) > 0): ?>
<div class="card bg-slate-800 border-warning border-opacity-50 p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Saldo total activo</span>
        <span class="fw-bold text-warning fs-5">$<?= number_format($saldoTotal, 0, ',', '.') ?></span>
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

<?php if (!empty($pagosRecientes)): ?>
<div class="accordion mt-3" id="accordionPagos">
    <div class="accordion-item bg-slate-800 border-secondary">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed bg-slate-800 text-light" type="button"
                    data-bs-toggle="collapse" data-bs-target="#colPagos">
                <i class="bi bi-clock-history me-2 text-info"></i>
                Últimos pagos
                <span class="badge bg-secondary ms-2"><?= count($pagosRecientes) ?></span>
            </button>
        </h2>
        <div id="colPagos" class="accordion-collapse collapse" data-bs-parent="#accordionPagos">
            <div class="accordion-body p-0">
                <div class="d-flex flex-column">
                <?php foreach ($pagosRecientes as $p): ?>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top border-secondary">
                        <div>
                            <div class="text-light small fw-semibold">
                                <?= date('d/m/Y', strtotime($p['fecha_pago_real'])) ?>
                            </div>
                            <div class="text-secondary" style="font-size:0.7rem;">
                                <?= htmlspecialchars($p['credito_codigo']) ?>
                            </div>
                        </div>
                        <div class="fw-bold text-success small">
                            $<?= number_format((float)$p['monto_pagado'], 0, ',', '.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
