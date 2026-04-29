<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Response;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\PagoRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\RendicionRepository;
use App\Services\RendicionService;

class RendicionController
{
    private RendicionRepository $rendicionRepo;
    private PersonalRepository  $personalRepo;
    private PagoRepository      $pagoRepo;
    private RendicionService    $rendicionService;

    public function __construct()
    {
        $this->rendicionRepo    = new RendicionRepository();
        $this->personalRepo     = new PersonalRepository();
        $this->pagoRepo         = new PagoRepository();
        $this->rendicionService = new RendicionService();
    }

    // ── Listado ───────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = 20;
        $offset     = ($page - 1) * $limit;
        $rendiciones = $this->rendicionRepo->findAll($limit, $offset);
        $total      = $this->rendicionRepo->countAll();
        $totalPages = (int)ceil($total / $limit);

        View::render('rendiciones/index', [
            'titulo'      => 'Rendiciones',
            'rendiciones' => $rendiciones,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'total'       => $total,
        ]);
    }

    // ── Ficha de rendición ────────────────────────────────────────────────────

    public function ficha(): void
    {
        Auth::requireLogin();

        $id        = (int)($_GET['id'] ?? 0);
        $rendicion = $this->rendicionRepo->findById($id);

        if (!$rendicion) {
            $_SESSION['flash_error'] = 'Rendición no encontrada.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/rendiciones');
            exit;
        }

        // Cargar pagos de esta rendición
        $stmt = \App\Helpers\Database::getInstance()->prepare("
            SELECT p.*, pc_pers.nombre AS cobrador_nombre,
                   cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                   cr.codigo AS credito_codigo
            FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal pc_pers ON p.id_cobrador = pc_pers.id_personal
            WHERE p.id_rendicion = ?
            ORDER BY p.id_pago ASC
        ");
        $stmt->execute([$id]);
        $pagosRaw = $stmt->fetchAll();

        $pagos = [];
        foreach ($pagosRaw as $row) {
            $pago = new \App\Models\Pago();
            $pago->id_pago          = (int)$row['id_pago'];
            $pago->id_credito       = (int)$row['id_credito'];
            $pago->monto_pagado     = (float)$row['monto_pagado'];
            $pago->forma_pago       = $row['forma_pago'];
            $pago->fecha_pago_real  = $row['fecha_pago_real'];
            $pago->anulado          = (bool)$row['anulado'];
            $pago->cobrador_nombre  = $row['cobrador_nombre'] ?? null;
            $pago->cliente_nombre   = $row['cliente_nombre']  ?? null;
            $pago->cliente_dni      = $row['cliente_dni']     ?? null;
            $pago->credito_codigo   = $row['credito_codigo']  ?? null;
            $pago->cuotasAplicadas  = $this->pagoRepo->getCuotasAplicadas($pago->id_pago);
            $pagos[] = $pago;
        }

        View::render('rendiciones/ficha', [
            'titulo'    => 'Rendición #' . $rendicion->id_rendicion,
            'rendicion' => $rendicion,
            'pagos'     => $pagos,
        ]);
    }

    // ── Formulario nueva rendición (grilla bulk) ───────────────────────────────

    public function nueva(): void
    {
        Auth::requireAdmin();

        $personal = $this->personalRepo->findAllActive();
        // Sólo cobradores o ambos
        $cobradores = array_filter($personal, fn($p) =>
            str_contains($p->rol_operativo, 'cobrador') || str_contains($p->rol_operativo, 'ambos')
        );

        View::render('rendiciones/nueva', [
            'titulo'     => 'Nueva Rendición',
            'cobradores' => array_values($cobradores),
        ]);
    }

    // ── Procesar rendición ────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAdmin();

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        $header = [
            'id_cobrador'                     => (int)($_POST['id_cobrador'] ?? 0),
            'fecha_rendicion'                 => Sanitizer::clean($_POST['fecha_rendicion']                 ?? ''),
            'total_efectivo_declarado'        => $_POST['total_efectivo_declarado']                        ?? '0',
            'total_transferencias_declarado'  => $_POST['total_transferencias_declarado']                  ?? '0',
            'observaciones'                   => Sanitizer::clean($_POST['observaciones']                  ?? ''),
        ];

        // Parsear filas desde JSON embebido en campo oculto
        $filasJson = $_POST['filas_json'] ?? '[]';
        $filas = json_decode($filasJson, true) ?? [];

        // Sanitizar cada fila
        $filasSanitizadas = [];
        foreach ($filas as $f) {
            $filasSanitizadas[] = [
                'id_credito'      => (int)($f['id_credito']      ?? 0),
                'monto'           => $f['monto']                 ?? '0',
                'forma_pago'      => Sanitizer::clean($f['forma_pago']      ?? 'efectivo'),
                'fecha_pago_real' => Sanitizer::clean($f['fecha_pago_real'] ?? ''),
            ];
        }

        $result = $this->rendicionService->crear($header, $filasSanitizadas, $usuarioId);

        if (!$result['ok']) {
            $_SESSION['flash_error']         = $result['message'];
            $_SESSION['rendicion_form_data'] = json_encode(['header' => $header, 'filas' => $filasSanitizadas]);
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/rendiciones/nueva');
            exit;
        }

        $_SESSION['flash_success'] = $result['message'];
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/rendiciones/ficha?id=' . $result['id_rendicion']);
        exit;
    }
}
