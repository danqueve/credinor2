<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
$esEdicion = ($usuario !== null);
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
            <div class="card-body p-4">
                <form action="<?= $appUrl ?>/usuarios/<?= $action ?>" method="POST"
                      x-data="usuarioForm()" @submit.prevent="submit">

                    <?= \App\Helpers\Csrf::getFormField() ?>

                    <div class="form-section-header mb-3">
                        <i class="bi bi-person-fill" style="color:#60a5fa;"></i>
                        Credenciales de acceso
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-light">Usuario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-person text-secondary"></i>
                                </span>
                                <input type="text" name="username"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       value="<?= htmlspecialchars($usuario->username ?? '') ?>"
                                       placeholder="DNI o nombre de usuario" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">
                                Contraseña <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-key text-secondary"></i>
                                </span>
                                <input type="password" name="password"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       placeholder="<?= $esEdicion ? 'Dejar vacío para no cambiar' : 'Contraseña inicial' ?>"
                                       <?= $esEdicion ? '' : 'required' ?>>
                            </div>
                            <?php if ($esEdicion): ?>
                                <div class="form-text text-secondary">Dejar vacío para mantener la contraseña actual.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-section-header mb-3" style="border-left-color:#fbbf24;">
                        <i class="bi bi-shield-check" style="color:#fde68a;"></i>
                        Rol y permisos
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-light">Rol <span class="text-danger">*</span></label>
                            <select name="rol" class="form-select bg-slate-900 border-secondary text-light"
                                    x-model="rol" required>
                                <option value="admin"    <?= ($usuario->rol ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
                                <option value="cobrador" <?= ($usuario->rol ?? 'cobrador') === 'cobrador' ? 'selected' : '' ?>>Cobrador</option>
                                <option value="cliente"  <?= ($usuario->rol ?? '') === 'cliente'  ? 'selected' : '' ?>>Cliente</option>
                            </select>
                        </div>

                        <!-- Personal (solo cobrador) -->
                        <div class="col-md-8" x-show="rol === 'cobrador'" x-cloak>
                            <label class="form-label text-light">Personal vinculado</label>
                            <select name="id_personal" class="form-select bg-slate-900 border-secondary text-light">
                                <option value="">— Sin vincular —</option>
                                <?php foreach ($personal as $p): ?>
                                    <option value="<?= $p->id_personal ?>"
                                        <?= ($usuario->id_personal ?? null) === $p->id_personal ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p->nombre) ?> (DNI <?= htmlspecialchars($p->dni) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cliente (solo rol cliente) -->
                        <div class="col-md-8" x-show="rol === 'cliente'" x-cloak>
                            <label class="form-label text-light">Cliente vinculado <span class="text-danger">*</span></label>
                            <select name="id_cliente" class="form-select bg-slate-900 border-secondary text-light">
                                <option value="">— Seleccionar cliente —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c->id_cliente ?>"
                                        <?= ($usuario->id_cliente ?? null) === $c->id_cliente ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c->nombre) ?> — DNI <?= htmlspecialchars($c->dni) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-secondary">El usuario solo podrá ver el estado de cuenta de este cliente.</div>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo"
                               <?= ($usuario === null || $usuario->activo) ? 'checked' : '' ?>>
                        <label class="form-check-label text-light" for="activo">Usuario activo</label>
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

<script>
function usuarioForm() {
    return {
        rol: '<?= htmlspecialchars($usuario->rol ?? 'cobrador') ?>',
        submit(e) { e.$el.submit(); }
    };
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
