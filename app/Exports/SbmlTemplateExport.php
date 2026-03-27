<?php

namespace App\Exports;

use App\Models\Sbml;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SbmlTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * Constructor to set year and type (create or edit)
     */
    public function __construct(
        protected int $tahun,
        protected string $type = 'create', // 'create' or 'edit'
    ) {}

    public function array(): array
    {
        $data = [];

        if ($this->type === 'edit') {
            // For edit: fetch existing data from database
            $entries = Sbml::where('tahun_anggaran', $this->tahun)
                ->orderByRaw("FIELD(jenis_kegiatan, 'survei', 'sensus')")
                ->orderByRaw("FIELD(status_kepegawaian, 'non_organik', 'organik')")
                ->orderByRaw("FIELD(jenis_penugasan, 'pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan')")
                ->get();

            foreach ($entries as $entry) {
                $data[] = [
                    $this->getJenisKegiatanLabel($entry->jenis_kegiatan),
                    $this->getStatusKepegawaianLabel($entry->status_kepegawaian),
                    $this->getJenisPenugasanLabel($entry->jenis_penugasan),
                    $entry->honor_max,
                    $entry->status === 'aktif' ? 'Aktif' : 'Nonaktif',
                    $entry->keterangan ?? '',
                ];
            }
        } else {
            // For create: generate template with all valid combinations (no koseka in survei)
            $combinations = [
                // Survei - Non Organik
                ['survei', 'non_organik', 'pcl_ppl'],
                ['survei', 'non_organik', 'pml'],
                ['survei', 'non_organik', 'pengolahan'],
                ['survei', 'non_organik', 'pengawas_pengolahan'],
                // Survei - Organik
                ['survei', 'organik', 'pcl_ppl'],
                ['survei', 'organik', 'pml'],
                ['survei', 'organik', 'pengolahan'],
                ['survei', 'organik', 'pengawas_pengolahan'],
                // Sensus - Non Organik
                ['sensus', 'non_organik', 'pcl_ppl'],
                ['sensus', 'non_organik', 'pml'],
                ['sensus', 'non_organik', 'pengolahan'],
                ['sensus', 'non_organik', 'pengawas_pengolahan'],
                ['sensus', 'non_organik', 'koseka'],
                // Sensus - Organik
                ['sensus', 'organik', 'pcl_ppl'],
                ['sensus', 'organik', 'pml'],
                ['sensus', 'organik', 'pengolahan'],
                ['sensus', 'organik', 'pengawas_pengolahan'],
                ['sensus', 'organik', 'koseka'],
            ];

            foreach ($combinations as [$jenis_kegiatan, $status_kepegawaian, $jenis_penugasan]) {
                $data[] = [
                    $this->getJenisKegiatanLabel($jenis_kegiatan),
                    $this->getStatusKepegawaianLabel($status_kepegawaian),
                    $this->getJenisPenugasanLabel($jenis_penugasan),
                    '', // honor_max empty for create
                    'Aktif',
                    '',
                ];
            }
        }

        // Add sample row explanation at the end
        $data[] = [''];
        $data[] = ['Catatan: Pastikan mengisi semua kolom yang diperlukan. Kolom berwarna kuning harus diisi.'];

        return $data;
    }

    public function headings(): array
    {
        return [
            'Jenis Kegiatan',
            'Status Kepegawaian',
            'Jenis Penugasan',
            'Honor Maksimal (IDR)',
            'Status',
            'Keterangan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(30);

        // Style header row
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('1')->getFill()->getStartColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_YELLOW);

        // Highlight required column (D - honor_max)
        $sheet->getStyle('D:D')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('D:D')->getFill()->getStartColor()->setARGB('FFFFFFCC'); // Light yellow

        return [];
    }

    public function title(): string
    {
        return "SBML {$this->tahun}";
    }

    private function getJenisKegiatanLabel(string $jenis): string
    {
        return $jenis === 'sensus' ? 'Sensus' : 'Survei';
    }

    private function getStatusKepegawaianLabel(string $status): string
    {
        return $status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik';
    }

    private function getJenisPenugasanLabel(string $jenis): string
    {
        $labels = [
            'pcl_ppl' => 'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
            'pml' => 'PML (Petugas Pemeriksaan Lapangan)',
            'pengolahan' => 'Petugas Pengolahan Data',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            'koseka' => 'Koseka (Koordinator Sensus Kecamatan)',
        ];

        return $labels[$jenis] ?? $jenis;
    }
}
