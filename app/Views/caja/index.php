<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
$fmt = fn($n) => '$' . number_format((float)$n, 2, ',', '.');
$canManage = \App\Helpers\Auth::canManage();
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="h3 mb-0 text-white fw-bold">
        <i class="bi bi-safe2-fill me-2 text-success"></i>Caja — Movimientos
    </h2>
</div>

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Formulario nuevo movimiento ── -->
    <?php if ($canManage): ?>
    <div class="col-12 col-lg-4">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-header bg-transparent border-secondary py-3">
                <h6 class="mb-0 fw-bold text-light">
                    <i class="bi bi-plus-circle text-success me-2"></i>Registrar Movimiento
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $appUrl ?>/caja/store">
                    <?= \App\Helpers\Csrf::getFormField() ?>

                    <!-- Tipo -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Tipo</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="ingreso" checked>
                                <label class="form-check-label text-success fw-semibold" for="tipoIngreso">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Ingreso
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoEgreso" value="egreso">
                                <label class="form-check-label text-danger fw-semibold" for="tipoEgreso">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Egreso
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Monto -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small" for="monto">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                            <input type="number" name="monto" id="monto" step="0.01" min="0.01"
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- Concepto -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small" for="concepto">Concepto</label>
                        <input type="text" name="concepto" id="concepto" maxlength="255"
                               class="form-control bg-slate-700 border-secondary text-light"
                               placeholder="Ej: Aporte socio, Gasto oficina..." required>
                    </div>

                    <!-- Fecha -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small" for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha"
                               class="form-control bg-slate-700 border-secondary text-light"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-4">
                        <label class="form-label text-secondary small" for="observaciones">Observaciones <span class="text-muted">(opcional)</span></label>
                        <textarea name="observaciones" id="observaciones" rows="2"
                                  class="form-control bg-slate-700 border-secondary text-light"
                                  placeholder="Detalle adicional..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save me-2"></i>Guardar Movimiento
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Historial de movimientos manuales ── -->
    <div class="col-12 <?= $canManage ? 'col-lg-8' : '' ?>">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h6 class="mb-0 fw-bold text-light">
                    <i class="bi bi-clock-history text-info me-2"></i>Últimos movimientos manuales
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-secondary small text-uppercase">
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                            <th>Usuario</th>
                            <?php if ($canManage): ?>
                                <th></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td class="text-light"><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                            <td>
                                <?php if ($m['tipo'] === 'ingreso'): ?>
                                    <span class="badge bg-success"><i class="bi bi-arrow-down me-1"></i>Ingreso</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-arrow-up me-1"></i>Egreso</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-light">
                                <?= htmlspecialchars($m['concepto']) ?>
                                <?php if ($m['observaciones']): ?>
                                    <br><small class="text-secondary"><?= htmlspecialchars($m['observaciones']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold <?= $m['tipo'] === 'ingreso' ? 'text-success' : 'text-danger' ?>">
                                <?= $fmt($m['monto']) ?>
                            </td>
                            <td class="text-secondary small"><?= htmlspecialchars($m['usuario_nombre'] ?? '—') ?></td>
                            <?php if ($canManage): ?>
                            <td class="text-end">
                                <form method="POST" action="<?= $appUrl ?>/caja/delete"
                                      onsubmit="return confirm('¿Eliminar este movimiento?')">
                                    <?= \App\Helpers\Csrf::getFormField() ?>
                                    <input type="hidden" name="id_movimiento" value="<?= $m['id_movimiento'] ?>">
                                    <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.75rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center text-secondary py-4">
                            <i class="bi bi-inbox me-2"></i>Sin movimientos manuales registrados.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
