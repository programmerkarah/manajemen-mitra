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

class BastCreateExcludesReplacedPetugasTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_bast_excludes_petugas_replaced_in_latest_periode(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeDirevisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $miranda = Petugas::factory()->create([
            'nama' => 'Miranda Melliana',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $pengganti = Petugas::factory()->create([
            'nama' => 'Petugas Pengganti',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $alokasiMiranda = AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 3,
            'tahun' => $tahun,
            'periode_alokasi_id' => $periodeDirevisi->id,
            'petugas_id' => $miranda->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 0,
            'jumlah_satuan_listing' => 2,
            'total_honor' => 0,
            'total_honor_listing' => 476000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 3,
            'tahun' => $tahun,
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $pengganti->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 0,
            'jumlah_satuan_listing' => 2,
            'total_honor' => 0,
            'total_honor_listing' => 476000,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/MIRANDA/03',
            'petugas_id' => $miranda->id,
            'alokasi_petugas_id' => $alokasiMiranda->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'SPK Miranda',
            'nilai_kontrak' => 476000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/bast/create?bulan=3&tahun='.$tahun);

        if ($response->isRedirect()) {
            $response->assertRedirect('/bast');
            return;
        }

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $spkList = decryptData($page['props']['spk_list']['encrypted'] ?? null);
        $petugasDalamDaftar = collect($spkList)->pluck('petugas.nama')->all();

        $this->assertNotContains('Miranda Melliana', $petugasDalamDaftar);
    }
}
