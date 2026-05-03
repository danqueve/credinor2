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

    // Stats calculadas en listado
    public float $saldo_total     = 0.0;
    public int $cuotas_vencidas   = 0;
    public int $creditos_activos  = 0;
    public int $total_pagos       = 0;
    // Crédito principal activo
    public ?int $id_credito       = null;
    public ?string $credito_codigo = null;
    public float $credito_saldo   = 0.0;
    public int $cuotas_pagadas    = 0;
    public int $cuotas_total      = 0;
    public ?float $monto_cuota    = null;
    public ?string $proxima_cuota = null;
}
