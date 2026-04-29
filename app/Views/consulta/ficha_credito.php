<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();

$cuotasPagadas  = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'pagada'));
$cuotasVencidas = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'vencida'));
$proxCuota      = null;
foreach ($credito->cuotas as $q) {
    if (in_array($q->estado, ['pendiente', 'parcial', 'vencida'])) {
        $proxCuota = $q;
        break;
    }
}
$porcentaje = $credito->monto_total > 0
    ? round((($credito->monto_total - $credito->saldo_pendiente) / $credito->monto_total) * 100, 1)
    : 0;

// Armar mensaje WhatsApp
$msgWa = '';
if ($cliente && $proxCuota) {
    $msgWa = urlencode(
        'Hola ' . $cliente->nombre . ', te recordamos que tu cuota #' . $proxCuota->numero_cuota .
        ' de $' . number_format($proxCuota->monto_esperado, 0, ',', '.') .
        ' (crédito ' . $credito->codigo . ') vence el ' .
        date('d/m/Y', strtotime($proxCuota->fecha_vencimiento)) . '. — Credinor'
    );
}
?>

<!-- Header crédito -->
<div class="card bg-slate-800 border-0 p-3 mb-3">
    <?php if ($cliente): ?>
    <a href="<?= $appUrl ?>/consulta/cliente?id=<?= $cliente->id_cliente ?>"
       class="text-secondary small text-decoration-none mb-1 d-block">
        <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($cliente->nombre) ?>
    </a>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="font-monospace text-light fw-bold"><?= htmlspecialchars($credito->codigo) ?></span>
            <span class="badge bg-<?= $credito->estadoBadge() ?> ms-2"><?= $credito->estadoLabel() ?></span>
        </div>
        <div class="fw-bold text-warning fs-5">$<?= number_format($credito->saldo_pendiente, 0, ',', '.') ?></div>
    </div>

    <!-- Progreso -->
    <div class="mt-2">
        <div class="d-flex justify-content-between small text-secondary mb-1">
            <span><?= $cuotasPagadas ?>/<?= $credito->cantidad_cuotas ?> cuotas pagadas</span>
            <span><?= $porcentaje ?>%</span>
        </div>
        <div class="progress" style="height: 8px; border-radius: 6px;">
            <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%; border-radius: 6px;"></div>
        </div>
    </div>
</div>

<!-- Próxima cuota + WhatsApp -->
<?php if ($proxCuota): ?>
<div class="card border-0 p-3 mb-3 <?= $proxCuota->estado === 'vencida' ? 'bg-danger bg-opacity-25' : 'bg-info bg-opacity-10' ?>">
    <div class="small text-secondary mb-1">Próxima cuota</div>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold text-light">Cuota #<?= $proxCuota->numero_cuota ?></span>
            <div class="small text-secondary"><?= date('d/m/Y', strtotime($proxCuota->fecha_vencimiento)) ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-5 <?= $proxCuota->estado === 'vencida' ? 'text-danger' : 'text-info' ?>">
                $<?= number_format($proxCuota->monto_esperado, 0, ',', '.') ?>
            </div>
            <span class="badge bg-<?= $proxCuota->estadoBadge() ?>"><?= $proxCuota->estadoLabel() ?></span>
        </div>
    </div>

    <!-- Botones de contacto -->
    <?php if ($cliente && $cliente->telefono): ?>
    <div class="d-flex gap-2 mt-3">
        <a href="tel:<?= htmlspecialchars($cliente->telefono) ?>" class="btn btn-outline-success flex-fill btn-sm">
            <i class="bi bi-telephone me-1"></i> Llamar
        </a>
        <a href="https://wa.me/549<?= preg_replace('/\D/', '', $cliente->telefono) ?>?text=<?= $msgWa ?>"
           class="btn btn-success flex-fill btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-1"></i> Recordatorio
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Resumen -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Pagadas</div>
            <div class="fw-bold text-success"><?= $cuotasPagadas ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Vencidas</div>
            <div class="fw-bold <?= $cuotasVencidas > 0 ? 'text-danger' : 'text-secondary' ?>"><?= $cuotasVencidas ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Frecuencia</div>
            <div class="fw-bold text-info small"><?= ucfirst($credito->frecuencia) ?></div>
        </div>
    </div>
</div>

<!-- Calendario de cuotas (versión compacta) -->
<h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: .08em;">
    Cuotas
</h6>

<div class="d-flex flex-column gap-1">
<?php foreach ($credito->cuotas as $q): ?>
    <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3
         <?= $q->estado === 'pagada' ? 'bg-success bg-opacity-10' :
            ($q->estado === 'vencida' ? 'bg-danger bg-opacity-10' : 'bg-slate-800') ?>">
        <span class="text-secondary small" style="width: 24px; text-align:right;"><?= $q->numero_cuota ?></span>
        <span class="text-secondary small" style="width: 72px;"><?= date('d/m/Y', strtotime($q->fecha_vencimiento)) ?></span>
        <span class="flex-grow-1 text-light small">$<?= number_format($q->monto_esperado, 0, ',', '.') ?></span>
        <span class="badge bg-<?= $q->estadoBadge() ?>" style="font-size: 0.6rem;"><?= $q->estadoLabel() ?></span>
    </div>
<?php endforeach; ?>
</div>

<!-- Últimos pagos -->
<?php if (!empty($pagos)): ?>
<h6 class="text-secondary text-uppercase mt-3 mb-2" style="font-size: 0.7rem; letter-spacing: .08em;">
    Últimos Pagos
</h6>
<div class="d-flex flex-column gap-1">
<?php foreach (array_slice($pagos, 0, 5) as $p): ?>
    <?php if (!$p->anulado): ?>
    <div class="d-flex justify-content-between align-items-center bg-slate-800 rounded-3 px-3 py-2">
        <div>
            <div class="text-light small"><?= date('d/m/Y', strtotime($p->fecha_pago_real)) ?></div>
            <div class="text-secondary" style="font-size: 0.68rem;"><?= $p->formasPagoLabel() ?></div>
        </div>
        <div class="fw-bold text-success small">$<?= number_format($p->monto_pagado, 0, ',', '.') ?></div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
