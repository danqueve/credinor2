<?php

declare(strict_types=1);

namespace App\Models;

class Pago
{
    public int $id_pago = 0;
    public int $id_credito = 0;
    public ?int $id_cobrador = null;
    public float $monto_pagado = 0.0;
    public string $forma_pago = 'efectivo';
    public ?string $referencia_externa = null;
    public string $fecha_pago_real = '';
    public string $fecha_registro = '';
    public ?int $id_rendicion = null;
    public ?string $observaciones = null;
    public bool $anulado = false;
    public ?string $motivo_anulacion = null;
    public ?int $anulado_por = null;
    public ?string $anulado_at = null;
    public int $created_by = 0;
    public string $created_at = '';

    // Relaciones cargadas por JOIN
    public ?string $cobrador_nombre = null;
    public ?string $cliente_nombre = null;
    public ?string $cliente_dni = null;
    public ?string $credito_codigo = null;

    /** @var array<int, array{id_cuota: int, numero_cuota: int, monto_aplicado: float}> */
    public array $cuotasAplicadas = [];

    public function formasPagoLabel(): string
    {
        return match ($this->forma_pago) {
            'efectivo'      => 'Efectivo',
            'transferencia' => 'Transferencia',
            'mp'            => 'Mercado Pago',
            'otro'          => 'Otro',
            default         => $this->forma_pago,
        };
    }

    public function formaPagoBadge(): string
    {
        return match ($this->forma_pago) {
            'efectivo'      => 'success',
            'transferencia' => 'info',
            'mp'            => 'primary',
            default         => 'secondary',
        };
    }
}
