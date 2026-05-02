<?php

declare(strict_types=1);

namespace App\Models;

class Usuario
{
    public int $id_usuario;
    public string $username;
    public ?string $apellido = null;
    public ?string $nombre   = null;
    public ?string $dni      = null;
    public string $password_hash;
    public string $rol;
    public ?int $id_personal;
    public ?int $id_cliente;
    public bool $activo;
    public ?string $ultimo_login;
    public int $intentos_fallidos;
    public ?string $bloqueado_hasta;
    public ?string $totp_secret = null;

    public function isBloqueado(): bool
    {
        if (!$this->bloqueado_hasta) {
            return false;
        }
        return strtotime($this->bloqueado_hasta) > time();
    }
}
