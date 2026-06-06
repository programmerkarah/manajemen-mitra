<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BappSeRealisasiTemplateExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{id:int, nama:string}>  $unitSampelItems
     */
    public function __construct(
        private readonly array $rows = [],
        private readonly array $unitSampelItems = [],
    ) {}

    public function headings(): array
    {
        $base = [
            'Nomor SPK',
            'NIK Petugas',
            'Nama Petugas',
            'Realisasi SLS',
        ];

        foreach ($this->unitSampelItems as $unit) {
            $base[] = 'Realisasi '.$unit['nama'];
        }

        return $base;
    }

    public function array(): array
    {
        if (! empty($this->rows)) {
            return array_map(function (array $row): array {
                $base = [
                    (string) ($row['nomor_spk'] ?? ''),
                    (string) ($row['nik_petugas'] ?? ''),
                    (string) ($row['nama_petugas'] ?? ''),
                    '',
                ];

                foreach ($this->unitSampelItems as $unit) {
                    $base[] = '';
                }

                return $base;
            }, $this->rows);
        }

        $exampleRow = [
            'Contoh: 1673/SPK-SE2026/001',
            '13730xxxxxxxxxxxx',
            'Nama Mitra',
            508,
        ];

        foreach ($this->unitSampelItems as $unit) {
            $exampleRow[] = 320;
        }

        return [$exampleRow];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $headerRange = sprintf('A1:%s1', $lastColumn);

        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB(Color::COLOR_YELLOW);

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('@');

        return [];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $column = $cell->getColumn();

        if (in_array($column, ['A', 'B'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return 'Template Realisasi BAPP SE2026';
    }
}
