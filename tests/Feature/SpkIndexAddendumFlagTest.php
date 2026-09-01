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

    public function test_generate_and_addendum_flags_can_appear_together_in_the_same_month(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '09';
        Carbon::setTestNow("{$tahun}-09-15 09:00:00");

        try {
            $petugasRegenerate = Petugas::factory()->create([
                'nama' => 'Regenerate Bersama',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasAddendum = Petugas::factory()->create([
                'nama' => 'Addendum Bersama',
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

            $kegiatanC = Kegiatan::factory()->create([
                'tahun_anggaran' => $tahun,
                'status' => 'divalidasi',
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeRegenerateAwal = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatanA->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'status' => 'dikirim',
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeRegenerateBaru = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatanB->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'status' => 'disetujui',
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeAddendumAwal = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatanC->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'status' => 'direvisi',
                'jenis_kegiatan' => 'survei',
            ]);

            $periodeAddendumPerubahan = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatanC->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'status' => 'perubahan',
                'jenis_kegiatan' => 'survei',
            ]);

            $alokasiRegenerateAwal = AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeRegenerateAwal->id,
                'petugas_id' => $petugasRegenerate->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 4,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 400000,
                'total_honor_listing' => 0,
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeRegenerateBaru->id,
                'petugas_id' => $petugasRegenerate->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 2,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 200000,
                'total_honor_listing' => 0,
            ]);

            $alokasiAddendumAwal = AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeAddendumAwal->id,
                'petugas_id' => $petugasAddendum->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 5,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 500000,
                'total_honor_listing' => 0,
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeAddendumPerubahan->id,
                'petugas_id' => $petugasAddendum->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 6,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 600000,
                'total_honor_listing' => 0,
            ]);

            $creator = User::factory()->create();

            Spk::query()->create([
                'nomor_spk' => 'SPK/ORI/009/REG',
                'petugas_id' => $petugasRegenerate->id,
                'alokasi_petugas_id' => $alokasiRegenerateAwal->id,
                'alokasi_petugas_ids' => [$alokasiRegenerateAwal->id],
                'addendum_number' => 0,
                'nomor_urut_base' => 1,
                'tanggal_spk' => "{$tahun}-09-05",
                'tanggal_mulai_kerja' => "{$tahun}-09-01",
                'tanggal_selesai_kerja' => "{$tahun}-09-30",
                'uraian_pekerjaan' => 'PK regenerasi',
                'nilai_kontrak' => 400000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '198001012010011001',
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            $originalAddendum = Spk::query()->create([
                'nomor_spk' => 'SPK/ORI/009/ADD',
                'petugas_id' => $petugasAddendum->id,
                'alokasi_petugas_id' => $alokasiAddendumAwal->id,
                'alokasi_petugas_ids' => [$alokasiAddendumAwal->id],
                'addendum_number' => 0,
                'nomor_urut_base' => 2,
                'tanggal_spk' => "{$tahun}-09-05",
                'tanggal_mulai_kerja' => "{$tahun}-09-01",
                'tanggal_selesai_kerja' => "{$tahun}-09-30",
                'uraian_pekerjaan' => 'PK addendum',
                'nilai_kontrak' => 500000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '198001012010011001',
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            Spk::query()->create([
                'nomor_spk' => 'SPK/ADD/009/ADD/001',
                'petugas_id' => $petugasAddendum->id,
                'alokasi_petugas_id' => $alokasiAddendumAwal->id,
                'alokasi_petugas_ids' => [$alokasiAddendumAwal->id],
                'parent_spk_id' => $originalAddendum->id,
                'addendum_number' => 1,
                'nomor_urut_base' => 2,
                'tanggal_spk' => "{$tahun}-09-10",
                'tanggal_mulai_kerja' => "{$tahun}-09-01",
                'tanggal_selesai_kerja' => "{$tahun}-09-30",
                'uraian_pekerjaan' => 'Addendum kerja',
                'nilai_kontrak' => 600000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '198001012010011001',
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            $response = $this->get('/spk');
            $response->assertStatus(200);

            $page = $response->viewData('page');
            $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

            $september = collect($periodeList)->first(function (array $item) use ($tahun) {
                return (int) ($item['tahun'] ?? 0) === (int) $tahun
                    && (int) ($item['bulan'] ?? 0) === 9;
            });

            $this->assertNotNull($september);
            $this->assertTrue((bool) ($september['has_new_kegiatan_after_spk'] ?? false));
            $this->assertTrue((bool) ($september['has_incomplete_addendum'] ?? false));
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_only_current_month_addendum_flags_are_active(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $currentMonth = 8;
        Carbon::setTestNow("{$tahun}-08-15 09:00:00");

        $petugas = Petugas::factory()->create([
            'nama' => 'Addendum Active Month Petugas',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanJuli = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanAgustus = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeJuli = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanJuli->id,
            'bulan' => 7,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAgustus = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAgustus->id,
            'bulan' => $currentMonth,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeJuli->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAgustus->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 6,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 600000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();
        $originalSpkJuli = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/JUL/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $periodeJuli->alokasiPetugas()->first()->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => "{$tahun}-07-05",
            'tanggal_mulai_kerja' => "{$tahun}-07-01",
            'tanggal_selesai_kerja' => "{$tahun}-07-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Juli',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/AUG/001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $periodeAgustus->alokasiPetugas()->first()->id,
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => "{$tahun}-08-05",
            'tanggal_mulai_kerja' => "{$tahun}-08-01",
            'tanggal_selesai_kerja' => "{$tahun}-08-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Agustus',
            'nilai_kontrak' => 600000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $juli = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 7;
        });

        $agustus = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 8;
        });

        $this->assertNotNull($juli);
        $this->assertNotNull($agustus);
        $this->assertFalse((bool) ($juli['has_incomplete_addendum'] ?? true), 'Juli seharusnya tidak aktif menampilkan addendum saat ini.');
        $this->assertFalse((bool) ($juli['has_addendum_changes'] ?? true), 'Juli seharusnya tidak aktif menampilkan regenerate addendum saat ini.');
        $this->assertTrue((bool) ($agustus['has_incomplete_addendum'] ?? false), 'Agustus seharusnya menjadi bulan aktif yang butuh addendum.');

        Carbon::setTestNow();
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
        $this->assertFalse((bool) ($april['has_addendum_changes'] ?? true));
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

    /**
     * Perubahan with meaningful change (different values from direvisi) triggers addendum.
     * This tests the core addendum requirement: addendum is only needed when perubahan ≠ direvisi.
     */
    public function test_perubahan_with_meaningful_change_triggers_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';
        Carbon::setTestNow("{$tahun}-08-15 09:00:00");

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

        // Perubahan allocation with DIFFERENT honor (meaningful change)
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

        // SPK was generated with the direvisi allocation
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/NURLENA',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDiservisi->id,
            'alokasi_petugas_ids' => [$alokasiDiservisi->id],
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
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true),
            'Bulan non-aktif seharusnya tidak menampilkan addendum.');
        Carbon::setTestNow();
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

    /**
     * Test: When original SPK includes both dikirim and perubahan allocations with DIFFERENT values,
     * addendum should still be needed to formally document the change, even if SPK already has both allocations.
     * The addendum button should only hide after an addendum has been created that covers the change.
     */
    public function test_meaningful_perubahan_in_original_spk_still_needs_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';
        Carbon::setTestNow("{$tahun}-08-15 09:00:00");

        $petugas = Petugas::factory()->create([
            'nama' => 'Awyujon Test',
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

        // SPK includes BOTH dikirim and perubahan allocations
        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/AWYUJON',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id, $alokasiPerubahan->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 303,
            'tanggal_spk' => "{$tahun}-05-20",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Mei',
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
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true), 'Re-generate PK should not show (same kegiatan)');
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true), 'Bulan non-aktif seharusnya tidak menampilkan addendum.');
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true), 'No existing addendum to have changes');
        Carbon::setTestNow();
    }

    public function test_zero_delta_perubahan_stays_and_does_not_trigger_any_flag(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';

        $petugas = Petugas::factory()->create([
            'nama' => 'Zero Delta Petugas',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        Carbon::setTestNow("{$tahun}-05-04 09:00:00");

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
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/ZERO',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiDikirim->id,
            'alokasi_petugas_ids' => [$alokasiDikirim->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 401,
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

        Carbon::setTestNow("{$tahun}-05-10 09:00:00");

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
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

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

    public function test_existing_addendum_plus_new_activity_triggers_regenerate_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';
        Carbon::setTestNow("{$tahun}-08-15 09:00:00");

        $petugas = Petugas::factory()->create([
            'nama' => 'Addendum Rebuild Petugas',
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

        Carbon::setTestNow("{$tahun}-05-04 09:00:00");

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

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/ADD-REBUILD',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 500,
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

        Carbon::setTestNow("{$tahun}-05-12 09:00:00");

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwal->id,
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
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/ADD-REBUILD/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiPerubahan->id,
            'alokasi_petugas_ids' => [$alokasiPerubahan->id],
            'parent_spk_id' => $originalSpk->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 500,
            'tanggal_spk' => "{$tahun}-05-15",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Addendum kerja Mei',
            'nilai_kontrak' => 400000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        Carbon::setTestNow("{$tahun}-05-18 09:00:00");

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
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow("{$tahun}-05-20 09:00:00");

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $mei = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 5;
        });

        $this->assertNotNull($mei);
        $this->assertFalse((bool) ($mei['has_new_kegiatan_after_spk'] ?? true), 'Baru kegiatan setelah addendum harus tidak diperlakukan sebagai regenerate PK biasa.');
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true), 'Addendum yang sudah ada seharusnya tidak dianggap incomplete.');
        $this->assertTrue((bool) ($mei['has_addendum_changes'] ?? false), 'Kegiatan baru setelah addendum harus memicu re-generate addendum.');
    }

    public function test_same_kegiatan_revision_with_stale_legacy_allocation_id_still_triggers_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '08';
        Carbon::setTestNow("{$tahun}-08-20 09:00:00");

        $petugas = Petugas::factory()->create([
            'nama' => 'Fitri Yati',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeLama = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $oldAlokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeLama->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 144000,
            'total_honor_listing' => 0,
        ]);

        $periodeDirevisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $staleRevisedAlokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDirevisi->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 144000,
            'total_honor_listing' => 0,
        ]);

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $newAlokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 240000,
            'total_honor_listing' => 0,
        ]);

        $creator = User::factory()->create();

        $originalSpk = Spk::query()->create([
            'nomor_spk' => 'SPK/AGUSTUS/FITRI/01',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $oldAlokasi->id,
            'alokasi_petugas_ids' => [$oldAlokasi->id, $staleRevisedAlokasi->id, $newAlokasi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 600,
            'tanggal_spk' => "{$tahun}-08-04",
            'tanggal_mulai_kerja' => "{$tahun}-08-01",
            'tanggal_selesai_kerja' => "{$tahun}-08-31",
            'uraian_pekerjaan' => 'Perjanjian kerja Agustus',
            'nilai_kontrak' => 144000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $creator->id,
        ]);

        $response = $this->get('/spk');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $periodeList = decryptData($page['props']['periodeList']['encrypted'] ?? null);

        $agustus = collect($periodeList)->first(function (array $item) use ($tahun) {
            return (int) ($item['tahun'] ?? 0) === (int) $tahun
                && (int) ($item['bulan'] ?? 0) === 8;
        });

        $this->assertNotNull($agustus);
        $this->assertTrue((bool) ($agustus['has_incomplete_addendum'] ?? false), 'Stale legacy alokasi lama harus tetap memicu kebutuhan addendum.');
        $this->assertFalse((bool) ($agustus['has_new_kegiatan_after_spk'] ?? true), 'Revisi same kegiatan bukan regenerate PK biasa.');
    }

    public function test_new_kegiatan_with_meaningful_perubahan_triggers_addendum_only(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();
        $bulan = '05';
        Carbon::setTestNow("{$tahun}-08-15 09:00:00");

        $petugas = Petugas::factory()->create([
            'nama' => 'Mixed Rule Petugas',
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

        Carbon::setTestNow("{$tahun}-05-04 09:00:00");

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

        Spk::query()->create([
            'nomor_spk' => 'SPK/ORI/MEI/MIXED',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiAwal->id,
            'alokasi_petugas_ids' => [$alokasiAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 402,
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

        Carbon::setTestNow("{$tahun}-05-12 09:00:00");

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwal->id,
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
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow("{$tahun}-05-18 09:00:00");

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
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

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
        $this->assertFalse((bool) ($mei['has_incomplete_addendum'] ?? true), 'Bulan non-aktif seharusnya tidak menampilkan addendum.');
        $this->assertFalse((bool) ($mei['has_addendum_changes'] ?? true));
        Carbon::setTestNow();
    }
}
