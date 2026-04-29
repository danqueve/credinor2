<?php

declare(strict_types=1);

namespace App\Helpers;

class Totp
{
    private const CHARS  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const DIGITS = 6;
    private const STEP   = 30;

    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $bytes  = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::CHARS[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    public static function getCode(string $secret, ?int $timeSlot = null): string
    {
        $timeSlot ??= (int)floor(time() / self::STEP);
        $key  = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlot);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ((ord($hash[$offset + 3]) & 0xFF))
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $slot = (int)floor(time() / self::STEP);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $slot + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a URL to a QR code image via Google Charts API.
     */
    public static function getQrUrl(string $secret, string $username, string $issuer = 'Credinor'): string
    {
        $label = rawurlencode($issuer . ':' . $username);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
        ]);
        $otpauth = 'otpauth://totp/' . $label . '?' . $params;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauth);
    }

    private static function base32Decode(string $secret): string
    {
        $secret  = strtoupper($secret);
        $charMap = array_flip(str_split(self::CHARS));
        $buffer  = 0;
        $bufLen  = 0;
        $output  = '';
        foreach (str_split($secret) as $char) {
            if (!isset($charMap[$char])) {
                continue;
            }
            $buffer = ($buffer << 5) | $charMap[$char];
            $bufLen += 5;
            if ($bufLen >= 8) {
                $bufLen -= 8;
                $output .= chr(($buffer >> $bufLen) & 0xFF);
            }
        }
        return $output;
    }
}
