<?php

declare(strict_types=1);

namespace App\Models;

class Personal
{
    public int $id_personal    = 0;
    public string $nombre      = '';
    public string $dni         = '';
    public ?string $telefono   = null;
    public string $rol_operativo = 'vendedor';
    public ?int $id_zona       = null;
    public float $comision_pct = 0.0;
    public string $estado      = 'activo';

    // Relaciones
    public ?string $zona_nombre = null;
}
