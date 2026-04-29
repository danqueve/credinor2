<?php
$appUrl = $_ENV['APP_URL'] ?? '';
$user   = \App\Helpers\Auth::user();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($titulo) ? htmlspecialchars($titulo) . ' — ' : '' ?>Credinor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link rel="manifest" href="<?= $appUrl ?>/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link href="<?= $appUrl ?>/assets/css/app.css" rel="stylesheet">
    <style>
        body { padding-bottom: 70px; }
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
            background: #0f172a; border-top: 1px solid #334155;
            display: flex; justify-content: space-around; align-items: center;
            height: 60px;
        }
        .bottom-nav a {
            flex: 1; display: flex; flex-direction: column; align-items: center;
            justify-content: center; color: #64748b; text-decoration: none;
            font-size: 0.65rem; gap: 2px; padding: 6px 0;
            transition: color .15s;
        }
        .bottom-nav a.active, .bottom-nav a:hover { color: #38bdf8; }
        .bottom-nav a i { font-size: 1.35rem; }
        .page-header {
            background: #0f172a; padding: 12px 16px 10px;
            border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 100;
        }
        .card { border-radius: 12px; }
        .list-item-touch { min-height: 56px; display: flex; align-items: center; }
    </style>
</head>
<body class="bg-slate-900 text-light">

<!-- Header superior -->
<div class="page-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <span class="fw-bold text-info" style="font-size: 1.1rem;">Credinor</span>
        <?php if (isset($titulo)): ?>
            <span class="text-secondary">·</span>
            <span class="text-light" style="font-size: 0.95rem;"><?= htmlspecialchars($titulo) ?></span>
        <?php endif; ?>
    </div>
    <a href="<?= $appUrl ?>/logout" class="btn btn-sm btn-outline-secondary py-1 px-2">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</div>

<!-- Contenido principal -->
<main class="container-fluid px-3 py-3">
    <?= $content ?? '' ?>
</main>

<!-- Navegación inferior (bottom nav) -->
<nav class="bottom-nav">
    <?php $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
    <a href="<?= $appUrl ?>/consulta" class="<?= str_ends_with($path, '/consulta') ? 'active' : '' ?>">
        <i class="bi bi-house-door"></i>
        <span>Inicio</span>
    </a>
    <a href="<?= $appUrl ?>/consulta/buscar" class="<?= str_contains($path, '/buscar') ? 'active' : '' ?>">
        <i class="bi bi-search"></i>
        <span>Buscar</span>
    </a>
    <a href="<?= $appUrl ?>/consulta/buscar" class="<?= str_contains($path, '/ficha') ? 'active' : '' ?>">
        <i class="bi bi-person-lines-fill"></i>
        <span>Clientes</span>
    </a>
    <a href="<?= $appUrl ?>/logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Salir</span>
    </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const APP_URL = '<?= $appUrl ?>';
    const CSRF_TOKEN = '<?= \App\Helpers\Csrf::getToken() ?>';
    // Registrar service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(APP_URL + '/service-worker.js').catch(() => {});
    }
</script>
</body>
</html>
