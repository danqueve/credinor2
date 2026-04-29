<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CreditoService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CreditoService::calcularPreview() — pure business logic, no DB.
 */
class CreditoServiceTest extends TestCase
{
    private CreditoService $service;

    protected function setUp(): void
    {
        // calcularPreview has no DB dependency, safe to instantiate with a stub repo
        $this->service = $this->getMockBuilder(CreditoService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testMontoTotalCalculation(): void
    {
        $result = $this->service->calcularPreview(1000.0, 10, 120.0);
        $this->assertSame(1200.0, $result['monto_total']);
    }

    public function testInteresImplicito(): void
    {
        $result = $this->service->calcularPreview(1000.0, 10, 120.0);
        $this->assertSame(200.0, $result['interes_implicito']);
    }

    public function testInteresImplicitoPct(): void
    {
        $result = $this->service->calcularPreview(1000.0, 10, 120.0);
        $this->assertSame(20.0, $result['interes_implicito_pct']);
    }

    public function testGastosAdminReduceInteres(): void
    {
        $result = $this->service->calcularPreview(1000.0, 10, 120.0, 50.0);
        $this->assertSame(150.0, $result['interes_implicito']);
    }

    public function testZeroCapitalDoesNotDivideByZero(): void
    {
        $result = $this->service->calcularPreview(0.0, 10, 120.0);
        $this->assertSame(0.0, $result['interes_implicito_pct']);
    }

    public function testSingleCuota(): void
    {
        $result = $this->service->calcularPreview(500.0, 1, 600.0);
        $this->assertSame(600.0, $result['monto_total']);
        $this->assertSame(100.0, $result['interes_implicito']);
        $this->assertSame(20.0, $result['interes_implicito_pct']);
    }

    public function testRoundingPrecision(): void
    {
        // 3 × 33.34 = 100.02 — should not produce floating point error
        $result = $this->service->calcularPreview(100.0, 3, 33.34);
        $this->assertSame(100.02, $result['monto_total']);
    }
}
