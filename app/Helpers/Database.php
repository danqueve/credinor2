<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use PDOException;

/**
 * Singleton PDO — conexión única reutilizable en toda la app.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = $_ENV['DB_HOST']  ?? 'localhost';
            $port    = $_ENV['DB_PORT']  ?? '3306';
            $dbname  = $_ENV['DB_NAME']  ?? 'credinor2';
            $user    = $_ENV['DB_USER']  ?? 'root';
            $pass    = $_ENV['DB_PASS']  ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-03:00'",
                ]);
            } catch (PDOException $e) {
                // En producción, no exponer detalles de conexión
                $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
                $msg   = $debug ? $e->getMessage() : 'Error de conexión a la base de datos.';
                http_response_code(500);
                die("Error crítico: {$msg}");
            }
        }

        return self::$instance;
    }

    // Evitar clonación o instanciación directa
    private function __construct() {}
    private function __clone() {}
}
