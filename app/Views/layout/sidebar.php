<nav id="sidebar" class="bg-slate-800 border-end border-secondary min-vh-100">
    <div class="p-3 pt-4">

        <!-- Brand -->
        <div class="mb-4 pb-3" style="border-bottom: 1px solid rgba(51,65,85,0.5);">
            <div class="d-flex align-items-center">
                <div class="sidebar-icon-wrap icon-wrap-blue me-2" style="width:40px;height:40px;border-radius:12px;font-size:1.15rem;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="sidebar-brand-name">Credinor</div>
                    <div class="sidebar-brand-tag">Sistema de Créditos</div>
                </div>
            </div>
        </div>

        <ul class="list-unstyled components mb-5">
            <!-- Dashboard -->
            <li class="mb-1">
                <a href="<?= $appUrl ?>/dashboard" class="sidebar-nav-link">
                    <span class="sidebar-icon-wrap icon-wrap-blue">
                        <i class="bi bi-grid-1x2-fill"></i>
                    </span>
                    Dashboard
                </a>
            </li>

            <!-- Clientes -->
            <li class="mb-1">
                <a href="#clienteSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                   class="sidebar-nav-link">
                    <span class="sidebar-icon-wrap icon-wrap-green">
                        <i class="bi bi-people-fill"></i>
                    </span>
                    <span class="flex-grow-1">Clientes</span>
                    <i class="bi bi-chevron-down sidebar-collapse-arrow"></i>
                </a>
                <ul class="collapse list-unstyled ps-2 mt-1" id="clienteSubmenu">
                    <li><a href="<?= $appUrl ?>/clientes"><i class="bi bi-dot me-1"></i>Listado</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/clientes/nuevo"><i class="bi bi-dot me-1"></i>Nuevo Cliente</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Créditos -->
            <li class="mb-1">
                <a href="#creditoSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                   class="sidebar-nav-link">
                    <span class="sidebar-icon-wrap icon-wrap-yellow">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                    <span class="flex-grow-1">Créditos</span>
                    <i class="bi bi-chevron-down sidebar-collapse-arrow"></i>
                </a>
                <ul class="collapse list-unstyled ps-2 mt-1" id="creditoSubmenu">
                    <li><a href="<?= $appUrl ?>/creditos"><i class="bi bi-dot me-1"></i>Listado</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/creditos/nuevo"><i class="bi bi-dot me-1"></i>Nuevo Crédito</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Pagos -->
            <li class="mb-1">
                <a href="#pagoSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                   class="sidebar-nav-link">
                    <span class="sidebar-icon-wrap icon-wrap-green">
                        <i class="bi bi-receipt"></i>
                    </span>
                    <span class="flex-grow-1">Pagos</span>
                    <i class="bi bi-chevron-down sidebar-collapse-arrow"></i>
                </a>
                <ul class="collapse list-unstyled ps-2 mt-1" id="pagoSubmenu">
                    <li><a href="<?= $appUrl ?>/pagos"><i class="bi bi-dot me-1"></i>Historial</a></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                        <li><a href="<?= $appUrl ?>/pagos/nuevo"><i class="bi bi-dot me-1"></i>Registrar Pago</a></li>
                        <li><a href="<?= $appUrl ?>/rendiciones"><i class="bi bi-dot me-1"></i>Rendiciones Bulk</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php if ($user['rol'] === 'admin'): ?>
                <!-- Separador Administración -->
                <li class="mb-1 mt-4">
                    <div class="px-2 mb-2" style="font-size:0.63rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--slate-600);">
                        Administración
                    </div>
                </li>
                <li class="mb-1">
                    <a href="<?= $appUrl ?>/reportes" class="sidebar-nav-link">
                        <span class="sidebar-icon-wrap icon-wrap-purple">
                            <i class="bi bi-bar-chart-fill"></i>
                        </span>
                        Reportes
                    </a>
                </li>
                <li class="mb-1">
                    <a href="<?= $appUrl ?>/comisiones" class="sidebar-nav-link">
                        <span class="sidebar-icon-wrap icon-wrap-orange">
                            <i class="bi bi-percent"></i>
                        </span>
                        Comisiones
                    </a>
                </li>
                <li class="mb-1">
                    <a href="<?= $appUrl ?>/personal" class="sidebar-nav-link">
                        <span class="sidebar-icon-wrap icon-wrap-gray">
                            <i class="bi bi-person-badge-fill"></i>
                        </span>
                        Personal & Zonas
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
