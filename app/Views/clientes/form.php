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

<div class="card bg-slate-800 border-secondary">
    <div class="card-header card-header-primary py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-vcard text-primary"></i>
            <span class="fw-semibold text-light"><?= htmlspecialchars($titulo) ?></span>
        </div>
        <a href="<?= $appUrl ?>/clientes" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
    <div class="card-body p-4">
        <form action="<?= $appUrl ?>/clientes/<?= $action ?>" method="POST">
            <?= \App\Helpers\Csrf::getFormField() ?>

            <div class="form-section-header mb-3">
                <i class="bi bi-person-fill" style="color:#60a5fa;"></i>
                Datos Personales
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label for="nombre" class="form-label text-light">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="nombre" name="nombre" value="<?= htmlspecialchars($cliente->nombre ?? '') ?>" required autofocus>
                </div>
                <div class="col-md-4">
                    <label for="dni" class="form-label text-light">DNI <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="dni" name="dni" value="<?= htmlspecialchars($cliente->dni ?? '') ?>" required>
                </div>
            </div>

            <div class="form-section-header mb-3" style="border-left-color:#22c55e;">
                <i class="bi bi-geo-alt-fill" style="color:#4ade80;"></i>
                Contacto y Ubicación
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="telefono" class="form-label text-light">Teléfono (WhatsApp)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-slate-700 border-secondary">
                            <i class="bi bi-whatsapp text-success"></i>
                        </span>
                        <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="telefono" name="telefono" value="<?= htmlspecialchars($cliente->telefono ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="id_zona" class="form-label text-light">Zona de Cobro</label>
                    <select class="form-select bg-slate-900 border-secondary text-light" id="id_zona" name="id_zona">
                        <option value="">-- Sin Zona --</option>
                        <?php foreach($zonas as $z): ?>
                            <option value="<?= $z->id_zona ?>" <?= (($cliente->id_zona ?? null) === $z->id_zona) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($z->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="direccion" class="form-label text-light">Dirección</label>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="direccion" name="direccion" value="<?= htmlspecialchars($cliente->direccion ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="barrio" class="form-label text-light">Barrio / Localidad</label>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="barrio" name="barrio" value="<?= htmlspecialchars($cliente->barrio ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="referencias" class="form-label text-light">Referencias del domicilio / Notas adicionales</label>
                <textarea class="form-control bg-slate-900 border-secondary text-light" id="referencias" name="referencias" rows="3"><?= htmlspecialchars($cliente->referencias ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <label for="coordenadas_gps" class="form-label text-light">
                    Coordenadas GPS <span class="text-secondary small">— Opcional</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-slate-700 border-secondary">
                        <i class="bi bi-pin-map text-secondary"></i>
                    </span>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="coordenadas_gps" name="coordenadas_gps" value="<?= htmlspecialchars($cliente->coordenadas_gps ?? '') ?>" placeholder="-31.416,-64.183">
                </div>
            </div>

            <hr class="border-secondary mb-4">

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?= $appUrl ?>/clientes" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i> Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
