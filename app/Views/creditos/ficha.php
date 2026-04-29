<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();

// Calcular progreso de pago
$porcentajePagado = $credito->monto_total > 0
    ? round((($credito->monto_total - $credito->saldo_pendiente) / $credito->monto_total) * 100, 1)
    : 0;

$cuotasPagadas  = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'pagada'));
$cuotasVencidas = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'vencida'));
$cuotasPendientes = count(array_filter($credito->cuotas, fn($q) => in_array($q->estado, ['pendiente','parcial'])));
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="<?= $appUrl ?>/creditos" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Créditos
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">
            <span class="font-monospace"><?= htmlspecialchars($credito->codigo) ?></span>
            <span class="badge bg-<?= $credito->estadoBadge() ?> ms-2 fs-6"><?= $credito->estadoLabel() ?></span>
        </h2>
        <?php if ($cliente): ?>
            <a href="<?= $appUrl ?>/clientes/ficha?id=<?= $cliente->id_cliente ?>" class="text-info text-decoration-none">
                <i class="bi bi-person me-1"></i><?= htmlspecialchars($cliente->nombre) ?>
                <span class="text-secondary ms-1">(DNI: <?= htmlspecialchars($cliente->dni) ?>)</span>
            </a>
        <?php endif; ?>
    </div>
    <?php if ($_SESSION['usuario_rol'] === 'admin' && $credito->estado === 'activo'): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= $appUrl ?>/pagos/nuevo?id_credito=<?= $credito->id_credito ?>"
               class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Registrar Pago
            </a>
            <a href="<?= $appUrl ?>/creditos/refinanciar?id=<?= $credito->id_credito ?>"
               class="btn btn-warning">
                <i class="bi bi-arrow-repeat me-1"></i> Refinanciar
            </a>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalIncobrable">
                <i class="bi bi-slash-circle me-1"></i> Incobrable
            </button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalAnular">
                <i class="bi bi-x-circle me-1"></i> Anular
            </button>
        </div>
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

