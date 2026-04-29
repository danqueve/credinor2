<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    private DashboardRepository $repo;

    public function __construct()
    {
        $this->repo = new DashboardRepository();
    }

    public function getData(): array
    {
        return [
            'stats'                => $this->repo->getGlobalStats(),
            'proximosVencimientos' => $this->repo->getProximosVencimientos(5),
            'actividadReciente'    => $this->repo->getActividadReciente(5),
            'cobranzaSemanal'      => $this->repo->getCobranzaSemanal(),
            'carteraPorEstado'     => $this->repo->getCarteraPorEstado(),
            'agingResumen'         => $this->repo->getAgingResumen(),
            'capitalResumen'       => $this->repo->getCapitalResumen(),
        ];
    }
}
