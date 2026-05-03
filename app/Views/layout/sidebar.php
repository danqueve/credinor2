<nav id="sidebar" class="bg-slate-900 border-end border-secondary">
    <div class="d-flex flex-column min-vh-100">

        <!-- Brand -->
        <div class="sidebar-brand-area px-3 pt-4 pb-3" style="border-bottom:1px solid rgba(51,65,85,0.5);">
            <div class="d-flex align-items-center gap-2">
                <div class="sidebar-icon-wrap icon-wrap-blue flex-shrink-0" style="width:40px;height:40px;border-radius:12px;font-size:1.15rem;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="sidebar-text">
                    <div class="sidebar-brand-name">Credinor</div>
                    <div class="sidebar-brand-tag">Sistema de Créditos</div>
                </div>
            </div>
        </div>

        <!-- Nav items -->
        <div class="px-2 pt-3 flex-grow-1">
            <ul class="list-unstyled components mb-0">

                <!-- Dashboard -->
                <li class="mb-1">
                    <a href="<?= $appUrl ?>/dashboard"
                       class="sidebar-nav-link rail-item"
                       data-rail-title="Dashboard">
                        <span class="sidebar-icon-wrap icon-wrap-blue flex-shrink-0">
                            <i class="bi bi-grid-1x2-fill"></i>
                        </span>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>

                <!-- Clientes -->
                <li class="mb-1">
                    <a href="#clienteSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                       class="sidebar-nav-link rail-item"
                       data-rail-href="<?= $appUrl ?>/clientes"
                       data-rail-title="Clientes">
                        <span class="sidebar-icon-wrap icon-wrap-green flex-shrink-0">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <span class="sidebar-text flex-grow-1">Clientes</span>
                        <i class="bi bi-chevron-down sidebar-collapse-arrow sidebar-text"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-2 mt-1 sidebar-submenu" id="clienteSubmenu">
                        <li><a href="<?= $appUrl ?>/clientes"><i class="bi bi-dot me-1"></i>Listado</a></li>
                        <?php if ($user['rol'] === 'admin'): ?>
                            <li><a href="<?= $appUrl ?>/clientes/nuevo"><i class="bi bi-dot me-1"></i>Nuevo Cliente</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Créditos -->
                <li class="mb-1">
                    <a href="#creditoSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                       class="sidebar-nav-link rail-item"
                       data-rail-href="<?= $appUrl ?>/creditos"
                       data-rail-title="Créditos">
                        <span class="sidebar-icon-wrap icon-wrap-yellow flex-shrink-0">
                            <i class="bi bi-cash-stack"></i>
                        </span>
                        <span class="sidebar-text flex-grow-1">Créditos</span>
                        <i class="bi bi-chevron-down sidebar-collapse-arrow sidebar-text"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-2 mt-1 sidebar-submenu" id="creditoSubmenu">
                        <li><a href="<?= $appUrl ?>/creditos"><i class="bi bi-dot me-1"></i>Listado</a></li>
                        <?php if ($user['rol'] === 'admin'): ?>
                            <li><a href="<?= $appUrl ?>/creditos/nuevo"><i class="bi bi-dot me-1"></i>Nuevo Crédito</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Pagos -->
                <li class="mb-1">
                    <a href="#pagoSubmenu" data-bs-toggle="collapse" aria-expanded="false"
                       class="sidebar-nav-link rail-item"
                       data-rail-href="<?= $appUrl ?>/pagos"
                       data-rail-title="Pagos">
                        <span class="sidebar-icon-wrap icon-wrap-green flex-shrink-0">
                            <i class="bi bi-receipt"></i>
                        </span>
                        <span class="sidebar-text flex-grow-1">Pagos</span>
                        <i class="bi bi-chevron-down sidebar-collapse-arrow sidebar-text"></i>
                    </a>
                    <ul class="collapse list-unstyled ps-2 mt-1 sidebar-submenu" id="pagoSubmenu">
                        <li><a href="<?= $appUrl ?>/pagos"><i class="bi bi-dot me-1"></i>Historial</a></li>
                        <?php if ($user['rol'] === 'admin'): ?>
                            <li><a href="<?= $appUrl ?>/pagos/nuevo"><i class="bi bi-dot me-1"></i>Registrar Pago</a></li>
                        <?php endif; ?>
                        <?php if (in_array($user['rol'], ['admin', 'supervisor'], true)): ?>
                            <li><a href="<?= $appUrl ?>/rendiciones"><i class="bi bi-dot me-1"></i>Rendiciones Bulk</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <?php if (in_array($user['rol'], ['admin', 'supervisor'], true)): ?>
                    <!-- Separador Admin -->
                    <li class="mb-1 mt-4">
                        <div class="sidebar-section-label px-2 mb-2">
                            Administración
                        </div>
                    </li>
                    <li class="mb-1">
                        <a href="<?= $appUrl ?>/reportes"
                           class="sidebar-nav-link rail-item" data-rail-title="Reportes">
                            <span class="sidebar-icon-wrap icon-wrap-purple flex-shrink-0">
                                <i class="bi bi-bar-chart-fill"></i>
                            </span>
                            <span class="sidebar-text">Reportes</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= $appUrl ?>/caja"
                           class="sidebar-nav-link rail-item" data-rail-title="Caja">
                            <span class="sidebar-icon-wrap icon-wrap-green flex-shrink-0">
                                <i class="bi bi-safe2-fill"></i>
                            </span>
                            <span class="sidebar-text">Caja</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= $appUrl ?>/comisiones"
                           class="sidebar-nav-link rail-item" data-rail-title="Comisiones">
                            <span class="sidebar-icon-wrap icon-wrap-orange flex-shrink-0">
                                <i class="bi bi-percent"></i>
                            </span>
                            <span class="sidebar-text">Comisiones</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= $appUrl ?>/personal"
                           class="sidebar-nav-link rail-item" data-rail-title="Personal &amp; Zonas">
                            <span class="sidebar-icon-wrap icon-wrap-gray flex-shrink-0">
                                <i class="bi bi-person-badge-fill"></i>
                            </span>
                            <span class="sidebar-text">Personal &amp; Zonas</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= $appUrl ?>/usuarios"
                           class="sidebar-nav-link rail-item" data-rail-title="Usuarios">
                            <span class="sidebar-icon-wrap icon-wrap-blue flex-shrink-0">
                                <i class="bi bi-shield-lock-fill"></i>
                            </span>
                            <span class="sidebar-text">Usuarios</span>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>

        <!-- Toggle (solo desktop) -->
        <div class="sidebar-toggle-area p-3 d-none d-lg-flex align-items-center justify-content-start"
             style="border-top:1px solid rgba(51,65,85,0.5);">
            <button id="sidebarRailToggle" class="sidebar-rail-toggle">
                <i class="bi bi-chevron-left sidebar-rail-chevron"></i>
                <span class="sidebar-text" style="font-size:.8rem;">Colapsar</span>
            </button>
        </div>

    </div>
</nav>
