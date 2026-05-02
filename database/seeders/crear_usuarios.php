<?php

/**
 * Seeder: Crear usuario admin + usuarios para todos los clientes existentes.
 *
 * Uso: php database/seeders/crear_usuarios.php
 *
 * - Admin: username='admin', password='admin123'
 * - Clientes: username=DNI, password=DNI
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__, 2));
require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Conexión PDO directa
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_NAME'] ?? 'credinor2'
);
$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$creados   = 0;
$existentes = 0;
$errores   = 0;

// ─── 1. Usuario admin ──────────────────────────────────────────────────────────
$stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = ? AND deleted_at IS NULL");
$stmtCheck->execute(['admin']);

if (!$stmtCheck->fetch()) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare("
        INSERT INTO usuarios (username, password_hash, rol, activo)
        VALUES ('admin', ?, 'admin', 1)
    ")->execute([$hash]);
    echo "[OK] Usuario admin creado (password: admin123)\n";
    $creados++;
} else {
    echo "[--] Usuario admin ya existe, omitido.\n";
    $existentes++;
}

// ─── 2. Usuarios para clientes ────────────────────────────────────────────────
$clientes = $pdo->query("SELECT id_cliente, nombre, dni FROM clientes WHERE deleted_at IS NULL")->fetchAll();

$stmtInsert = $pdo->prepare("
    INSERT INTO usuarios (username, password_hash, rol, id_cliente, activo)
    VALUES (?, ?, 'cliente', ?, 1)
");

foreach ($clientes as $c) {
    $dni = trim($c['dni']);
    if (empty($dni)) {
        echo "[!!] Cliente #{$c['id_cliente']} ({$c['nombre']}) no tiene DNI, omitido.\n";
        $errores++;
        continue;
    }

    $stmtCheck->execute([$dni]);
    if ($stmtCheck->fetch()) {
        echo "[--] Usuario '{$dni}' ya existe ({$c['nombre']}), omitido.\n";
        $existentes++;
        continue;
    }

    try {
        $hash = password_hash($dni, PASSWORD_BCRYPT);
        $stmtInsert->execute([$dni, $hash, $c['id_cliente']]);
        echo "[OK] Usuario cliente '{$dni}' creado ({$c['nombre']})\n";
        $creados++;
    } catch (\PDOException $e) {
        echo "[!!] Error al crear '{$dni}': " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n";
echo "═══════════════════════════════════\n";
echo " Creados:    $creados\n";
echo " Ya existen: $existentes\n";
echo " Errores:    $errores\n";
echo "═══════════════════════════════════\n";
