<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Historial de Pagos</h2>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="<?= $appUrl ?>/reportes/exportar/cobros?format=pdf&q=<?= urlencode($search ?? '') ?>&desde=<?= urlencode($desde ?? '') ?>&hasta=<?= urlencode($hasta ?? '') ?>"
           class="btn btn-outline-danger" target="_blank" rel="noopener">
            <i class="bi bi-file-pdf me-1"></i> Exportar PDF
        </a>
    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
        <a href="<?= $appUrl ?>/pagos/nuevo" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i> Registrar Pago
        </a>
    <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- Filtros -->
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= $appUrl ?>/pagos" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       class="form-control form-control-sm bg-slate-700 border-secondary text-light"
                       placeholder="Buscar por cliente, DNI o código de crédito...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label text-secondary small mb-1">Desde</label>
                <input type="date" name="desde" value="<?= htmlspecialchars($desde ?? '') ?>"
                       class="form-control form-control-sm bg-slate-700 border-secondary text-light">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label text-secondary small mb-1">Hasta</label>
                <input type="date" name="hasta" value="<?= htmlspecialchars($hasta ?? '') ?>"
                       class="form-control form-control-sm bg-slate-700 border-secondary text-light">
            </div>
            <div class="col-4 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
            <?php if ($search !== '' || ($desde ?? '') !== '' || ($hasta ?? '') !== ''): ?>
                <div class="col-4 col-md-2">
                    <a href="<?= $appUrl ?>/pagos" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-x-lg me-1"></i> Limpiar
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-header bg-transparent border-secondary py-3">
        <span class="text-secondary small">
            <?= number_format($total) ?> pago<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0 small">
            <thead class="border-secondary">
                <tr class="text-secondary text-uppercase" style="font-size:0.72rem;">
                    <th>#</th>
                    <th>Fecha Pago</th>
                    <th>Cliente</th>
                    <th>Crédito</th>
                    <th class="text-end">Monto</th>
                    <th>Forma</th>
                    <th>Cobrador</th>
                    <th>Estado</th>
                    <th>Cuotas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pagos)): ?>
                <tr>
                    <td colspan="10" class="text-center text-secondary py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No se encontraron pagos.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                <tr class="<?= $p->anulado ? 'opacity-50' : '' ?>">
                    <td class="text-secondary font-monospace"><?= $p->id_pago ?></td>
                    <td class="text-light"><?= date('d/m/Y', strtotime($p->fecha_pago_real)) ?></td>
                    <td>
                        <div class="text-light"><?= htmlspecialchars($p->cliente_nombre ?? '') ?></div>
                        <div class="text-secondary" style="font-size:0.7rem;">DNI: <?= htmlspecialchars($p->cliente_dni ?? '') ?></div>
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
                    <td class="text-light"><?= htmlspecialchars($p->cobrador_nombre ?? '—') ?></td>
                    <td>
                        <?php if ($p->anulado): ?>
                            <span class="badge bg-danger">Anulado</span>
                        <?php else: ?>
                            <span class="badge bg-success">Vigente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-secondary">
                        <?php if (!empty($p->cuotasAplicadas)): ?>
                            <?= implode(', ', array_map(fn($c) => '#' . $c['numero_cuota'], $p->cuotasAplicadas)) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $p->id_credito ?>"
                           class="btn btn-sm btn-outline-secondary" title="Ver crédito">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer border-secondary bg-transparent py-3">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($p2 = 1; $p2 <= $totalPages; $p2++): ?>
                    <li class="page-item <?= $p2 === $page ? 'active' : '' ?>">
                        <a class="page-link bg-slate-700 border-secondary text-light"
                           href="<?= $appUrl ?>/pagos?page=<?= $p2 ?>&q=<?= urlencode($search) ?>&desde=<?= urlencode($desde ?? '') ?>&hasta=<?= urlencode($hasta ?? '') ?>">
                            <?= $p2 ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
