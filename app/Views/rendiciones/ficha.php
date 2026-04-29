<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="<?= $appUrl ?>/rendiciones" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Rendiciones
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">
            Rendición #<?= $rendicion->id_rendicion ?>
            <span class="badge bg-<?= $rendicion->estadoBadge() ?> ms-2 fs-6">
                <?= $rendicion->estadoLabel() ?>
            </span>
        </h2>
        <div class="text-secondary mt-1">
            <?= htmlspecialchars($rendicion->cobrador_nombre ?? '—') ?>
            — <?= date('d/m/Y', strtotime($rendicion->fecha_rendicion)) ?>
        </div>
    </div>
</div>

<!-- KPIs de conciliación -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-secondary text-center p-3">
            <div class="text-secondary small">Efectivo declarado</div>
            <div class="h5 text-light fw-bold mt-1">
                $<?= number_format($rendicion->total_efectivo_declarado, 2, ',', '.') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-secondary text-center p-3">
            <div class="text-secondary small">Transf. declaradas</div>
            <div class="h5 text-light fw-bold mt-1">
                $<?= number_format($rendicion->total_transferencias_declarado, 2, ',', '.') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-secondary text-center p-3">
            <div class="text-secondary small">Total declarado</div>
            <div class="h5 text-warning fw-bold mt-1">
                $<?= number_format($rendicion->total_declarado, 2, ',', '.') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-slate-800 border-secondary text-center p-3">
            <div class="text-secondary small">Total registrado</div>
            <div class="h5 text-success fw-bold mt-1">
                $<?= number_format($rendicion->total_registrado, 2, ',', '.') ?>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de diferencia -->
<?php if (abs($rendicion->diferencia) >= 0.005): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <strong>Diferencia detectada:</strong>
            $<?= number_format(abs($rendicion->diferencia), 2, ',', '.') ?>
            — El total declarado no coincide con los pagos registrados.
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success d-flex align-items-center mb-4">
        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
        <div><strong>Rendición conciliada</strong> — Totales coinciden perfectamente.</div>
    </div>
<?php endif; ?>

<!-- Detalle de pagos -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-light">
            <i class="bi bi-list-check me-2 text-info"></i>
            Pagos incluidos (<?= count($pagos) ?>)
        </h5>
        <span class="text-secondary small">
            Efectivo: $<?= number_format(
                array_sum(array_map(fn($p) => !$p->anulado && $p->forma_pago === 'efectivo' ? $p->monto_pagado : 0, $pagos)),
                2, ',', '.'
            ) ?>
            &nbsp;|&nbsp;
            Transferencias: $<?= number_format(
                array_sum(array_map(fn($p) => !$p->anulado && in_array($p->forma_pago, ['transferencia','mp']) ? $p->monto_pagado : 0, $pagos)),
                2, ',', '.'
            ) ?>
        </span>
    </div>
    <?php if (empty($pagos)): ?>
        <div class="card-body text-center py-4 text-secondary">Sin pagos.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0 small">
                <thead class="border-secondary">
                    <tr class="text-secondary text-uppercase" style="font-size:0.7rem;">
                        <th>Fecha pago</th>
                        <th>Cliente</th>
                        <th>Crédito</th>
                        <th class="text-end">Monto</th>
                        <th>Forma</th>
                        <th>Cuotas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pagos as $p): ?>
                    <tr class="<?= $p->anulado ? 'opacity-50' : '' ?>">
                        <td class="text-light"><?= date('d/m/Y', strtotime($p->fecha_pago_real)) ?></td>
                        <td>
                            <div class="text-light"><?= htmlspecialchars($p->cliente_nombre ?? '') ?></div>
                            <div class="text-secondary" style="font-size:0.68rem;">DNI <?= htmlspecialchars($p->cliente_dni ?? '') ?></div>
                        </td>
                        <td>
                            <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $p->id_credito ?>"
                               class="text-info text-decoration-none font-monospace">
                                <?= htmlspecialchars($p->credito_codigo ?? '') ?>
                            </a>
                        </td>
                        <td class="text-end fw-bold <?= $p->anulado ? 'text-secondary text-decoration-line-through' : 'text-success' ?>">
                            $<?= number_format($p->monto_pagado, 2, ',', '.') ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $p->formaPagoBadge() ?>">
                                <?= $p->formasPagoLabel() ?>
                            </span>
                        </td>
                        <td class="text-secondary">
                            <?= !empty($p->cuotasAplicadas)
                                ? implode(', ', array_map(fn($c) => '#' . $c['numero_cuota'], $p->cuotasAplicadas))
                                : '—' ?>
                        </td>
                        <td>
                            <?php if ($p->anulado): ?>
                                <span class="badge bg-danger">Anulado</span>
                            <?php else: ?>
                                <span class="badge bg-success">Vigente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="border-secondary">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end text-secondary">Total registrado:</td>
                        <td class="text-end text-success">
                            $<?= number_format(
                                array_sum(array_map(fn($p) => $p->anulado ? 0 : $p->monto_pagado, $pagos)),
                                2, ',', '.'
                            ) ?>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($rendicion->observaciones): ?>
    <div class="card bg-slate-800 border-secondary mt-4">
        <div class="card-body">
            <h6 class="text-secondary small mb-2">Observaciones</h6>
            <p class="text-light mb-0"><?= nl2br(htmlspecialchars($rendicion->observaciones)) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
