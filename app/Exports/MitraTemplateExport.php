<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MitraTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        // Return sample data
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
                '987654321098765',
                'Bank Mandiri',
                '9876543210',
                'Jane Smith',
                'Contoh catatan lainnya',
            ],
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
        $sheet->getColumnDimension('M')->setWidth(40);

        return [
            // Header style
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A90E2'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Template Import Mitra';
    }
}
