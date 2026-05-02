<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
?>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- Cabecera del cliente -->
<div class="text-center mb-4 pt-1">
    <div class="client-avatar mx-auto mb-3" style="width:72px;height:72px;font-size:1.8rem;">
        <?= mb_strtoupper(mb_substr($cliente->nombre, 0, 1)) ?>
    </div>
    <h1 class="h5 fw-bold text-light mb-1"><?= htmlspecialchars($cliente->nombre) ?></h1>
    <div class="small font-monospace text-info">DNI <?= htmlspecialchars($cliente->dni) ?></div>
    <?php if ($cliente->zona_nombre): ?>
        <span class="badge badge-zona mt-1"><?= htmlspecialchars($cliente->zona_nombre) ?></span>
    <?php endif; ?>
</div>

<!-- Sin créditos -->
<?php if (empty($creditos)): ?>
    <div class="card bg-slate-800 border-secondary text-center p-4">
        <i class="bi bi-cash-stack text-secondary fs-2 d-block mb-2"></i>
        <p class="text-secondary mb-0">No tiene créditos registrados.</p>
    </div>
<?php else: ?>
    <?php foreach ($creditos as $cr):
        $cuotasPagadas = 0;
        $cuotasTotal   = count($cr->cuotas ?? []);
        $proximaCuota  = null;
        foreach ($cr->cuotas ?? [] as $cu) {
            if ($cu['estado'] === 'pagada') {
                $cuotasPagadas++;
            } elseif ($proximaCuota === null && in_array($cu['estado'], ['pendiente', 'vencida', 'parcial'])) {
                $proximaCuota = $cu;
            }
        }
        $pct = $cuotasTotal > 0 ? round($cuotasPagadas / $cuotasTotal * 100) : 0;

        $estadoClass = match ($cr->estado) {
            'activo'       => 'badge-activo',
            'cancelado'    => 'badge-activo',
            'vencido'      => 'badge-vencido',
            'refinanciado' => 'badge-refinanciado',
            'incobrable'   => 'badge-vencido',
            default        => 'bg-secondary',
        };
    ?>
    <div class="card bg-slate-800 border-secondary mb-3">
        <div class="card-body p-3">

            <!-- Cabecera del crédito -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-bold text-light">Crédito <span class="font-monospace text-info"><?= htmlspecialchars($cr->codigo) ?></span></div>
                    <div class="small text-secondary">
                        Capital: <span class="text-light fw-semibold">$<?= number_format($cr->capital_inicial ?? 0, 0, ',', '.') ?></span>
                        &nbsp;·&nbsp;
                        <?= $cr->cantidad_cuotas ?> cuotas
                    </div>
                </div>
                <span class="badge <?= $estadoClass ?>"><?= ucfirst($cr->estado) ?></span>
            </div>

            <!-- Progreso -->
            <div class="mb-3">
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <span><?= $cuotasPagadas ?> de <?= $cuotasTotal ?> cuotas abonadas</span>
                    <span><?= $pct ?>%</span>
                </div>
                <div class="progress" style="height:6px;background:rgba(51,65,85,0.8);">
                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>"
                         style="width:<?= $pct ?>%;border-radius:3px;"></div>
                </div>
            </div>

            <!-- Mini stats -->
            <div class="row g-2 mb-3">
                <div class="col-4 text-center">
                    <div class="small text-secondary">Saldo</div>
                    <div class="fw-bold <?= $cr->saldo_pendiente > 0 ? 'text-warning' : 'text-success' ?>">
                        $<?= number_format($cr->saldo_pendiente ?? 0, 0, ',', '.') ?>
                    </div>
                </div>
                <?php if ($proximaCuota): ?>
                <div class="col-4 text-center">
                    <div class="small text-secondary">Próx. cuota</div>
                    <div class="fw-semibold text-light">$<?= number_format($proximaCuota['monto_esperado'], 0, ',', '.') ?></div>
                </div>
                <div class="col-4 text-center">
                    <div class="small text-secondary">Vencimiento</div>
                    <div class="fw-semibold <?= strtotime($proximaCuota['fecha_vencimiento']) < time() ? 'text-danger' : 'text-light' ?>">
                        <?= date('d/m/y', strtotime($proximaCuota['fecha_vencimiento'])) ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-8 text-center">
                    <div class="small text-success"><i class="bi bi-check-circle me-1"></i>Crédito al día</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Historial de cuotas (colapsable) -->
            <?php if (!empty($cr->cuotas)): ?>
            <div>
                <button class="btn btn-sm btn-outline-secondary w-100"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#cuotas-<?= $cr->id_credito ?>">
                    <i class="bi bi-list-ul me-1"></i>Ver cuotas
                </button>
                <div class="collapse mt-2" id="cuotas-<?= $cr->id_credito ?>">
                    <div class="list-group list-group-flush" style="border-radius:8px;overflow:hidden;">
                        <?php foreach ($cr->cuotas as $cu): ?>
                        <div class="list-group-item bg-slate-700 border-secondary px-3 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small text-secondary me-2">#<?= $cu['numero_cuota'] ?></span>
                                    <span class="small text-light"><?= date('d/m/Y', strtotime($cu['fecha_vencimiento'])) ?></span>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-semibold text-light">$<?= number_format($cu['monto_esperado'], 0, ',', '.') ?></div>
                                    <?php
                                    $badgeCuota = match ($cu['estado']) {
                                        'pagada'   => 'badge-activo',
                                        'vencida'  => 'badge-vencido',
                                        'parcial'  => 'badge-refinanciado',
                                        default    => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeCuota ?>" style="font-size:0.6rem;"><?= ucfirst($cu['estado']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
