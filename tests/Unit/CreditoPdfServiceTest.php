<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Pago;
use App\Services\CreditoPdfService;
use PHPUnit\Framework\TestCase;

class CreditoPdfServiceTest extends TestCase
{
    public function testBuildHtmlIncluyeCronogramaYPagos(): void
    {
        $service = new CreditoPdfService();

        $credito = new Credito();
        $credito->codigo = 'CR-001';
        $credito->capital = 1000.0;
        $credito->monto_total = 1200.0;
        $credito->saldo_pendiente = 300.0;
        $credito->cantidad_cuotas = 4;
        $credito->estado = 'activo';

        $cliente = new Cliente();
        $cliente->nombre = 'Ana Pérez';
        $cliente->dni = '12345678';

        $pago = new Pago();
        $pago->fecha_pago_real = '2026-07-20';
        $pago->monto_pagado = 200.0;
        $pago->forma_pago = 'efectivo';
        $pago->cobrador_nombre = 'Luis';
        $pago->anulado = false;

        $html = $service->buildHtml($credito, $cliente, [$pago]);

        $this->assertStringContainsString('Cronograma de cuotas', $html);
        $this->assertStringContainsString('Historial de pagos', $html);
        $this->assertStringContainsString('Ana Pérez', $html);
        $this->assertStringContainsString('CR-001', $html);
    }
}
