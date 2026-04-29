<nav class="navbar navbar-expand-lg navbar-dark bg-slate-800 border-bottom border-secondary mb-4">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-primary d-lg-none">
            <i class="bi bi-list"></i>
        </button>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-light d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                    </div>
                    <span><?= htmlspecialchars($user['username']) ?></span>
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
