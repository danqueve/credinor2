<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\Auth;

class RoleMiddleware
{
    public static function admin(): void
    {
        Auth::requireAdmin();
    }
}
