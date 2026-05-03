<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">

        <div class="card bg-slate-800 border-secondary">
            <div class="card-header card-header-primary py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge text-primary"></i>
                    <span class="fw-semibold text-light"><?= htmlspecialchars($titulo) ?></span>
                </div>
                <a href="<?= $appUrl ?>/personal" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
            <div class="card-body p-4">
                <form action="<?= $appUrl ?>/personal/<?= $action ?>" method="POST">
                    <?= \App\Helpers\Csrf::getFormField() ?>

                    <div class="form-section-header mb-3">
                        <i class="bi bi-person-fill" style="color:#60a5fa;"></i>
                        Datos Personales
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label text-light">Nombre Completo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-person text-secondary"></i>
                                </span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light"
                                       id="nombre" name="nombre"
                                       value="<?= htmlspecialchars($empleado->nombre ?? '') ?>"
                                       placeholder="Ej: Juan Pérez" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="dni" class="form-label text-light">DNI <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-card-text text-secondary"></i>
                                </span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light"
                                       id="dni" name="dni"
                                       value="<?= htmlspecialchars($empleado->dni ?? '') ?>"
                                       placeholder="Sin puntos ni espacios" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="telefono" class="form-label text-light">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-telephone text-secondary"></i>
                                </span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light"
                                       id="telefono" name="telefono"
                                       value="<?= htmlspecialchars($empleado->telefono ?? '') ?>"
                                       placeholder="Ej: 381 1234567">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="id_zona" class="form-label text-light">Zona Asignada</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-geo-alt text-secondary"></i>
                                </span>
                                <select class="form-select bg-slate-900 border-secondary text-light" id="id_zona" name="id_zona">
                                    <option value="">-- Sin Zona --</option>
                                    <?php foreach($zonas as $z): ?>
                                        <option value="<?= $z->id_zona ?>" <?= ($empleado->id_zona === $z->id_zona) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($z->nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-header mb-3" style="border-left-color:#fbbf24;">
                        <i class="bi bi-person-gear" style="color:#fde68a;"></i>
                        Roles y Comisiones
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="rol_operativo" class="form-label text-light">Rol Operativo <span class="text-danger">*</span></label>
                            <select class="form-select bg-slate-900 border-secondary text-light" id="rol_operativo" name="rol_operativo" required>
                                <option value="cobrador" <?= (($empleado->rol_operativo ?? 'cobrador') === 'cobrador') ? 'selected' : '' ?>>Cobrador</option>
                                <option value="admin"    <?= (($empleado->rol_operativo ?? '') === 'admin')    ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="comision_pct" class="form-label text-light">Comisión (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       id="comision_pct" name="comision_pct"
                                       value="<?= htmlspecialchars((string)($empleado->comision_pct ?? '0')) ?>">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-percent text-secondary"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label text-light">Estado <span class="text-danger">*</span></label>
                            <select class="form-select bg-slate-900 border-secondary text-light" id="estado" name="estado" required>
                                <option value="activo"   <?= (($empleado->estado ?? 'activo') === 'activo')   ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= (($empleado->estado ?? '') === 'inactivo')        ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-header mb-3" style="border-left-color:#34d399;">
                        <i class="bi bi-key-fill" style="color:#34d399;"></i>
                        Acceso al sistema
                    </div>

                    <?php if ($usuarioVinculado): ?>
                        <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-person-check-fill fs-5"></i>
                            <span>Usuario vinculado: <strong><?= htmlspecialchars($usuarioVinculado->username) ?></strong></span>
                        </div>
                    <?php else: ?>
                        <div class="mb-4" x-data="{ crear: false }">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="crear_usuario" id="crear_usuario"
                                       value="1" x-model="crear">
                                <label class="form-check-label text-light" for="crear_usuario">
                                    Crear acceso al sistema para este cobrador
                                </label>
                                <div class="form-text text-secondary">El usuario podrá iniciar sesión y ver únicamente los clientes asignados a este cobrador.</div>
                            </div>
                            <div x-show="crear" x-transition class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label text-light">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-slate-700 border-secondary">
                                            <i class="bi bi-at text-secondary"></i>
                                        </span>
                                        <input type="text" class="form-control bg-slate-900 border-secondary text-light"
                                               id="username" name="username"
                                               placeholder="Ej: cobrador1" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_new" class="form-label text-light">Contraseña <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-slate-700 border-secondary">
                                            <i class="bi bi-lock text-secondary"></i>
                                        </span>
                                        <input type="password" class="form-control bg-slate-900 border-secondary text-light"
                                               id="password_new" name="password_new"
                                               placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="border-secondary mb-4">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= $appUrl ?>/personal" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i> Guardar Empleado
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
