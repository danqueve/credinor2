<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;

class Audit
{
    /**
     * Registra una acción en la tabla de auditoría.
     */
    public static function log(string $accion, ?string $entidad = null, ?int $entidadId = null, ?array $datosAntes = null, ?array $datosDespues = null): void
    {
        $pdo = Database::getInstance();
        $userId = Session::get('usuario_id');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $antesJson   = $datosAntes ? json_encode($datosAntes, JSON_UNESCAPED_UNICODE) : null;
        $despuesJson = $datosDespues ? json_encode($datosDespues, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare("
            INSERT INTO auditoria (id_usuario, accion, entidad, entidad_id, datos_antes, datos_despues, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $accion,
            $entidad,
            $entidadId,
            $antesJson,
            $despuesJson,
            $ip,
            $userAgent
        ]);
    }
}
