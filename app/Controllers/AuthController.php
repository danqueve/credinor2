<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Url;
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
            Url::redirect('/dashboard');
        }

        View::render('auth/login');
    }

    public function handleLogin(): void
    {
        if (Auth::isLoggedIn()) {
            Url::redirect('/dashboard');
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
            Url::redirect('/auth/totp');
        }

        if ($result['ok']) {
            $rol = $_SESSION['usuario_rol'] ?? 'cobrador';
            $dest = match ($rol) {
                'cliente'  => '/mi-cuenta',
                'cobrador' => '/consulta',
                default    => '/dashboard',
            };
            Url::redirect($dest);
        }

        View::render('auth/login', ['error' => $result['message'], 'username' => htmlspecialchars($username)]);
    }

    public function showTotp(): void
    {
        if (!Session::get('totp_pending_id')) {
            Url::redirect('/login');
        }
        View::render('auth/totp');
    }

    public function handleTotp(): void
    {
        if (!Session::get('totp_pending_id')) {
            Url::redirect('/login');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            View::render('auth/totp', ['error' => 'Error de seguridad CSRF.']);
            return;
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $result = $this->authService->verifyTotp($code);

        if ($result['ok']) {
            // TOTP solo es para admin
            Url::redirect('/dashboard');
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
            Url::redirect('/perfil/2fa');
        }
        $secret = Totp::generateSecret();
        Session::set('totp_setup_secret', $secret);
        Url::redirect('/perfil/2fa');
    }

    public function confirmarSetup2fa(): void
    {
        Auth::requireLogin();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            Url::redirect('/perfil/2fa');
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $secret = Session::get('totp_setup_secret') ?? '';

        if (!$secret || !Totp::verify($secret, $code)) {
            $_SESSION['flash_error'] = 'Código incorrecto. Intentá nuevamente.';
            Url::redirect('/perfil/2fa');
        }

        $idUsuario = (int)Session::get('usuario_id');
        $repo = new \App\Repositories\UsuarioRepository();
        $repo->saveTotpSecret($idUsuario, $secret);

        Session::set('totp_setup_secret', null);
        $_SESSION['flash_success'] = '2FA activado correctamente.';
        Url::redirect('/perfil/2fa');
    }

    public function desactivar2fa(): void
    {
        Auth::requireLogin();
        $token = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            Url::redirect('/perfil/2fa');
        }

        $code   = trim($_POST['totp_code'] ?? '');
        $result = $this->authService->desactivarTotp((int)Session::get('usuario_id'), $code);

        if ($result['ok']) {
            $_SESSION['flash_success'] = '2FA desactivado.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        Url::redirect('/perfil/2fa');
    }

    public function logout(): void
    {
        // Require POST for logout to prevent CSRF pre-fetching? 
        // For simplicity in Phase 1, we allow GET, but best practice is POST.
        $this->authService->logout();
        Url::redirect('/login');
    }
}
