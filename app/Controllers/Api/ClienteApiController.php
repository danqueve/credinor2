<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Sanitizer;
use App\Helpers\Auth;
use App\Repositories\ClienteRepository;

class ClienteApiController
{
    private ClienteRepository $clienteRepo;

    public function __construct()
    {
        $this->clienteRepo = new ClienteRepository();
    }

    /**
     * Endpoint para autocompletado de clientes
     * URL: /api/clientes/buscar?q=termino
     */
    public function search(): void
    {
        Auth::requireLogin();
        $term = Sanitizer::clean($_GET['q'] ?? '');
        
        if (strlen($term) < 2) {
            Response::json(true, []);
            return;
        }

        $resultados = $this->clienteRepo->searchByDniOrName($term);
        Response::json(true, $resultados);
    }
}
