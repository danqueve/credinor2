<nav id="sidebar" class="bg-slate-800 border-end border-secondary min-vh-100">
    <div class="p-4 pt-4">
        <h4 class="text-primary fw-bold mb-4 d-flex align-items-center">
            <i class="bi bi-wallet2 me-2"></i> Credinor
        </h4>
        <ul class="list-unstyled components mb-5">
            <li class="active mb-2">
                <a href="<?= $appUrl ?>/dashboard" class="text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                    <i class="bi bi-grid-1x2-fill me-2 text-secondary"></i> Dashboard
                </a>
            </li>
            
            <li class="mb-2">
                <a href="#clienteSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                    <i class="bi bi-people-fill me-2 text-secondary"></i> Clientes
                </a>
                <ul class="collapse list-unstyled ps-4 mt-1" id="clienteSubmenu">
                    <li><a href="<?= $appUrl ?>/clientes" class="text-decoration-none text-light-50 d-block py-1">Listado</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/clientes/nuevo" class="text-decoration-none text-light-50 d-block py-1">Nuevo Cliente</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="mb-2">
                <a href="#creditoSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                    <i class="bi bi-cash-stack me-2 text-secondary"></i> Créditos
                </a>
                <ul class="collapse list-unstyled ps-4 mt-1" id="creditoSubmenu">
                    <li><a href="<?= $appUrl ?>/creditos" class="text-decoration-none text-light-50 d-block py-1">Listado</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/creditos/nuevo" class="text-decoration-none text-light-50 d-block py-1">Nuevo Crédito</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="mb-2">
                <a href="#pagoSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                    <i class="bi bi-receipt me-2 text-secondary"></i> Pagos
                </a>
                <ul class="collapse list-unstyled ps-4 mt-1" id="pagoSubmenu">
                    <li><a href="<?= $appUrl ?>/pagos" class="text-decoration-none text-light-50 d-block py-1">Historial</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/pagos/nuevo" class="text-decoration-none text-light-50 d-block py-1 text-success">Registrar Pago</a></li>
                        <li><a href="<?= $appUrl ?>/rendiciones" class="text-decoration-none text-light-50 d-block py-1 text-warning">Rendiciones Bulk</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php if ($user['rol'] === 'admin'): ?>
                <li class="mb-2 mt-4">
                    <span class="text-uppercase text-secondary small fw-bold px-3">Administración</span>
                </li>
                <li class="mb-2">
                    <a href="<?= $appUrl ?>/reportes" class="text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                        <i class="bi bi-bar-chart-fill me-2 text-secondary"></i> Reportes
                    </a>
                </li>
                <li class="mb-2">
                    <a href="<?= $appUrl ?>/comisiones" class="text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                        <i class="bi bi-percent me-2 text-secondary"></i> Comisiones
                    </a>
                </li>
                <li class="mb-2">
                    <a href="<?= $appUrl ?>/personal" class="text-decoration-none text-light d-block py-2 px-3 rounded hover-bg-slate-700">
                        <i class="bi bi-person-badge-fill me-2 text-secondary"></i> Personal & Zonas
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
