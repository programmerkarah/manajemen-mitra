<?php

namespace App\Exports;

use App\Models\Petugas;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PetugasExistingExport extends DefaultValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function collection(): Collection
    {
        return Petugas::query()
            ->latest()
            ->get()
            ->map(function (Petugas $petugas): array {
                return [
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'email' => $petugas->email,
                    'telepon' => $petugas->telepon,
                    'alamat' => $petugas->alamat,
                    'pendidikan' => $petugas->pendidikan,
                    'tahun_bergabung' => $petugas->tahun_bergabung,
                    'status' => $petugas->status,
                    'jenis_petugas' => $petugas->jenis_petugas,
                    'jabatan' => $petugas->jabatan,
                    'golongan' => $petugas->golongan,
                    'jenis_kelamin' => $petugas->jenis_kelamin,
                    'kecamatan' => $petugas->kecamatan,
                    'desa_kelurahan' => $petugas->desa_kelurahan,
                    'tanggal_lahir' => $petugas->tanggal_lahir?->format('Y-m-d'),
                    'npwp' => $petugas->npwp,
                    'bank' => $petugas->bank,
                    'no_rekening' => $petugas->no_rekening,
                    'nama_rekening' => $petugas->nama_rekening,
                    'catatan' => $petugas->catatan,
                ];
            });
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
            'jenis_kelamin',
            'kecamatan',
            'desa_kelurahan',
            'tanggal_lahir',
            'npwp',
            'bank',
            'no_rekening',
            'nama_rekening',
            'catatan',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
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
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(25);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(25);
        $sheet->getColumnDimension('Q')->setWidth(25);
        $sheet->getColumnDimension('R')->setWidth(20);
        $sheet->getColumnDimension('S')->setWidth(25);
        $sheet->getColumnDimension('T')->setWidth(30);

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('L1:O1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC107'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('P1:T1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A90E2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Data Existing Petugas';
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
