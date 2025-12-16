<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PetugasTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        // Return sample data with explanation rows
        return [
            [
                'John Doe',
                '1234567890123456',
                'john@example.com',
                '081234567890',
                'Jl. Contoh No. 123, Jakarta',
                'S1',
                '2025',
                'aktif',
                'Non-Organik',
                'Mitra Statistik',
                'Non PNS',
                '123456789012345',
                'Bank BCA',
                '1234567890',
                'John Doe',
                'Contoh catatan mitra',
            ],
            [
                'Jane Smith',
                '9876543210987654',
                'jane@example.com',
                '089876543210',
                'Jl. Example No. 456, Bandung',
                'S2',
                '2024',
                'aktif',
                'Organik',
                'Statistisi Ahli Pertama',
                'III/b',
                '987654321098765',
                'Bank Mandiri',
                '9876543210',
                'Jane Smith',
                'Contoh catatan lainnya',
            ],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 4 kosong
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 5 kosong
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 6 kosong
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 7 kosong
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 8 kosong
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], // Baris 9 kosong
            ['Keterangan: Kolom dengan warna hijau (A-K) adalah field yang wajib diisi. Kolom dengan warna biru (L-P) bersifat opsional.'],
        ];
    }

    public function headings(): array
    {
        return [
            'nama',
            'nik',
            'email',
            'telepon',
            'alamat',
            'pendidikan',
            'tahun_bergabung',
            'status',
            'jenis_petugas',
            'jabatan',
            'golongan',
            'npwp',
            'bank',
            'no_rekening',
            'nama_rekening',
            'catatan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(25);
        $sheet->getColumnDimension('M')->setWidth(25);
        $sheet->getColumnDimension('N')->setWidth(40);
        $sheet->getColumnDimension('O')->setWidth(25);
        $sheet->getColumnDimension('P')->setWidth(30);

        // Style for mandatory fields (A:K) - Green
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style for optional fields (L:P) - Blue
        $sheet->getStyle('L1:P1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A90E2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style for note row (row 10)
        $sheet->getStyle('A10:P10')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->mergeCells('A10:P10');

        return [];
    }

    public function title(): string
    {
        return 'Template Import Petugas';
    }
}
