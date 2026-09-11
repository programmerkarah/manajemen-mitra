<?php

namespace Tests\Feature;

use App\Http\Controllers\SpkController;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\Spk;
use App\Models\User;
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

    public function test_spk_lampiran_renders_partial_volume_and_partial_honor_values(): void
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
            'totalHonor' => 10000,
            'bebanAnggaran' => '111.222',
            'uraianTugas' => [
                [
                    'uraian' => 'Uraian Honor Parsial',
                    'volume' => 2,
                    'satuan' => 'DOK',
                    'harga_satuan' => 5000,
                    'jumlah' => 10000,
                    'kode_coa' => '123',
                    'tanggal_mulai' => '2026-01-01',
                    'tanggal_selesai' => '2026-01-10',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Uraian Honor Parsial', $html);
        $this->assertStringContainsString('>2<', $html);
        $this->assertStringContainsString('10.000', $html);
        $this->assertStringNotContainsString('>5<', $html);
    }

    public function test_spk_lampiran_keeps_listing_volume_from_jumlah_satuan_listing_when_same_month_has_two_phases(): void
    {
        $satuanPencacahan = Satuan::query()->create([
            'kode' => 'D'.bin2hex(random_bytes(4)),
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);
        $satuanListing = Satuan::query()->create([
            'kode' => 'L'.bin2hex(random_bytes(4)),
            'nama' => 'Listing',
            'status' => 'aktif',
        ]);

        $kegiatan = new Kegiatan([
            'id' => 999999,
            'nama_kegiatan' => 'Survei Uji',
            'tahun_anggaran' => 2026,
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'pcl_ppl',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'satuan_id' => $satuanPencacahan->id,
            'rate' => 1000,
            'satuan_listing_id' => $satuanListing->id,
            'rate_listing' => 2000,
            'status' => 'aktif',
        ]);

        $petugas = new Petugas([
            'nama' => 'Petugas Uji',
            'jenis_petugas' => 'non-organik',
        ]);

        $periode = new PeriodeAlokasi([
            'bulan' => 1,
            'tahun' => 2026,
            'tanggal_mulai' => Carbon::parse('2026-01-01'),
            'tanggal_selesai' => Carbon::parse('2026-01-31'),
            'tanggal_mulai_listing' => Carbon::parse('2026-01-01'),
            'tanggal_selesai_listing' => Carbon::parse('2026-01-15'),
        ]);

        $alokasi = new AlokasiPetugas([
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 20,
            'jumlah_satuan_listing' => 8,
            'total_honor' => 20000,
            'total_honor_listing' => 16000,
            'jumlah_unit_sampel' => 10,
        ]);
        $alokasi->setRelation('petugas', $petugas);
        $alokasi->setRelation('periodeAlokasi', $periode);

        $reflection = new \ReflectionMethod(SpkController::class, 'getUraianTugas');
        $reflection->setAccessible(true);

        $rows = $reflection->invoke(new SpkController, $kegiatan, $alokasi);

        $this->assertCount(2, $rows);
        $this->assertSame('listing', $rows[0]['phase']);
        $this->assertSame(8, $rows[0]['volume']);
        $this->assertSame('pencacahan', $rows[1]['phase']);
        $this->assertSame(20, $rows[1]['volume']);
    }

    public function test_regular_spk_number_reuses_canceled_gap_before_higher_numbers(): void
    {
        $user = User::factory()->create();
        $petugas = Petugas::factory()->create();
        $alokasi = AlokasiPetugas::factory()->create([
            'petugas_id' => $petugas->id,
            'periode_alokasi_id' => 1,
            'jumlah_satuan' => 10,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 20000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        for ($i = 1; $i <= 187; $i++) {
            Spk::query()->create([
                'nomor_spk' => "PPIS/13730/{$i}/K/2026",
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasi->id,
                'tanggal_spk' => '2026-09-01',
                'tanggal_mulai_kerja' => '2026-09-01',
                'tanggal_selesai_kerja' => '2026-09-30',
                'uraian_pekerjaan' => 'Uraian',
                'nilai_kontrak' => 20000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '1234567890',
                'file_path' => null,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
        }

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/234/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'tanggal_spk' => '2026-09-15',
            'tanggal_mulai_kerja' => '2026-09-01',
            'tanggal_selesai_kerja' => '2026-09-30',
            'uraian_pekerjaan' => 'Uraian',
            'nilai_kontrak' => 20000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '1234567890',
            'file_path' => null,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $canceledSpk = Spk::query()->where('nomor_spk', 'PPIS/13730/188/K/2026')->firstOrFail();
        $canceledSpk->delete();

        $reflection = new \ReflectionMethod(SpkController::class, 'getNextNomorUrut');
        $reflection->setAccessible(true);

        $this->assertSame(188, $reflection->invoke(new SpkController, 2026));
    }

    public function test_cancelling_regular_root_pk_keeps_addendum_chain_intact(): void
    {
        $user = User::factory()->create();
        $petugas = Petugas::factory()->create();
        $alokasi = AlokasiPetugas::factory()->create([
            'petugas_id' => $petugas->id,
            'periode_alokasi_id' => 1,
            'jumlah_satuan' => 10,
            'total_honor' => 20000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $parentSpk = Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/77/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'tanggal_spk' => '2026-09-01',
            'tanggal_mulai_kerja' => '2026-09-01',
            'tanggal_selesai_kerja' => '2026-09-30',
            'uraian_pekerjaan' => 'Uraian',
            'nilai_kontrak' => 20000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '1234567890',
            'file_path' => null,
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $addendumSpk = Spk::query()->create([
            'parent_spk_id' => $parentSpk->id,
            'addendum_number' => 1,
            'nomor_spk' => 'PPIS/13730/77-A1/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'tanggal_spk' => '2026-09-02',
            'tanggal_mulai_kerja' => '2026-09-01',
            'tanggal_selesai_kerja' => '2026-09-30',
            'uraian_pekerjaan' => 'Uraian',
            'nilai_kontrak' => 22000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '1234567890',
            'file_path' => null,
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $reflection = new \ReflectionMethod(SpkController::class, 'resolveSpksForCancellation');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(new SpkController, collect([$alokasi->id]), $petugas->id);

        $this->assertCount(1, $result);
        $this->assertSame($parentSpk->id, $result->first()->id);
        $this->assertNotSame($addendumSpk->id, $result->first()->id);
    }
}
