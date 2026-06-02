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
}
