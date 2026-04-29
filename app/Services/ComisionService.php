<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ComisionRepository;

class ComisionService
{
    private ComisionRepository $repo;

    public function __construct()
    {
        $this->repo = new ComisionRepository();
    }

    /**
     * Calcula y persiste las comisiones del período dado.
     * Si ya existía una liquidación sin pagar, la borra y recalcula.
     * Devuelve el resumen de filas insertadas.
     */
    public function liquidar(string $periodo): array
    {
        // Elimina liquidaciones previas no pagadas para reliquidar
        $this->repo->deletePorPeriodo($periodo);

        $filas = 0;
        $totalComision = 0.0;

        // Comisiones de cobranza
        foreach ($this->repo->getCobranzaPorPeriodo($periodo) as $row) {
            $pct    = (float)$row['comision_pct'];
            $base   = (float)$row['monto_cobrado'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) {
                continue;
            }
            $this->repo->insertar([
                'id_personal'    => (int)$row['id_cobrador'],
                'periodo'        => $periodo,
                'tipo'           => 'cobranza',
                'monto_base'     => $base,
                'pct'            => $pct,
                'monto_comision' => $comision,
            ]);
            $totalComision += $comision;
            $filas++;
        }

        // Comisiones de venta
        foreach ($this->repo->getVentaPorPeriodo($periodo) as $row) {
            $pct    = (float)$row['comision_pct'];
            $base   = (float)$row['monto_vendido'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) {
                continue;
            }
            $this->repo->insertar([
                'id_personal'    => (int)$row['id_vendedor'],
                'periodo'        => $periodo,
                'tipo'           => 'venta',
                'monto_base'     => $base,
                'pct'            => $pct,
                'monto_comision' => $comision,
            ]);
            $totalComision += $comision;
            $filas++;
        }

        return [
            'periodo'        => $periodo,
            'filas'          => $filas,
            'total_comision' => $totalComision,
        ];
    }

    public function getLiquidacion(string $periodo): array
    {
        return $this->repo->getLiquidacion($periodo);
    }

    public function getPeriodosLiquidados(): array
    {
        return $this->repo->getPeriodosLiquidados();
    }

    public function marcarPagada(int $idComision): void
    {
        $this->repo->marcarPagada($idComision);
    }
}
