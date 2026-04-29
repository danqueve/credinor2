<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PagoService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PagoService::calcularFifo() — pure FIFO distribution logic, no DB.
 */
class PagoServiceFifoTest extends TestCase
{
    private PagoService $service;

    protected function setUp(): void
    {
        $this->service = $this->getMockBuilder(PagoService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    private function cuota(int $id, float $esperado, float $pagado = 0.0, float $recargo = 0.0): array
    {
        return [
            'id_cuota'          => $id,
            'numero_cuota'      => $id,
            'fecha_vencimiento' => '2026-01-01',
            'monto_esperado'    => $esperado,
            'monto_pagado'      => $pagado,
            'monto_recargo'     => $recargo,
        ];
    }

    public function testExactPaymentOneCuota(): void
    {
        $result = $this->service->calcularFifo(100.0, [$this->cuota(1, 100.0)]);
        $this->assertCount(1, $result['aplicaciones']);
        $this->assertSame(100.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(0.0, $result['monto_restante']);
    }

    public function testPartialPaymentOneCuota(): void
    {
        $result = $this->service->calcularFifo(60.0, [$this->cuota(1, 100.0)]);
        $this->assertCount(1, $result['aplicaciones']);
        $this->assertSame(60.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(40.0, $result['aplicaciones'][0]['saldo_nuevo']);
        $this->assertSame(0.0, $result['monto_restante']);
    }

    public function testOverpaymentLeavesRemainder(): void
    {
        $result = $this->service->calcularFifo(150.0, [$this->cuota(1, 100.0)]);
        $this->assertSame(100.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(50.0, $result['monto_restante']);
    }

    public function testFifoOrderAppliedCorrectly(): void
    {
        $cuotas = [
            $this->cuota(1, 100.0),
            $this->cuota(2, 100.0),
            $this->cuota(3, 100.0),
        ];
        $result = $this->service->calcularFifo(250.0, $cuotas);
        $this->assertCount(3, $result['aplicaciones']);
        $this->assertSame(100.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(100.0, $result['aplicaciones'][1]['monto_aplicado']);
        $this->assertSame(50.0,  $result['aplicaciones'][2]['monto_aplicado']);
        $this->assertSame(0.0,   $result['monto_restante']);
    }

    public function testPartiallyPaidCuotaConsidered(): void
    {
        // cuota 1 already has 60 paid, remaining saldo = 40
        $result = $this->service->calcularFifo(40.0, [$this->cuota(1, 100.0, 60.0)]);
        $this->assertSame(40.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(0.0,  $result['aplicaciones'][0]['saldo_nuevo']);
        $this->assertSame(0.0,  $result['monto_restante']);
    }

    public function testRecargoIncludedInSaldo(): void
    {
        // 100 + 10 recargo - 0 paid = 110 saldo
        $result = $this->service->calcularFifo(110.0, [$this->cuota(1, 100.0, 0.0, 10.0)]);
        $this->assertSame(110.0, $result['aplicaciones'][0]['monto_aplicado']);
        $this->assertSame(0.0,   $result['monto_restante']);
    }

    public function testZeroPaymentProducesNoApplications(): void
    {
        $result = $this->service->calcularFifo(0.0, [$this->cuota(1, 100.0)]);
        $this->assertEmpty($result['aplicaciones']);
        $this->assertSame(0.0, $result['monto_restante']);
    }

    public function testEmptyCuotasLeavesFullRemainder(): void
    {
        $result = $this->service->calcularFifo(200.0, []);
        $this->assertEmpty($result['aplicaciones']);
        $this->assertSame(200.0, $result['monto_restante']);
    }

    public function testSkipFullyPaidCuota(): void
    {
        $cuotas = [
            $this->cuota(1, 100.0, 100.0),  // already fully paid
            $this->cuota(2, 100.0),
        ];
        $result = $this->service->calcularFifo(100.0, $cuotas);
        $this->assertCount(1, $result['aplicaciones']);
        $this->assertSame(2, $result['aplicaciones'][0]['id_cuota']);
    }
}
