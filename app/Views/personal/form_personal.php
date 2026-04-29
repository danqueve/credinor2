<?php 
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start(); 
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-white fw-bold d-flex align-items-center">
                    <i class="bi bi-person-badge text-primary me-2"></i>
                    <?= htmlspecialchars($titulo) ?>
                </h2>
                <p class="text-secondary mb-0">Complete los datos del empleado para registrarlo en el sistema.</p>
            </div>
            <a href="<?= $appUrl ?>/personal" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger rounded-3 alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                </div>
                <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="card bg-slate-800 border-secondary rounded-4 shadow-lg overflow-hidden">
            <div class="card-header bg-slate-800 border-bottom border-secondary py-3 px-4">
                <h5 class="card-title mb-0 text-light fw-semibold">Información Personal</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                <form action="<?= $appUrl ?>/personal/<?= $action ?>" method="POST">
                    <?= \App\Helpers\Csrf::getFormField() ?>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Nombre Completo <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light fs-6" id="nombre" name="nombre" value="<?= htmlspecialchars($empleado->nombre ?? '') ?>" placeholder="Ej: Juan Pérez" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="dni" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Documento (DNI) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-card-text"></i></span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light fs-6" id="dni" name="dni" value="<?= htmlspecialchars($empleado->dni ?? '') ?>" placeholder="Sin puntos ni espacios" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label for="telefono" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Teléfono</label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control bg-slate-900 border-secondary text-light fs-6" id="telefono" name="telefono" value="<?= htmlspecialchars($empleado->telefono ?? '') ?>" placeholder="Ej: 381 1234567">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="id_zona" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Zona Asignada</label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-geo-alt"></i></span>
                                <select class="form-select bg-slate-900 border-secondary text-light fs-6" id="id_zona" name="id_zona">
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

                    <h5 class="text-light fw-semibold mb-4 pb-2 border-bottom border-secondary">Roles y Comisiones</h5>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label for="rol_operativo" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Rol Operativo <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg bg-slate-900 border-secondary text-light fs-6 shadow-sm rounded-3" id="rol_operativo" name="rol_operativo" required>
                                <option value="vendedor" <?= (($empleado->rol_operativo ?? '') === 'vendedor') ? 'selected' : '' ?>>Vendedor</option>
                                <option value="cobrador" <?= (($empleado->rol_operativo ?? '') === 'cobrador') ? 'selected' : '' ?>>Cobrador</option>
                                <option value="ambos" <?= (($empleado->rol_operativo ?? '') === 'ambos') ? 'selected' : '' ?>>Ambos</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="comision_pct" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Comisión (%)</label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <input type="number" step="0.01" min="0" max="100" class="form-control bg-slate-900 border-secondary text-light fs-6 border-end-0" id="comision_pct" name="comision_pct" value="<?= htmlspecialchars((string)($empleado->comision_pct ?? '0')) ?>">
                                <span class="input-group-text bg-slate-900 border-secondary text-secondary"><i class="bi bi-percent"></i></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Estado <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg bg-slate-900 border-secondary text-light fs-6 shadow-sm rounded-3" id="estado" name="estado" required>
                                <option value="activo" <?= (($empleado->estado ?? 'activo') === 'activo') ? 'selected' : '' ?>>🟢 Activo</option>
                                <option value="inactivo" <?= (($empleado->estado ?? '') === 'inactivo') ? 'selected' : '' ?>>🔴 Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 d-flex justify-content-end border-top border-secondary">
                        <a href="<?= $appUrl ?>/personal" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Guardar Empleado
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
