<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function testGenerateSecretLength(): void
    {
        $secret = Totp::generateSecret();
        $this->assertSame(16, strlen($secret));
    }

    public function testGenerateSecretIsBase32(): void
    {
        $secret = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGenerateSecretIsUnique(): void
    {
        $this->assertNotSame(Totp::generateSecret(), Totp::generateSecret());
    }

    public function testGetCodeReturnsSixDigits(): void
    {
        $secret = Totp::generateSecret();
        $code   = Totp::getCode($secret);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testVerifyCurrentCode(): void
    {
        $secret = Totp::generateSecret();
        $code   = Totp::getCode($secret);
        $this->assertTrue(Totp::verify($secret, $code));
    }

    public function testVerifyWrongCode(): void
    {
        $secret = Totp::generateSecret();
        $this->assertFalse(Totp::verify($secret, '000000'));
    }

    public function testVerifyRejectsNonNumericInput(): void
    {
        $secret = Totp::generateSecret();
        $this->assertFalse(Totp::verify($secret, 'abcdef'));
    }

    public function testVerifyRejectsFiveDigits(): void
    {
        $secret = Totp::generateSecret();
        $this->assertFalse(Totp::verify($secret, '12345'));
    }

    public function testVerifyPreviousTimeSlot(): void
    {
        $secret   = Totp::generateSecret();
        $prevSlot = (int)floor(time() / 30) - 1;
        $code     = Totp::getCode($secret, $prevSlot);
        $this->assertTrue(Totp::verify($secret, $code, window: 1));
    }

    public function testVerifyNextTimeSlot(): void
    {
        $secret   = Totp::generateSecret();
        $nextSlot = (int)floor(time() / 30) + 1;
        $code     = Totp::getCode($secret, $nextSlot);
        $this->assertTrue(Totp::verify($secret, $code, window: 1));
    }

    public function testGetQrUrlContainsSecret(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $url    = Totp::getQrUrl($secret, 'admin', 'Credinor');
        $this->assertStringContainsString($secret, urldecode($url));
    }

    public function testGetQrUrlContainsIssuer(): void
    {
        $url = Totp::getQrUrl('AAAAAAAAAAAAAAAA', 'admin', 'Credinor');
        $this->assertStringContainsString('Credinor', urldecode($url));
    }
}
