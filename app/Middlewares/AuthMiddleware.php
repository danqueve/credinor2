<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\Auth;

class AuthMiddleware
{
    public static function handle(): void
    {
        Auth::requireLogin();
    }
}
