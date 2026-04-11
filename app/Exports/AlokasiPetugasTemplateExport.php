<?php

namespace App\Exports;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlokasiPetugasTemplateExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $periodeAlokasiId,
        protected string $type = 'create',
        protected ?Kegiatan $kegiatan = null,
        protected ?string $tahapan = null,
    ) {}

    /**
     * Whether listing columns should appear:
     * only for survei with has_listing_updating=true where tahapan is not pencacahan_only.
     * Defaults to true (full template) when kegiatan is unknown.
     */
    private function hasListing(): bool
    {
        if ($this->kegiatan === null) {
            return true;
        }

        if ($this->kegiatan->jenis_kegiatan !== 'survei' || ! $this->kegiatan->has_listing_updating) {
            return false;
        }

        return $this->tahapan !== 'pencacahan_only';
    }

    /**
     * Whether parsial columns should appear: only for survei.
     * Defaults to true when kegiatan is unknown.
     */
    private function hasParsial(): bool
    {
        if ($this->kegiatan === null) {
            return true;
        }

        return $this->kegiatan->jenis_kegiatan === 'survei';
    }

    public function array(): array
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();
        $data = [];

        if ($this->type === 'edit' && $this->periodeAlokasiId) {
            $entries = AlokasiPetugas::where('periode_alokasi_id', $this->periodeAlokasiId)
                ->with(['petugas'])
                ->get();

            foreach ($entries as $entry) {
                $row = [
                    $this->formatNikDropdownValue($entry->petugas?->nama, $entry->petugas?->nik),
                    $this->mapPeranCodeForTemplate($entry->peran),
                ];

                if ($hasListing) {
                    $row[] = $entry->jumlah_satuan_listing ?? '';
                }

                $row[] = $entry->jumlah_satuan ?? '';

                if ($hasParsial) {
                    $row[] = $entry->is_partial_payment ? 'Ya' : 'Tidak';
                }

                if ($hasListing && $hasParsial) {
                    $row[] = $entry->partial_jumlah_satuan_listing ?? '';
                }

                if ($hasParsial) {
                    $row[] = $entry->partial_jumlah_satuan ?? '';
                }

                $data[] = $row;
            }
        } else {
            $sampleRow = ['Nama Petugas - 1234567890123456', 'PCL/PPL'];

            if ($hasListing) {
                $sampleRow[] = '5';
            }

            $sampleRow[] = '10';

            if ($hasParsial) {
                $sampleRow[] = 'Tidak';
            }

            if ($hasListing && $hasParsial) {
                $sampleRow[] = '';
            }

            if ($hasParsial) {
                $sampleRow[] = '';
            }

            $data[] = $sampleRow;

            $emptyRow = array_fill(0, count($sampleRow), '');
            for ($i = 0; $i < 5; $i++) {
                $data[] = $emptyRow;
            }
        }

        $data[] = [''];
        $data[] = ['Petunjuk Pengisian:'];
        $data[] = ['1. Pilih petugas dari dropdown di kolom NIK (format: Nama - NIK/NIP)'];
        $data[] = ['2. Pilih Kode Penugasan dari dropdown: PCL/PPL, PML, Petugas Pengolahan, Pengawas Pengolahan'];

        $num = 3;

        if ($hasListing) {
            $data[] = ["{$num}. Jumlah Satuan Listing diisi angka bulat >= 0"];
            $num++;
        }

        $data[] = ["{$num}. Jumlah Satuan Pencacahan diisi angka bulat >= 0"];
        $num++;

        if ($hasParsial) {
            $data[] = ["{$num}. Pembayaran Parsial diisi Ya/Tidak"];
            $num++;

            if ($hasListing) {
                $data[] = ["{$num}. Jika Pembayaran Parsial = Ya, isi jumlah satuan parsial listing dan/atau pencacahan"];
            } else {
                $data[] = ["{$num}. Jika Pembayaran Parsial = Ya, isi jumlah satuan parsial pencacahan"];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();

        $columns = ['Nama - NIK', 'Kode Penugasan'];

        if ($hasListing) {
            $columns[] = 'Jumlah Satuan Listing';
        }

        $columns[] = 'Jumlah Satuan Pencacahan';

        if ($hasParsial) {
            $columns[] = 'Pembayaran Parsial';
        }

        if ($hasListing && $hasParsial) {
            $columns[] = 'Jumlah Satuan Parsial Listing';
        }

        if ($hasParsial) {
            $columns[] = 'Jumlah Satuan Parsial Pencacahan';
        }

        return $columns;
    }

    public function styles(Worksheet $sheet)
    {
        $hasListing = $this->hasListing();
        $hasParsial = $this->hasParsial();

        $widths = [59, 30];

        if ($hasListing) {
            $widths[] = 22;
        }

        $widths[] = 26;

        if ($hasParsial) {
            $widths[] = 20;
        }

        if ($hasListing && $hasParsial) {
            $widths[] = 28;
        }

        if ($hasParsial) {
            $widths[] = 32;
        }

        $colLetter = 'A';
        foreach ($widths as $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
            $colLetter++;
        }

        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_YELLOW);
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Pre-format the entire NIK column as text so that any value typed by
        // the user is stored as text. This prevents 16-digit NIKs from being
        // truncated due to IEEE 754 float precision (max ~15 significant digits).
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // Required: NIK, Kode, (Listing?), Pencacahan, (Parsial flag?)
        $requiredCount = 2 + ($hasListing ? 1 : 0) + 1 + ($hasParsial ? 1 : 0);
        $requiredLastCol = chr(ord('A') + $requiredCount - 1);

        for ($col = 'A'; $col <= $requiredLastCol; $col++) {
            $sheet->getStyle("{$col}2:{$col}100")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("{$col}2:{$col}100")->getFill()->getStartColor()->setARGB('FFFFCCCC');
        }

        return [];
    }

    public function title(): string
    {
        if ($this->periodeAlokasiId) {
            $periode = PeriodeAlokasi::find($this->periodeAlokasiId);
            if ($periode) {
                return "Alokasi {$periode->bulan}/{$periode->tahun}";
            }
        }

        return 'Alokasi Petugas';
    }

    /**
     * Force column A data cells to be stored as a string type so NIK values
     * (16-digit numbers) are never rendered in scientific notation by Excel.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'A' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function mapPeranCodeForTemplate(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            default => 'PCL/PPL',
        };
    }

    private function formatNikDropdownValue(?string $nama, ?string $nik): string
    {
        $nama = trim((string) ($nama ?? ''));
        $nik = trim((string) ($nik ?? ''));

        if ($nama === '' && $nik === '') {
            return '';
        }

        if ($nama === '') {
            return $nik;
        }

        if ($nik === '') {
            return $nama;
        }

        return $nama.' - '.$nik;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $mainSheet = $event->sheet->getDelegate();
                $spreadsheet = $mainSheet->getParent();
                $sheet = new Worksheet($spreadsheet, 'Daftar Petugas Aktif');

                $spreadsheet->addSheet($sheet);

                $sheet->setCellValue('A1', 'nip_nik');
                $sheet->setCellValue('B1', 'nama_petugas');
                $sheet->setCellValue('C1', 'pilihan_dropdown');
                $sheet->setCellValue('D1', 'kode_penugasan_dropdown');

                $sheet->getStyle('A1:D1')->getFont()->setBold(true);
                $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:D1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(24);
                $sheet->getColumnDimension('B')->setWidth(42);
                $sheet->getColumnDimension('C')->setWidth(64);
                $sheet->getColumnDimension('D')->setWidth(32);

                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

                $row = 2;
                $activePetugas = Petugas::query()
                    ->where('status', 'aktif')
                    ->orderBy('nama')
                    ->get(['nik', 'nama']);

                foreach ($activePetugas as $petugas) {
                    $nik = (string) ($petugas->nik ?? '');
                    $nama = (string) ($petugas->nama ?? '');

                    $sheet->setCellValueExplicit('A'.$row, $nik, DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$row, $nama);
                    $sheet->setCellValue('C'.$row, $this->formatNikDropdownValue($nama, $nik));
                    $row++;
                }

                $sheet->setCellValue('D2', 'PCL/PPL');
                $sheet->setCellValue('D3', 'PML');
                $sheet->setCellValue('D4', 'Petugas Pengolahan');
                $sheet->setCellValue('D5', 'Pengawas Pengolahan');

                $sheet->freezePane('A2');

                $lastPetugasRow = max(2, $row - 1);
                if ($lastPetugasRow >= 2) {
                    $listFormula = "'Daftar Petugas Aktif'!\$C\$2:\$C\$".$lastPetugasRow;

                    $validation = $mainSheet->getCell('A2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('NIK tidak valid');
                    $validation->setError('Silakan pilih petugas dari dropdown yang tersedia.');
                    $validation->setPromptTitle('Pilih Petugas');
                    $validation->setPrompt('Pilih petugas (Nama - NIK/NIP) pada dropdown.');
                    $validation->setFormula1($listFormula);

                    $kodeValidation = $mainSheet->getCell('B2')->getDataValidation();
                    $kodeValidation->setType(DataValidation::TYPE_LIST);
                    $kodeValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $kodeValidation->setAllowBlank(true);
                    $kodeValidation->setShowInputMessage(true);
                    $kodeValidation->setShowErrorMessage(true);
                    $kodeValidation->setShowDropDown(true);
                    $kodeValidation->setErrorTitle('Kode penugasan tidak valid');
                    $kodeValidation->setError('Silakan pilih kode penugasan dari dropdown yang tersedia.');
                    $kodeValidation->setPromptTitle('Pilih Kode Penugasan');
                    $kodeValidation->setPrompt('Gunakan dropdown untuk memilih jenis penugasan.');
                    $kodeValidation->setFormula1("'Daftar Petugas Aktif'!\$D\$2:\$D\$5");

                    for ($rowNumber = 3; $rowNumber <= 100; $rowNumber++) {
                        $mainSheet->getCell('A'.$rowNumber)->setDataValidation(clone $validation);
                        $mainSheet->getCell('B'.$rowNumber)->setDataValidation(clone $kodeValidation);
                    }
                }
            },
        ];
    }
}
