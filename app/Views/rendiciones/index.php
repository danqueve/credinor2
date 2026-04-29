<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold">
        <i class="bi bi-journal-check me-2 text-warning"></i>Rendiciones
    </h2>
    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
        <a href="<?= $appUrl ?>/rendiciones/nueva" class="btn btn-warning text-dark">
            <i class="bi bi-plus-lg me-1"></i> Nueva Rendición
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card bg-slate-800 border-secondary">
    <div class="card-header bg-transparent border-secondary py-3">
        <span class="text-secondary small"><?= number_format($total) ?> rendición(es)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0 small">
            <thead class="border-secondary">
                <tr class="text-secondary text-uppercase" style="font-size:0.72rem;">
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cobrador</th>
                    <th class="text-end">Efectivo decl.</th>
                    <th class="text-end">Transf. decl.</th>
                    <th class="text-end">Total decl.</th>
                    <th class="text-end">Total regist.</th>
                    <th class="text-end">Diferencia</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rendiciones)): ?>
                <tr>
                    <td colspan="10" class="text-center text-secondary py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Sin rendiciones registradas.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rendiciones as $r): ?>
                <tr>
                    <td class="text-secondary font-monospace"><?= $r->id_rendicion ?></td>
                    <td class="text-light"><?= date('d/m/Y', strtotime($r->fecha_rendicion)) ?></td>
                    <td class="text-light"><?= htmlspecialchars($r->cobrador_nombre ?? '—') ?></td>
                    <td class="text-end text-light">$<?= number_format($r->total_efectivo_declarado, 2, ',', '.') ?></td>
                    <td class="text-end text-light">$<?= number_format($r->total_transferencias_declarado, 2, ',', '.') ?></td>
                    <td class="text-end fw-bold text-light">$<?= number_format($r->total_declarado, 2, ',', '.') ?></td>
                    <td class="text-end text-success">$<?= number_format($r->total_registrado, 2, ',', '.') ?></td>
                    <td class="text-end fw-bold <?= abs($r->diferencia) < 0.005 ? 'text-success' : 'text-danger' ?>">
                        <?= abs($r->diferencia) < 0.005 ? '—' : '$' . number_format($r->diferencia, 2, ',', '.') ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $r->estadoBadge() ?>"><?= $r->estadoLabel() ?></span>
                    </td>
                    <td>
                        <a href="<?= $appUrl ?>/rendiciones/ficha?id=<?= $r->id_rendicion ?>"
                           class="btn btn-sm btn-outline-secondary">
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
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link bg-slate-700 border-secondary text-light"
                           href="<?= $appUrl ?>/rendiciones?page=<?= $p ?>">
                            <?= $p ?>
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
