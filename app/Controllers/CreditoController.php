<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\CreditoRepository;
use App\Repositories\ClienteRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PersonalRepository;
use App\Services\CreditoService;
use App\Services\CuotaCalendarioService;

class CreditoController
{
    private CreditoRepository  $creditoRepo;
    private ClienteRepository  $clienteRepo;
    private PagoRepository     $pagoRepo;
    private PersonalRepository $personalRepo;
    private CreditoService     $creditoService;

    public function __construct()
    {
        $this->creditoRepo    = new CreditoRepository();
        $this->clienteRepo    = new ClienteRepository();
        $this->pagoRepo       = new PagoRepository();
        $this->personalRepo   = new PersonalRepository();
        $this->creditoService = new CreditoService();
    }

    // ── Listado ───────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAdminReadOnly();

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = Sanitizer::clean($_GET['q']      ?? '');
        $estado = Sanitizer::clean($_GET['estado'] ?? '');
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $creditos   = $this->creditoRepo->findAll($limit, $offset, $search, $estado);
        $total      = $this->creditoRepo->countAll($search, $estado);
        $totalPages = (int)ceil($total / $limit);

        View::render('creditos/index', [
            'titulo'     => 'Créditos',
            'creditos'   => $creditos,
            'search'     => $search,
            'estado'     => $estado,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

    // ── Ficha de crédito ──────────────────────────────────────────────────────

    public function ficha(): void
    {
        Auth::requireAdminReadOnly();

        $id      = (int)($_GET['id'] ?? 0);
        $credito = $this->creditoRepo->findById($id);

        if (!$credito) {
            $_SESSION['flash_error'] = 'Crédito no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos');
            exit;
        }

        $cliente              = $this->clienteRepo->findById($credito->id_cliente);
        $pagos                = $this->pagoRepo->findByCredito($id);
        $clienteIncobrable    = $cliente
            ? $this->creditoRepo->clienteTieneIncobrable($cliente->id_cliente)
            : false;

        View::render('creditos/ficha', [
            'titulo'            => 'Crédito ' . $credito->codigo,
            'credito'           => $credito,
            'cliente'           => $cliente,
            'pagos'             => $pagos,
            'clienteIncobrable' => $clienteIncobrable,
        ]);
    }

    // ── Nuevo crédito ─────────────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAdmin();

        $personal = $this->personalRepo->findAllActive();

        // Pre-seleccionar cliente si viene por querystring (ej. desde ficha de cliente)
        $clientePreseleccionado = null;
        $creditosActivos        = [];
        $idClienteGet           = (int)($_GET['id_cliente'] ?? 0);
        if ($idClienteGet > 0) {
            $clientePreseleccionado = $this->clienteRepo->findById($idClienteGet);
            if ($clientePreseleccionado) {
                $creditosActivos = $this->creditoRepo->findActivosByCliente($idClienteGet);
            }
        }

        View::render('creditos/form_nuevo', [
            'titulo'                => 'Nuevo Crédito',
            'personal'              => $personal,
            'clientePreseleccionado' => $clientePreseleccionado,
            'creditosActivos'       => $creditosActivos,
        ]);
    }

    // ── Guardar nuevo crédito ─────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAdmin();

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $data = [
            'id_cliente'       => (int)($_POST['id_cliente']      ?? 0),
            'capital'          => (float)str_replace(',', '.', $_POST['capital']      ?? '0'),
            'cantidad_cuotas'  => (int)($_POST['cantidad_cuotas'] ?? 0),
            'valor_cuota'      => (float)str_replace(',', '.', $_POST['valor_cuota']  ?? '0'),
            'gastos_admin'     => (float)str_replace(',', '.', $_POST['gastos_admin'] ?? '0'),
            'frecuencia'       => Sanitizer::clean($_POST['frecuencia']    ?? ''),
            'fecha_inicio'     => Sanitizer::clean($_POST['fecha_inicio']  ?? ''),
            'id_vendedor'      => !empty($_POST['id_vendedor']) ? (int)$_POST['id_vendedor'] : null,
            'id_cobrador'      => !empty($_POST['id_cobrador']) ? (int)$_POST['id_cobrador'] : null,
            'destino_opcional' => Sanitizer::clean($_POST['destino_opcional'] ?? ''),
            'observaciones'    => Sanitizer::clean($_POST['observaciones']    ?? ''),
        ];

