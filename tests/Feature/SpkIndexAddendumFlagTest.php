<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkIndexAddendumFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_regenerate_addendum_flag_is_false_when_latest_addendum_matches_latest_allocation_snapshot(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '03';

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanPerubahan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Cici Liani Indrias Putri',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanPerubahan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 3,
            'tahun' => $tahun,
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatanPerubahan->id,
            'bulan' => 3,
            'tahun' => $tahun,
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 7,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 700000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/2026/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ADD/2026/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Addendum kerja',
            'nilai_kontrak' => 700000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $maret = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 3;
        });

        $this->assertNotNull($maret);
        $this->assertFalse((bool) ($maret['has_addendum_changes'] ?? true));
    }
}
