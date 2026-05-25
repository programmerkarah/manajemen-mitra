<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\SkKpa;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkKpaIndexAndKegiatanCopyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): array
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst(str_replace('_', ' ', $roleName)), 'description' => '']
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_sk_kpa_index_shows_all_active_year_kegiatan_and_summary_cards(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatanTanpaSk = Kegiatan::factory()->create([
            'tahun_anggaran' => $activeYear,
        ]);

        $kegiatanSkGenerate = Kegiatan::factory()->create([
            'tahun_anggaran' => $activeYear,
        ]);

        $kegiatanSkDisahkan = Kegiatan::factory()->create([
            'tahun_anggaran' => $activeYear,
        ]);

        $kegiatanTanpaAlokasi = Kegiatan::factory()->create([
            'tahun_anggaran' => $activeYear,
        ]);

        $petugas = Petugas::factory()->create();

        $periodeKegiatanTanpaSk = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanTanpaSk->id,
            'tahun' => (int) $activeYear,
            'bulan' => 3,
            'status' => 'dikirim',
        ]);

        $periodeKegiatanSkGenerate = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSkGenerate->id,
            'tahun' => (int) $activeYear,
            'bulan' => 3,
            'status' => 'dikirim',
        ]);

        $periodeKegiatanSkDisahkan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSkDisahkan->id,
            'tahun' => (int) $activeYear,
            'bulan' => 3,
            'status' => 'dikirim',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanTanpaAlokasi->id,
            'tahun' => (int) $activeYear,
            'bulan' => 3,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeKegiatanTanpaSk->id,
            'petugas_id' => $petugas->id,
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeKegiatanSkGenerate->id,
            'petugas_id' => $petugas->id,
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeKegiatanSkDisahkan->id,
            'petugas_id' => $petugas->id,
        ]);

        SkKpa::create([
            'nomor_sk' => '001/SK/TEST',
            'kegiatan_id' => $kegiatanSkGenerate->id,
            'bulan' => 3,
            'tahun' => (int) $activeYear,
            'tanggal_sk' => "{$activeYear}-03-05",
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'Perihal SK',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/test-generate.pdf',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        SkKpa::create([
            'nomor_sk' => '002/SK/TEST',
            'kegiatan_id' => $kegiatanSkDisahkan->id,
            'bulan' => 3,
            'tahun' => (int) $activeYear,
            'tanggal_sk' => "{$activeYear}-03-06",
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'Perihal SK',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/test-signed.pdf',
            'signed_file_path' => 'sk/test-signed-final.pdf',
            'status' => 'diterbitkan',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/sk-kpa');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('SkKpa/Index')
            ->where('kegiatan.meta.total', 3)
            ->where('summary.total_kegiatan_aktif', 3)
            ->where('summary.total_sk_belum_dibuat', 1)
            ->where('summary.total_sk_digenerate', 2)
            ->where('summary.total_sk_disahkan', 1)
        );
    }

    public function test_copy_kegiatan_includes_source_users_in_dropdown_options(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $sourceKetua = User::factory()->create([
            'is_active' => false,
        ]);

        $sourcePj = User::factory()->create([
            'is_active' => false,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $sourceKetua->id,
            'pj_lainnya_id' => $sourcePj->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get("/kegiatan/{$kegiatan->hashed_id}/copy");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Kegiatan/Create')
            ->where('ketuaTimUsers', fn ($users) => collect($users)->contains('id', $sourceKetua->id))
            ->where('pjLainnyaUsers', fn ($users) => collect($users)->contains('id', $sourcePj->id))
        );
    }

    public function test_create_sk_page_detects_same_month_revision_as_sk_perubahan_candidate(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = (int) ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $activeYear,
            'status' => 'aktif',
        ]);

        $periodeAwal = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $activeYear,
            'bulan' => 3,
            'status' => 'dikirim',
            'created_at' => now()->subDays(10),
        ]);

        $petugasAwal = Petugas::factory()->create();
        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeAwal->id,
            'petugas_id' => $petugasAwal->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $existingSk = SkKpa::create([
            'nomor_sk' => '003/SK/TEST',
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 3,
            'tahun' => $activeYear,
            'tanggal_sk' => "{$activeYear}-03-15",
            'nama_kpa' => 'Nama KPA',
            'perihal' => 'Perihal SK',
            'dasar_hukum' => json_encode([]),
            'file_path' => 'sk/test-existing.pdf',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $existingSk->forceFill([
            'created_at' => now()->subDays(9),
            'updated_at' => now()->subDays(9),
        ])->saveQuietly();

        $periodeRevisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $activeYear,
            'bulan' => 3,
            'status' => 'perubahan',
            'created_at' => now()->subDay(),
        ]);

        $petugasBaru = Petugas::factory()->create();
        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeRevisi->id,
            'petugas_id' => $petugasBaru->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get("/sk-kpa/kegiatan/{$kegiatan->hashed_id}/create");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('SkKpa/Create')
            ->where('personnelChangeInfo.has_changes', true)
            ->where('personnelChangeInfo.total_changes', 1)
            ->where('personnelChangeInfo.first_change_month', 'Maret')
        );
    }
}
