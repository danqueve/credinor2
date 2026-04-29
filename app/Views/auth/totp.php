<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - Credinor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            max-width: 380px;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5 mx-3">
    <div class="text-center mb-4">
        <i class="bi bi-shield-lock-fill text-warning" style="font-size: 3rem;"></i>
        <h2 class="mt-2 fw-bold text-white">Verificación 2FA</h2>
        <p class="text-secondary small">Ingresá el código de 6 dígitos de tu app autenticadora.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $appUrl ?>/auth/totp" autocomplete="off">
        <?= \App\Helpers\Csrf::getFormField() ?>
        <div class="mb-4">
            <label for="totp_code" class="form-label text-light">Código TOTP</label>
            <input type="text" class="form-control form-control-lg bg-dark border-secondary text-light text-center fw-bold letter-spacing-wide"
                   id="totp_code" name="totp_code"
                   maxlength="6" pattern="\d{6}" inputmode="numeric"
                   placeholder="000000" autofocus required>
        </div>
        <button type="submit" class="btn btn-warning w-100 py-2 fw-bold">
            <i class="bi bi-shield-check me-1"></i> Verificar
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="<?= $appUrl ?>/login" class="text-secondary small">← Volver al inicio de sesión</a>
    </div>
</div>

</body>
</html>
