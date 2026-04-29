<?php

declare(strict_types=1);

namespace App\Models;

class Credito
{
    public int $id_credito = 0;
    public string $codigo = '';
    public int $id_cliente = 0;
    public ?int $id_vendedor = null;
    public ?int $id_cobrador = null;
    public float $capital = 0.0;
    public int $cantidad_cuotas = 0;
    public float $valor_cuota = 0.0;
    public float $monto_total = 0.0;
    public float $interes_implicito = 0.0;
    public float $interes_implicito_pct = 0.0;
    public float $gastos_admin = 0.0;
    public string $frecuencia = 'semanal';
    public string $fecha_inicio = '';
    public ?string $fecha_fin_estimada = null;
    public float $saldo_pendiente = 0.0;
    public ?string $destino_opcional = null;
    public string $estado = 'activo';
    public ?int $id_credito_origen = null;
    public ?string $observaciones = null;
    public int $created_by = 0;
    public ?int $updated_by = null;
    public string $created_at = '';
    public ?string $updated_at = null;

    // Relaciones cargadas por JOIN
    public ?string $cliente_nombre = null;
    public ?string $cliente_dni = null;
    public ?string $vendedor_nombre = null;
    public ?string $cobrador_nombre = null;

    /** @var Cuota[] */
    public array $cuotas = [];

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'activo'      => 'success',
            'finalizado'  => 'secondary',
            'anulado'     => 'danger',
            'refinanciado' => 'warning',
            'incobrable'  => 'dark',
            default       => 'secondary',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'activo'      => 'Activo',
            'finalizado'  => 'Finalizado',
            'anulado'     => 'Anulado',
            'refinanciado' => 'Refinanciado',
            'incobrable'  => 'Incobrable',
            default       => $this->estado,
        };
    }
}
