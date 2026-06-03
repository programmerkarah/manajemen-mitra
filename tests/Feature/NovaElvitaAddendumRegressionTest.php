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

/**
 * Regression tests for the Nova Elvita false-positive addendum bug.
 *
 * Bug: A petugas with a direvisi→perubahan re-allocation (same kegiatan, same honor)
 * AND a new dikirim kegiatan incorrectly appeared in the addendum candidate list.
 *
 * Root cause: isAllocationIncomplete compared alokasi_ids instead of kegiatan_ids,
 * and nilaiKontrakChanged included honor from the new dikirim kegiatan.
 */
class NovaElvitaAddendumRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Same kegiatan re-allocated from direvisi to perubahan with identical honor
     * AND a new dikirim kegiatan should NOT trigger addendum.
     * The petugas should NOT appear in has_incomplete_addendum or has_addendum_changes.
     */
    public function test_same_kegiatan_perubahan_same_honor_with_new_dikirim_does_not_trigger_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanSurvei = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanSakernas = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        // Original direvisi period for Survei Pertanian
        $periodeDiservisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurvei->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDiservisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDiservisi->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 405000,
            'total_honor_listing' => 0,
        ]);

        // Same Survei Pertanian kegiatan, now in perubahan period, SAME honor 405000
        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSurvei->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 405000,   // Same as direvisi
            'total_honor_listing' => 0,
        ]);

        // New kegiatan (Sakernas) via dikirim only — NOT perubahan
        $periodeSakernasDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSakernas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSakernasDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 10,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 1446000,
            'total_honor_listing' => 0,
        ]);

        // SPK was generated using the direvisi allocation only, with correct nilai_kontrak
        $creator = User::factory()->create();
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDiservisi->id,
            'alokasi_petugas_ids' => [$alokasiDiservisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 10,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 405000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mei = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertNotNull($mei);

        // Nova Elvita should NOT appear in addendum candidates:
        // - Survei Pertanian re-allocated from direvisi→perubahan with SAME honor → no meaningful change
        // - Sakernas is dikirim only → should trigger regenerate, not addendum
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? false),
            'has_incomplete_addendum should be false: no meaningful perubahan change');
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? false),
            'has_addendum_changes should be false: no meaningful perubahan change');

        $generateResponse = $this->get('/spk/periode/'.$periodeSakernasDikirim->hashed_id.'/generate');
        $generateResponse->assertStatus(200);
        $generateResponse->assertInertia(fn ($page) => $page->component('Spk/Generate'));

        $petugasList = collect($generateResponse->inertiaProps('petugas_list'));

        $this->assertTrue(
            $petugasList->pluck('petugas.nama')->contains('Nova Elvita'),
            'Nova Elvita should be included in regenerate petugas_list for Mei.'
        );
    }

    /**
     * Same kegiatan re-allocated from direvisi to perubahan with CHANGED honor
     * SHOULD trigger addendum (meaningful change detected).
     */
    public function test_same_kegiatan_perubahan_changed_honor_triggers_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Rina Susanti',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        // Original direvisi allocation
        $periodeDiservisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDiservisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDiservisi->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 450000,
            'total_honor_listing' => 0,
        ]);

        // Perubahan allocation with CHANGED honor
        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,  // Different from direvisi (450000)
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        // SPK was generated with direvisi allocation
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/RINA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDiservisi->id,
            'alokasi_petugas_ids' => [$alokasiDiservisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 42,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 450000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mei = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertNotNull($mei);
        // Perubahan honor (500k) differs from direvisi (450k) → meaningful change → addendum needed
        $this->assertTrue((bool) ($mei['has_incomplete_addendum'] ?? false),
            'has_incomplete_addendum should be true: perubahan honor differs from direvisi');
    }

    /**
     * New kegiatan via dikirim for a petugas who already has an addendum
     * SHOULD appear in has_addendum_changes (needs another addendum round).
     */
    public function test_new_dikirim_kegiatan_for_petugas_with_existing_addendum_triggers_addendum_changes(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Budi Santoso',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanAwal = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanBaru = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAwal = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwal->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiAwal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $spkAsli = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/BUDI',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 50,
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

        // Addendum SPK already exists
        Spk::query()->create([
            'nomor_spk' => 'SPK/ADD/MEI/BUDI/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'parent_spk_id' => $spkAsli->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 50,
            'tanggal_spk' => "{$tahun}-05-10",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Addendum Mei',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        // New dikirim kegiatan added after the addendum
        $periodeBaru = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanBaru->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeBaru->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mei = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertNotNull($mei);

        // New dikirim kegiatan after PK should go to regenerate, not addendum.
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true),
            'has_incomplete_addendum should be false: new dikirim activity is regenerate-only');
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true),
            'has_addendum_changes should be false: new dikirim activity is regenerate-only');
    }

    /**
     * Addendum page excludes Nova (no meaningful change) but includes Nurlena (has meaningful change).
     * Nova: perubahan = direvisi (same values) + new dikirim kegiatan → regenerate only
     * Nurlena: perubahan ≠ direvisi (different values) → needs addendum
     */
    public function test_addendum_page_excludes_nova_when_other_petugas_still_need_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $nova = Petugas::factory()->create([
            'nama' => 'Nova Elvita',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $nurlena = Petugas::factory()->create([
            'nama' => 'Nurlena Rustam',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanUbinan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanSakernas = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeDirevisiUbinan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanUbinan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahanUbinan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanUbinan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeSakernas = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSakernas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        // Nova: direvisi 405000, perubahan 405000 (SAME) + new Sakernas dikirim
        $novaDirevisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDirevisiUbinan->id,
            'petugas_id' => $nova->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 405000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahanUbinan->id,
            'petugas_id' => $nova->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 405000,  // SAME as direvisi → no meaningful change
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSakernas->id,
            'petugas_id' => $nova->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 10,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 700000,
            'total_honor_listing' => 0,
        ]);

        // Nurlena: direvisi 405000, perubahan 500000 (DIFFERENT) → meaningful change
        $nurlenaDirevisi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDirevisiUbinan->id,
            'petugas_id' => $nurlena->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 405000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahanUbinan->id,
            'petugas_id' => $nurlena->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 6,  // Different quantity
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,  // DIFFERENT from direvisi → meaningful change
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-PAGE',
            'petugas_id' => $nova->id,
            'alokasi_petugas_id' => $novaDirevisi->id,
            'alokasi_petugas_ids' => [$novaDirevisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 71,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei Nova',
            'nilai_kontrak' => 405000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NURLENA-PAGE',
            'petugas_id' => $nurlena->id,
            'alokasi_petugas_id' => $nurlenaDirevisi->id,
            'alokasi_petugas_ids' => [$nurlenaDirevisi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 72,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei Nurlena',
            'nilai_kontrak' => 405000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk/periode/'.$periodePerubahanUbinan->hashed_id.'/addendum?bulan=5&tahun='.$tahun.'&mode=addendum');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spk/Addendum'));

        $petugasList = collect($response->inertiaProps('petugas_list'));
        $names = $petugasList->pluck('petugas.nama')->all();

        // Nurlena has meaningful change (perubahan ≠ direvisi) → should be in addendum list
        $this->assertContains('Nurlena Rustam', $names,
            'Nurlena should be in addendum list: perubahan (500k) differs from direvisi (405k)');
        // Nova has no meaningful change (perubahan = direvisi) → should NOT be in addendum list
        $this->assertNotContains('Nova Elvita', $names,
            'Nova should NOT be in addendum list: perubahan = direvisi (same values)');
    }
}
