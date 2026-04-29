<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';

// Separar créditos activos y el resto
$creditosActivos  = array_filter($creditos ?? [], fn($c) => $c->estado === 'activo');
$creditosHistorial = array_filter($creditos ?? [], fn($c) => $c->estado !== 'activo');

$saldoTotal = array_sum(array_map(fn($c) => $c->saldo_pendiente, $creditosActivos));

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold">Ficha del Cliente</h2>
    <div>
        <a href="<?= $appUrl ?>/clientes" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
            <a href="<?= $appUrl ?>/clientes/editar?id=<?= $cliente->id_cliente ?>" class="btn btn-outline-primary me-2">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="<?= $appUrl ?>/creditos/nuevo?id_cliente=<?= $cliente->id_cliente ?>" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Nuevo Crédito
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Información Personal -->
    <div class="col-12 col-xl-4">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-body text-center p-4">
                <div class="bg-slate-700 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                    <i class="bi bi-person text-secondary" style="font-size: 3rem;"></i>
                </div>
                <h4 class="text-light fw-bold mb-1"><?= htmlspecialchars($cliente->nombre) ?></h4>
                <p class="text-info mb-4">DNI: <?= htmlspecialchars($cliente->dni) ?></p>

                <ul class="list-group list-group-flush text-start border-top border-secondary">
                    <li class="list-group-item bg-transparent text-light border-secondary px-0 py-3">
                        <i class="bi bi-telephone text-secondary me-2"></i>
                        <?= htmlspecialchars($cliente->telefono ?? 'Sin teléfono') ?>
                        <?php if($cliente->telefono): ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $cliente->telefono) ?>" target="_blank" class="btn btn-sm btn-success float-end rounded-circle py-0 px-1"><i class="bi bi-whatsapp"></i></a>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item bg-transparent text-light border-secondary px-0 py-3">
                        <i class="bi bi-geo-alt text-secondary me-2"></i>
                        <?= htmlspecialchars($cliente->direccion ?? 'Sin dirección') ?>
                        <?= $cliente->barrio ? ' (' . htmlspecialchars($cliente->barrio) . ')' : '' ?>
                    </li>
                    <li class="list-group-item bg-transparent text-light border-secondary px-0 py-3">
                        <i class="bi bi-map text-secondary me-2"></i>
                        Zona: <?= $cliente->zona_nombre ? htmlspecialchars($cliente->zona_nombre) : '<span class="text-muted fst-italic">Sin zona asignada</span>' ?>
                    </li>
                    <?php if($cliente->referencias): ?>
                        <li class="list-group-item bg-transparent text-light border-secondary px-0 py-3 text-break">
                            <i class="bi bi-info-circle text-secondary me-2"></i>
                            <?= nl2br(htmlspecialchars($cliente->referencias)) ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Créditos -->
    <div class="col-12 col-xl-8">

        <!-- Créditos Activos -->
        <div class="card bg-slate-800 border-secondary mb-4">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-light">
                    <i class="bi bi-cash-stack text-success me-2"></i>
                    Créditos Activos
                    <?php if (!empty($creditosActivos)): ?>
                        <span class="badge bg-success ms-1"><?= count($creditosActivos) ?></span>
                    <?php endif; ?>
                </h5>
                <?php if (!empty($creditosActivos)): ?>
                    <span class="text-warning fw-bold small">
                        Saldo total: $<?= number_format($saldoTotal, 2, ',', '.') ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($creditosActivos)): ?>
                <div class="card-body text-center py-4 text-secondary">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Sin créditos activos.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 small">
                        <thead class="border-secondary">
                            <tr class="text-secondary text-uppercase" style="font-size: 0.72rem;">
                                <th>Código</th>
                                <th class="text-end">Capital</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Saldo</th>
                                <th>Frecuencia</th>
                                <th>Cobrador</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creditosActivos as $c): ?>
                            <tr>
                                <td>
                                    <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $c->id_credito ?>"
                                       class="text-info text-decoration-none fw-bold font-monospace">
                                        <?= htmlspecialchars($c->codigo) ?>
                                    </a>
                                    <div class="text-secondary" style="font-size:0.7rem;">
                                        Inicio: <?= date('d/m/Y', strtotime($c->fecha_inicio)) ?>
                                    </div>
                                </td>
                                <td class="text-end text-light">$<?= number_format($c->capital, 2, ',', '.') ?></td>
                                <td class="text-end text-light">$<?= number_format($c->monto_total, 2, ',', '.') ?></td>
                                <td class="text-end fw-bold text-warning">$<?= number_format($c->saldo_pendiente, 2, ',', '.') ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst($c->frecuencia) ?></span></td>
                                <td class="text-light"><?= htmlspecialchars($c->cobrador_nombre ?? '—') ?></td>
                                <td>
                                    <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $c->id_credito ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Historial de créditos cerrados -->
        <?php if (!empty($creditosHistorial)): ?>
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 text-light">
                    <i class="bi bi-clock-history text-secondary me-2"></i>
                    Historial de Créditos
                    <span class="badge bg-secondary ms-1"><?= count($creditosHistorial) ?></span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 small">
                    <thead class="border-secondary">
                        <tr class="text-secondary text-uppercase" style="font-size: 0.72rem;">
                            <th>Código</th>
                            <th class="text-end">Capital</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Inicio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($creditosHistorial as $c): ?>
                        <tr>
                            <td class="font-monospace text-secondary"><?= htmlspecialchars($c->codigo) ?></td>
                            <td class="text-end text-secondary">$<?= number_format($c->capital, 2, ',', '.') ?></td>
                            <td class="text-end text-secondary">$<?= number_format($c->monto_total, 2, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-<?= $c->estadoBadge() ?>"><?= $c->estadoLabel() ?></span>
                            </td>
                            <td class="text-secondary"><?= date('d/m/Y', strtotime($c->fecha_inicio)) ?></td>
                            <td>
                                <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $c->id_credito ?>"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
