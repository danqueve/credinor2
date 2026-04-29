<?php

declare(strict_types=1);

namespace App\Services;

use DateTime;

class CuotaCalendarioService
{
    /** @var array<string, bool> */
    private array $diasCobranza;

    public function __construct()
    {
        $config = require APP_PATH . '/../config/app.php';
        $this->diasCobranza = $config['dias_cobranza'] ?? [
            'lunes'     => true,
            'martes'    => true,
            'miercoles' => true,
            'jueves'    => true,
            'viernes'   => true,
            'sabado'    => true,
            'domingo'   => false,
        ];
    }

    /**
     * Genera el calendario de cuotas completo.
     *
     * @return array<int, array{numero_cuota: int, fecha_vencimiento: string, monto_esperado: float}>
     */
    public function generar(string $fechaInicio, int $cantidad, string $frecuencia, float $valorCuota): array
    {
        $cuotas = [];
        $fecha  = new DateTime($fechaInicio);

        // Primera cuota: si es diaria, ajustar al primer día de cobranza válido
        if ($frecuencia === 'diaria') {
            $fecha = $this->ajustarDiaCobranza($fecha);
        }

        for ($i = 1; $i <= $cantidad; $i++) {
            if ($i > 1) {
                $fecha = $this->avanzar(clone $fecha, $frecuencia);
            }
            $cuotas[] = [
                'numero_cuota'      => $i,
                'fecha_vencimiento' => $fecha->format('Y-m-d'),
                'monto_esperado'    => $valorCuota,
            ];
        }

        return $cuotas;
    }

    /**
     * Retorna solo la fecha de la última cuota (para fecha_fin_estimada).
     */
    public function calcularFechaFin(string $fechaInicio, int $cantidad, string $frecuencia): string
    {
        $calendario = $this->generar($fechaInicio, $cantidad, $frecuencia, 0.0);
        return end($calendario)['fecha_vencimiento'];
    }

    private function avanzar(DateTime $fecha, string $frecuencia): DateTime
    {
        switch ($frecuencia) {
            case 'diaria':
                $fecha->modify('+1 day');
                $fecha = $this->ajustarDiaCobranza($fecha);
                break;
            case 'semanal':
                $fecha->modify('+7 days');
                break;
            case 'quincenal':
                $fecha->modify('+15 days');
                break;
            case 'mensual':
                $fecha->modify('+1 month');
                break;
        }
        return $fecha;
    }

    private function ajustarDiaCobranza(DateTime $fecha): DateTime
    {
        // Mapa numérico de PHP (0=domingo…6=sábado) a nuestras claves de config
        $mapa = [
            0 => 'domingo',
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
        ];

        for ($iter = 0; $iter < 14; $iter++) {
            $dia = $mapa[(int)$fecha->format('w')] ?? '';
            if (!empty($this->diasCobranza[$dia])) {
                break;
            }
            $fecha->modify('+1 day');
        }

        return $fecha;
    }
}
