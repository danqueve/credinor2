<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\View;

class DashboardController
{
    private \App\Services\DashboardService $service;

    public function __construct()
    {
        $this->service = new \App\Services\DashboardService();
    }

    public function index(): void
    {
        \App\Helpers\Auth::requireLogin();

        $data = $this->service->getData();

        \App\Helpers\View::render('dashboard/index', [
            'stats'                => $data['stats'],
            'proximosVencimientos' => $data['proximosVencimientos'],
            'actividadReciente'    => $data['actividadReciente'],
            'cobranzaSemanal'      => $data['cobranzaSemanal'],
            'carteraPorEstado'     => $data['carteraPorEstado'],
            'agingResumen'         => $data['agingResumen'],
            'capitalResumen'       => $data['capitalResumen'],
            'titulo'               => 'Dashboard'
        ]);
    }
}
