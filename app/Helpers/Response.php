<?php

declare(strict_types=1);

namespace App\Helpers;

class Response
{
    /**
     * Devuelve una respuesta JSON estandarizada.
     * { ok: bool, data: mixed, errors: string[], message?: string }
     */
    public static function json(bool $ok, mixed $data = null, array $errors = [], string $message = '', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'ok'     => $ok,
            'data'   => $data,
            'errors' => $errors
        ];

        if ($message !== '') {
            $response['message'] = $message;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
