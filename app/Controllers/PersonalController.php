<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Sanitizer;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Models\Zona;
use App\Models\Personal;
use App\Repositories\ZonaRepository;
use App\Repositories\PersonalRepository;

class PersonalController
{
    private ZonaRepository $zonaRepo;
    private PersonalRepository $personalRepo;

    public function __construct()
    {
        $this->zonaRepo = new ZonaRepository();
        $this->personalRepo = new PersonalRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $zonas = $this->zonaRepo->findAll();
        $personal = $this->personalRepo->findAllActive();

        View::render('personal/index', [
            'titulo' => 'Gestión de Zonas y Personal',
            'zonas' => $zonas,
            'personal' => $personal
        ]);
    }

    // --- ZONAS CRUD ---

    public function createZona(): void
    {
        Auth::requireAdmin();
        $personal = $this->personalRepo->findAllActive();
        View::render('personal/form_zona', [
            'titulo' => 'Nueva Zona',
            'zona' => new Zona(),
            'personal' => $personal,
            'action' => 'storeZona'
        ]);
    }

    public function storeZona(): void
    {
        Auth::requireAdmin();
        $nombre = Sanitizer::clean($_POST['nombre'] ?? '');
        $id_cobrador = !empty($_POST['id_cobrador_default']) ? (int)$_POST['id_cobrador_default'] : null;

        if (empty($nombre)) {
            $_SESSION['flash_error'] = 'El nombre de la zona es obligatorio.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/zonas/nueva');
            exit;
        }

        $zona = new Zona();
        $zona->nombre = $nombre;
        $zona->id_cobrador_default = $id_cobrador;

        $id = $this->zonaRepo->insert($zona);
        Audit::log('crear_zona', $id, "Zona creada: $nombre", $_SESSION['usuario_id']);

        $_SESSION['flash_success'] = 'Zona creada con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
    }

    public function editZona(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $zona = $this->zonaRepo->findById($id);

        if (!$zona) {
            $_SESSION['flash_error'] = 'Zona no encontrada.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
            exit;
        }

        $personal = $this->personalRepo->findAllActive();
        View::render('personal/form_zona', [
            'titulo' => 'Editar Zona',
            'zona' => $zona,
            'personal' => $personal,
            'action' => 'updateZona?id=' . $id
        ]);
    }

    public function updateZona(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $nombre = Sanitizer::clean($_POST['nombre'] ?? '');
        $id_cobrador = !empty($_POST['id_cobrador_default']) ? (int)$_POST['id_cobrador_default'] : null;

        if (empty($nombre)) {
            $_SESSION['flash_error'] = 'El nombre de la zona es obligatorio.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/zonas/editar?id=' . $id);
            exit;
        }

        $zona = new Zona();
        $zona->id_zona = $id;
        $zona->nombre = $nombre;
        $zona->id_cobrador_default = $id_cobrador;

        $this->zonaRepo->update($zona);
        Audit::log('editar_zona', $id, "Zona editada: $nombre", $_SESSION['usuario_id']);

        $_SESSION['flash_success'] = 'Zona actualizada con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
    }

    // --- PERSONAL CRUD ---

    public function createPersonal(): void
    {
        Auth::requireAdmin();
        $zonas = $this->zonaRepo->findAll();
        View::render('personal/form_personal', [
            'titulo' => 'Nuevo Empleado',
            'empleado' => new Personal(),
            'zonas' => $zonas,
            'action' => 'storePersonal'
        ]);
    }

    public function storePersonal(): void
    {
        Auth::requireAdmin();
        $empleado = new Personal();
        $empleado->nombre = Sanitizer::clean($_POST['nombre'] ?? '');
        $empleado->dni = Sanitizer::clean($_POST['dni'] ?? '');
        $empleado->telefono = Sanitizer::clean($_POST['telefono'] ?? '');
        $empleado->rol_operativo = Sanitizer::clean($_POST['rol_operativo'] ?? 'vendedor');
        $empleado->id_zona = !empty($_POST['id_zona']) ? (int)$_POST['id_zona'] : null;
        $empleado->comision_pct = (float)($_POST['comision_pct'] ?? 0);
        $empleado->estado = Sanitizer::clean($_POST['estado'] ?? 'activo');

        if (empty($empleado->nombre) || empty($empleado->dni)) {
            $_SESSION['flash_error'] = 'El nombre y DNI son obligatorios.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal/nuevo');
            exit;
        }

        $id = $this->personalRepo->insert($empleado);
        Audit::log('crear_personal', $id, "Empleado creado: {$empleado->nombre}", $_SESSION['usuario_id']);

        $_SESSION['flash_success'] = 'Empleado creado con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
    }

    public function editPersonal(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $empleado = $this->personalRepo->findById($id);

        if (!$empleado) {
            $_SESSION['flash_error'] = 'Empleado no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
            exit;
        }

        $zonas = $this->zonaRepo->findAll();
        View::render('personal/form_personal', [
            'titulo' => 'Editar Empleado',
            'empleado' => $empleado,
            'zonas' => $zonas,
            'action' => 'updatePersonal?id=' . $id
        ]);
    }

    public function updatePersonal(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $empleado = new Personal();
        $empleado->id_personal = $id;
        $empleado->nombre = Sanitizer::clean($_POST['nombre'] ?? '');
        $empleado->dni = Sanitizer::clean($_POST['dni'] ?? '');
        $empleado->telefono = Sanitizer::clean($_POST['telefono'] ?? '');
        $empleado->rol_operativo = Sanitizer::clean($_POST['rol_operativo'] ?? 'vendedor');
        $empleado->id_zona = !empty($_POST['id_zona']) ? (int)$_POST['id_zona'] : null;
        $empleado->comision_pct = (float)($_POST['comision_pct'] ?? 0);
        $empleado->estado = Sanitizer::clean($_POST['estado'] ?? 'activo');

        if (empty($empleado->nombre) || empty($empleado->dni)) {
            $_SESSION['flash_error'] = 'El nombre y DNI son obligatorios.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal/editar?id=' . $id);
            exit;
        }

        $this->personalRepo->update($empleado);
        Audit::log('editar_personal', $id, "Empleado editado: {$empleado->nombre}", $_SESSION['user']['id_usuario']);

        $_SESSION['flash_success'] = 'Empleado actualizado con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/personal');
    }
}
