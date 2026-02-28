<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class SpkLampiranHonorFilterTest extends TestCase
{
    public function test_addendum_lampiran_only_renders_rows_with_positive_honor(): void
    {
        $html = view('spk-addendum-lampiran', [
            'petugas' => (object) [
                'nama' => 'Petugas Uji',
                'jenis_petugas' => 'non-organik',
            ],
            'addendum_number' => 1,
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'survei',
                'nama_kegiatan' => 'Survei Uji',
            ],
            'peran' => 'pcl_ppl',
            'bulan_label' => 'Januari',
            'tahun' => 2026,
            'nomorSpk' => '001/ABC/001',
            'tanggalSpk' => Carbon::parse('2026-01-01'),
            'sampaiTanggal' => Carbon::parse('2026-01-31'),
            'kegiatan_list' => [
                [
                    'nama_kegiatan' => 'Kegiatan Honor Nol',
                    'peran' => 'pcl_ppl',
                    'peran_label' => 'Pencacah',
                    'jumlah_satuan' => 10,
                    'jumlah_satuan_listing' => 0,
                    'total_honor' => 0,
                    'total_honor_listing' => 0,
                    'satuan_kode' => 'DOK',
                    'kode_coa' => '123',
                    'periode_mulai' => '2026-01-01',
                    'periode_selesai' => '2026-01-10',
                ],
                [
                    'nama_kegiatan' => 'Kegiatan Honor Positif',
                    'peran' => 'pcl_ppl',
                    'peran_label' => 'Pencacah',
                    'jumlah_satuan' => 2,
                    'jumlah_satuan_listing' => 0,
                    'total_honor' => 10000,
                    'total_honor_listing' => 0,
                    'satuan_kode' => 'DOK',
                    'kode_coa' => '456',
                    'periode_mulai' => '2026-01-11',
                    'periode_selesai' => '2026-01-20',
                ],
            ],
        ])->render();

        $this->assertStringNotContainsString('Kegiatan Honor Nol', $html);
        $this->assertStringContainsString('Kegiatan Honor Positif', $html);
    }

    public function test_spk_lampiran_only_renders_tasks_with_positive_honor(): void
    {
        $html = view('spk-lampiran', [
            'petugas' => (object) [
                'nama' => 'Petugas Uji',
                'jenis_petugas' => 'non-organik',
            ],
            'kegiatan' => (object) [
                'jenis_kegiatan' => 'survei',
                'nama_kegiatan' => 'Survei Uji',
            ],
            'periode' => (object) [
                'bulan' => 1,
                'tahun' => 2026,
            ],
            'workType' => 'lapangan',
            'nomorSpk' => '001/ABC/001',
            'totalHonor' => 15000,
            'bebanAnggaran' => '111.222',
            'uraianTugas' => [
                [
                    'uraian' => 'Uraian Honor Nol',
                    'volume' => 5,
                    'satuan' => 'DOK',
                    'harga_satuan' => 0,
                    'jumlah' => 0,
                    'kode_coa' => '123',
                    'tanggal_mulai' => '2026-01-01',
                    'tanggal_selesai' => '2026-01-10',
                ],
                [
                    'uraian' => 'Uraian Honor Positif',
                    'volume' => 3,
                    'satuan' => 'DOK',
                    'harga_satuan' => 5000,
                    'jumlah' => 15000,
                    'kode_coa' => '456',
                    'tanggal_mulai' => '2026-01-11',
                    'tanggal_selesai' => '2026-01-20',
                ],
            ],
        ])->render();

        $this->assertStringNotContainsString('Uraian Honor Nol', $html);
        $this->assertStringContainsString('Uraian Honor Positif', $html);
    }
}
