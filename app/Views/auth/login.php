<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Credinor</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $appUrl ?>/assets/css/app.css" rel="stylesheet">
    <link rel="manifest" href="<?= $appUrl ?>/manifest.json">
    <style>
        body {
            background-color: #0f172a; /* Slate 900 */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background-color: #1e293b; /* Slate 800 */
            border: 1px solid #334155;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
        }
        .btn-primary {
            background-color: #3b82f6; /* Blue 500 */
            border-color: #3b82f6;
        }
        .btn-primary:hover {
            background-color: #2563eb; /* Blue 600 */
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5 mx-3">
    <div class="text-center mb-4">
        <i class="bi bi-wallet2 text-primary" style="font-size: 3rem;"></i>
        <h2 class="mt-2 fw-bold text-white">Credinor</h2>
        <p class="text-secondary">Gestión de Préstamos</p>
    </div>

    <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $appUrl ?>/login">
        <?= \App\Helpers\Csrf::getFormField() ?>
        
        <div class="mb-3">
            <label for="username" class="form-label text-light">Usuario</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control bg-dark border-secondary text-light" id="username" name="username" 
                       value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label text-light">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-key"></i></span>
                <input type="password" class="form-control bg-dark border-secondary text-light" id="password" name="password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            Ingresar <i class="bi bi-box-arrow-in-right ms-1"></i>
        </button>
    </form>
</div>

</body>
</html>
