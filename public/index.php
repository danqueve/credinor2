<?php

declare(strict_types=1);

// ─── Constantes globales ───────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// ─── Autoloader + .env ────────────────────────────────────────────────────
require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// ─── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Tucuman');

// ─── Sesión segura ────────────────────────────────────────────────────────
App\Helpers\Session::start();

// ─── CSRF (genera token si no existe) ─────────────────────────────────────
App\Helpers\Csrf::init();

// ─── Router ───────────────────────────────────────────────────────────────
$routes = require CONFIG_PATH . '/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Quitar el prefijo de la app del URI (ej: /credinor2/public → '')
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
}
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/') ?: '/';

// Buscar ruta
$handler = $routes[$method][$uri] ?? null;

if ($handler === null) {
    http_response_code(404);
    require APP_PATH . '/Views/errors/404.php';
    exit;
}

// Despachar
[$controllerClass, $action] = $handler;
$controller = new $controllerClass();
$controller->$action();
