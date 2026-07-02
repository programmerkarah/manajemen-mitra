<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PengajuanPulsaTemplateExport implements FromArray, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param array<int, array{id: int, nama: string, key: string}> $petugasOptions
     * @param array<string, array<int, array{id: int, nama: string}>> $kegiatanOptionsByPetugasKey
     */
    public function __construct(
        private readonly string $bulan,
        private readonly int $tahun,
        private readonly array $petugasOptions,
        private readonly array $kegiatanOptionsByPetugasKey,
        private readonly array $jenisOptionsByPetugasKey,
    ) {
    }

    public function array(): array
    {
        return array_fill(0, 30, ['', '', '', '', '']);
    }

    public function headings(): array
    {
        return ['petugas_nama', 'kegiatan_nama', 'jenis_pulsa', 'nominal', 'petugas_key'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(28);
        }

        $sheet->getColumnDimension('E')->setVisible(false);
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return 'Template Pengajuan Pulsa';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $spreadsheet = $event->sheet->getDelegate()->getParent();
                $mainSheet = $event->sheet->getDelegate();

                $referenceSheet = new Worksheet($spreadsheet, 'Referensi Dropdown');
                $spreadsheet->addSheet($referenceSheet);

                $referenceSheet->setCellValue('A1', 'petugas_nama');
                $referenceSheet->setCellValue('B1', 'petugas_key');
                $referenceSheet->getStyle('A1:B1')->getFont()->setBold(true);
                $referenceSheet->getColumnDimension('A')->setWidth(34);
                $referenceSheet->getColumnDimension('B')->setWidth(20);

                $petugasRows = collect($this->petugasOptions)
                    ->sortBy('nama')
                    ->values();

                $petugasRow = 2;
                foreach ($petugasRows as $petugas) {
                    $referenceSheet->setCellValueExplicit('A'.$petugasRow, (string) $petugas['nama'], DataType::TYPE_STRING);
                    $referenceSheet->setCellValueExplicit('B'.$petugasRow, (string) $petugas['key'], DataType::TYPE_STRING);
                    $petugasRow++;
                }

                $referenceSheet->freezePane('A2');

                $nextReferenceColumnIndex = 3;
                foreach ($petugasRows as $petugas) {
                    $petugasKey = (string) $petugas['key'];
                    $activityColumn = Coordinate::stringFromColumnIndex($nextReferenceColumnIndex);
                    $jenisColumn = Coordinate::stringFromColumnIndex($nextReferenceColumnIndex + 1);
                    $activityNames = collect($this->kegiatanOptionsByPetugasKey[$petugasKey] ?? [])
                        ->pluck('nama')
                        ->filter(fn ($value) => trim((string) $value) !== '')
                        ->unique()
                        ->sort()
                        ->values();

                    if ($activityNames->isEmpty()) {
                        $activityNames = collect(['-']);
                    }

                    $jenisNames = collect($this->jenisOptionsByPetugasKey[$petugasKey] ?? [])
                        ->filter(fn ($value) => trim((string) $value) !== '')
                        ->unique()
                        ->sort()
                        ->values();

                    if ($jenisNames->isEmpty()) {
                        $jenisNames = collect(['pendataan']);
                    }

                    $referenceSheet->setCellValue($activityColumn.'1', $petugasKey);
                    $referenceSheet->setCellValue($jenisColumn.'1', $petugasKey.'_JENIS');
                    $referenceSheet->getColumnDimension($activityColumn)->setWidth(36);
                    $referenceSheet->getColumnDimension($jenisColumn)->setWidth(22);

                    $activityRow = 2;
                    foreach ($activityNames as $activityName) {
                        $referenceSheet->setCellValueExplicit($activityColumn.$activityRow, (string) $activityName, DataType::TYPE_STRING);
                        $activityRow++;
                    }

                    $lastActivityRow = max(2, $activityRow - 1);
                    $spreadsheet->addNamedRange(new NamedRange(
                        $petugasKey,
                        $referenceSheet,
                        '$'.$activityColumn.'$2:$'.$activityColumn.'$'.$lastActivityRow
                    ));

                    $jenisRow = 2;
                    foreach ($jenisNames as $jenisName) {
                        $referenceSheet->setCellValueExplicit($jenisColumn.$jenisRow, (string) $jenisName, DataType::TYPE_STRING);
                        $jenisRow++;
                    }

                    $lastJenisRow = max(2, $jenisRow - 1);
                    $spreadsheet->addNamedRange(new NamedRange(
                        $petugasKey.'_JENIS',
                        $referenceSheet,
                        '$'.$jenisColumn.'$2:$'.$jenisColumn.'$'.$lastJenisRow
                    ));

                    $nextReferenceColumnIndex += 2;
                }

                $referenceSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                $petugasLastRow = max(2, count($petugasRows) + 1);
                $petugasListRange = "'Referensi Dropdown'!\$A\$2:\$A\$".$petugasLastRow;

                $petugasValidation = $mainSheet->getCell('A2')->getDataValidation();
                $petugasValidation->setType(DataValidation::TYPE_LIST);
                $petugasValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $petugasValidation->setAllowBlank(true);
                $petugasValidation->setShowInputMessage(true);
                $petugasValidation->setShowErrorMessage(true);
                $petugasValidation->setShowDropDown(true);
                $petugasValidation->setErrorTitle('Petugas tidak valid');
                $petugasValidation->setError('Silakan pilih nama petugas dari dropdown yang tersedia.');
                $petugasValidation->setPromptTitle('Pilih Petugas');
                $petugasValidation->setPrompt('Pilih petugas berdasarkan nama.');
                $petugasValidation->setFormula1($petugasListRange);

                $jenisValidation = $mainSheet->getCell('C2')->getDataValidation();
                $jenisValidation->setType(DataValidation::TYPE_LIST);
                $jenisValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $jenisValidation->setAllowBlank(true);
                $jenisValidation->setShowInputMessage(true);
                $jenisValidation->setShowErrorMessage(true);
                $jenisValidation->setShowDropDown(true);
                $jenisValidation->setErrorTitle('Jenis pulsa tidak valid');
                $jenisValidation->setError('Silakan pilih jenis pulsa dari dropdown yang tersedia.');
                $jenisValidation->setPromptTitle('Pilih Jenis Pulsa');
                $jenisValidation->setPrompt('Pilih pendataan atau pelatihan.');
                $jenisValidation->setFormula1('"pendataan,pelatihan"');

                for ($row = 2; $row <= 31; $row++) {
                    $mainSheet->getCell('A'.$row)->setDataValidation(clone $petugasValidation);

                    $kegiatanValidation = $mainSheet->getCell('B'.$row)->getDataValidation();
                    $kegiatanValidation->setType(DataValidation::TYPE_LIST);
                    $kegiatanValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $kegiatanValidation->setAllowBlank(true);
                    $kegiatanValidation->setShowInputMessage(true);
                    $kegiatanValidation->setShowErrorMessage(true);
                    $kegiatanValidation->setShowDropDown(true);
                    $kegiatanValidation->setErrorTitle('Kegiatan tidak valid');
                    $kegiatanValidation->setError('Silakan pilih kegiatan yang sesuai dengan petugas yang dipilih.');
                    $kegiatanValidation->setPromptTitle('Pilih Kegiatan');
                    $kegiatanValidation->setPrompt('Kegiatan akan mengikuti petugas yang dipilih.');
                    $kegiatanValidation->setFormula1('=INDIRECT($E'.$row.')');

                    $mainSheet->getCell('C'.$row)->setDataValidation(clone $jenisValidation);
                    $mainSheet->setCellValueExplicit(
                        'E'.$row,
                        '=IFERROR(VLOOKUP(A'.$row.',\'Referensi Dropdown\'!$A$2:$B$'.$petugasLastRow.',2,FALSE),"")',
                        DataType::TYPE_FORMULA,
                    );
                    $mainSheet->getCell('C'.$row)->getDataValidation()->setFormula1('=INDIRECT($E'.$row.'&"_JENIS")');
                }
            },
        ];
    }
}
