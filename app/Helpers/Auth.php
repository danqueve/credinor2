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
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
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
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                Response::json(false, null, ['Requiere permisos de administrador'], 'Acceso denegado', 403);
            } else {
                http_response_code(403);
                require APP_PATH . '/Views/errors/403.php';
                exit;
            }
        }
    }

    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id'          => Session::get('usuario_id'),
            'username'    => Session::get('usuario_nombre'),
            'rol'         => Session::get('usuario_rol'),
            'id_personal' => Session::get('usuario_personal_id'),
        ];
    }
}
