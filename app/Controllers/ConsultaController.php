<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\VisitaCobranzaRepository;

/**
 * Vistas de solo lectura para el rol "consulta" (cobradores en calle).
 * Optimizadas para mobile-first.
 */
class ConsultaController
{
    private CreditoRepository        $creditoRepo;
    private ClienteRepository        $clienteRepo;
    private PagoRepository           $pagoRepo;
    private PersonalRepository       $personalRepo;
    private VisitaCobranzaRepository $visitaRepo;

    public function __construct()
    {
        $this->creditoRepo  = new CreditoRepository();
        $this->clienteRepo  = new ClienteRepository();
        $this->pagoRepo     = new PagoRepository();
        $this->personalRepo = new PersonalRepository();
        $this->visitaRepo   = new VisitaCobranzaRepository();
    }

    // ── Registrar visita de cobranza ─────────────────────────────────────────

    public function registrarVisita(): void
    {
        Auth::requireCobrador();
        if ((Auth::user()['rol'] ?? '') === 'supervisor') {
            Response::json(false, null, ['El rol supervisor es solo lectura.'], 'Acceso denegado', 403);
            return;
        }

        $personalId = isset($_SESSION['usuario_personal_id']) ? (int)$_SESSION['usuario_personal_id'] : null;
        if (!$personalId) {
            Response::json(false, null, ['Cobrador sin perfil de personal vinculado.']);
            return;
        }

        $idCuota      = (int)($_POST['id_cuota'] ?? 0);
        $resultado    = Sanitizer::clean($_POST['resultado'] ?? '');
        $observaciones = Sanitizer::clean($_POST['observaciones'] ?? '') ?: null;
        $geoLat       = !empty($_POST['geo_lat']) ? (float)$_POST['geo_lat'] : null;
        $geoLng       = !empty($_POST['geo_lng']) ? (float)$_POST['geo_lng'] : null;

        $valid = ['intentada', 'no_contesta', 'promesa', 'cobrada'];
        if ($idCuota <= 0 || !in_array($resultado, $valid, true)) {
            Response::json(false, null, ['Datos inválidos.']);
            return;
        }

        // Verificar que la cuota pertenece a un crédito del cobrador
        if (!$this->pagoRepo->belongsToCobrador(
            $this->getCreditoIdByCuota($idCuota), $personalId
        )) {
            Response::json(false, null, ['Sin acceso a esta cuota.']);
            return;
        }

        $id = $this->visitaRepo->insert($idCuota, $personalId, $resultado, $observaciones, $geoLat, $geoLng);
        Response::json(true, ['id_visita' => $id]);
    }

    private function getCreditoIdByCuota(int $idCuota): int
    {
        $db   = \App\Helpers\Database::getInstance();
        $stmt = $db->prepare("SELECT id_credito FROM cuotas WHERE id_cuota = ?");
        $stmt->execute([$idCuota]);
        return (int)$stmt->fetchColumn();
    }

    // ── Búsqueda JSON para live-search (Alpine.js) ───────────────────────────

    public function buscarJson(): void
    {
        Auth::requireCobrador();

        $personalId = isset($_SESSION['usuario_personal_id']) ? (int)$_SESSION['usuario_personal_id'] : null;
        $q          = Sanitizer::clean($_GET['q'] ?? '');

        if (strlen($q) < 2) {
            Response::json(true, []);
            return;
        }

        $clientes = $personalId
            ? $this->clienteRepo->searchByCobrador($q, $personalId)
            : $this->clienteRepo->searchFull($q);

        $data = array_map(fn($cl) => [
            'id_cliente'     => $cl->id_cliente,
            'nombre'         => $cl->nombre,
            'dni'            => $cl->dni,
            'direccion'      => $cl->direccion,
            'saldo_total'    => $cl->saldo_total,
            'cuotas_vencidas'=> $cl->cuotas_vencidas,
        ], $clientes);

        Response::json(true, $data);
    }

    // ── Página offline ───────────────────────────────────────────────────────

    public function offline(): void
    {
        View::render('offline', ['titulo' => 'Sin conexión'], layoutMobile: true);
    }

    // ── Dashboard del cobrador del día ────────────────────────────────────────

