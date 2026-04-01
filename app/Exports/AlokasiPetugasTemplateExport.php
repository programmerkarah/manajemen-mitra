<?php

namespace App\Exports;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlokasiPetugasTemplateExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
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
                    $entry->petugas?->nik ?? '',
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
            $sampleRow = ['1234567890123456', 'pcl_ppl'];

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
        $data[] = ['1. Isi NIK sesuai data petugas yang sudah terdaftar di sistem'];
        $data[] = ['2. Kode Penugasan wajib salah satu: pcl_ppl, pml, pengolahan, pengawasan_pengolahan, koseka'];

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

        $columns = ['NIK', 'Kode Penugasan'];

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

        $widths = [22, 30];

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
            'pcl_ppl' => 'pcl_ppl',
            'pml' => 'pml',
            'pengolahan' => 'pengolahan',
            'pengawas_pengolahan' => 'pengawasan_pengolahan',
            'koseka' => 'koseka',
            default => $peran,
        };
    }
}
