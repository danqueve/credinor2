<?php
ob_start();
?>
<div class="text-center py-5 px-4">
    <i class="bi bi-wifi-off text-secondary" style="font-size: 3.5rem;"></i>
    <h5 class="text-light mt-3 mb-2">Sin conexión</h5>
    <p class="text-secondary small mb-4">
        No hay red disponible. Si ya visitaste las páginas antes, podés seguir navegando con los datos guardados.
    </p>
    <button onclick="location.reload()" class="btn btn-outline-info btn-sm me-2">
        <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
    </button>
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
