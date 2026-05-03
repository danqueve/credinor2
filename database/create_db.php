<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS credinor2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Base de datos 'credinor2' creada (o ya existia).\n";

    // Ejecutar todas las migraciones en orden
    $pdo->exec('USE credinor2');
    $migraciones = glob(__DIR__ . '/migrations/0*.sql');
    sort($migraciones);

    foreach ($migraciones as $archivo) {
        $sql = file_get_contents($archivo);
        // Dividir por ; para ejecutar sentencia por sentencia
        $sentencias = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => $s !== '' && !preg_match('/^--/', $s)
        );
        foreach ($sentencias as $sentencia) {
            if (trim($sentencia) === '') continue;
            try {
                $pdo->exec($sentencia);
            } catch (PDOException $e) {
                // Ignorar errores de "ya existe" (1050, 1060, 1061, 1091)
                if (!in_array($e->getCode(), ['42S01', '42S21', '42000'])) {
                    $mysql = $pdo->errorInfo()[1] ?? 0;
                    if (!in_array($mysql, [1050, 1060, 1061, 1091, 1826])) {
                        echo "  AVISO en " . basename($archivo) . ": " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "  OK: " . basename($archivo) . "\n";
    }

    echo "\nMigraciones completadas. Base de datos lista.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
