<?php 
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start(); 
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-1 text-white fw-bold d-flex align-items-center">
                    <i class="bi bi-map text-primary me-2"></i>
                    <?= htmlspecialchars($titulo) ?>
                </h2>
                <p class="text-secondary mb-0">Defina los detalles de la nueva zona de trabajo.</p>
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
            <div class="card-body p-4 p-md-5">
                <form action="<?= $appUrl ?>/zonas/<?= $action ?>" method="POST">
                    <?= \App\Helpers\Csrf::getFormField() ?>
                    
                    <div class="mb-4">
                        <label for="nombre" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Nombre de la Zona <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-pin-map"></i></span>
                            <input type="text" class="form-control bg-slate-900 border-secondary text-light fs-6" id="nombre" name="nombre" value="<?= htmlspecialchars($zona->nombre ?? '') ?>" placeholder="Ej: Zona Norte" required autofocus>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="id_cobrador_default" class="form-label text-light-50 small fw-bold text-uppercase tracking-wider">Cobrador por Defecto</label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary"><i class="bi bi-person-badge"></i></span>
                            <select class="form-select bg-slate-900 border-secondary text-light fs-6" id="id_cobrador_default" name="id_cobrador_default">
                                <option value="">-- Seleccionar Cobrador (Opcional) --</option>
                                <?php foreach($personal as $p): ?>
                                    <option value="<?= $p->id_personal ?>" <?= ($zona->id_cobrador_default === $p->id_personal) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p->nombre) ?> (<?= htmlspecialchars($p->rol_operativo) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-text text-secondary mt-2"><i class="bi bi-info-circle me-1"></i> Este cobrador se asignará por defecto a los clientes nuevos de esta zona.</div>
                    </div>

                    <div class="mt-4 pt-3 d-flex justify-content-end border-top border-secondary">
                        <a href="<?= $appUrl ?>/personal" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Guardar Zona
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
