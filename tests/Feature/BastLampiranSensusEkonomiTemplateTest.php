<?php

namespace Tests\Feature;

use Tests\TestCase;

class BastLampiranSensusEkonomiTemplateTest extends TestCase
{
    public function test_sensus_ekonomi_bast_lampiran_renders_landscape_and_template_sections(): void
    {
        $html = view('bast-lampiran-spk-sensus-ekonomi', [
            'bast' => (object) [
                'nomor_bast' => 'B-001/BAST-SE2026/1373/PL.200/2026',
                'tanggal_bast' => '2026-08-31',
                'tanggal_pelaksanaan' => '2026-06-15',
                'tanggal_selesai' => '2026-08-31',
                'lokasi_kegiatan' => 'Kota Sawahlunto',
                'nama_ppk' => 'PPK Uji',
                'petugas' => [
                    'nama' => 'Petugas Uji',
                ],
                'target_jumlah_frame_sampel' => 12,
                'target_muatan_prelist_usaha' => 124,
                'target_muatan_prelist_keluarga' => 493,
                'hasil_jumlah_frame_sampel' => null,
                'hasil_realisasi_usaha' => 124,
                'hasil_realisasi_keluarga' => 493,
                'kegiatan_list' => [
                    [
                        'nama_kegiatan' => 'Sensus Ekonomi 2026',
                        'jenis_kegiatan' => 'sensus',
                        'peran' => 'pcl_ppl',
                        'uraian_pencacahan' => 'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin I dan termin II',
                        'nilai_perjanjian' => 2500000,
                        'wilayah_kerja' => [
                            [
                                'no' => 1,
                                'nama_kecamatan' => 'Talawi',
                                'nama_desa' => 'Kolok Mudik',
                                'jumlah_sls' => '12',
                                'muatan_label' => '124 usaha/493 keluarga',
                            ],
                        ],
                        'ketua_tim' => [
                            'nama' => 'Ketua Tim Uji',
                        ],
                    ],
                    [
                        'nama_kegiatan' => 'Sensus Ekonomi 2026',
                        'jenis_kegiatan' => 'sensus',
                        'peran' => 'pml',
                        'uraian_pencacahan' => 'Placeholder lama PML',
                        'nilai_perjanjian' => 2500000,
                        'wilayah_kerja' => [],
                        'ketua_tim' => [
                            'nama' => 'Ketua Tim Uji',
                        ],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('size: A4 landscape;', $html);
        $this->assertSame(4, substr_count($html, '<div class="page">'));
        $this->assertStringContainsString('BERITA ACARA SERAH TERIMA PEKERJAAN', $html);
        $this->assertStringContainsString('PETUGAS LAPANGAN SENSUS EKONOMI 2026', $html);
        $this->assertStringContainsString('BERITA ACARA SERAH TERIMA', $html);
        $this->assertStringContainsString('PETUGAS PEMERIKSAAN LAPANGAN SENSUS EKONOMI 2026', $html);
        $this->assertStringContainsString('I.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DAFTAR URAIAN PEKERJAAN', $html);
        $this->assertStringContainsString('II.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;BUKTI PENYELESAIAN PEKERJAAN', $html);
        $this->assertStringContainsString('A. DAFTAR WILAYAH KERJA', $html);
        $this->assertStringContainsString('B. Screenshot Aplikasi Fasih', $html);
        $this->assertStringContainsString('Talawi', $html);
        $this->assertStringContainsString('Kolok Mudik', $html);
        $this->assertStringContainsString('Melakukan pendataan lapangan <em>door to door</em> Sensus Ekonomi 2026 termin I dan termin II', $html);
        $this->assertStringContainsString('1. Melakukan pemeriksaan hasil pendataan lapangan <em>door to door</em> Sensus Ekonomi 2026 termin I dan termin II', $html);
        $this->assertStringContainsString('2. Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan <em>door to door</em> Sensus Ekonomi 2026', $html);
        $this->assertStringContainsString('12 SLS/sub-SLS dan/atau 124 usaha/493 keluarga', $html);
        $this->assertStringContainsString('Telah mencapai target pekerjaan sebesar 12 SLS/sub-SLS dan/atau 124 usaha/493 keluarga', $html);
    }
}
