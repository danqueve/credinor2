<?php

declare(strict_types=1);

namespace App\Helpers;

class View
{
    public static function render(string $viewPath, array $data = [], bool $layoutMobile = false): void
    {
        $file = APP_PATH . '/Views/' . $viewPath . '.php';
        if (!file_exists($file)) {
            die("Error: View {$viewPath} not found.");
        }

        // Inyectar el tipo de layout para que la vista pueda elegirlo
        $data['_layoutMobile'] = $layoutMobile;
        extract($data);
        require $file;
    }
}
