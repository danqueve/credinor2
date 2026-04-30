<?php
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$scriptDir = $scriptDir === '' ? '' : $scriptDir;
$appUrl = $_ENV['APP_URL'] ?? '';
if (empty($appUrl)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
}
$user = \App\Helpers\Auth::user();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? htmlspecialchars($titulo) . ' - ' : '' ?>Credinor</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- PWA -->
    <link rel="manifest" href="<?= $appUrl ?>/manifest.json">
    <meta name="theme-color" content="#0f172a">

    <!-- Custom CSS -->
    <link href="<?= $appUrl ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-slate-900 text-light">

    <div class="wrapper d-flex align-items-stretch">
        
        <!-- Sidebar -->
        <?php require APP_PATH . '/Views/layout/sidebar.php'; ?>

        <!-- Page Content  -->
        <div id="content" class="w-100">
            <!-- Navbar -->
            <?php require APP_PATH . '/Views/layout/navbar.php'; ?>

            <!-- Main Content -->
            <main class="container-fluid p-4">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Toast Container para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- App JS -->
    <script>
        const APP_URL = '<?= $appUrl ?>';
        const CSRF_TOKEN = '<?= \App\Helpers\Csrf::getToken() ?>';
    </script>
    <script src="<?= $appUrl ?>/assets/js/app.js"></script>

    <script>
        // Toggle Sidebar
        document.getElementById('sidebarCollapse')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
