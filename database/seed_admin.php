<?php
/**
 * Crea el usuario admin inicial.
 * Ejecutar UNA sola vez: php database/seed_admin.php
 *
 * Lee la conexión del .env del proyecto (igual que database/setup.php) —
 * nunca hardcodear credenciales acá, sea local o producción.
 */
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$db   = $_ENV['DB_NAME'] ?? 'credinor2';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

$username = 'admin';
$password = bin2hex(random_bytes(6));  // contraseña aleatoria — se imprime una sola vez
$rol      = 'admin';

try {
    $pdo  = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $hash = password_hash($password, PASSWORD_ARGON2ID);

    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (username, password_hash, rol, activo)
         VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)"
    );
    $stmt->execute([$username, $hash, $rol]);

    echo "Usuario admin creado.\n";
    echo "  Usuario:    $username\n";
    echo "  Contraseña: $password\n";
    echo "\nCAMBIÁ LA CONTRASEÑA DESPUÉS DEL PRIMER LOGIN.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
