<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Resuelve la URL base de la app.
 *
 * Si APP_URL está definida en el .env, se usa tal cual (así funciona
 * en producción, con dominio propio). Si no, se auto-detecta a partir
 * del host y del directorio del script actual — así funciona en
 * localhost sin tener que hardcodear "http://localhost/credinor2/public"
 * en ningún lado.
 */
class Url
{
    private static ?string $base = null;

    public static function base(): string
    {
        if (self::$base !== null) {
            return self::$base;
        }

        $env = trim((string)($_ENV['APP_URL'] ?? ''));
        if ($env !== '') {
            return self::$base = rtrim($env, '/');
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');

        return self::$base = self::scheme() . '://' . $host . $dir;
    }

    public static function to(string $path = ''): string
    {
        if ($path === '') {
            return self::base();
        }

        return self::base() . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::to($path));
        exit;
    }

    /**
     * Esquema real de la request, considerando un proxy/balanceador
     * (cPanel, CloudFlare) que termina TLS antes de llegar a PHP.
     */
    public static function scheme(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if ($forwarded !== '') {
            return strtolower(trim(explode(',', $forwarded)[0])) === 'https' ? 'https' : 'http';
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    /** Solo para tests: fuerza a recalcular la base en la próxima llamada. */
    public static function reset(): void
    {
        self::$base = null;
    }
}
