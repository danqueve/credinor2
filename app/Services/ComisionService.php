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
     * Calcula y persiste las comisiones del rango de fechas.
     * Si ya existía una liquidación sin pagar para ese rango, la borra y recalcula.
     */
    public function liquidar(string $desde, string $hasta): array
    {
        $periodo = $desde . '_' . $hasta;
        $this->repo->deletePorPeriodo($periodo);

        $filas = 0;
        $totalComision = 0.0;

        foreach ($this->repo->getCobranzaPorRango($desde, $hasta) as $row) {
            $pct      = (float)$row['comision_pct'];
            $base     = (float)$row['monto_cobrado'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) continue;
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

        foreach ($this->repo->getVentaPorRango($desde, $hasta) as $row) {
            $pct      = (float)$row['comision_pct'];
            $base     = (float)$row['monto_vendido'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) continue;
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

    /**
     * Calcula comisiones en tiempo real para el rango (sin persistir).
     */
    public function getComisionesPorRango(string $desde, string $hasta): array
    {
        $rows = [];
        foreach ($this->repo->getCobranzaPorRango($desde, $hasta) as $row) {
            $pct      = (float)$row['comision_pct'];
            $base     = (float)$row['monto_cobrado'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) continue;
            $rows[] = [
                'personal_nombre' => $row['nombre'],
                'tipo'            => 'cobranza',
                'monto_base'      => $base,
                'pct'             => $pct,
                'monto_comision'  => $comision,
                'pagada'          => false,
                'id_comision'     => null,
            ];
        }
        foreach ($this->repo->getVentaPorRango($desde, $hasta) as $row) {
            $pct      = (float)$row['comision_pct'];
            $base     = (float)$row['monto_vendido'];
            $comision = round($base * $pct / 100, 2);
            if ($comision <= 0) continue;
            $rows[] = [
                'personal_nombre' => $row['nombre'],
                'tipo'            => 'venta',
                'monto_base'      => $base,
                'pct'             => $pct,
                'monto_comision'  => $comision,
                'pagada'          => false,
                'id_comision'     => null,
            ];
        }
        // Ordenar por tipo, luego nombre
        usort($rows, fn($a, $b) => [$a['tipo'], $a['personal_nombre']] <=> [$b['tipo'], $b['personal_nombre']]);
        return $rows;
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
