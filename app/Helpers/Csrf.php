<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;

class Csrf
{
    private const TOKEN_KEY = 'csrf_token';

    public static function init(): void
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
    }

    public static function getToken(): string
    {
        return Session::get(self::TOKEN_KEY, '');
    }

    public static function validate(string $token): bool
    {
        $storedToken = self::getToken();
        if (empty($storedToken) || empty($token)) {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    public static function getFormField(): string
    {
        $token = self::getToken();
        return sprintf('<input type="hidden" name="%s" value="%s">', self::TOKEN_KEY, htmlspecialchars($token));
    }
}
