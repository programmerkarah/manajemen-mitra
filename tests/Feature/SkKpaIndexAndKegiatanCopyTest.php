<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
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
}
