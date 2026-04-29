<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold"><i class="bi bi-percent me-2 text-warning"></i>Comisiones</h2>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Selector de período + botón liquidar -->
    <div class="col-12 col-md-5">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h6 class="mb-0 fw-bold text-light">Seleccionar Período</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= $appUrl ?>/comisiones" class="d-flex gap-2 align-items-end mb-3">
                    <div class="flex-grow-1">
                        <label class="form-label text-secondary small mb-1">Período</label>
                        <input type="month" name="periodo" value="<?= htmlspecialchars($periodo) ?>"
                               class="form-control bg-dark text-light border-secondary">
                    </div>
                    <button type="submit" class="btn btn-outline-info">Ver</button>
                </form>

                <form method="POST" action="<?= $appUrl ?>/comisiones/liquidar"
                      onsubmit="return confirm('¿Generar/recalcular liquidación para <?= htmlspecialchars($periodo) ?>?')">
                    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-calculator me-1"></i> Liquidar <?= htmlspecialchars($periodo) ?>
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($periodos)): ?>
        <div class="card bg-slate-800 border-secondary mt-3">
            <div class="card-header bg-transparent border-secondary py-3">
                <h6 class="mb-0 fw-bold text-light">Períodos liquidados</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($periodos as $p): ?>
                    <a href="<?= $appUrl ?>/comisiones?periodo=<?= $p ?>"
                       class="list-group-item list-group-item-action bg-transparent border-secondary text-light <?= $p === $periodo ? 'active' : '' ?>">
                        <i class="bi bi-calendar3 me-2 text-secondary"></i><?= $p ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabla de liquidación -->
    <div class="col-12 col-md-7">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-light">Liquidación — <?= htmlspecialchars($periodo) ?></h6>
                <?php if (!empty($liquidacion)): ?>
                    <span class="badge bg-warning text-dark">
                        Total: $<?= number_format($totales['total'], 2, ',', '.') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($liquidacion)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Sin liquidación para este período.<br>
                        <small>Usá el botón "Liquidar" para calcularla.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 small">
                            <thead>
                                <tr class="text-secondary">
                                    <th>Personal</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Base</th>
                                    <th class="text-end">Pct</th>
                                    <th class="text-end">Comisión</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liquidacion as $row): ?>
                                    <tr>
                                        <td class="text-light"><?= htmlspecialchars($row['personal_nombre']) ?></td>
                                        <td>
                                            <?php if ($row['tipo'] === 'cobranza'): ?>
                                                <span class="badge bg-info-subtle text-info">Cobranza</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success">Venta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-secondary">$<?= number_format((float)$row['monto_base'], 2, ',', '.') ?></td>
                                        <td class="text-end text-secondary"><?= number_format((float)$row['pct'], 2) ?>%</td>
                                        <td class="text-end fw-bold text-warning">$<?= number_format((float)$row['monto_comision'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?php if ($row['pagada']): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Pagada</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!$row['pagada']): ?>
                                                <form method="POST" action="<?= $appUrl ?>/comisiones/pagar"
                                                      onsubmit="return confirm('¿Marcar como pagada?')" class="d-inline">
                                                    <input type="hidden" name="id_comision" value="<?= $row['id_comision'] ?>">
                                                    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
                                                    <button type="submit" class="btn btn-xs btn-outline-success py-0 px-2">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="text-secondary border-top border-secondary">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal Cobranza:</td>
                                    <td class="text-end fw-bold text-info">$<?= number_format($totales['cobranza'], 2, ',', '.') ?></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal Venta:</td>
                                    <td class="text-end fw-bold text-success">$<?= number_format($totales['venta'], 2, ',', '.') ?></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="border-top border-secondary">
                                    <td colspan="4" class="text-end fw-bold text-light">TOTAL:</td>
                                    <td class="text-end fw-bold text-warning fs-6">$<?= number_format($totales['total'], 2, ',', '.') ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
