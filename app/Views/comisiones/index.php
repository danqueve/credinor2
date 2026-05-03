<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
$canManage = \App\Helpers\Auth::canManage();
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

    <!-- ── Filtro de rango + botón liquidar ── -->
    <div class="col-12 col-md-5">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h6 class="mb-0 fw-bold text-light"><i class="bi bi-calendar-range me-2 text-info"></i>Rango de fechas</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= $appUrl ?>/comisiones" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-6">
                            <label class="form-label text-secondary small mb-1">Desde</label>
                            <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"
                                   class="form-control bg-dark text-light border-secondary">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small mb-1">Hasta</label>
                            <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"
                                   class="form-control bg-dark text-light border-secondary">
                        </div>
                        <div class="col-12 mt-1">
                            <button type="submit" class="btn btn-outline-info w-100">
                                <i class="bi bi-search me-1"></i>Ver comisiones
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($canManage && $esPreview): ?>
                    <!-- Preview: aún no liquidado, ofrecer liquidar -->
                    <form method="POST" action="<?= $appUrl ?>/comisiones/liquidar"
                          onsubmit="return confirm('¿Generar/recalcular liquidación para <?= htmlspecialchars($desde) ?> → <?= htmlspecialchars($hasta) ?>?')">
                        <?= \App\Helpers\Csrf::getFormField() ?>
                        <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
                        <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-calculator me-1"></i>Liquidar este período
                        </button>
                    </form>
                    <div class="mt-2 text-secondary small text-center">
                        <i class="bi bi-eye me-1"></i>Vista previa — aún no guardada
                    </div>
                <?php elseif (!$esPreview): ?>
                    <!-- Ya liquidado -->
                    <div class="alert alert-success py-2 mb-2 text-center small">
                        <i class="bi bi-check-circle me-1"></i>Liquidación guardada
                    </div>
                    <?php if ($canManage): ?>
                    <form method="POST" action="<?= $appUrl ?>/comisiones/liquidar"
                          onsubmit="return confirm('¿Recalcular la liquidación para <?= htmlspecialchars($desde) ?> → <?= htmlspecialchars($hasta) ?>? Las comisiones no pagadas se borrarán.')">
                        <?= \App\Helpers\Csrf::getFormField() ?>
                        <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
                        <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                        <button type="submit" class="btn btn-outline-warning w-100 btn-sm">
                            <i class="bi bi-arrow-repeat me-1"></i>Recalcular
                        </button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($periodos)): ?>
        <div class="card bg-slate-800 border-secondary mt-3">
            <div class="card-header bg-transparent border-secondary py-2">
                <h6 class="mb-0 fw-bold text-light small">Períodos liquidados</h6>
            </div>
            <div class="list-group list-group-flush" style="max-height:260px;overflow-y:auto;">
                <?php foreach ($periodos as $p):
                    // Mostrar rango amigable
                    if (str_contains($p, '_')) {
                        [$pd, $ph] = explode('_', $p, 2);
                        $label = date('d/m/y', strtotime($pd)) . ' → ' . date('d/m/y', strtotime($ph));
                    } else {
                        $label = $p;
                    }
                    $activeClass = ($p === $periodo) ? 'active' : '';
                    // Armar href según tipo de período
                    if (str_contains($p, '_')) {
                        [$pd, $ph] = explode('_', $p, 2);
                        $href = "$appUrl/comisiones?desde=$pd&hasta=$ph";
                    } else {
                        $href = "$appUrl/comisiones?periodo=$p";
                    }
                ?>
                    <a href="<?= $href ?>"
                       class="list-group-item list-group-item-action bg-transparent border-secondary text-light py-2 small <?= $activeClass ?>">
                        <i class="bi bi-calendar-check me-2 text-secondary"></i><?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Tabla de liquidación / preview ── -->
    <div class="col-12 col-md-7">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-light">
                    <?php if ($esPreview): ?>
                        <span class="badge bg-secondary me-1">Preview</span>
                    <?php else: ?>
                        <span class="badge bg-success me-1">Liquidado</span>
                    <?php endif; ?>
                    <?= date('d/m/Y', strtotime($desde)) ?> → <?= date('d/m/Y', strtotime($hasta)) ?>
                </h6>
                <?php if (!empty($liquidacion)): ?>
                    <span class="badge bg-warning text-dark fs-6">
                        Total: $<?= number_format($totales['total'], 2, ',', '.') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($liquidacion)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Sin actividad en este rango de fechas.<br>
                        <small>No hay pagos ni créditos registrados entre <?= date('d/m/Y', strtotime($desde)) ?> y <?= date('d/m/Y', strtotime($hasta)) ?>.</small>
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
                                            <?php if ($esPreview): ?>
                                                <span class="badge bg-secondary">Preview</span>
                                            <?php elseif ($row['pagada']): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Pagada</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($canManage && !$esPreview && !$row['pagada'] && $row['id_comision']): ?>
                                                <form method="POST" action="<?= $appUrl ?>/comisiones/pagar"
                                                      onsubmit="return confirm('¿Marcar como pagada?')" class="d-inline">
                                                    <?= \App\Helpers\Csrf::getFormField() ?>
                                                    <input type="hidden" name="id_comision" value="<?= $row['id_comision'] ?>">
                                                    <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
                                                    <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
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
