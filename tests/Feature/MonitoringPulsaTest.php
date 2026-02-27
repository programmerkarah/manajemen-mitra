<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPulsaTest extends TestCase
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

    public function test_index_is_accessible_by_admin(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_operator(): void
    {
        [$user, $role] = $this->makeUserWithRole('operator');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_ketua_tim(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_forbidden_for_unauthenticated_user(): void
    {
        $response = $this->get('/monitoring-pulsa');

        $response->assertRedirect();
    }

    public function test_index_is_forbidden_for_unauthorized_role(): void
    {
        [$user, $role] = $this->makeUserWithRole('pj');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertRedirect();
    }

    public function test_index_renders_inertia_component(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertInertia(fn ($page) => $page->component('MonitoringPulsa/Index'));
    }

    public function test_index_excludes_draft_submissions(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
        $petugas = Petugas::factory()->create();
        $bulan = now()->format('m');
        $tahun = \App\Services\ActiveYearService::get();

        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);
        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pelatihan',
            'nominal' => 30000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get("/monitoring-pulsa?bulan={$bulan}");

        $response->assertStatus(200);
    }

    public function test_ketua_tim_only_sees_own_kegiatan(): void
    {
        [$ketuaTim, $role] = $this->makeUserWithRole('ketua_tim');
        [$otherKetuaTim] = $this->makeUserWithRole('ketua_tim');

        $ownKegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
        $otherKegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $otherKetuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugas = Petugas::factory()->create();
        $bulan = now()->format('m');
        $tahun = \App\Services\ActiveYearService::get();

        PengajuanPulsa::create([
            'kegiatan_id' => $ownKegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);
        PengajuanPulsa::create([
            'kegiatan_id' => $otherKegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $otherKetuaTim->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $role->id])
            ->get("/monitoring-pulsa?bulan={$bulan}");

        $response->assertStatus(200);
    }
}
