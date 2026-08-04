<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReporteService;
use PHPUnit\Framework\TestCase;

class HojaRutaHtmlTest extends TestCase
{
    /**
     * buildHojaRutaHtml() no toca la base — es puro armado de HTML a partir
     * de arrays. El constructor real de ReporteService sí instancia
     * repositorios que abren conexión a la DB, así que lo deshabilitamos
     * (mismo patrón que CreditoServiceTest) para que este test no dependa
     * de tener una base disponible.
     */
    private function crearServiceSinDb(): ReporteService
    {
        return $this->getMockBuilder(ReporteService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    private function meta(): array
    {
        return ['cobrador_nombre' => 'Maxi', 'desde' => '2026-05-05', 'hasta' => '2026-05-11'];
    }

    private function cuota(array $overrides = []): array
    {
        return array_merge([
            'numero_cuota'       => 6,
            'fecha_vencimiento'  => '2026-05-06',
            'a_cobrar'           => 12000.0,
            'credito_codigo'     => 'CR-2026-00001',
            'total_cuotas'       => 10,
            'cliente_nombre'     => 'Erika Silvina',
            'cliente_apellido'   => 'Abraham',
            'cliente_dni'        => '40216602',
            'cliente_telefono'   => '3812478060',
            'cliente_direccion'  => 'Sáenz Peña 707',
            'cliente_barrio'     => 'Manantial',
            'zona_nombre'        => 'CAT',
        ], $overrides);
    }

    public function testIncluyeDireccionDelCliente(): void
    {
        $service = $this->crearServiceSinDb();
        $html = $service->buildHojaRutaHtml([$this->cuota()], $this->meta());

        $this->assertStringContainsString('Sáenz Peña 707', $html);
        $this->assertStringContainsString('Manantial', $html);
    }

    public function testMuestraApellidoYNombre(): void
    {
        $service = $this->crearServiceSinDb();
        $html = $service->buildHojaRutaHtml([$this->cuota()], $this->meta());

        $this->assertStringContainsString('Abraham, Erika Silvina', $html);
    }

    public function testTotalACobrarSumaCorrectamenteLasCuotas(): void
    {
        $service = $this->crearServiceSinDb();
        $data = [
            $this->cuota(['a_cobrar' => 12000.0]),
            $this->cuota(['numero_cuota' => 7, 'fecha_vencimiento' => '2026-05-07', 'a_cobrar' => 8000.0]),
        ];

        $html = $service->buildHojaRutaHtml($data, $this->meta());

        // 12000 + 8000 = 20000
        $this->assertStringContainsString('TOTAL A COBRAR:', $html);
        $this->assertStringContainsString('$ 20.000', $html);
    }

    public function testAgrupaPorZonaConSubtotal(): void
    {
        $service = $this->crearServiceSinDb();
        $data = [
            $this->cuota(['a_cobrar' => 12000.0, 'zona_nombre' => 'CAT']),
            $this->cuota(['numero_cuota' => 3, 'a_cobrar' => 5000.0, 'zona_nombre' => 'Centro', 'cliente_nombre' => 'Luis', 'cliente_apellido' => 'Gómez']),
        ];

        $html = $service->buildHojaRutaHtml($data, $this->meta());

        $this->assertStringContainsString('zona-header', $html);
        $this->assertStringContainsString('CAT', $html);
        $this->assertStringContainsString('Centro', $html);
        $this->assertStringContainsString('Subtotal CAT:', $html);
        $this->assertStringContainsString('Subtotal Centro:', $html);
    }

    public function testEstadoVacioCuandoNoHayCuotas(): void
    {
        $service = $this->crearServiceSinDb();
        $html = $service->buildHojaRutaHtml([], $this->meta());

        $this->assertStringContainsString('Sin cuotas por cobrar en el período seleccionado.', $html);
        $this->assertStringContainsString('$ 0', $html);
    }
}
