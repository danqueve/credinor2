<?php 
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start(); 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold"><?= htmlspecialchars($titulo) ?></h2>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 border-secondary" id="personalTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active text-light bg-transparent border-secondary border-bottom-0" id="zonas-tab" data-bs-toggle="tab" data-bs-target="#zonas" type="button" role="tab" aria-controls="zonas" aria-selected="true">
            <i class="bi bi-geo-alt me-2"></i> Zonas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-light bg-transparent border-secondary border-bottom-0" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="false">
            <i class="bi bi-people me-2"></i> Personal
        </button>
    </li>
</ul>

<div class="tab-content" id="personalTabsContent">
    <!-- Tab Zonas -->
    <div class="tab-pane fade show active" id="zonas" role="tabpanel" aria-labelledby="zonas-tab">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-light">Listado de Zonas</h5>
                <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                    <a href="<?= $appUrl ?>/zonas/nueva" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Nueva Zona
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Cobrador por Defecto</th>
                                <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                                    <th class="text-end">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($zonas)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No hay zonas registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($zonas as $z): ?>
                                    <tr>
                                        <td><?= $z->id_zona ?></td>
                                        <td class="fw-bold text-light"><?= htmlspecialchars($z->nombre) ?></td>
                                        <td>
                                            <?php if($z->cobrador_nombre): ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($z->cobrador_nombre) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                                            <td class="text-end">
                                                <a href="<?= $appUrl ?>/zonas/editar?id=<?= $z->id_zona ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Personal -->
    <div class="tab-pane fade" id="personal" role="tabpanel" aria-labelledby="personal-tab">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-light">Listado de Personal</h5>
                <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                    <a href="<?= $appUrl ?>/personal/nuevo" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Nuevo Empleado
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Rol Operativo</th>
                                <th>Zona Asignada</th>
                                <th>Comisión</th>
                                <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                                    <th class="text-end">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($personal)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary">No hay personal registrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($personal as $p): ?>
                                    <tr>
                                        <td class="fw-bold text-light">
                                            <?= htmlspecialchars($p->nombre) ?>
                                            <div class="small text-secondary"><i class="bi bi-telephone"></i> <?= htmlspecialchars($p->telefono ?? 'S/D') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($p->dni) ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= ucfirst($p->rol_operativo) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($p->zona_nombre ?? 'Ninguna') ?></td>
                                        <td><?= number_format($p->comision_pct, 2) ?>%</td>
                                        <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                                            <td class="text-end">
                                                <a href="<?= $appUrl ?>/personal/editar?id=<?= $p->id_personal ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Recordar el tab activo
    document.addEventListener("DOMContentLoaded", function() {
        let activeTab = localStorage.getItem('activePersonalTab');
        if (activeTab) {
            let tabElement = document.querySelector(`button[data-bs-target="${activeTab}"]`);
            if (tabElement) {
                let tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
        
        const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabElms.forEach(t => {
            t.addEventListener('shown.bs.tab', event => {
                localStorage.setItem('activePersonalTab', event.target.getAttribute('data-bs-target'));
            });
        });
    });
</script>

<?php 
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php'; 
?>
