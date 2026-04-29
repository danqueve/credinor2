<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
?>

<!-- Buscador grande -->
<form method="GET" action="<?= $appUrl ?>/consulta/buscar" class="mb-4">
    <div class="input-group input-group-lg">
        <span class="input-group-text bg-slate-800 border-secondary text-secondary">
            <i class="bi bi-search"></i>
        </span>
        <input type="search" name="q" class="form-control bg-slate-800 border-secondary text-light"
               placeholder="Nombre, DNI, dirección, teléfono..."
               value="<?= htmlspecialchars($q) ?>"
               autocomplete="off" autofocus
               style="font-size: 1rem;">
        <button class="btn btn-info" type="submit">Buscar</button>
    </div>
</form>

<?php if ($q !== '' && empty($clientes)): ?>
    <div class="text-center py-5 text-secondary">
        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
        <div>Sin resultados para "<strong><?= htmlspecialchars($q) ?></strong>"</div>
    </div>
<?php elseif (!empty($clientes)): ?>
    <div class="small text-secondary mb-2"><?= count($clientes) ?> resultado(s)</div>
    <div class="d-flex flex-column gap-2">
    <?php foreach ($clientes as $cl): ?>
        <a href="<?= $appUrl ?>/consulta/cliente?id=<?= $cl->id_cliente ?>"
           class="card bg-slate-800 border-0 text-decoration-none list-item-touch px-3 py-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:42px;height:42px;">
                    <span class="text-dark fw-bold"><?= mb_strtoupper(mb_substr($cl->nombre, 0, 1)) ?></span>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-light fw-semibold text-truncate"><?= htmlspecialchars($cl->nombre) ?></div>
                    <div class="small text-secondary">DNI <?= htmlspecialchars($cl->dni) ?></div>
                    <?php if ($cl->direccion): ?>
                    <div class="small text-secondary text-truncate">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($cl->direccion) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-secondary"><i class="bi bi-chevron-right"></i></div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-secondary">
        <i class="bi bi-person-lines-fill fs-1 d-block mb-2"></i>
        <div class="small">Ingresá nombre, DNI, dirección o teléfono</div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
