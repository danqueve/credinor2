<nav class="navbar navbar-expand-lg navbar-dark app-navbar mb-4">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-primary d-lg-none">
            <i class="bi bi-list"></i>
        </button>

        <!-- Breadcrumb (solo desktop) -->
        <div class="ms-3 d-none d-lg-flex align-items-center">
            <?php if (isset($titulo)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="<?= $appUrl ?>/dashboard" class="text-secondary text-decoration-none">
                                <i class="bi bi-house-door"></i>
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-slate-400" aria-current="page">
                            <?= htmlspecialchars($titulo) ?>
                        </li>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if ($user['rol'] === 'admin'): ?>
            <a href="<?= $appUrl ?>/caja" class="btn btn-sm btn-outline-success">
                <i class="bi bi-safe2-fill me-1"></i><span class="d-none d-md-inline">Caja</span>
            </a>
            <?php endif; ?>

            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-light d-flex align-items-center gap-2" href="#"
                   id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($user['username']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-slate-800 border-secondary" aria-labelledby="navbarDropdown">
                    <li><h6 class="dropdown-header">Rol: <?= ucfirst($user['rol']) ?></h6></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <?php if ($user['rol'] === 'admin'): ?>
                    <li>
                        <a href="<?= $appUrl ?>/perfil/2fa" class="dropdown-item text-light">
                            <i class="bi bi-shield-lock me-2 text-warning"></i> Configurar 2FA
                        </a>
                    </li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <?php endif; ?>
                    <li>
                        <form action="<?= $appUrl ?>/logout" method="POST" class="m-0">
                            <?= \App\Helpers\Csrf::getFormField() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
