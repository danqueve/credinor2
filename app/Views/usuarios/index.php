<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-white"><?= htmlspecialchars($titulo) ?></h2>
        <p class="text-secondary small mb-0 mt-1">
            <i class="bi bi-shield-lock me-1"></i><?= count($usuarios) ?> usuarios registrados
        </p>
    </div>
    <a href="<?= $appUrl ?>/usuarios/nuevo" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
    </a>
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
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card bg-slate-800 border-secondary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Vinculado a</th>
                        <th class="text-center">Estado</th>
                        <th class="text-secondary small">Último acceso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class="bi bi-person-x d-block fs-2 mb-2"></i>No hay usuarios.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u):
                        $rolBadge = match ($u['rol']) {
                            'admin'    => ['badge-activo', 'Admin'],
                            'cobrador' => ['badge-refinanciado', 'Cobrador'],
                            'cliente'  => ['bg-info bg-opacity-20 text-info border border-info border-opacity-25', 'Cliente'],
                            default    => ['bg-secondary', $u['rol']],
                        };
                        $vinculo = match ($u['rol']) {
                            'cobrador' => $u['personal_nombre'] ?? '—',
                            'cliente'  => ($u['cliente_nombre'] ?? '—') . ($u['cliente_dni'] ? ' · DNI ' . $u['cliente_dni'] : ''),
                            default    => '—',
                        };
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-light"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="small text-secondary">#<?= $u['id_usuario'] ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $rolBadge[0] ?>"><?= $rolBadge[1] ?></span>
                        </td>
                        <td class="small text-secondary"><?= htmlspecialchars($vinculo) ?></td>
                        <td class="text-center">
                            <?php if ($u['activo']): ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-vencido">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-secondary">
                            <?= $u['ultimo_login'] ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : '—' ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= $appUrl ?>/usuarios/editar?id=<?= $u['id_usuario'] ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   data-bs-toggle="tooltip" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= $appUrl ?>/usuarios/delete"
                                      onsubmit="return confirm('¿Eliminar el usuario \'<?= htmlspecialchars(addslashes($u['username'])) ?>\'?')">
                                    <?= \App\Helpers\Csrf::getFormField() ?>
                                    <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
