<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\Totp;
use App\Helpers\View;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }

        View::render('auth/login');
    }

    public function handleLogin(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }

        // CSRF ya validado por CsrfMiddleware en routes.php si lo mapeamos (pero por ahora lo llamamos manual o global)
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            $error = "Error de seguridad CSRF.";
            View::render('auth/login', ['error' => $error]);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Ingrese usuario y contraseña.";
            View::render('auth/login', ['error' => $error, 'username' => htmlspecialchars($username)]);
            return;
        }

        $result = $this->authService->login($username, $password);

        if ($result['ok'] && ($result['totp_required'] ?? false)) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/auth/totp');
            exit;
        }

        if ($result['ok']) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }

        View::render('auth/login', ['error' => $result['message'], 'username' => htmlspecialchars($username)]);
    }

    public function showTotp(): void
    {
        if (!Session::get('totp_pending_id')) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
            exit;
        }
        View::render('auth/totp');
    }

    public function handleTotp(): void
    {
        if (!Session::get('totp_pending_id')) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            View::render('auth/totp', ['error' => 'Error de seguridad CSRF.']);
            return;
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $result = $this->authService->verifyTotp($code);

        if ($result['ok']) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }

        View::render('auth/totp', ['error' => $result['message']]);
    }

    // ── Perfil 2FA setup ────────────────────────────────────────────────

    public function showPerfil2fa(): void
    {
        Auth::requireLogin();
        $idUsuario = (int)Session::get('usuario_id');
        $repo = new \App\Repositories\UsuarioRepository();
        $user = $repo->findById($idUsuario);

        // Si ya tiene secret, mostrar estado; si no, generar uno provisional en sesión
        $pending = Session::get('totp_setup_secret');
        View::render('auth/perfil_2fa', [
            'titulo'         => 'Configuración 2FA',
            'totp_activo'    => $user && $user->totp_secret !== null,
            'setup_secret'   => $pending,
            'qr_url'         => $pending ? Totp::getQrUrl($pending, $user->username ?? '') : null,
        ]);
    }

    public function iniciarSetup2fa(): void
    {
        Auth::requireLogin();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
            exit;
        }
        $secret = Totp::generateSecret();
        Session::set('totp_setup_secret', $secret);
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
        exit;
    }

    public function confirmarSetup2fa(): void
    {
        Auth::requireLogin();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
            exit;
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $secret = Session::get('totp_setup_secret') ?? '';

        if (!$secret || !Totp::verify($secret, $code)) {
            $_SESSION['flash_error'] = 'Código incorrecto. Intentá nuevamente.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
            exit;
        }

        $idUsuario = (int)Session::get('usuario_id');
        $repo = new \App\Repositories\UsuarioRepository();
        $repo->saveTotpSecret($idUsuario, $secret);

        Session::set('totp_setup_secret', null);
        $_SESSION['flash_success'] = '2FA activado correctamente.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
        exit;
    }

    public function desactivar2fa(): void
    {
        Auth::requireLogin();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
            exit;
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $result = $this->authService->desactivarTotp((int)Session::get('usuario_id'), $code);

        if ($result['ok']) {
            $_SESSION['flash_success'] = '2FA desactivado.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/perfil/2fa');
        exit;
    }

    public function logout(): void
    {
        // Require POST for logout to prevent CSRF pre-fetching? 
        // For simplicity in Phase 1, we allow GET, but best practice is POST.
        $this->authService->logout();
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
        exit;
    }
}
