<?php

declare(strict_types=1);

namespace App\Models;

class Personal
{
    public int $id_personal;
    public string $nombre;
    public string $dni;
    public ?string $telefono;
    public string $rol_operativo; // 'vendedor','cobrador','ambos'
    public ?int $id_zona;
    public float $comision_pct;
    public string $estado; // 'activo','inactivo'

    // Relaciones
    public ?string $zona_nombre = null;
}
