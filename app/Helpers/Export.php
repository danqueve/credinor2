<?php

declare(strict_types=1);

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exportadores genéricos de tablas (headers + rows) a Excel y CSV,
 * para no repetir el mismo bloque de "armar spreadsheet + headers HTTP"
 * en cada reporte.
 */
class Export
{
    /**
     * @param string[] $headers   Encabezados de columna
     * @param array<int, array<int, string|int|float|null>> $rows  Filas, mismo orden que $headers
     * @param array<int, string> $numberFormats  Índice de columna (0-based) => formato numérico de PhpSpreadsheet, ej. [3 => '#,##0.00']
     * @param bool $terminate  false solo en tests: evita el exit para poder inspeccionar la salida
     */
    public static function xlsx(array $headers, array $rows, string $filename, string $sheetTitle = 'Datos', array $numberFormats = [], bool $terminate = true): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        // Nota: PhpSpreadsheet 2.x eliminó setCellValueByColumnAndRow()/getCellByColumnAndRow();
        // la forma de coordenadas por array [columna 1-based, fila 1-based] es la reemplazante.
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $lastCol = $sheet->getCell([count($headers), 1])->getColumn();
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);

        $row = 2;
        foreach ($rows as $data) {
            foreach (array_values($data) as $i => $value) {
                $sheet->setCellValue([$i + 1, $row], $value);
                if (isset($numberFormats[$i])) {
                    $sheet->getCell([$i + 1, $row])->getStyle()->getNumberFormat()->setFormatCode($numberFormats[$i]);
                }
            }
            $row++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // $terminate=false (solo en tests) implica que no hay una respuesta HTTP
        // real detrás — evita enviar headers para no chocar con salida ya emitida
        // (ej. el runner de PHPUnit).
        if ($terminate) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        if ($terminate) {
            exit;
        }
    }

    /**
     * @param string[] $headers
     * @param array<int, array<int, string|int|float|null>> $rows
     * @param bool $terminate  false solo en tests: evita el exit para poder inspeccionar la salida
     */
    public static function csv(array $headers, array $rows, string $filename, string $separator = ';', bool $terminate = true): void
    {
        if ($terminate) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
        }

        $out = fopen('php://output', 'w');
        // BOM UTF-8: sin esto, Excel en español muestra mal los acentos (é, ñ, etc.)
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers, $separator);
        foreach ($rows as $data) {
            fputcsv($out, array_values($data), $separator);
        }

        fclose($out);
        if ($terminate) {
            exit;
        }
    }
}
