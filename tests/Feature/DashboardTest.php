<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Bast;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('mitraReviewSummary')
                ->where('mitraReviewSummary.year.total_reviews', 0)
                ->where('mitraReviewSummary.current_month.total_reviews', 0));
    }

    public function test_dashboard_kegiatan_bulan_ini_scopes_spk_count_to_each_periode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $year = $now->year;
        $month = (int) $now->format('m');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'tahun' => $year,
            'status' => 'dikirim',
        ]);

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasi = AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        Spk::query()->create([
            'nomor_spk' => sprintf('SPK/%d/%04d', 1, $alokasi->id),
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'tanggal_spk' => $now->toDateString(),
            'tanggal_mulai_kerja' => $now->toDateString(),
            'tanggal_selesai_kerja' => $now->copy()->addDays(7)->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja dashboard',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'Pejabat Pembuat Komitmen',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('kegiatanBulanIni.0.hashed_id', $kegiatan->hashed_id)
                ->where('kegiatanBulanIni.0.periode_alokasi.hashed_id', $periode->hashed_id)
                ->where('kegiatanBulanIni.0.spk.count', 1)
                ->where('kegiatanBulanIni.0.spk.has_spk', true));
    }

    public function test_dashboard_counts_spk_and_bast_for_each_kegiatan_based_on_allocated_mitra_in_same_month(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $year = $now->year;
        $month = (int) $now->format('m');
        $monthPadded = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        $kegiatanA = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        $kegiatanB = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        $periodeA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'bulan' => $monthPadded,
            'tahun' => $year,
            'status' => 'dikirim',
        ]);

        $periodeB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'bulan' => $monthPadded,
            'tahun' => $year,
            'status' => 'dikirim',
        ]);

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasiA = AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periodeA->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periodeB->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $spk = Spk::query()->create([
            'nomor_spk' => sprintf('SPK/%d/%04d', 2, $alokasiA->id),
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasiA->id,
            'tanggal_spk' => $now->toDateString(),
            'tanggal_mulai_kerja' => $now->toDateString(),
            'tanggal_selesai_kerja' => $now->copy()->addDays(7)->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja lintas kegiatan',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'Pejabat Pembuat Komitmen',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        Bast::query()->create([
            'nomor_bast' => sprintf('BAST/%d/%04d', 1, $spk->id),
            'spk_id' => $spk->id,
            'periode_alokasi_id' => $periodeA->id,
            'kegiatan_id' => $kegiatanA->id,
            'tanggal_bast' => $now->copy()->addDays(8)->toDateString(),
            'tanggal_serah_terima' => $now->copy()->addDays(8)->toDateString(),
            'menggunakan_fasih' => false,
            'uraian_pekerjaan' => 'BAST lintas kegiatan',
            'nama_ketua_tim' => 'Ketua Tim',
            'nip_ketua_tim' => '198001012010011002',
            'nama_ppk' => 'Pejabat Pembuat Komitmen',
            'nip_ppk' => '198001012010011001',
            'hasil_pekerjaan' => 'Selesai',
            'status' => 'diterima',
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $kegiatanBulanIni = collect($response->viewData('page')['props']['kegiatanBulanIni'] ?? [])
            ->keyBy('hashed_id');

        $kegiatanAProps = $kegiatanBulanIni->get($kegiatanA->hashed_id);
        $kegiatanBProps = $kegiatanBulanIni->get($kegiatanB->hashed_id);

        $this->assertNotNull($kegiatanAProps);
        $this->assertNotNull($kegiatanBProps);

        $this->assertSame(1, data_get($kegiatanAProps, 'spk.count'));
        $this->assertTrue((bool) data_get($kegiatanAProps, 'spk.is_complete'));
        $this->assertSame(1, data_get($kegiatanAProps, 'bast.count'));
        $this->assertTrue((bool) data_get($kegiatanAProps, 'bast.is_complete'));

        $this->assertSame(1, data_get($kegiatanBProps, 'spk.count'));
        $this->assertTrue((bool) data_get($kegiatanBProps, 'spk.is_complete'));
        $this->assertSame(1, data_get($kegiatanBProps, 'bast.count'));
        $this->assertTrue((bool) data_get($kegiatanBProps, 'bast.is_complete'));
    }

    public function test_dashboard_marks_spk_and_bast_as_not_required_when_no_mitra_statistik_is_allocated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $year = $now->year;
        $month = (int) $now->format('m');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'tahun' => $year,
            'status' => 'dikirim',
        ]);

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'organik',
        ]);

        AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 0,
            'total_honor_listing' => 0,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $kegiatanProps = collect($response->viewData('page')['props']['kegiatanBulanIni'] ?? [])
            ->firstWhere('hashed_id', $kegiatan->hashed_id);

        $this->assertNotNull($kegiatanProps);
        $this->assertFalse((bool) data_get($kegiatanProps, 'spk.requires_document'));
        $this->assertFalse((bool) data_get($kegiatanProps, 'spk.is_complete'));
        $this->assertFalse((bool) data_get($kegiatanProps, 'bast.requires_document'));
        $this->assertFalse((bool) data_get($kegiatanProps, 'bast.is_complete'));
    }

    public function test_attention_items_for_ketua_tim_only_include_own_draft_kegiatan(): void
    {
        $ketuaRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $ketua = User::factory()->create();
        $ketua->roles()->attach($ketuaRole->id);

        $otherKetua = User::factory()->create();
        $otherKetua->roles()->attach($ketuaRole->id);

        Kegiatan::factory()->create([
            'status' => 'draft',
            'tahun_anggaran' => Carbon::now()->year,
            'ketua_tim_user_id' => $ketua->id,
        ]);

        Kegiatan::factory()->create([
            'status' => 'draft',
            'tahun_anggaran' => Carbon::now()->year,
            'ketua_tim_user_id' => $otherKetua->id,
        ]);

        $this->actingAs($ketua)
            ->withSession(['active_role_id' => $ketuaRole->id]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('attentionItems', 1)
                ->where('attentionItems.0.key', 'kegiatan_draft')
                ->where('attentionItems.0.count', 1));
    }

    public function test_user_with_admin_active_role_sees_all_kegiatan_data(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );
        $ketuaRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach([$adminRole->id, $ketuaRole->id]);

        $otherKetua = User::factory()->create();
        $otherKetua->roles()->attach($ketuaRole->id);

        $year = Carbon::now()->year;
        $startDate = Carbon::now()->copy()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->copy()->endOfMonth()->toDateString();

        Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'ketua_tim_user_id' => $adminUser->id,
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
        ]);

        Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'ketua_tim_user_id' => $otherKetua->id,
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
        ]);

        $this->actingAs($adminUser)
            ->withSession(['active_role_id' => $adminRole->id]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('kegiatanBulanIni', 2));
    }

    public function test_dashboard_uses_latest_sk_when_no_current_month_change_indicator(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $year = $now->year;
        $currentMonth = (int) $now->format('m');
        $previousMonth = max(1, $currentMonth - 1);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT),
            'tahun' => $year,
            'status' => 'dikirim',
            'revision_number' => 0,
            'parent_periode_id' => null,
        ]);

        $skPrevious = SkKpa::query()->create([
            'nomor_sk' => 'SK-DASH-001',
            'kegiatan_id' => $kegiatan->id,
            'bulan' => $previousMonth,
            'tahun' => $year,
            'tanggal_sk' => $now->copy()->subMonth()->toDateString(),
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'SK fallback dashboard',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/dashboard-fallback.pdf',
            'status' => 'diterbitkan',
            'is_signed' => true,
            'created_by' => $user->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('kegiatanBulanIni.0.sk.hashed_id', $skPrevious->hashed_id)
                ->where('kegiatanBulanIni.0.sk_meta.source', 'periode_terakhir')
                ->where('kegiatanBulanIni.0.sk_meta.show_missing', false));
    }

    public function test_dashboard_marks_sk_missing_when_no_sk_exists_and_no_change_indicator(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $year = $now->year;
        $currentMonth = (int) $now->format('m');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'aktif',
            'tahun_anggaran' => $year,
            'tanggal_mulai' => $now->copy()->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->copy()->endOfMonth()->toDateString(),
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT),
            'tahun' => $year,
            'status' => 'dikirim',
            'revision_number' => 0,
            'parent_periode_id' => null,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('kegiatanBulanIni.0.sk', null)
                ->where('kegiatanBulanIni.0.sk_meta.show_missing', true));
    }
}
