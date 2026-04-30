<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Sanitizer;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\ZonaRepository;

class ClienteController
{
    private ClienteRepository  $clienteRepo;
    private ZonaRepository    $zonaRepo;
    private CreditoRepository $creditoRepo;

    public function __construct()
    {
        $this->clienteRepo  = new ClienteRepository();
        $this->zonaRepo     = new ZonaRepository();
        $this->creditoRepo  = new CreditoRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = Sanitizer::clean($_GET['q'] ?? '');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $clientes = $this->clienteRepo->findAll($limit, $offset, $search);
        $total = $this->clienteRepo->countAll($search);
        $totalPages = ceil($total / $limit);

        View::render('clientes/index', [
            'titulo'     => 'Gestión de Clientes',
            'clientes'   => $clientes,
            'search'     => $search,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

    public function ficha(): void
    {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = $this->clienteRepo->findById($id);

        if (!$cliente) {
            $_SESSION['flash_error'] = 'Cliente no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes');
            exit;
        }

        $creditos = $this->creditoRepo->findByCliente($id);

        View::render('clientes/ficha', [
            'titulo'   => 'Ficha del Cliente',
            'cliente'  => $cliente,
            'creditos' => $creditos,
        ]);
    }

    // A partir de aquí, acciones que serán protegidas por RoleMiddleware (solo admin)

    public function create(): void
    {
        Auth::requireAdmin();
        $zonas = $this->zonaRepo->findAll();
        View::render('clientes/form', [
            'titulo' => 'Nuevo Cliente',
            'cliente' => new Cliente(),
            'zonas' => $zonas,
            'action' => 'store'
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $cliente = new Cliente();
        $this->fillFromPost($cliente);

        if (empty($cliente->nombre) || empty($cliente->dni)) {
            $_SESSION['flash_error'] = 'Nombre y DNI son obligatorios.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/nuevo');
            exit;
        }

        try {
            $id = $this->clienteRepo->insert($cliente);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['flash_error'] = "Ya existe un cliente registrado con el DNI {$cliente->dni}.";
            } else {
                $_SESSION['flash_error'] = 'Error al guardar el cliente. Intente nuevamente.';
            }
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/nuevo');
            exit;
        }

        Audit::log('crear_cliente', 'clientes', $id);

        $_SESSION['flash_success'] = 'Cliente registrado con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/ficha?id=' . $id);
    }

    public function edit(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = $this->clienteRepo->findById($id);

        if (!$cliente) {
            $_SESSION['flash_error'] = 'Cliente no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes');
            exit;
        }

        $zonas = $this->zonaRepo->findAll();
        View::render('clientes/form', [
            'titulo' => 'Editar Cliente',
            'cliente' => $cliente,
            'zonas' => $zonas,
            'action' => 'update?id=' . $id
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = new Cliente();
        $cliente->id_cliente = $id;
        $this->fillFromPost($cliente);

        if (empty($cliente->nombre) || empty($cliente->dni)) {
            $_SESSION['flash_error'] = 'Nombre y DNI son obligatorios.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/editar?id=' . $id);
            exit;
        }

        try {
            $this->clienteRepo->update($cliente);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['flash_error'] = "Ya existe otro cliente con el DNI {$cliente->dni}.";
            } else {
                $_SESSION['flash_error'] = 'Error al actualizar el cliente. Intente nuevamente.';
            }
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/editar?id=' . $id);
            exit;
        }

        Audit::log('editar_cliente', 'clientes', $id);

        $_SESSION['flash_success'] = 'Cliente actualizado con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/clientes/ficha?id=' . $id);
    }

    private function fillFromPost(Cliente $cliente): void
    {
        $cliente->nombre = Sanitizer::clean($_POST['nombre'] ?? '');
        $cliente->dni = Sanitizer::clean($_POST['dni'] ?? '');
        $cliente->direccion = Sanitizer::clean($_POST['direccion'] ?? '');
        $cliente->barrio = Sanitizer::clean($_POST['barrio'] ?? '');
        $cliente->telefono = Sanitizer::clean($_POST['telefono'] ?? '');
        $cliente->coordenadas_gps = Sanitizer::clean($_POST['coordenadas_gps'] ?? '');
        $cliente->referencias = Sanitizer::clean($_POST['referencias'] ?? '');
        $cliente->id_zona = !empty($_POST['id_zona']) ? (int)$_POST['id_zona'] : null;
    }
}
