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

    /** Solo usuarios del sistema (admin/cobrador) — los clientes se gestionan automáticamente */
    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT u.*
            FROM usuarios u
            WHERE u.deleted_at IS NULL
              AND u.rol IN ('admin','supervisor','cobrador')
            ORDER BY FIELD(u.rol,'admin','supervisor','cobrador'), u.apellido ASC, u.nombre ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUsername(string $username): ?Usuario
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByPersonal(int $idPersonal): ?Usuario
    {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios
            WHERE id_personal = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$idPersonal]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByCliente(int $idCliente): ?Usuario
    {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios
            WHERE id_cliente = ? AND rol = 'cliente' AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$idCliente]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function insert(string $username, string $passwordHash, string $rol, ?int $idPersonal, ?int $idCliente, bool $activo, ?string $apellido = null, ?string $nombre = null, ?string $dni = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (username, apellido, nombre, dni, password_hash, rol, id_personal, id_cliente, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $apellido, $nombre, $dni, $passwordHash, $rol, $idPersonal, $idCliente, $activo ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $username, ?string $passwordHash, string $rol, ?int $idPersonal, ?int $idCliente, bool $activo, ?string $apellido = null, ?string $nombre = null, ?string $dni = null): void
    {
        if ($passwordHash !== null) {
            $stmt = $this->db->prepare("
                UPDATE usuarios
                SET username=?, apellido=?, nombre=?, dni=?, password_hash=?, rol=?, id_personal=?, id_cliente=?, activo=?
                WHERE id_usuario=?
            ");
            $stmt->execute([$username, $apellido, $nombre, $dni, $passwordHash, $rol, $idPersonal, $idCliente, $activo ? 1 : 0, $id]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE usuarios
                SET username=?, apellido=?, nombre=?, dni=?, rol=?, id_personal=?, id_cliente=?, activo=?
                WHERE id_usuario=?
            ");
            $stmt->execute([$username, $apellido, $nombre, $dni, $rol, $idPersonal, $idCliente, $activo ? 1 : 0, $id]);
        }
    }

    public function softDelete(int $id): void
    {
        $this->db->prepare("UPDATE usuarios SET deleted_at = NOW() WHERE id_usuario = ?")
                 ->execute([$id]);
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->prepare("
            UPDATE usuarios
            SET ultimo_login = NOW(), intentos_fallidos = 0, bloqueado_hasta = NULL
            WHERE id_usuario = ?
        ")->execute([$id]);
    }

    public function saveTotpSecret(int $id, ?string $secret): void
    {
        $this->db->prepare("UPDATE usuarios SET totp_secret = ? WHERE id_usuario = ?")
                 ->execute([$secret, $id]);
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

    private function hydrate(array $row): Usuario
    {
        $user = new Usuario();
        $user->id_usuario        = (int)$row['id_usuario'];
        $user->username          = $row['username'];
        $user->apellido          = $row['apellido']       ?? null;
        $user->nombre            = $row['nombre']         ?? null;
        $user->dni               = $row['dni']            ?? null;
        $user->password_hash     = $row['password_hash'];
        $user->rol               = $row['rol'];
        $user->id_personal       = isset($row['id_personal']) ? (int)$row['id_personal'] : null;
        $user->id_cliente        = isset($row['id_cliente'])  ? (int)$row['id_cliente']  : null;
        $user->activo            = (bool)$row['activo'];
        $user->ultimo_login      = $row['ultimo_login']      ?? null;
        $user->intentos_fallidos = (int)($row['intentos_fallidos'] ?? 0);
        $user->bloqueado_hasta   = $row['bloqueado_hasta']   ?? null;
        $user->totp_secret       = $row['totp_secret']       ?? null;
        return $user;
    }
}
