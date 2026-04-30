<?php 
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start(); 
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-white"><?= htmlspecialchars($titulo) ?></h2>
        <p class="text-secondary small mb-0 mt-1">
            <i class="bi bi-people me-1"></i> Gestión de clientes activos
        </p>
    </div>
    <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
        <a href="<?= $appUrl ?>/clientes/nuevo" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Cliente
        </a>
    <?php endif; ?>
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

<!-- Buscador -->
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="card-body">
        <form action="<?= $appUrl ?>/clientes" method="GET" class="row g-3 align-items-center">
            <div class="col-12 col-md-8 col-lg-6 position-relative">
                <div class="input-group">
                    <span class="input-group-text bg-slate-700 border-secondary text-light">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control bg-slate-900 border-secondary text-light" id="buscador-clientes" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por DNI o Nombre..." autocomplete="off">
                    <?php if(!empty($search)): ?>
                        <a href="<?= $appUrl ?>/clientes" class="btn btn-outline-secondary border-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit">Buscar</button>
                </div>
                <!-- Resultados del Autocomplete (se maneja por JS) -->
                <ul class="list-group position-absolute w-100 mt-1 shadow d-none z-3" id="autocomplete-results" style="max-height: 250px; overflow-y: auto;"></ul>
            </div>
        </form>
    </div>
</div>

<!-- Listado -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre / Dirección</th>
                        <th>Contacto</th>
                        <th>Zona</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($clientes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">No se encontraron clientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($clientes as $c): ?>
                            <tr>
                                <td class="fw-bold text-info"><?= htmlspecialchars($c->dni) ?></td>
                                <td>
                                    <div class="fw-bold text-light"><?= htmlspecialchars($c->nombre) ?></div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($c->direccion ?? 'S/D') ?> 
                                        <?= $c->barrio ? ' - ' . htmlspecialchars($c->barrio) : '' ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($c->telefono ?? 'S/D') ?></td>
                                <td>
                                    <?php if($c->zona_nombre): ?>
                                        <span class="badge badge-zona"><?= htmlspecialchars($c->zona_nombre) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sin zona</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $appUrl ?>/clientes/ficha?id=<?= $c->id_cliente ?>" class="btn btn-sm btn-outline-light me-1" data-bs-toggle="tooltip" title="Ver Ficha">
                                        <i class="bi bi-person-vcard"></i>
                                    </a>
                                    <?php if($_SESSION['usuario_rol'] === 'admin'): ?>
                                        <a href="<?= $appUrl ?>/clientes/editar?id=<?= $c->id_cliente ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Paginación -->
    <?php if($totalPages > 1): ?>
    <div class="card-footer border-secondary bg-transparent py-3">
        <nav aria-label="Navegación de páginas">
            <ul class="pagination pagination-sm justify-content-center mb-0" data-bs-theme="dark">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link <?= ($i === $page) ? 'bg-primary border-primary' : 'bg-slate-700 border-secondary text-light' ?>" href="?q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('buscador-clientes');
    const results = document.getElementById('autocomplete-results');
    let debounceTimer;

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            results.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(async () => {
            const res = await apiCall(`/api/clientes/buscar?q=${encodeURIComponent(query)}`);
            if (res.ok && res.data.length > 0) {
                results.innerHTML = '';
                res.data.forEach(client => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action bg-slate-800 text-light border-secondary cursor-pointer';
                    li.innerHTML = `
                        <div class="fw-bold">${client.nombre}</div>
                        <div class="small text-secondary">DNI: ${client.dni} | ${client.direccion || ''}</div>
                    `;
                    li.style.cursor = 'pointer';
                    li.onclick = () => {
                        window.location.href = `<?= $appUrl ?>/clientes/ficha?id=${client.id_cliente}`;
                    };
                    results.appendChild(li);
                });
                results.classList.remove('d-none');
            } else {
                results.innerHTML = '<li class="list-group-item bg-slate-800 text-secondary border-secondary">No se encontraron clientes</li>';
                results.classList.remove('d-none');
            }
        }, 300); // 300ms debounce
    });

    // Cerrar autocomplete si se hace click afuera
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('d-none');
        }
    });

    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
});
</script>

<?php 
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php'; 
?>
