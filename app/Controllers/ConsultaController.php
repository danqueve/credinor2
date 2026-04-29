<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\ClienteRepository;
use App\Repositories\CreditoRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PersonalRepository;

/**
 * Vistas de solo lectura para el rol "consulta" (cobradores en calle).
 * Optimizadas para mobile-first.
 */
class ConsultaController
{
    private CreditoRepository $creditoRepo;
    private ClienteRepository $clienteRepo;
    private PagoRepository    $pagoRepo;
    private PersonalRepository $personalRepo;

    public function __construct()
    {
        $this->creditoRepo  = new CreditoRepository();
        $this->clienteRepo  = new ClienteRepository();
        $this->pagoRepo     = new PagoRepository();
        $this->personalRepo = new PersonalRepository();
    }

    // ── Dashboard del cobrador del día ────────────────────────────────────────

    public function dashboard(): void
    {
        Auth::requireLogin();

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

        View::render('consulta/dashboard', [
            'titulo'           => 'Mi Día',
            'cuotasHoy'        => $cuotasHoy,
            'totalEsperado'    => $totalEsperadoHoy,
            'cuotasPagas'      => $cuotasPagas,
            'hoy'              => $hoy,
        ], layoutMobile: true);
    }

    // ── Búsqueda rápida ────────────────────────────────────────────────────────

    public function buscar(): void
    {
        Auth::requireLogin();

        $q        = Sanitizer::clean($_GET['q'] ?? '');
        $clientes = [];

        if (strlen($q) >= 2) {
            $clientes = $this->clienteRepo->searchFull($q);
        }

        View::render('consulta/buscar', [
            'titulo'   => 'Búsqueda',
            'q'        => $q,
            'clientes' => $clientes,
        ], layoutMobile: true);
    }

    // ── Ficha de cliente (modo consulta) ──────────────────────────────────────

    public function fichaCliente(): void
    {
        Auth::requireLogin();

        $id      = (int)($_GET['id'] ?? 0);
        $cliente = $this->clienteRepo->findById($id);

        if (!$cliente) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
            exit;
        }

        $creditos = $this->creditoRepo->findByCliente($id);

        View::render('consulta/ficha_cliente', [
            'titulo'   => $cliente->nombre . ' ' . ($cliente->apellido ?? ''),
            'cliente'  => $cliente,
            'creditos' => $creditos,
        ], layoutMobile: true);
    }

    // ── Ficha de crédito (modo consulta) ──────────────────────────────────────

    public function fichaCredito(): void
    {
        Auth::requireLogin();

        $id      = (int)($_GET['id'] ?? 0);
        $credito = $this->creditoRepo->findById($id);

        if (!$credito) {
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/consulta/buscar');
            exit;
        }

        $cliente = $this->clienteRepo->findById($credito->id_cliente);
        $pagos   = $this->pagoRepo->findByCredito($id);

        View::render('consulta/ficha_credito', [
            'titulo'  => 'Crédito ' . $credito->codigo,
            'credito' => $credito,
            'cliente' => $cliente,
            'pagos'   => $pagos,
        ], layoutMobile: true);
    }
}
