<?php
$appUrl    = $_ENV['APP_URL'] ?? '';
$esEdicion = ($usuario !== null);
ob_start();
?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header card-header-primary py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary"></i>
                    <span class="fw-semibold text-light"><?= htmlspecialchars($titulo) ?></span>
                </div>
                <a href="<?= $appUrl ?>/usuarios" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="card-body p-4"
                 x-data="{
                     dni: '<?= htmlspecialchars($usuario->dni ?? '') ?>',
                     init() {
                         this.$watch('dni', v => {
                             <?php if (!$esEdicion): ?>
                             this.$refs.username.value = v;
                             this.$refs.password.value = v;
                             <?php endif; ?>
                         });
                     }
                 }">

                <form action="<?= $appUrl ?>/usuarios/<?= $action ?>" method="POST">
                    <?= \App\Helpers\Csrf::getFormField() ?>

                    <!-- Datos personales -->
                    <div class="form-section-header mb-3">
                        <i class="bi bi-person-fill" style="color:#60a5fa;"></i>
                        Datos personales
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-light">Apellido <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-person text-secondary"></i>
                                </span>
                                <input type="text" name="apellido"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       value="<?= htmlspecialchars($usuario->apellido ?? '') ?>"
                                       placeholder="Apellido" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Nombre <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-person text-secondary"></i>
                                </span>
                                <input type="text" name="nombre"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       value="<?= htmlspecialchars($usuario->nombre ?? '') ?>"
                                       placeholder="Nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">DNI <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-card-text text-secondary"></i>
                                </span>
                                <input type="text" name="dni"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       x-model="dni"
                                       value="<?= htmlspecialchars($usuario->dni ?? '') ?>"
                                       placeholder="Sin puntos ni espacios" required>
                            </div>
                        </div>
                    </div>

                    <!-- Acceso -->
                    <div class="form-section-header mb-3" style="border-left-color:#fbbf24;">
                        <i class="bi bi-key-fill" style="color:#fde68a;"></i>
                        Acceso al sistema
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-light">
                                Usuario
                                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">= DNI</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-at text-secondary"></i>
                                </span>
                                <input type="text" name="username" x-ref="username"
                                       class="form-control bg-slate-900 border-secondary text-secondary"
                                       value="<?= htmlspecialchars($usuario->username ?? '') ?>"
                                       placeholder="Se completa con el DNI"
                                       <?= !$esEdicion ? 'readonly' : '' ?>>
                            </div>
                            <div class="form-text text-secondary">Se usa el DNI como nombre de usuario.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">
                                Clave inicial
                                <?php if (!$esEdicion): ?>
                                    <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">= DNI</span>
                                <?php endif; ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-lock text-secondary"></i>
                                </span>
                                <input type="password" name="password" x-ref="password"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       placeholder="<?= $esEdicion ? 'Dejar vacío para no cambiar' : 'Se completa con el DNI' ?>"
                                       <?= !$esEdicion ? 'readonly' : '' ?>>
                            </div>
                            <?php if ($esEdicion): ?>
                                <div class="form-text text-secondary">Dejar vacío para mantener la contraseña actual.</div>
                            <?php else: ?>
                                <div class="form-text text-secondary">La clave inicial es el DNI. El usuario puede cambiarla.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-light">Rol <span class="text-danger">*</span></label>
                            <select name="rol" class="form-select bg-slate-900 border-secondary text-light" required>
                                <option value="admin"      <?= ($usuario->rol ?? '') === 'admin'      ? 'selected' : '' ?>>Admin</option>
                                <option value="supervisor" <?= ($usuario->rol ?? '') === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                <option value="cobrador"   <?= ($usuario->rol ?? 'cobrador') === 'cobrador' ? 'selected' : '' ?>>Cobrador</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                       <?= ($usuario === null || $usuario->activo) ? 'checked' : '' ?>>
                                <label class="form-check-label text-light" for="activo">Usuario activo</label>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary mb-4">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= $appUrl ?>/usuarios" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i><?= $esEdicion ? 'Actualizar' : 'Crear usuario' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
