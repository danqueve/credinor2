<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Autenticación de Dos Factores</h2>
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

<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-body p-4">

                <?php if ($totp_activo): ?>
                    <!-- Estado: 2FA activo -->
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-fill-check text-success" style="font-size: 3.5rem;"></i>
                        <h5 class="mt-3 text-success fw-bold">2FA Activado</h5>
                        <p class="text-secondary small">Tu cuenta está protegida con autenticación de dos factores.</p>
                    </div>
                    <form method="POST" action="<?= $appUrl ?>/perfil/2fa/desactivar"
                          onsubmit="return confirm('¿Desactivar 2FA? Tu cuenta quedará menos protegida.')">
                        <?= \App\Helpers\Csrf::getFormField() ?>
                        <label class="form-label text-secondary small">Código TOTP para confirmar desactivación</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="text" name="totp_code" class="form-control bg-dark border-secondary text-light text-center"
                                   maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-shield-slash me-1"></i> Desactivar 2FA
                        </button>
                    </form>

                <?php elseif ($setup_secret): ?>
                    <!-- Estado: configurando 2FA -->
                    <div class="text-center mb-3">
                        <i class="bi bi-qr-code text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-2 text-warning fw-bold">Configurar 2FA</h5>
                        <p class="text-secondary small">Escaneá el QR con tu app autenticadora (Google Authenticator, Authy, etc.)</p>
                    </div>

                    <div class="text-center mb-3">
                        <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" class="rounded border border-secondary" width="200" height="200">
                    </div>

                    <div class="bg-dark rounded p-2 mb-3 text-center">
                        <span class="text-secondary small">Secret manual:</span><br>
                        <code class="text-warning user-select-all"><?= htmlspecialchars($setup_secret) ?></code>
                    </div>

                    <form method="POST" action="<?= $appUrl ?>/perfil/2fa/activar">
                        <?= \App\Helpers\Csrf::getFormField() ?>
                        <label class="form-label text-secondary small">Ingresá el código para confirmar</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="bi bi-shield-check"></i>
                            </span>
                            <input type="text" name="totp_code" class="form-control bg-dark border-secondary text-light text-center"
                                   maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check2-shield me-1"></i> Activar 2FA
                        </button>
                    </form>

                <?php else: ?>
                    <!-- Estado: 2FA no configurado -->
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-exclamation text-secondary" style="font-size: 3.5rem;"></i>
                        <h5 class="mt-3 text-light fw-bold">2FA No Activado</h5>
                        <p class="text-secondary small">Activá la autenticación de dos factores para mayor seguridad.</p>
                    </div>
                    <form method="POST" action="<?= $appUrl ?>/perfil/2fa/iniciar">
                        <?= \App\Helpers\Csrf::getFormField() ?>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-shield-plus me-1"></i> Configurar 2FA
                        </button>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
