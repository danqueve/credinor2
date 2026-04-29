<?php

declare(strict_types=1);

namespace App\Models;

class Rendicion
{
    public int $id_rendicion = 0;
    public int $id_cobrador = 0;
    public string $fecha_rendicion = '';
    public float $total_efectivo_declarado = 0.0;
    public float $total_transferencias_declarado = 0.0;
    public float $total_declarado = 0.0;   // columna STORED en MySQL
    public float $total_registrado = 0.0;
    public float $diferencia = 0.0;        // columna STORED en MySQL
    public string $estado = 'borrador';    // borrador | conciliada | con_diferencia
    public ?string $observaciones = null;
    public int $created_by = 0;
    public string $created_at = '';

    // Relaciones
    public ?string $cobrador_nombre = null;

    /** @var Pago[] */
    public array $pagos = [];

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'conciliada'     => 'success',
            'con_diferencia' => 'danger',
            default          => 'secondary',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'conciliada'     => 'Conciliada',
            'con_diferencia' => 'Con diferencia',
            default          => 'Borrador',
        };
    }
}