    public function dashboard(): void
    {
        Auth::requireCobrador();

        $usuarioId    = (int)($_SESSION['usuario_id'] ?? 0);
        $personalId   = $_SESSION['usuario_personal_id'] ?? null;
        $hoy          = date('Y-m-d');

        // Cuotas que vencen hoy para el cobrador logueado
        $cuotasHoy = $this->creditoRepo->getCuotasVencenHoyPorCobrador(
            $personalId ? (int)$personalId : null,
            $hoy
        );

        // Resumen rápido
        $totalEsperadoHoy = array_sum(array_column($cuotasHoy, 'monto_esperado'));
        $cuotasPagas      = count(array_filter($cuotasHoy, fn($c) => $c['estado'] === 'pagada'));
        $totalCobradoHoy  = $personalId
            ? $this->pagoRepo->getTotalCobradoPorCobrador((int)$personalId, $hoy)
            : 0.0;

        View::render('consulta/dashboard', [
            'titulo'           => 'Mi Día',
            'cuotasHoy'        => $cuotasHoy,
            'totalEsperado'    => $totalEsperadoHoy,
            'totalCobrado'     => $totalCobradoHoy,
            'cuotasPagas'      => $cuotasPagas,
            'hoy'              => $hoy,
        ], layoutMobile: true);
    }

    // ── Búsqueda rápida ────────────────────────────────────────────────────────

    public function buscar(): void
    {
        Auth::requireCobrador();

        $personalId    = isset($_SESSION['usuario_personal_id']) ? (int)$_SESSION['usuario_personal_id'] : null;
        $q             = Sanitizer::clean($_GET['q'] ?? '');
        $clientes      = [];
        $buscando      = $q !== '';

        if (strlen($q) >= 2) {
            $clientes = $personalId
                ? $this->clienteRepo->searchByCobrador($q, $personalId)
                : $this->clienteRepo->searchFull($q);
        }

        $filtro  = Sanitizer::clean($_GET['filtro'] ?? '');
        $cartera = $personalId
            ? $this->clienteRepo->findByCobrador($personalId, $filtro)
            : [];

        View::render('consulta/buscar', [
            'titulo'   => 'Mi Cartera',
            'q'        => $q,
            'buscando' => $buscando,
            'clientes' => $clientes,
            'cartera'  => $cartera,
            'filtro'   => $filtro,
        ], layoutMobile: true);
    }

    // ── Ficha de cliente (modo consulta) ──────────────────────────────────────

    public function fichaCliente(): void
    {
        Auth::requireCobrador();

        $personalId = isset($_SESSION['usuario_personal_id']) ? (int)$_SESSION['usuario_personal_id'] : null;
        $id         = (int)($_GET['id'] ?? 0);
        $cliente    = $this->clienteRepo->findById($id);

        if (!$cliente) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
            exit;
        }

        if ($personalId) {
            $creditos = $this->creditoRepo->findByClienteAndCobrador($id, $personalId);
            if (empty($creditos)) {
                header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
                exit;
            }
        } else {
            $creditos = $this->creditoRepo->findByCliente($id);
        }

        $pagosRecientes = $this->pagoRepo->findRecentesPorCliente($id, 5);

        View::render('consulta/ficha_cliente', [
            'titulo'         => $cliente->nombre . ' ' . ($cliente->apellido ?? ''),
            'cliente'        => $cliente,
            'creditos'       => $creditos,
            'pagosRecientes' => $pagosRecientes,
        ], layoutMobile: true);
    }

    // ── Ficha de crédito (modo consulta) ──────────────────────────────────────

    public function fichaCredito(): void
    {
        Auth::requireCobrador();

        $personalId = isset($_SESSION['usuario_personal_id']) ? (int)$_SESSION['usuario_personal_id'] : null;
        $id         = (int)($_GET['id'] ?? 0);
        $credito    = $this->creditoRepo->findById($id);

        if (!$credito) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
            exit;
        }

        if ($personalId && !$this->pagoRepo->belongsToCobrador($id, $personalId)) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
            exit;
        }

        $cliente  = $this->clienteRepo->findById($credito->id_cliente);
        $pagos    = $this->pagoRepo->findByCredito($id);
        $visitas  = $this->visitaRepo->getUltimasPorCredito($id);

        View::render('consulta/ficha_credito', [
            'titulo'  => 'Crédito ' . $credito->codigo,
            'credito' => $credito,
            'cliente' => $cliente,
            'pagos'   => $pagos,
            'visitas' => $visitas,
        ], layoutMobile: true);
    }
}
