<?php

namespace Tests\Feature;

use Tests\TestCase;

class BastLampiranPartialOutputTest extends TestCase
{
    public function test_bast_lampiran_renders_partial_result_values(): void
    {
        $html = view('bast-lampiran-spk', [
            'bast' => (object) [
                'nomor_bast' => 'PPIS/13730/1/BAST/2026',
                'tanggal_bast' => '2026-01-31',
                'lokasi_kegiatan' => 'Kota Sawahlunto',
                'petugas' => [
                    'nama' => 'Petugas Uji',
                ],
                'kegiatan_list' => [
                    [
                        'nama_kegiatan' => 'Survei Uji',
                        'jenis_kegiatan' => 'survei',
                        'peran' => 'pcl_ppl',
                        'hasil_listing' => 1,
                        'satuan_listing' => 'dokumen',
                        'non_response_listing' => 0,
                        'hasil_pendataan_lapangan' => 2,
                        'satuan_pendataan' => 'dokumen',
                        'non_response' => 0,
                        'hasil_pengolahan' => null,
                        'hasil_pengolahan_listing' => null,
                        'keterangan' => null,
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('>1<', $html);
        $this->assertStringContainsString('>2<', $html);
        $this->assertStringNotContainsString('>5<', $html);
        $this->assertStringNotContainsString('>8<', $html);
    }
}