<div class="row g-4">

    <!-- Columna izquierda: datos del crédito -->
    <div class="col-12 col-xl-4">

        <!-- KPIs principales -->
        <div class="card bg-slate-800 border-secondary mb-3">
            <div class="card-body p-4">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="text-secondary small">Capital prestado</div>
                        <div class="h5 text-light fw-bold mt-1">$<?= number_format($credito->capital, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small">Total a devolver</div>
                        <div class="h5 text-light fw-bold mt-1">$<?= number_format($credito->monto_total, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small">Interés implícito</div>
                        <div class="h5 text-warning fw-bold mt-1">
                            <?= number_format($credito->interes_implicito_pct, 2) ?>%
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small">Saldo pendiente</div>
                        <div class="h5 fw-bold mt-1 <?= $credito->saldo_pendiente > 0 ? 'text-danger' : 'text-success' ?>">
                            $<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-secondary mb-1">
                        <span>Cobrado</span>
                        <span><?= $porcentajePagado ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= $porcentajePagado ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles del crédito -->
        <div class="card bg-slate-800 border-secondary mb-3">
            <div class="card-header bg-transparent border-secondary py-2">
                <h6 class="mb-0 text-light">Detalles del Crédito</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Cuotas</span>
                    <span><?= $credito->cantidad_cuotas ?> × $<?= number_format($credito->valor_cuota, 2, ',', '.') ?></span>
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Frecuencia</span>
                    <span class="badge bg-secondary"><?= ucfirst($credito->frecuencia) ?></span>
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Inicio</span>
                    <span><?= date('d/m/Y', strtotime($credito->fecha_inicio)) ?></span>
                </li>
                <?php if ($credito->fecha_fin_estimada): ?>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Fin estimado</span>
                    <span><?= date('d/m/Y', strtotime($credito->fecha_fin_estimada)) ?></span>
                </li>
                <?php endif; ?>
                <?php if ($credito->gastos_admin > 0): ?>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Gastos admin</span>
                    <span>$<?= number_format($credito->gastos_admin, 2, ',', '.') ?></span>
                </li>
                <?php endif; ?>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Vendedor</span>
                    <span><?= htmlspecialchars($credito->vendedor_nombre ?? '—') ?></span>
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Cobrador</span>
                    <span><?= htmlspecialchars($credito->cobrador_nombre ?? '—') ?></span>
                </li>
                <?php if ($credito->destino_opcional): ?>
                <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between">
                    <span class="text-secondary">Destino</span>
                    <span><?= htmlspecialchars($credito->destino_opcional) ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Resumen de cuotas -->
        <div class="card bg-slate-800 border-secondary">
            <div class="card-body py-3">
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="small text-secondary">Pagadas</div>
                        <div class="h5 text-success fw-bold"><?= $cuotasPagadas ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-secondary">Pendientes</div>
                        <div class="h5 text-info fw-bold"><?= $cuotasPendientes ?></div>
                    </div>
                    <div class="col-4">
                        <div class="small text-secondary">Vencidas</div>
                        <div class="h5 <?= $cuotasVencidas > 0 ? 'text-danger' : 'text-secondary' ?> fw-bold"><?= $cuotasVencidas ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Columna derecha: tabla de cuotas -->
    <div class="col-12 col-xl-8">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 text-light"><i class="bi bi-calendar3 me-2 text-info"></i>Calendario de Cuotas</h5>
            </div>
            <?php if (empty($credito->cuotas)): ?>
                <div class="card-body text-center py-5 text-secondary">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i> Sin cuotas registradas.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 small">
                    <thead class="border-secondary">
                        <tr class="text-secondary text-uppercase" style="font-size: 0.72rem;">
                            <th class="text-center">#</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Pagado</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                            <th>Fecha Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($credito->cuotas as $q): ?>
                        <?php $diasAtraso = $q->getDiasAtraso(); ?>
                        <tr class="<?= $diasAtraso > 0 ? 'table-danger bg-opacity-10' : '' ?>">
                            <td class="text-center text-secondary"><?= $q->numero_cuota ?></td>
                            <td class="text-light">
                                <?= date('d/m/Y', strtotime($q->fecha_vencimiento)) ?>
                                <?php if ($diasAtraso > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $diasAtraso ?>d</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-light">$<?= number_format($q->monto_esperado, 2, ',', '.') ?></td>
                            <td class="text-end <?= $q->monto_pagado > 0 ? 'text-success' : 'text-secondary' ?>">
                                $<?= number_format($q->monto_pagado, 2, ',', '.') ?>
                            </td>
                            <td class="text-end fw-bold <?= $q->getSaldo() > 0 ? 'text-warning' : 'text-success' ?>">
                                $<?= number_format($q->getSaldo(), 2, ',', '.') ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $q->estadoBadge() ?>"><?= $q->estadoLabel() ?></span>
                            </td>
                            <td class="text-secondary">
                                <?= $q->fecha_pagada ? date('d/m/Y', strtotime($q->fecha_pagada)) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Historial de pagos -->
        <div class="card bg-slate-800 border-secondary mt-4">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-light"><i class="bi bi-receipt me-2 text-success"></i>Historial de Pagos</h5>
                <?php if (!empty($pagos)): ?>
                    <span class="badge bg-success"><?= count(array_filter($pagos, fn($p) => !$p->anulado)) ?> vigente(s)</span>
                <?php endif; ?>
            </div>
            <?php if (empty($pagos)): ?>
                <div class="card-body text-center py-4 text-secondary">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Sin pagos registrados.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 small">
                        <thead class="border-secondary">
                            <tr class="text-secondary text-uppercase" style="font-size:0.7rem;">
                                <th>Fecha pago</th>
                                <th class="text-end">Monto</th>
                                <th>Forma</th>
                                <th>Cuotas</th>
                                <th>Cobrador</th>
                                <th>Recibo</th>
                                <th>Estado</th>
                                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                                    <th></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pagos as $p): ?>
                            <tr class="<?= $p->anulado ? 'opacity-50' : '' ?>">
                                <td class="text-light">
                                    <?= date('d/m/Y', strtotime($p->fecha_pago_real)) ?>
                                    <div class="text-secondary" style="font-size:0.68rem;">
                                        reg: <?= date('d/m/Y H:i', strtotime($p->fecha_registro)) ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold <?= $p->anulado ? 'text-secondary text-decoration-line-through' : 'text-success' ?>">
                                    $<?= number_format($p->monto_pagado, 2, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $p->formaPagoBadge() ?>">
                                        <?= $p->formasPagoLabel() ?>
                                    </span>
                                    <?php if ($p->referencia_externa): ?>
                                        <div class="text-secondary" style="font-size:0.68rem;"><?= htmlspecialchars($p->referencia_externa) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary">
                                    <?php if (!empty($p->cuotasAplicadas)): ?>
                                        <?= implode(', ', array_map(fn($c) => '#' . $c['numero_cuota'], $p->cuotasAplicadas)) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="text-light"><?= htmlspecialchars($p->cobrador_nombre ?? '—') ?></td>
                                <td class="text-secondary font-monospace" style="font-size:0.7rem;">
                                    <?php
                                        $recibo = (new \App\Repositories\PagoRepository())->findReciboPorPago($p->id_pago);
                                        if ($recibo): ?>
                                            <?php if (!empty($recibo['pdf_path'])): ?>
                                                <a href="<?= $appUrl ?>/recibos/descargar?id_pago=<?= $p->id_pago ?>"
                                                   class="text-info text-decoration-none" target="_blank" title="Descargar PDF">
                                                    <i class="bi bi-file-pdf me-1"></i><?= htmlspecialchars($recibo['numero']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($recibo['numero']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p->anulado): ?>
                                        <span class="badge bg-danger" title="<?= htmlspecialchars($p->motivo_anulacion ?? '') ?>">Anulado</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Vigente</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                                <td>
                                    <?php if (!$p->anulado): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Anular pago"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalAnularPago"
                                                data-pago-id="<?= $p->id_pago ?>"
                                                data-pago-monto="$<?= number_format($p->monto_pagado, 2, ',', '.') ?>">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
<!-- Modal Anular Pago -->
<div class="modal fade" id="modalAnularPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-slate-800 border-secondary">
            <form method="POST" action="<?= $appUrl ?>/pagos/anular">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
                <input type="hidden" name="id_pago" id="inputPagoId">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Anular Pago
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-light">
                        ¿Confirma la anulación del pago de <strong id="textPagoMonto"></strong>?
                        Las cuotas afectadas volverán a su estado anterior.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo de anulación <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control bg-slate-700 border-secondary text-light"
                                  rows="3" minlength="10" required
                                  placeholder="Mínimo 10 caracteres..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalAnularPago')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('inputPagoId').value = btn.dataset.pagoId;
    document.getElementById('textPagoMonto').textContent = btn.dataset.pagoMonto;
});
</script>
<?php endif; ?>

<?php if ($_SESSION['usuario_rol'] === 'admin' && $credito->estado === 'activo'): ?>
<!-- Modal Incobrable -->
<div class="modal fade" id="modalIncobrable" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-slate-800 border-secondary">
            <form method="POST" action="<?= $appUrl ?>/creditos/incobrable" id="formIncobrable">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
                <input type="hidden" name="id_credito" value="<?= $credito->id_credito ?>">
                <input type="hidden" name="override_incobrable" id="overrideIncobrableInput" value="0">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-secondary">
                        <i class="bi bi-slash-circle me-2"></i>Marcar como Incobrable
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-light">
                        Marcar el crédito <strong><?= htmlspecialchars($credito->codigo) ?></strong> como incobrable.
                        El saldo de <strong>$<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?></strong>
                        quedará como pérdida en los reportes. El historial se conserva.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control bg-slate-700 border-secondary text-light"
                                  rows="3" minlength="10" required
                                  placeholder="Mínimo 10 caracteres..."></textarea>
                    </div>
                    <?php if ($clienteIncobrable ?? false): ?>
                    <div class="alert alert-warning py-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Este cliente ya tiene otro crédito incobrable.
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="checkOverride"
                                   onchange="document.getElementById('overrideIncobrableInput').value = this.checked ? '1' : '0'">
                            <label class="form-check-label text-light" for="checkOverride">
                                Confirmo que deseo continuar (override RN-09)
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-slash-circle me-1"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Anular Crédito -->
<div class="modal fade" id="modalAnular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-slate-800 border-secondary">
            <form method="POST" action="<?= $appUrl ?>/creditos/anular">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
                <input type="hidden" name="id_credito" value="<?= $credito->id_credito ?>">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Anular Crédito
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-light">
                        ¿Confirma la anulación del crédito <strong><?= htmlspecialchars($credito->codigo) ?></strong>?
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo de anulación <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control bg-slate-700 border-secondary text-light"
                                  rows="3" minlength="10" required
                                  placeholder="Mínimo 10 caracteres..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
