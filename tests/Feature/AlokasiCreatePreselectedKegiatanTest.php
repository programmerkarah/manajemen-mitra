<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiCreatePreselectedKegiatanTest extends TestCase
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

    public function test_create_alokasi_with_preselected_kegiatan_provides_budget_info_and_required_fields(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'pagu_pencacahan' => 1250000,
            'pagu_listing' => 450000,
            'has_listing_updating' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where('selectedKegiatan.id', $kegiatan->id)
            ->where('selectedKegiatan.pagu_pencacahan', fn ($value) => (float) $value === 1250000.0)
            ->where('selectedKegiatan.pagu_listing', fn ($value) => (float) $value === 450000.0)
            ->where('selectedKegiatan.has_listing_updating', true)
            ->has("budget_info.{$kegiatan->id}")
            ->where("budget_info.{$kegiatan->id}.pagu_pencacahan", fn ($value) => (float) $value === 1250000.0)
            ->where("budget_info.{$kegiatan->id}.pagu_listing", fn ($value) => (float) $value === 450000.0)
        );
    }
}
