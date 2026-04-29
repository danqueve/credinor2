<?php

declare(strict_types=1);

namespace App\Models;

class Cuota
{
    public int $id_cuota = 0;
    public int $id_credito = 0;
    public int $numero_cuota = 0;
    public string $fecha_vencimiento = '';
    public float $monto_esperado = 0.0;
    public float $monto_pagado = 0.0;
    public float $monto_recargo = 0.0;
    public string $estado = 'pendiente';
    public ?string $fecha_pagada = null;

    public function getSaldo(): float
    {
        return round($this->monto_esperado + $this->monto_recargo - $this->monto_pagado, 2);
    }

    public function getDiasAtraso(): int
    {
        if (in_array($this->estado, ['pagada', 'condonada'])) {
            return 0;
        }
        $hoy  = new \DateTime('today');
        $vence = new \DateTime($this->fecha_vencimiento);
        return $hoy > $vence ? (int)$hoy->diff($vence)->days : 0;
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'pagada'    => 'success',
            'parcial'   => 'warning',
            'vencida'   => 'danger',
            'condonada' => 'secondary',
            default     => 'info',  // pendiente
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'pagada'    => 'Pagada',
            'parcial'   => 'Parcial',
            'vencida'   => 'Vencida',
            'condonada' => 'Condonada',
            default     => 'Pendiente',
        };
    }
}
