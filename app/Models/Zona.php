<?php

declare(strict_types=1);

namespace App\Models;

class Zona
{
    public int $id_zona;
    public string $nombre;
    public ?int $id_cobrador_default;

    // Relaciones (se cargan al hidratar si es necesario)
    public ?string $cobrador_nombre = null;
}
