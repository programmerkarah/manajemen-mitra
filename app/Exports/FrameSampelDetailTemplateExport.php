<?php

namespace App\Exports;

use App\Models\Kegiatan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FrameSampelDetailTemplateExport implements FromArray, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array{code:string,label:string,description:string,mode?:string}>  $metadata
     * @param  array<int, array{id:int,nama:string}>  $unitSampelList
     * @param  array<int, array<string, mixed>>  $templateRows
     */
    public function __construct(
        protected array $metadata,
        protected array $unitSampelList = [],
        protected string $metodeSampling = 'targeted',
        protected array $templateRows = [],
    ) {}

    public function headings(): array
    {
        $headings = [];

        foreach ($this->metadata as $column) {
            $label = trim((string) ($column['label'] ?? '')) ?: trim((string) ($column['code'] ?? 'Metadata'));
            $mode = (string) ($column['mode'] ?? 'code_name');

            if ($mode === 'code_only') {
                $headings[] = 'Kode '.$label;

                continue;
            }

            if ($mode === 'name_only') {
                $headings[] = $label;

                continue;
            }

            $headings[] = 'Kode '.$label;
            $headings[] = $label;
        }

        if ($this->metodeSampling === 'purpossive') {
            $headings[] = 'Nama Sampel';
            $headings[] = 'Jenis Sampel';
        } elseif (empty($this->unitSampelList)) {
            $headings[] = 'Jumlah Sampel Dalam Frame';
        } else {
            foreach ($this->unitSampelList as $unitSampel) {
                $nama = trim((string) ($unitSampel['nama'] ?? 'Unit Sampel'));
                $headings[] = 'Jumlah '.$nama.' Dalam Frame';
            }
        }

        return $headings;
    }

    public function array(): array
    {
        if (! empty($this->templateRows)) {
            return array_map(fn (array $row): array => $this->mapTemplateRow($row), $this->templateRows);
        }

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
            $sheet->getColumnDimensionByColumn($columnIndex)->setWidth(22);
        }

        return [];
    }

    public function title(): string
    {
        return 'Detail Frame Sampel';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                if ($this->metodeSampling !== Kegiatan::METODE_SAMPLING_PURPOSSIVE) {
                    return;
                }

                $headingIndex = array_search('Jenis Sampel', $this->headings(), true);

                if ($headingIndex === false) {
                    return;
                }

                $columnLetter = Coordinate::stringFromColumnIndex($headingIndex + 1);
                $maxRow = max(count($this->templateRows) + 1, 6);
                $dropdownValues = Kegiatan::purpossiveSampleRoleValues();

                if ($dropdownValues === []) {
                    return;
                }

                $validationFormula = '"'.implode(',', $dropdownValues).'"';

                for ($row = 2; $row <= $maxRow; $row++) {
                    $validation = $event->sheet->getDelegate()->getCell($columnLetter.$row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowInputMessage(true);
                    $validation->setError('Silakan pilih jenis sampel dari dropdown yang tersedia.');
                    $validation->setPrompt('Pilih jenis sampel: Utama, Cadangan, atau Lainnya.');
                    $validation->setFormula1($validationFormula);
                }
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private function mapTemplateRow(array $row): array
    {
        $values = [];
        $metadataValues = $this->resolveTemplateRowMetadata($row);

        foreach ($this->metadata as $column) {
            $columnLabel = trim((string) ($column['label'] ?? '')) ?: trim((string) ($column['code'] ?? 'Metadata'));
            $columnMode = (string) ($column['mode'] ?? 'code_name');

            if ($columnMode === 'code_only') {
                $values[] = trim((string) ($metadataValues[$column['code']] ?? ''));

                continue;
            }

            if ($columnMode === 'name_only') {
                $values[] = trim((string) ($metadataValues[$column['code'].'_label'] ?? ''));

                continue;
            }

            $values[] = trim((string) ($metadataValues[$column['code']] ?? ''));
            $values[] = trim((string) ($metadataValues[$column['code'].'_label'] ?? ''));
        }

        if ($this->metodeSampling === Kegiatan::METODE_SAMPLING_PURPOSSIVE) {
            $values[] = trim((string) ($row['sample_name'] ?? $row['nama_target'] ?? ''));
            $values[] = Kegiatan::normalizePurpossiveSampleRole((string) ($row['sample_role'] ?? null));

            return $values;
        }

        if (! empty($this->unitSampelList)) {
            foreach ($this->unitSampelList as $unitSampel) {
                $unitKey = (string) ($unitSampel['id'] ?? '');
                $values[] = (string) ($this->resolveTemplateRowTargetUnits($row)[$unitKey] ?? '');
            }

            return $values;
        }

        $values[] = (string) (array_values($this->resolveTemplateRowTargetUnits($row))[0] ?? '');

        return $values;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function resolveTemplateRowMetadata(array $row): array
    {
        $metadata = [];

        if (isset($row['identitas_tambahan']) && is_array($row['identitas_tambahan'])) {
            foreach ($row['identitas_tambahan'] as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    $metadata[(string) $key] = trim((string) $value);
                }
            }
        }

        if (isset($row['metadata_items']) && is_array($row['metadata_items'])) {
            foreach ($row['metadata_items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $code = trim((string) ($item['code'] ?? ''));

                if ($code === '') {
                    continue;
                }

                $codeValue = trim((string) ($item['codeValue'] ?? ''));
                $labelValue = trim((string) ($item['labelValue'] ?? ''));

                if ($codeValue !== '') {
                    $metadata[$code] = $codeValue;
                }

                if ($labelValue !== '') {
                    $metadata[$code.'_label'] = $labelValue;
                }
            }
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function resolveTemplateRowTargetUnits(array $row): array
    {
        $targetUnits = $row['target_unit_sampel'] ?? [];

        if (! is_array($targetUnits)) {
            return [];
        }

        return collect($targetUnits)
            ->map(fn ($value) => trim((string) $value))
            ->all();
    }
}
