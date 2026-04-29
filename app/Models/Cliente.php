<?php

declare(strict_types=1);

namespace App\Models;

class Cliente
{
    public int $id_cliente;
    public string $nombre;
    public string $dni;
    public ?string $direccion;
    public ?string $barrio;
    public ?string $telefono;
    public ?string $coordenadas_gps;
    public ?int $id_zona;
    public ?string $foto_url;
    public ?string $referencias;

    // Relaciones
    public ?string $zona_nombre = null;
}
