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

class BastSensusRealisasiTemplateExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows = []) {}

    public function headings(): array
    {
        return [
            'Nomor SPK',
            'NIK Petugas',
            'Nama Petugas',
            'Muatan Prelist (Keluarga)',
            'Muatan Prelist (Usaha)',
            'Realisasi (Keluarga)',
            'Realisasi (Usaha)',
        ];
    }

    public function array(): array
    {
        if (! empty($this->rows)) {
            return array_map(function (array $row): array {
                return [
                    (string) ($row['nomor_spk'] ?? ''),
                    (string) ($row['nik_petugas'] ?? ''),
                    (string) ($row['nama_petugas'] ?? ''),
                    (int) ($row['muatan_prelist_keluarga'] ?? 0),
                    (int) ($row['muatan_prelist_usaha'] ?? 0),
                    '',
                    '',
                ];
            }, $this->rows);
        }

        return [[
            'Contoh: 1673/SPK/2026',
            '13730xxxxxxxxxxxx',
            'Nama Mitra',
            320,
            210,
            300,
            180,
        ]];
    }

    public function styles(Worksheet $sheet)
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

        // Ensure identifier columns are treated as text in Excel.
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
        return 'Template Realisasi SE2026';
    }
}
