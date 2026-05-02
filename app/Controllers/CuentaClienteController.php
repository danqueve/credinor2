<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Helpers\View;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;

/**
 * Vista de estado de cuenta para el rol "cliente".
 * Solo puede ver sus propios créditos y pagos.
 */
class CuentaClienteController
{
    private ClienteRepository $clienteRepo;
    private CreditoRepository $creditoRepo;
    private PagoRepository    $pagoRepo;

    public function __construct()
    {
        $this->clienteRepo = new ClienteRepository();
        $this->creditoRepo = new CreditoRepository();
        $this->pagoRepo    = new PagoRepository();
    }

    public function index(): void
    {
        Auth::requireCliente();

        $idCliente = (int)(Session::get('usuario_cliente_id') ?? 0);
        $cliente   = $this->clienteRepo->findById($idCliente);

        if (!$cliente) {
            Session::destroy();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login');
            exit;
        }

        $creditos = $this->creditoRepo->findByCliente($idCliente);

        View::render('mi_cuenta/index', [
            'titulo'   => 'Mi Cuenta',
            'cliente'  => $cliente,
            'creditos' => $creditos,
        ], layoutMobile: true);
    }
}
