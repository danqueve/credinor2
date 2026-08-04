<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
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

        $cuota1 = new Cuota();
        $cuota1->numero_cuota = 1;
        $cuota1->fecha_vencimiento = '2026-06-01';
        $cuota1->monto_esperado = 300.0;
        $cuota1->monto_pagado = 300.0;
        $cuota1->estado = 'pagada';

        $cuota2 = new Cuota();
        $cuota2->numero_cuota = 2;
        $cuota2->fecha_vencimiento = '2026-07-01';
        $cuota2->monto_esperado = 300.0;
        $cuota2->monto_pagado = 0.0;
        $cuota2->estado = 'pendiente';

        $credito->cuotas = [$cuota1, $cuota2];

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
        // Con $credito->cuotas poblado, el loop del cronograma debe ejecutarse
        // en vez de caer en la rama "Sin cuotas registradas".
        $this->assertStringNotContainsString('Sin cuotas registradas', $html);
        $this->assertStringContainsString('$300,00', $html);
    }

    public function testBuildHtmlMuestraEstadoVacioSinCuotas(): void
    {
        $service = new CreditoPdfService();

        $credito = new Credito();
        $credito->codigo = 'CR-002';
        $credito->capital = 500.0;
        $credito->monto_total = 600.0;
        $credito->saldo_pendiente = 600.0;
        $credito->cantidad_cuotas = 0;
        $credito->estado = 'activo';
        $credito->cuotas = [];

        $html = $service->buildHtml($credito, null, []);

        $this->assertStringContainsString('Sin cuotas registradas', $html);
        $this->assertStringContainsString('Sin pagos registrados', $html);
    }
}
