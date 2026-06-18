<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
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

    public function test_addendum_mode_ignores_addendum_history_from_other_months(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Nurlena Rustam',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $creator = User::factory()->create();

        Carbon::setTestNow("{$tahun}-03-03 08:00:00");

        $periodeMaret = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 3,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiMaret = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMaret->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        $spkMaret = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MARET/NURLENA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiMaret->id,
            'alokasi_petugas_ids' => [$alokasiMaret->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 21,
            'tanggal_spk' => "{$tahun}-03-03",
            'tanggal_mulai_kerja' => "{$tahun}-03-01",
            'tanggal_selesai_kerja' => "{$tahun}-03-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Maret',
            'nilai_kontrak' => 200000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MARET/NURLENA/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiMaret->id,
            'alokasi_petugas_ids' => [$alokasiMaret->id],
            'parent_spk_id' => $spkMaret->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 21,
            'tanggal_spk' => "{$tahun}-03-20",
            'tanggal_mulai_kerja' => "{$tahun}-03-01",
            'tanggal_selesai_kerja' => "{$tahun}-03-31",
            'uraian_pekerjaan' => 'Addendum Maret',
            'nilai_kontrak' => 240000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-05-04 09:00:00");

        $periodeMeiDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiMeiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMeiDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NURLENA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiMeiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiMeiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 31,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $periodeMeiPerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeMeiPerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $response = $this->get('/spk/periode/'.$periodeMeiPerubahan->hashed_id.'/addendum?bulan=5&tahun='.$tahun.'&mode=addendum');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spk/Addendum'));

        $petugasList = collect($response->inertiaProps('petugas_list'));

        $this->assertCount(1, $petugasList);
        $this->assertSame('Nurlena Rustam', $petugasList->first()['petugas']['nama']);
        $this->assertSame('SPK/ORI/MEI/NURLENA', $petugasList->first()['existing_spk_nomor']);
    }

    public function test_may_revision_after_april_spk_does_not_show_addendum_without_may_original_spk(): void
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
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true));
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true));

        $addendumResponse = $this->get('/spk/periode/'.$periodeMei->hashed_id.'/addendum?bulan=5&tahun='.$tahun);
        $addendumResponse->assertRedirect(route('spk.index'));
        $addendumResponse->assertSessionHas('warning', 'Tidak ada petugas yang dapat dibuatkan addendum Perjanjian Kerja untuk periode tersebut.');
    }

    public function test_addendum_url_redirects_when_month_has_no_eligible_petugas(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-URL',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 404,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-URL/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 404,
            'tanggal_spk' => "{$tahun}-06-03",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Addendum kerja Mei',
            'nilai_kontrak' => 400000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk/periode/'.$periodePerubahan->hashed_id.'/addendum?bulan=5&tahun='.$tahun.'&mode=addendum');

        $response->assertRedirect(route('spk.index'));
        $response->assertSessionHas('warning', 'Tidak ada petugas yang dapat dibuatkan addendum Perjanjian Kerja untuk periode tersebut.');
    }

    public function test_addendum_url_redirects_when_regenerate_pk_is_still_needed(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita ReGenerate',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanA = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanB = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-REGEN',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 405,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $controller = app(App\Http\Controllers\SpkController::class);
        $reflection = new \ReflectionMethod($controller, 'hasNewKegiatanAfterSpk');
        $reflection->setAccessible(true);
        $monthPeriodes = PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", ['05'])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->with('spk')
            ->get();

        var_dump((bool) $reflection->invoke($controller, $tahun, 5, $monthPeriodes));
        foreach ($controller->resolveRegenerateCandidatesForMonth($tahun, 5) as $candidate) {
            var_dump($candidate);
        }

        $response = $this->get('/spk/periode/'.$periodePerubahan->hashed_id.'/addendum?bulan=5&tahun='.$tahun);

        $response->assertRedirect(route('spk.index'));
        $response->assertSessionHas('warning', 'Silakan selesaikan re-generate SPK terlebih dahulu sebelum membuat addendum.');
    }

    public function test_generate_addendum_uses_all_month_allocations_even_when_bulan_format_is_mixed(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita Mixed Month',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanA = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Kegiatan A',
        ]);

        $kegiatanB = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Kegiatan B',
        ]);

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'bulan' => 5,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 132000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();
        $this->actingAs($creator);

        Penandatangan::query()->create([
            'nama' => 'PPK Test',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => now()->startOfYear(),
            'periode_selesai' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $parentSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-MIXED',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 505,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->post('/spk/periode/'.$periodePerubahan->hashed_id.'/petugas/'.$petugas->hashed_id.'/generate-addendum', [
            'tanggal_spk' => "{$tahun}-06-03",
            'sampai_tanggal' => "{$tahun}-05-31",
            'parent_spk_id' => $parentSpk->id,
            'addendum_number' => 1,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $generatedAddendum = Spk::query()
            ->where('parent_spk_id', $parentSpk->id)
            ->where('addendum_number', 1)
            ->first();

        $this->assertNotNull($generatedAddendum);
        $this->assertSame(432000.0, (float) $generatedAddendum->nilai_kontrak);
        $this->assertEqualsCanonicalizing([$alokasiDikirim->id, $alokasiPerubahan->id], $generatedAddendum->alokasi_petugas_ids ?? []);
    }
}
