<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CajaRepository;

class CajaService
{
    private CajaRepository $repo;

    public function __construct()
    {
        $this->repo = new CajaRepository();
    }

    public function registrar(array $data, int $userId): void
    {
        $tipo    = $data['tipo'] ?? '';
        $monto   = (float)($data['monto'] ?? 0);
        $concepto = trim($data['concepto'] ?? '');
        $fecha   = $data['fecha'] ?? date('Y-m-d');
        $obs     = trim($data['observaciones'] ?? '') ?: null;

        if (!in_array($tipo, ['ingreso', 'egreso'])) {
            throw new \InvalidArgumentException('Tipo inválido.');
        }
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }
        if ($concepto === '') {
            throw new \InvalidArgumentException('El concepto es obligatorio.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new \InvalidArgumentException('Fecha inválida.');
        }

        $this->repo->insertar($tipo, $monto, $concepto, $fecha, $obs, $userId);
    }

    public function eliminar(int $id): void
    {
        $this->repo->softDelete($id);
    }

    public function getRecientes(int $limit = 50): array
    {
        return $this->repo->getRecientes($limit);
    }

    public function getSaldoHistorico(float $cobradoTotal, float $prestadoTotal): float
    {
        $ingresos = $this->repo->getTotalIngresos();
        $egresos  = $this->repo->getTotalEgresos();
        return $cobradoTotal - $prestadoTotal + $ingresos - $egresos;
    }

    public function getTotalesEnRango(string $desde, string $hasta): array
    {
        return $this->repo->getTotalesEnRango($desde, $hasta);
    }
}
