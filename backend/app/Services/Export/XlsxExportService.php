<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxExportService
{
    /**
     * Arma un .xlsx con una hoja por sección, en el mismo orden en que se
     * pasan las secciones. Cada valor se escribe "tal cual" (PhpSpreadsheet
     * ya distingue números/texto/fechas al leer el tipo PHP), a diferencia
     * del viejo CsvExportService que necesitaba formatear todo a string
     * (coma decimal, Sí/No) porque un CSV es texto plano.
     *
     * @param array<int, array{
     *     sheetName: string,
     *     columns: array<string, string>,
     *     rows: array<int, array<string, mixed>>
     * }> $sections
     */
    public function build(array $sections): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sections as $index => $section) {
            $sheet = $spreadsheet->createSheet($index);
            $sheet->setTitle($section['sheetName']);

            $headers = array_values($section['columns']);
            $keys = array_keys($section['columns']);

            $sheet->fromArray($headers, null, 'A1');

            $rowIndex = 2;
            foreach ($section['rows'] as $row) {
                $line = [];
                foreach ($keys as $key) {
                    $line[] = $this->formatValue($row[$key] ?? null);
                }
                $sheet->fromArray($line, null, "A{$rowIndex}");
                $rowIndex++;
            }

            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_export_');
        $writer->save($tempPath);
        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return $content;
    }

    private function formatValue(mixed $value): string|int|float|null
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return (string) $value;
    }
}
