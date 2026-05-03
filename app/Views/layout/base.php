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
        // Mobile: toggle sidebar overlay
        document.getElementById('sidebarCollapse')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Desktop: rail (icon-only) toggle
        (function () {
            const sidebar = document.getElementById('sidebar');
            const toggle  = document.getElementById('sidebarRailToggle');
            if (!sidebar || !toggle) return;

            let tooltips = [];

            function enableTooltips() {
                sidebar.querySelectorAll('.rail-item').forEach(function (el) {
                    const title = el.dataset.railTitle;
                    if (!title) return;
                    el.setAttribute('data-bs-original-title', title);
                    tooltips.push(new bootstrap.Tooltip(el, {
                        title: title,
                        placement: 'right',
                        trigger: 'hover',
                        boundary: 'window'
                    }));
                });
            }

            function disableTooltips() {
                tooltips.forEach(function (tt) { tt.dispose(); });
                tooltips = [];
                sidebar.querySelectorAll('.rail-item').forEach(function (el) {
                    el.removeAttribute('data-bs-original-title');
                });
            }

            function setRail(isRail) {
                if (isRail) {
                    sidebar.classList.add('sidebar-rail');
                    // Close open submenus
                    sidebar.querySelectorAll('.collapse.show').forEach(function (el) {
                        bootstrap.Collapse.getOrCreateInstance(el).hide();
                    });
                    enableTooltips();
                } else {
                    sidebar.classList.remove('sidebar-rail');
                    disableTooltips();
                }
            }

            // Restore persisted state
            setRail(localStorage.getItem('sidebarRail') === '1');

            toggle.addEventListener('click', function () {
                const next = !sidebar.classList.contains('sidebar-rail');
                setRail(next);
                localStorage.setItem('sidebarRail', next ? '1' : '0');
            });

            // In rail mode, submenu parents navigate directly to data-rail-href
            sidebar.querySelectorAll('[data-rail-href]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    if (sidebar.classList.contains('sidebar-rail')) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = el.dataset.railHref;
                    }
                });
            });
        })();
    </script>
</body>
</html>
