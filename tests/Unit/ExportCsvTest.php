<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Export;
use PHPUnit\Framework\TestCase;

class ExportCsvTest extends TestCase
{
    private function capturar(array $headers, array $rows): string
    {
        ob_start();
        Export::csv($headers, $rows, 'test.csv', ';', terminate: false);
        return ob_get_clean();
    }

    public function testIncluyeBomUtf8(): void
    {
        $csv = $this->capturar(['Nombre'], [['Pérez']]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function testUsaPuntoYComaComoSeparador(): void
    {
        $csv = $this->capturar(['Nombre', 'DNI', 'Saldo'], [['Ana Pérez', '12345678', 68000]]);

        $lineas = explode("\n", ltrim($csv, "\xEF\xBB\xBF"));
        $this->assertSame('Nombre;DNI;Saldo', $lineas[0]);
        $this->assertSame('"Ana Pérez";12345678;68000', $lineas[1]);
    }

    public function testConservaAcentosYEnies(): void
    {
        $csv = $this->capturar(['Cliente'], [['Muñoz Núñez, José']]);

        $this->assertStringContainsString('Muñoz Núñez, José', $csv);
    }

    public function testEscapaCamposConElSeparador(): void
    {
        $csv = $this->capturar(['Dirección'], [['Av. San Martín 450; Piso 2']]);

        $this->assertStringContainsString('"Av. San Martín 450; Piso 2"', $csv);
    }

    public function testFilaVaciaSoloTraeEncabezados(): void
    {
        $csv = $this->capturar(['#', 'Cliente'], []);

        $lineas = array_values(array_filter(explode("\n", ltrim($csv, "\xEF\xBB\xBF"))));
        $this->assertCount(1, $lineas);
        $this->assertSame('#;Cliente', $lineas[0]);
    }
}
