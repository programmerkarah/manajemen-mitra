<?php

namespace App\Exports;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlokasiPetugasTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * Constructor to set periode alokasi and type (create or edit)
     */
    public function __construct(
        protected ?int $periodeAlokasiId,
        protected string $type = 'create', // 'create' or 'edit'
    ) {}

    public function array(): array
    {
        $data = [];

        if ($this->type === 'edit' && $this->periodeAlokasiId) {
            // For edit: fetch existing allocations
            $entries = AlokasiPetugas::where('periode_alokasi_id', $this->periodeAlokasiId)
                ->with(['petugas', 'periodeAlokasi'])
                ->get();

            foreach ($entries as $entry) {
                $data[] = [
                    $entry->petugas?->nik ?? '',
                    $entry->petugas?->nama ?? '',
                    $this->getStatusKepegawaianLabel($entry->status_kepegawaian),
                    $this->getPeranLabel($entry->peran),
                    $entry->jumlah_satuan ?? '',
                    $entry->total_honor ?? '',
                    $entry->is_partial_payment ? 'Ya' : 'Tidak',
                    $entry->partial_jumlah_satuan ?? '',
                    $entry->estimasi_honor_partial ?? '',
                    $entry->jumlah_satuan_listing ?? '',
                    $entry->total_honor_listing ?? '',
                    $entry->non_response ?? '',
                    $entry->non_response_listing ?? '',
                    $entry->catatan ?? '',
                ];
            }
        } else {
            // For create: provide empty template with sample rows
            $data[] = [
                '1234567890123456',
                'John Doe',
                'Non-Organik',
                'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
                '10',
                '1500000',
                'Tidak',
                '',
                '',
                '',
                '',
                '',
                '',
                'Contoh catatan',
            ];

            // Add 5 more empty rows
            for ($i = 0; $i < 5; $i++) {
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Tidak',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
            }
        }

        // Add instruction row
        $data[] = [''];
        $data[] = ['Petunjuk Pengisian:'];
        $data[] = ['1. NIK dan Nama Petugas harus sesuai dengan data yang sudah terdaftar di sistem'];
        $data[] = ['2. Status Kepegawaian: Organik (PNS/PPPK) atau Non-Organik'];
        $data[] = ['3. Jenis Penugasan: PCL/PPL, PML, Petugas Pengolahan Data, Pengawas Pengolahan, atau Koseka'];
        $data[] = ['4. Jumlah Satuan: Jumlah unit pencacahan yang ditugaskan'];
        $data[] = ['5. Honor: Honor total untuk pencacahan'];

        return $data;
    }

    public function headings(): array
    {
        return [
            'NIK Petugas',
            'Nama Petugas',
            'Status Kepegawaian',
            'Jenis Penugasan',
            'Jumlah Satuan (Pencacahan)',
            'Honor (Pencacahan)',
            'Pembayaran Parsial',
            'Jumlah Satuan Parsial',
            'Honor Parsial',
            'Jumlah Satuan Listing',
            'Honor Listing',
            'Non Response Pencacahan',
            'Non Response Listing',
            'Catatan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(25);

        // Style header row
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_YELLOW);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Highlight required columns (A, B, C, D, E, F)
        for ($col = 'A'; $col <= 'F'; $col++) {
            $sheet->getStyle("{$col}2:{$col}100")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("{$col}2:{$col}100")->getFill()->getStartColor()->setARGB('FFFFCCCC'); // Light red
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

    private function getStatusKepegawaianLabel(string $status): string
    {
        return $status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik';
    }

    private function getPeranLabel(string $peran): string
    {
        $labels = [
            'pcl_ppl' => 'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
            'pml' => 'PML (Petugas Pemeriksaan Lapangan)',
            'pengolahan' => 'Petugas Pengolahan Data',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            'koseka' => 'Koseka (Koordinator Sensus Kecamatan)',
        ];

        return $labels[$peran] ?? $peran;
    }
}
