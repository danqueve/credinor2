<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Audit;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Repositories\PersonalRepository;
use App\Repositories\UsuarioRepository;

class UsuarioController
{
    private UsuarioRepository  $repo;
    private PersonalRepository $personalRepo;

    public function __construct()
    {
        $this->repo         = new UsuarioRepository();
        $this->personalRepo = new PersonalRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        View::render('usuarios/index', [
            'titulo'   => 'Gestión de Usuarios',
            'usuarios' => $this->repo->findAll(),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();

        View::render('usuarios/form', [
            'titulo'   => 'Nuevo Usuario',
            'usuario'  => null,
            'personal' => $this->personalRepo->findAllActive(),
            'action'   => 'store',
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $username   = Sanitizer::clean($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rol        = Sanitizer::clean($_POST['rol'] ?? 'cobrador');
        $idPersonal = !empty($_POST['id_personal']) ? (int)$_POST['id_personal'] : null;
        $activo     = isset($_POST['activo']);

        // Solo se permiten roles de sistema desde este formulario
        if (!in_array($rol, ['admin', 'cobrador'], true)) {
            $rol = 'cobrador';
        }

        if (empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'El usuario y la contraseña son obligatorios.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios/nuevo');
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $id = $this->repo->insert($username, $hash, $rol, $idPersonal, null, $activo);
        } catch (\PDOException $e) {
            $_SESSION['flash_error'] = $e->getCode() === '23000'
                ? "El usuario '{$username}' ya existe."
                : 'Error al guardar. Intente nuevamente.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios/nuevo');
            exit;
        }

        Audit::log('crear_usuario', 'usuarios', $id);
        $_SESSION['flash_success'] = "Usuario '{$username}' creado con éxito.";
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios');
        exit;
    }

    public function edit(): void
    {
        Auth::requireAdmin();
        $id      = (int)($_GET['id'] ?? 0);
        $usuario = $this->repo->findById($id);

        if (!$usuario) {
            $_SESSION['flash_error'] = 'Usuario no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios');
            exit;
        }

        View::render('usuarios/form', [
            'titulo'   => 'Editar Usuario',
            'usuario'  => $usuario,
            'personal' => $this->personalRepo->findAllActive(),
            'action'   => 'update?id=' . $id,
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        $id = (int)($_GET['id'] ?? 0);

        $username   = Sanitizer::clean($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rol        = Sanitizer::clean($_POST['rol'] ?? 'cobrador');
        $idPersonal = !empty($_POST['id_personal']) ? (int)$_POST['id_personal'] : null;
        $activo     = isset($_POST['activo']);

        if (!in_array($rol, ['admin', 'cobrador'], true)) {
            $rol = 'cobrador';
        }

        if (empty($username)) {
            $_SESSION['flash_error'] = 'El nombre de usuario es obligatorio.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios/editar?id=' . $id);
            exit;
        }

        $hash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;

        try {
            $this->repo->update($id, $username, $hash, $rol, $idPersonal, null, $activo);
        } catch (\PDOException $e) {
            $_SESSION['flash_error'] = $e->getCode() === '23000'
                ? "El usuario '{$username}' ya existe."
                : 'Error al actualizar. Intente nuevamente.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios/editar?id=' . $id);
            exit;
        }

        Audit::log('editar_usuario', 'usuarios', $id);
        $_SESSION['flash_success'] = 'Usuario actualizado con éxito.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios');
        exit;
    }

    public function delete(): void
    {
        Auth::requireAdmin();
        $id = (int)($_POST['id'] ?? 0);

        $usuario = $this->repo->findById($id);
        if (!$usuario) {
            $_SESSION['flash_error'] = 'Usuario no encontrado.';
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios');
            exit;
        }

        $this->repo->softDelete($id);
        Audit::log('eliminar_usuario', 'usuarios', $id);
        $_SESSION['flash_success'] = 'Usuario eliminado.';
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/usuarios');
        exit;
    }
}
