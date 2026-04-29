<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Credinor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: #cbd5e1; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { text-align: center; max-width: 500px; }
        .error-code { font-size: 6rem; font-weight: bold; color: #3b82f6; line-height: 1; }
    </style>
</head>
<body>
    <div class="error-card p-4">
        <i class="bi bi-file-earmark-x text-secondary" style="font-size: 4rem;"></i>
        <div class="error-code mt-3 mb-2">404</div>
        <h3 class="text-white mb-3">Página no encontrada</h3>
        <p class="mb-4">Lo sentimos, la página que estás buscando no existe o fue movida.</p>
        <a href="<?= $appUrl ?>/" class="btn btn-primary px-4 py-2">
            <i class="bi bi-house me-2"></i> Volver al Inicio
        </a>
    </div>
</body>
</html>
