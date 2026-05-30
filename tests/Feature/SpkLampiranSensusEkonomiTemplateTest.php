<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class SpkLampiranSensusEkonomiTemplateTest extends TestCase
{
    public function test_sensus_ekonomi_lampiran_renders_special_columns_and_fixed_work_items(): void
    {
        $html = view('spk-lampiran-sensus-ekonomi', [
            'petugas' => (object) [
                'nama' => 'Petugas Uji',
                'jenis_petugas' => 'non-organik',
            ],
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'sensus',
                'nama_kegiatan' => 'Sensus Ekonomi',
            ],
            'periode' => (object) [
                'tahun' => 2026,
                'tanggal_mulai' => Carbon::parse('2026-06-15'),
                'tanggal_selesai' => Carbon::parse('2026-08-31'),
            ],
            'nomorSpk' => '001/ABC/001',
            'totalHonor' => 2500000,
            'lampiranPayload' => [
                'groups' => [
                    [
                        'items' => [
                            'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin I',
                            'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door Sensus Ekonomi 2026',
                        ],
                        'waktu_penyelesaian' => 'Minimal 1 bulan',
                        'persentase' => '40%',
                        'volume' => '2,5 OB',
                        'nilai_perjanjian' => 1000000,
                    ],
                    [
                        'items' => [
                            'Melakukan pendataan lapangan door to door Sensus Ekonomi 2026 termin II',
                            'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door Sensus Ekonomi 2026',
                        ],
                        'waktu_penyelesaian' => '31 Agustus 2026',
                        'persentase' => '60%',
                        'volume' => '2,5 OB',
                        'nilai_perjanjian' => 1500000,
                    ],
                ],
                'total' => [
                    'waktu_penyelesaian' => '15 Juni-31 Agustus 2026',
                    'persentase' => '100%',
                    'volume' => 'Seluruh muatan 2,5 OB',
                    'nilai_perjanjian' => 2500000,
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Waktu Penyelesaian', $html);
        $this->assertStringContainsString('Persentase', $html);
        $this->assertStringContainsString('Nilai Perjanjian', $html);
        $this->assertStringContainsString('Minimal 1 bulan', $html);
        $this->assertStringContainsString('31 Agustus 2026', $html);
        $this->assertStringContainsString('Seluruh muatan 2,5 OB', $html);
        $this->assertStringContainsString('termin I', $html);
        $this->assertStringContainsString('termin II', $html);
        $this->assertStringNotContainsString('Harga Satuan', $html);
        $this->assertStringNotContainsString('Beban Anggaran', $html);
    }
}
