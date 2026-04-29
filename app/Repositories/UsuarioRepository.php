<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Usuario;
use PDO;

class UsuarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?Usuario
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $user = new Usuario();
        $user->id_usuario = (int)$row['id_usuario'];
        $user->username = $row['username'];
        $user->password_hash = $row['password_hash'];
        $user->rol = $row['rol'];
        $user->id_personal = $row['id_personal'] ? (int)$row['id_personal'] : null;
        $user->activo = (bool)$row['activo'];
        $user->ultimo_login = $row['ultimo_login'];
        $user->intentos_fallidos = (int)$row['intentos_fallidos'];
        $user->bloqueado_hasta = $row['bloqueado_hasta'];
        $user->totp_secret     = $row['totp_secret'] ?? null;

        return $user;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET ultimo_login = NOW(), intentos_fallidos = 0, bloqueado_hasta = NULL 
            WHERE id_usuario = ?
        ");
        $stmt->execute([$id]);
    }

    public function saveTotpSecret(int $id, ?string $secret): void
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET totp_secret = ? WHERE id_usuario = ?");
        $stmt->execute([$secret, $id]);
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $user = new Usuario();
        $user->id_usuario        = (int)$row['id_usuario'];
        $user->username          = $row['username'];
        $user->password_hash     = $row['password_hash'];
        $user->rol               = $row['rol'];
        $user->id_personal       = $row['id_personal'] ? (int)$row['id_personal'] : null;
        $user->activo            = (bool)$row['activo'];
        $user->ultimo_login      = $row['ultimo_login'];
        $user->intentos_fallidos = (int)$row['intentos_fallidos'];
        $user->bloqueado_hasta   = $row['bloqueado_hasta'];
        $user->totp_secret       = $row['totp_secret'] ?? null;
        return $user;
    }

    public function incrementIntentosFallidos(int $id, int $maxIntentos, int $bloqueoMinutos): void
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET intentos_fallidos = intentos_fallidos + 1,
                bloqueado_hasta = CASE 
                    WHEN intentos_fallidos + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                    ELSE bloqueado_hasta 
                END
            WHERE id_usuario = ?
        ");
        $stmt->execute([$maxIntentos, $bloqueoMinutos, $id]);
    }
}
