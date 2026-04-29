<?php

declare(strict_types=1);

namespace App\Helpers;

class Sanitizer
{
    public static function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function clean(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }
        return trim(strip_tags((string)$value));
    }

    public static function integer(mixed $value): ?int
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? $filtered : null;
    }

    public static function decimal(mixed $value): ?float
    {
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $filtered !== false ? $filtered : null;
    }
    
    public static function date(mixed $value): ?string
    {
        if (empty($value)) return null;
        $d = \DateTime::createFromFormat('Y-m-d', (string)$value);
        return $d && $d->format('Y-m-d') === $value ? $value : null;
    }
}
