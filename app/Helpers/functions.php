<?php

declare(strict_types=1);

use App\Helpers\Url;

if (!function_exists('url')) {
    /**
     * Atajo global para App\Helpers\Url::to().
     * Uso en vistas: <a href="<?= url('/creditos') ?>">
     */
    function url(string $path = ''): string
    {
        return Url::to($path);
    }
}
