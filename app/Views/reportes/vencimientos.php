<?php
$appUrl = $_ENV['APP_URL'] ?? '';
$hoy    = new \DateTime('today');
ob_start();
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-white"><?= htmlspecialchars($titulo) ?></h2>
        <p class="text-secondary small mb-0 mt-1">
            <i class="bi bi-calendar-event me-1"></i>
            <?= count($vencimientos) ?> clientes con cuotas en los próximos <?= $dias ?> días
        </p>
    </div>
    <a href="<?= $appUrl ?>/reportes" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Reportes
    </a>
</div>

<!-- Filtro de días -->
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="card-body py-3">
        <form action="<?= $appUrl ?>/reportes/vencimientos" method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="text-secondary small mb-0">Mostrar vencimientos en los próximos:</label>
            <div class="d-flex gap-2">
                <?php foreach ([7, 15, 30, 60, 90] as $d): ?>
                    <a href="?dias=<?= $d ?>"
                       class="btn btn-sm <?= $dias === $d ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= $d ?> días
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Crédito</th>
                        <th class="text-center">Cuota #</th>
                        <th>Vencimiento</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Saldo crédito</th>
                        <th>Zona</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($vencimientos)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class="bi bi-calendar-check d-block fs-2 mb-2"></i>
                            No hay vencimientos en los próximos <?= $dias ?> días.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vencimientos as $v):
                        $vencDt  = new \DateTime($v['fecha_vencimiento']);
                        $diff    = $hoy->diff($vencDt);
                        $isPast  = ($diff->invert === 1);
                        $diasNum = (int)$diff->days;

                        if ($isPast) {
                            $badgeClass = 'badge-vencido';
                            $badgeTxt   = 'Venció hace ' . $diasNum . 'd';
                        } elseif ($diasNum === 0) {
                            $badgeClass = 'badge-vencido';
                            $badgeTxt   = 'Vence hoy';
                        } elseif ($diasNum <= 7) {
                            $badgeClass = 'badge-refinanciado';
                            $badgeTxt   = 'En ' . $diasNum . ' días';
                        } else {
                            $badgeClass = 'badge-activo';
                            $badgeTxt   = 'En ' . $diasNum . ' días';
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-light"><?= htmlspecialchars($v['cliente_nombre']) ?></div>
                            <div class="small font-monospace text-info">DNI <?= htmlspecialchars($v['dni']) ?></div>
                            <?php if ($v['telefono']): ?>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $v['telefono']) ?>"
                                   target="_blank" class="small text-success text-decoration-none">
                                    <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($v['telefono']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace text-info small"><?= htmlspecialchars($v['credito_codigo']) ?></td>
                        <td class="text-center text-secondary small"><?= $v['numero_cuota'] ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= $badgeTxt ?></span>
                            <div class="small text-secondary mt-1"><?= date('d/m/Y', strtotime($v['fecha_vencimiento'])) ?></div>
                        </td>
                        <td class="text-end fw-semibold text-light">
                            $<?= number_format((float)$v['monto_esperado'], 0, ',', '.') ?>
                            <?php if ((float)$v['monto_pagado'] > 0): ?>
                                <div class="small text-success">Pagado: $<?= number_format((float)$v['monto_pagado'], 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-warning fw-bold">
                            $<?= number_format((float)$v['saldo_pendiente'], 0, ',', '.') ?>
                        </td>
                        <td class="small text-secondary"><?= htmlspecialchars($v['zona_nombre'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="<?= $appUrl ?>/clientes/ficha?id=<?= $v['id_cliente'] ?>"
                               class="btn btn-sm btn-outline-secondary"
                               data-bs-toggle="tooltip" title="Ver ficha">
                                <i class="bi bi-person-vcard"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
