<?php
/**
 * Crea el usuario admin inicial.
 * Ejecutar UNA sola vez: php database/seed_admin.php
 */
$username = 'admin';
$password = 'Credinor2026!';  // Cambiarlo después del primer login
$rol      = 'admin';

try {
    $pdo  = new PDO('mysql:host=localhost;port=3306;dbname=credinor2;charset=utf8mb4', 'root', '');
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
