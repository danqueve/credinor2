<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold"><i class="bi bi-cash-stack me-2 text-success"></i>Créditos</h2>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="<?= $appUrl ?>/reportes/exportar/creditos?format=pdf&q=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>"
           class="btn btn-outline-danger" target="_blank" rel="noopener">
            <i class="bi bi-file-pdf me-1"></i> Exportar PDF
        </a>
    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
        <a href="<?= $appUrl ?>/creditos/nuevo" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Crédito
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
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Filtros -->
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= $appUrl ?>/creditos" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       class="form-control form-control-sm bg-slate-700 border-secondary text-light"
                       placeholder="Buscar por cliente, DNI o código...">
            </div>
            <div class="col-6 col-md-3">
                <select name="estado" class="form-select form-select-sm bg-slate-700 border-secondary text-light">
                    <option value="">Todos los estados</option>
                    <?php foreach (['activo','finalizado','anulado','refinanciado','incobrable'] as $e): ?>
                        <option value="<?= $e ?>" <?= $estado === $e ? 'selected' : '' ?>>
                            <?= ucfirst($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
            <?php if ($search !== '' || $estado !== ''): ?>
                <div class="col-12 col-md-2">
                    <a href="<?= $appUrl ?>/creditos" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-x-lg me-1"></i> Limpiar
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-secondary small">
            <?= number_format($total) ?> crédito<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="border-secondary">
                <tr class="text-secondary small text-uppercase">
                    <th>Código</th>
                    <th>Cliente</th>
                    <th class="text-end">Capital</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Saldo</th>
                    <th>Frecuencia</th>
                    <th>Cobrador</th>
                    <th>Estado</th>
                    <th>Inicio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($creditos)): ?>
                <tr>
                    <td colspan="10" class="text-center text-secondary py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No se encontraron créditos.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($creditos as $c): ?>
                <tr>
                    <td>
                        <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $c->id_credito ?>"
                           class="text-info text-decoration-none fw-bold font-monospace">
                            <?= htmlspecialchars($c->codigo) ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-semibold text-light"><?= htmlspecialchars($c->cliente_nombre ?? '') ?></div>
                        <div class="text-secondary small">DNI: <?= htmlspecialchars($c->cliente_dni ?? '') ?></div>
                    </td>
                    <td class="text-end text-light">$<?= number_format($c->capital, 2, ',', '.') ?></td>
                    <td class="text-end text-light">$<?= number_format($c->monto_total, 2, ',', '.') ?></td>
                    <td class="text-end fw-bold <?= $c->saldo_pendiente > 0 ? 'text-warning' : 'text-success' ?>">
                        $<?= number_format($c->saldo_pendiente, 2, ',', '.') ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            <?= ucfirst($c->frecuencia) ?>
                        </span>
                    </td>
                    <td class="text-light"><?= htmlspecialchars($c->cobrador_nombre ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $c->estadoBadge() ?>">
                            <?= $c->estadoLabel() ?>
                        </span>
                    </td>
                    <td class="text-secondary small"><?= date('d/m/Y', strtotime($c->fecha_inicio)) ?></td>
                    <td>
                        <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $c->id_credito ?>"
                           class="btn btn-sm btn-outline-secondary" title="Ver ficha">
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
                           href="<?= $appUrl ?>/creditos?page=<?= $p ?>&q=<?= urlencode($search) ?>&estado=<?= urlencode($estado) ?>">
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
