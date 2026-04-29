<?php

declare(strict_types=1);

/**
 * Script de CLI para configurar la base de datos y correr migraciones.
 * Uso: php database/setup.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$db   = $_ENV['DB_NAME'] ?? 'credinor2';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    echo "Conectando al servidor MySQL...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Creando base de datos '{$db}' si no existe...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db}`");

    $migrationsPath = __DIR__ . '/migrations';
    $files = glob($migrationsPath . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename === '000_run_all.sql') continue;

        echo "Ejecutando migración: {$basename}...\n";
        $sql = file_get_contents($file);
        // Deshabilitar FK checks temporalmente para facilitar la creación si hay referencias circulares iniciales
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    }

    echo "Creando usuario administrador por defecto...\n";
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('Credinor@2026!', PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash, rol, activo) VALUES ('admin', ?, 'admin', 1)");
        $stmt->execute([$hash]);
        echo "✅ Admin creado. Usuario: admin | Pass: Credinor@2026!\n";
    } else {
        echo "⚠️ El usuario admin ya existe. Omitiendo.\n";
    }

    echo "\n🚀 ¡Setup completado con éxito!\n";

} catch (Exception $e) {
    die("❌ Error durante el setup: " . $e->getMessage() . "\n");
}
