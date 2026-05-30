<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FrameSampelDetailTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array{code:string,label:string,description:string}>  $metadata
     */
    public function __construct(
        protected array $metadata,
    ) {}

    public function headings(): array
    {
        $headings = [];

        foreach ($this->metadata as $column) {
            $label = trim((string) ($column['label'] ?? '')) ?: trim((string) ($column['code'] ?? 'Metadata'));
            $headings[] = 'Kode '.$label;
            $headings[] = $label;
        }

        $headings[] = 'Jumlah Sampel Dalam Frame';

        return $headings;
    }

    public function array(): array
    {
        $emptyRow = array_fill(0, count($this->headings()), '');

        return [
            $emptyRow,
            $emptyRow,
            $emptyRow,
            $emptyRow,
            $emptyRow,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('1')->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB(Color::COLOR_YELLOW);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $columnCount = count($this->headings());

        for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setWidth($columnIndex === $columnCount ? 24 : 22);
        }

        return [];
    }

    public function title(): string
    {
        return 'Detail Frame Sampel';
    }
}
