<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class PdfTypographySpacingTest extends TestCase
{
    public function test_spk_addendum_template_uses_tighter_letter_spacing(): void
    {
        $html = view('spk-addendum-main', [
            'petugas' => (object) [
                'nama' => 'Petugas Addendum',
                'alamat' => 'Sawahlunto',
                'jenis_petugas' => 'non-organik',
            ],
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'survei',
                'nama_kegiatan' => 'Survei Contoh',
            ],
            'periode' => (object) [
                'bulan' => 6,
                'tahun' => 2026,
            ],
            'nomorSpk' => 'PPIS/13730/1/K/2026',
            'parent_nomor_spk' => 'PPIS/13730/1/K/2026',
            'tanggalSpk' => Carbon::parse('2026-06-03'),
            'sampaiTanggal' => Carbon::parse('2026-06-30'),
            'tanggalPerpanjangan' => null,
            'penandatangan' => 'Kepala BPS',
            'kepalaBps' => 'Kepala BPS',
            'peran' => 'pcl',
            'peranLabel' => 'PCL',
            'total_honor' => 1000000,
            'uraianTugas' => ['Uraian 1'],
            'bebanAnggaran' => 'Belanja pegawai',
            'workType' => 'lapangan',
            'bulan_label' => 'Juni',
            'tahun' => 2026,
            'addendum_number' => 1,
            'parent_nomor_spk' => 'PPIS/13730/1/K/2026',
            'hasUbinanKegiatan' => false,
        ])->render();

        $this->assertStringContainsString('letter-spacing: -0.02em;', $html);
    }

    public function test_bast_lampiran_template_uses_tighter_letter_spacing(): void
    {
        $petugas = [
            [
                'nama_petugas' => 'Petugas Listing',
                'nomor_spk' => 'PPIS/13730/1/K/2026',
                'hasil_listing' => 3,
                'non_response_listing' => 1,
                'instrumen_listing' => 'Listing',
                'satuan_listing' => 'dokumen',
                'catatan' => '-',
            ],
            [
                'nama_petugas' => 'Petugas Pendataan',
                'nomor_spk' => 'PPIS/13730/2/K/2026',
                'hasil_pendataan_lapangan' => 4,
                'non_response' => 0,
                'instrumen_pendataan_lapangan' => 'Pendataan',
                'satuan_pendataan_lapangan' => 'dokumen',
                'catatan' => '-',
            ],
        ];

        $html = view('bast-lampiran', [
            'petugas' => $petugas,
            'nama_kegiatan' => 'Survei Contoh',
            'bulan_label' => 'Juni',
            'tahun' => 2026,
            'nama_ketua_tim' => 'Ketua Tim',
            'kepalaBps' => 'Kepala BPS',
            'menggunakan_fasih' => false,
        ])->render();

        $this->assertStringContainsString('letter-spacing: -0.02em;', $html);
    }
}