        $result = $this->creditoService->crear($data, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/nuevo?id_cliente=' . $data['id_cliente']);
            exit;
        }

        $_SESSION['flash_success'] = $result['message'];
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $result['id_credito']);
        exit;
    }

    // ── Anular crédito (POST) ─────────────────────────────────────────────────

    public function anular(): void
    {
        Auth::requireAdmin();

        $idCredito = (int)($_POST['id_credito'] ?? 0);
        $motivo    = Sanitizer::clean($_POST['motivo'] ?? '');
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $result = $this->creditoService->anular($idCredito, $motivo, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_success'] = $result['message'];
        }

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $idCredito);
        exit;
    }

    // ── Formulario de refinanciación ──────────────────────────────────────────

    public function refinanciarForm(): void
    {
        Auth::requireAdmin();

        $id      = (int)($_GET['id'] ?? 0);
        $credito = $this->creditoRepo->findById($id);

        if (!$credito || $credito->estado !== 'activo') {
            $_SESSION['flash_error'] = 'Crédito no válido para refinanciar.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos');
            exit;
        }

        $personal = $this->personalRepo->findAllActive();

        View::render('creditos/form_refinanciar', [
            'titulo'   => 'Refinanciar ' . $credito->codigo,
            'credito'  => $credito,
            'personal' => $personal,
        ]);
    }

    // ── Procesar refinanciación (POST) ────────────────────────────────────────

    public function refinanciar(): void
    {
        Auth::requireAdmin();

        $idCredito = (int)($_POST['id_credito'] ?? 0);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $data = [
            'cantidad_cuotas' => (int)($_POST['cantidad_cuotas']  ?? 0),
            'valor_cuota'     => (float)str_replace(',', '.', $_POST['valor_cuota']  ?? '0'),
            'gastos_admin'    => (float)str_replace(',', '.', $_POST['gastos_admin'] ?? '0'),
            'frecuencia'      => Sanitizer::clean($_POST['frecuencia']   ?? ''),
            'fecha_inicio'    => Sanitizer::clean($_POST['fecha_inicio'] ?? ''),
            'id_vendedor'     => !empty($_POST['id_vendedor']) ? (int)$_POST['id_vendedor'] : null,
            'id_cobrador'     => !empty($_POST['id_cobrador']) ? (int)$_POST['id_cobrador'] : null,
            'observaciones'   => Sanitizer::clean($_POST['observaciones'] ?? ''),
        ];

        $result = $this->creditoService->refinanciar($idCredito, $data, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/refinanciar?id=' . $idCredito);
            exit;
        }

        $_SESSION['flash_success'] = $result['message'];
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $result['id_credito']);
        exit;
    }

    // ── Marcar incobrable (POST) ──────────────────────────────────────────────

    public function marcarIncobrable(): void
    {
        Auth::requireAdmin();

        $idCredito = (int)($_POST['id_credito'] ?? 0);
        $motivo    = Sanitizer::clean($_POST['motivo'] ?? '');
        $override  = (bool)($_POST['override_incobrable'] ?? false);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        // RN-09: si el cliente ya tiene otro incobrable, se requiere override explícito
        $credito = $this->creditoRepo->findById($idCredito);
        if ($credito && $this->creditoRepo->clienteTieneIncobrable($credito->id_cliente) && !$override) {
            $_SESSION['flash_error'] = 'El cliente ya tiene un crédito incobrable. Confirme el override para continuar.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $idCredito);
            exit;
        }

        $result = $this->creditoService->marcarIncobrable($idCredito, $motivo, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_success'] = $result['message'];
        }

        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/creditos/ficha?id=' . $idCredito);
        exit;
    }
}
