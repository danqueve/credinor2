<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Audit;
use App\Helpers\Session;
use App\Helpers\Totp;
use App\Repositories\UsuarioRepository;

class AuthService
{
    private UsuarioRepository $repo;

    public function __construct()
    {
        $this->repo = new UsuarioRepository();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function login(string $username, string $password): array
    {
        $user = $this->repo->findByUsername($username);

        if (!$user) {
            Audit::log('login.fail', null, null, null, ['username' => $username, 'reason' => 'user_not_found']);
            return ['ok' => false, 'message' => 'Credenciales inválidas.'];
        }

        if (!$user->activo) {
            Audit::log('login.fail', 'usuarios', $user->id_usuario, null, ['reason' => 'inactive']);
            return ['ok' => false, 'message' => 'Usuario inactivo.'];
        }

        if ($user->isBloqueado()) {
            Audit::log('login.fail', 'usuarios', $user->id_usuario, null, ['reason' => 'blocked']);
            return ['ok' => false, 'message' => 'Demasiados intentos fallidos. Intente más tarde.'];
        }

        if (!password_verify($password, $user->password_hash)) {
            $maxIntentos = (int)($_ENV['LOGIN_MAX_INTENTOS'] ?? 5);
            $bloqueoMinutos = (int)($_ENV['LOGIN_BLOQUEO_MINUTOS'] ?? 15);
            
            $this->repo->incrementIntentosFallidos($user->id_usuario, $maxIntentos, $bloqueoMinutos);
            Audit::log('login.fail', 'usuarios', $user->id_usuario, null, ['reason' => 'invalid_password']);
            
            return ['ok' => false, 'message' => 'Credenciales inválidas.'];
        }

        // Login exitoso — verificar si requiere TOTP
        if ($user->rol === 'admin' && $user->totp_secret !== null) {
            // Guardar datos temporales en sesión para el segundo paso
            Session::regenerate(true);
            Session::set('totp_pending_id',       $user->id_usuario);
            Session::set('totp_pending_nombre',   $user->username);
            Session::set('totp_pending_rol',      $user->rol);
            Session::set('totp_pending_personal', $user->id_personal);
            return ['ok' => true, 'totp_required' => true, 'message' => '2FA requerido.'];
        }

        $this->repo->updateLastLogin($user->id_usuario);

        Session::regenerate(true);
        Session::set('usuario_id',          $user->id_usuario);
        Session::set('usuario_nombre',      $user->username);
        Session::set('usuario_rol',         $user->rol);
        Session::set('usuario_personal_id', $user->id_personal);
        Session::set('usuario_cliente_id',  $user->id_cliente);

        Audit::log('login.success', 'usuarios', $user->id_usuario);

        return ['ok' => true, 'totp_required' => false, 'message' => 'Bienvenido.'];
    }

    /**
     * Verifica el código TOTP del segundo paso de login.
     * @return array{ok: bool, message: string}
     */
    public function verifyTotp(string $code): array
    {
        $idUsuario = Session::get('totp_pending_id');
        if (!$idUsuario) {
            return ['ok' => false, 'message' => 'Sesión expirada. Ingresá nuevamente.'];
        }

        $user = $this->repo->findById((int)$idUsuario);
        if (!$user || !$user->totp_secret) {
            Session::destroy();
            return ['ok' => false, 'message' => 'Error de sesión.'];
        }

        if (!Totp::verify($user->totp_secret, $code)) {
            Audit::log('login.totp_fail', 'usuarios', $user->id_usuario);
            return ['ok' => false, 'message' => 'Código incorrecto.'];
        }

        $this->repo->updateLastLogin($user->id_usuario);

        // Limpiar datos temporales y establecer sesión definitiva
        $nombre   = Session::get('totp_pending_nombre');
        $rol      = Session::get('totp_pending_rol');
        $personal = Session::get('totp_pending_personal');

        Session::regenerate(true);
        Session::set('usuario_id',          $user->id_usuario);
        Session::set('usuario_nombre',      $nombre);
        Session::set('usuario_rol',         $rol);
        Session::set('usuario_personal_id', $personal);
        Session::set('usuario_cliente_id',  $user->id_cliente);

        Audit::log('login.success', 'usuarios', $user->id_usuario);
        return ['ok' => true, 'message' => 'Bienvenido.'];
    }

    /**
     * Desactiva 2FA para el usuario dado.
     */
    public function desactivarTotp(int $idUsuario, string $code): array
    {
        $user = $this->repo->findById($idUsuario);
        if (!$user || !$user->totp_secret) {
            return ['ok' => false, 'message' => '2FA no está activado.'];
        }
        if (!Totp::verify($user->totp_secret, $code)) {
            return ['ok' => false, 'message' => 'Código incorrecto.'];
        }
        $this->repo->saveTotpSecret($idUsuario, null);
        return ['ok' => true, 'message' => '2FA desactivado.'];
    }

    public function logout(): void
    {
        if (Session::has('usuario_id')) {
            Audit::log('logout', 'usuarios', Session::get('usuario_id'));
        }
        Session::destroy();
    }
}
