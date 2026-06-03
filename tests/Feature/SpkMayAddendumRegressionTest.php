<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkMayAddendumRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_may_revision_after_april_spk_shows_addendum_and_populates_petugas_list(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Nurlena',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        Carbon::setTestNow("{$tahun}-04-05 09:00:00");

        $periodeApril = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 4,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiApril = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeApril->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/APRIL/NURLENA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiApril->id,
            'alokasi_petugas_ids' => [$alokasiApril->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 11,
            'tanggal_spk' => "{$tahun}-04-05",
            'tanggal_mulai_kerja' => "{$tahun}-04-01",
            'tanggal_selesai_kerja' => "{$tahun}-04-30",
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-05-08 09:00:00");

        $periodeMei = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMei->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $indexResponse = $this->get('/spk');
        $indexResponse->assertStatus(200);

        $page = $indexResponse->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mei = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertNotNull($mei);
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true));
        $this->assertTrue(
            (bool) ($mei['has_incomplete_addendum'] ?? false)
                || (bool) ($mei['has_addendum_changes'] ?? false),
        );

        $addendumResponse = $this->get('/spk/periode/'.$periodeMei->hashed_id.'/addendum?bulan=5&tahun='.$tahun);
        $addendumResponse->assertStatus(200);
        $addendumResponse->assertInertia(fn ($page) => $page->component('Spk/Addendum'));

        $petugasList = collect($addendumResponse->inertiaProps('petugas_list'));

        $this->assertCount(1, $petugasList);
        $this->assertSame('Nurlena', $petugasList->first()['petugas']['nama']);
        $this->assertSame('SPK/ORI/APRIL/NURLENA', $petugasList->first()['existing_spk_nomor']);
    }
}
