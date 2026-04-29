<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Repositories\RendicionRepository;

class RendicionApiController
{
    private RendicionRepository $repo;

    public function __construct()
    {
        $this->repo = new RendicionRepository();
    }

    /**
     * GET /api/rendiciones/buscar_credito?q=DNI_or_code
     * Busca créditos activos para el autocomplete de la grilla bulk.
     */
    public function buscarCredito(): void
    {
        Auth::requireLogin();

        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            Response::json(true, []);
            return;
        }

        $resultados = $this->repo->buscarCreditoActivo($q);
        Response::json(true, $resultados);
    }
}
