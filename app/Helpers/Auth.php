<?php

declare(strict_types=1);

namespace App\Helpers;

class Auth
{
    public static function isLoggedIn(): bool
    {
        return Session::has('usuario_id');
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            if (self::isAjax()) {
                Response::json(false, null, ['No autenticado'], 'Acceso denegado', 401);
            } else {
                header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
                exit;
            }
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (Session::get('usuario_rol') !== 'admin') {
            self::denyAdmin();
        }
    }

    public static function requireAdminReadOnly(): void
    {
        self::requireLogin();
        if (!in_array(Session::get('usuario_rol'), ['admin', 'supervisor'], true)) {
            self::denyAdmin();
        }
    }

    public static function canManage(): bool
    {
        return Session::get('usuario_rol') === 'admin';
    }

    public static function canAdminRead(): bool
    {
        return in_array(Session::get('usuario_rol'), ['admin', 'supervisor'], true);
    }

    /** Permite admin y cobrador — bloquea clientes */
    public static function requireCobrador(): void
    {
        self::requireLogin();
        $rol = Session::get('usuario_rol');
        if (!in_array($rol, ['admin', 'supervisor', 'cobrador'], true)) {
            if ($rol === 'cliente') {
                header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/mi-cuenta');
                exit;
            }
            http_response_code(403);
            require APP_PATH . '/Views/errors/403.php';
            exit;
        }
    }

    /** Solo rol cliente — redirige al dashboard si es admin/cobrador */
    public static function requireCliente(): void
    {
        self::requireLogin();
        if (Session::get('usuario_rol') !== 'cliente') {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/dashboard');
            exit;
        }
    }

    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id'          => Session::get('usuario_id'),
            'username'    => Session::get('usuario_username') ?? Session::get('usuario_nombre'),
            'nombre'      => Session::get('usuario_nombre'),
            'rol'         => Session::get('usuario_rol'),
            'id_personal' => Session::get('usuario_personal_id'),
            'id_cliente'  => Session::get('usuario_cliente_id'),
        ];
    }

    private static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private static function denyAdmin(): void
    {
        if (self::isAjax()) {
            Response::json(false, null, ['Requiere permisos de administrador'], 'Acceso denegado', 403);
        } else {
            http_response_code(403);
            require APP_PATH . '/Views/errors/403.php';
            exit;
        }
    }
}
