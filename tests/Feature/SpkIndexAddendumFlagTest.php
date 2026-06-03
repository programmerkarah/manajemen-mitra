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

class SpkIndexAddendumFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_month_pending_allocation_triggers_regenerate_flag(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '04';

        $petugas = Petugas::factory()->create([
            'nama' => 'Regenerate Petugas',
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

        $periodeBaru = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanBaru->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiAwal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        $alokasiBaru = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeBaru->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Carbon::setTestNow("{$tahun}-04-05 09:00:00");

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/REG/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => "{$tahun}-04-05",
            'tanggal_mulai_kerja' => "{$tahun}-04-01",
            'tanggal_selesai_kerja' => "{$tahun}-04-30",
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow();

        $response = $this->get('/spk');

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $april = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 4;
        });

        $this->assertNotNull($april);
        $this->assertTrue((bool) ($april['has_new_kegiatan_after_spk'] ?? false));
        $this->assertFalse((bool) ($april['has_addendum_changes'] ?? true));
    }

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

        $alokasiDikirim = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodePerubahan->id,
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

    public function test_new_kegiatan_after_original_spk_requires_regenerate_and_not_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '04';

        $petugas = Petugas::factory()->create([
            'nama' => 'Cici Liani',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        Carbon::setTestNow("{$tahun}-04-02 09:00:00");

        $kegiatanAwal = Kegiatan::factory()->create([
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

        $alokasiAwal = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Carbon::setTestNow("{$tahun}-04-05 10:00:00");

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/APRIL/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
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

        Carbon::setTestNow("{$tahun}-04-12 11:00:00");

        $kegiatanBaru = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeBaru = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanBaru->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeBaru->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $april = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 4;
        });

        $this->assertNotNull($april);
        $this->assertTrue((bool) ($april['has_new_kegiatan_after_spk'] ?? false));
        $this->assertFalse((bool) ($april['has_incomplete_addendum'] ?? true));
    }

    public function test_petugas_with_existing_addendum_and_new_kegiatan_requires_addendum_regenerate(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '04';

        $petugas = Petugas::factory()->create([
            'nama' => 'Cici Liani',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        Carbon::setTestNow("{$tahun}-04-02 09:00:00");

        $kegiatanAwal = Kegiatan::factory()->create([
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

        $alokasiAwal = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Carbon::setTestNow("{$tahun}-04-05 10:00:00");

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/APRIL/002',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 2,
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

        Carbon::setTestNow("{$tahun}-04-08 09:00:00");

        Spk::query()->create([
            'nomor_spk' => 'SPK/ADD/APRIL/002',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 2,
            'tanggal_spk' => "{$tahun}-04-08",
            'tanggal_mulai_kerja' => "{$tahun}-04-01",
            'tanggal_selesai_kerja' => "{$tahun}-04-30",
            'uraian_pekerjaan' => 'Addendum kerja',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-04-12 11:00:00");

        $kegiatanBaru = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeBaru = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanBaru->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeBaru->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $april = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 4;
        });

        $this->assertNotNull($april);
        $this->assertFalse((bool) ($april['has_new_kegiatan_after_spk'] ?? true));
        $this->assertTrue((bool) ($april['has_addendum_changes'] ?? false));
    }

    public function test_zero_volume_or_honor_new_allocation_does_not_trigger_addendum_flags(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '04';

        $petugas = Petugas::factory()->create([
            'nama' => 'Riesvi Syafanda',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        Carbon::setTestNow("{$tahun}-04-02 09:00:00");

        $kegiatanAwal = Kegiatan::factory()->create([
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

        $alokasiAwal = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pengolahan',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 19,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 228000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Carbon::setTestNow("{$tahun}-04-05 10:00:00");

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/APRIL/003',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 3,
            'tanggal_spk' => "{$tahun}-04-05",
            'tanggal_mulai_kerja' => "{$tahun}-04-01",
            'tanggal_selesai_kerja' => "{$tahun}-04-30",
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 228000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-04-08 09:00:00");

        Spk::query()->create([
            'nomor_spk' => 'SPK/ADD/APRIL/003',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 3,
            'tanggal_spk' => "{$tahun}-04-08",
            'tanggal_mulai_kerja' => "{$tahun}-04-01",
            'tanggal_selesai_kerja' => "{$tahun}-04-30",
            'uraian_pekerjaan' => 'Addendum kerja',
            'nilai_kontrak' => 228000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-04-12 11:00:00");

        $kegiatanBaruNol = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeBaruNol = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanBaruNol->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeBaruNol->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pengolahan',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 0,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 0,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $april = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 4;
        });

        $this->assertNotNull($april);
        $this->assertFalse((bool) ($april['has_new_kegiatan_after_spk'] ?? true));
        $this->assertFalse((bool) ($april['has_addendum_changes'] ?? true));
    }

    public function test_perubahan_with_complete_allocation_ids_but_honor_mismatch_triggers_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

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

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NURLENA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 41,
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
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true));
        $this->assertTrue((bool) ($mei['has_incomplete_addendum'] ?? false));
    }

    public function test_index_sets_regenerate_when_generate_candidates_exist_and_no_addendum_signal(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
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
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiAwal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 100000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/REGEN-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 98,
            'tanggal_spk' => "{$tahun}-05-04",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
            'nilai_kontrak' => 100000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $periodeDisetujui = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDisetujui->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 150000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
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
        $this->assertTrue((bool) ($mei['has_new_kegiatan_after_spk'] ?? false));
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true));
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true));
    }

    public function test_may_addendum_hides_button_after_latest_addendum_matches_current_snapshot(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

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
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 300000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 101,
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

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 400000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 101,
            'tanggal_spk' => "{$tahun}-05-15",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Addendum kerja',
            'nilai_kontrak' => 400000,
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
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true));
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true));
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true));
    }

    /**
     * Regression test: addendum whose tanggal_spk is in a later month (e.g. June)
     * should still be recognised as belonging to the May contract period.
     * Buttons must not loop / reappear after the addendum is up-to-date.
     */
    public function test_addendum_with_out_of_month_tanggal_spk_is_recognised_correctly(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita OOM',
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
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 300000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'total_honor' => 400000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
        ]);

        $creator = User::factory()->create();

        // Original SPK: tanggal_spk in May
        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-OOM',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 202,
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

        // Addendum: tanggal_spk intentionally in JUNE (created on a later date)
        // but references the May original via parent_spk_id.
        // Covers only the updated perubahan allocation (realistic: only latest effective alloc).
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-OOM/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 202,
            'tanggal_spk' => "{$tahun}-06-03", // <-- June, NOT May
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Addendum kerja Mei (dibuat Juni)',
            'nilai_kontrak' => 400000,
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
        // Addendum covers current allocations — no buttons should appear
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true), 'Re-generate PK should not show when addendum exists (even with out-of-month tanggal_spk)');
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true), 'Re-generate Addendum should not show when addendum is up-to-date');
    }

    public function test_regenerated_original_spk_with_same_kegiatan_does_not_loop_regenerate_flag(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Nova Elvita Loop Guard',
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
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiDikirim = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'peran' => 'pcl_ppl',
            'total_honor' => 300000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
        ]);

        $alokasiPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'status_kepegawaian' => 'non_organik',
            'peran' => 'pcl_ppl',
            'total_honor' => 400000,
            'total_honor_listing' => 0,
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NOVA-LOOP',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id, $alokasiPerubahan->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 303,
            'tanggal_spk' => "{$tahun}-05-20",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei hasil regenerate',
            'nilai_kontrak' => 400000,
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
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true), 'Re-generate PK should not loop for same-kegiatan perubahan already covered by regenerated SPK');
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true));
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true));
    }
}
