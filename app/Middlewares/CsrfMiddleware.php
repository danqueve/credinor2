<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\Csrf;
use App\Helpers\Response;

class CsrfMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!Csrf::validate($token)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    Response::json(false, null, ['Token CSRF inválido'], 'Error de seguridad', 403);
                } else {
                    http_response_code(403);
                    die("Error de seguridad: Token CSRF inválido.");
                }
            }
        }
    }
}
