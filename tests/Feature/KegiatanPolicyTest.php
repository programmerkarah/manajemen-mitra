<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        return [$admin, $adminRole];
    }

    public function test_ketua_tim_lainnya_can_update_kegiatan_draft()
    {
        $ketuaTimRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $ketuaTim = User::factory()->create();
        $ketuaTim->roles()->attach($ketuaTimRole->id);

        $ketuaTimLain = User::factory()->create();
        $ketuaTimLain->roles()->attach($ketuaTimRole->id);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'draft',
        ]);

        $this->actingAs($ketuaTimLain)
            ->withSession(['active_role_id' => $ketuaTimRole->id]);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertTrue($canUpdate, 'Ketua tim lainnya harus bisa update kegiatan draft sebagai pj_lainnya_id');
    }

    public function test_ketua_tim_lainnya_can_update_kegiatan_divalidasi()
    {
        $ketuaTimRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $ketuaTim = User::factory()->create();
        $ketuaTim->roles()->attach($ketuaTimRole->id);

        $ketuaTimLain = User::factory()->create();
        $ketuaTimLain->roles()->attach($ketuaTimRole->id);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'divalidasi',
        ]);

        $this->actingAs($ketuaTimLain)
            ->withSession(['active_role_id' => $ketuaTimRole->id]);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertTrue($canUpdate, 'Ketua tim lainnya boleh update kegiatan divalidasi sesuai policy saat ini');
    }

    public function test_admin_can_delete_approved_kegiatan_without_periode_alokasi(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->delete("/kegiatan/{$kegiatan->hashed_id}");

        $response->assertRedirect(route('kegiatan.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('kegiatan', [
            'id' => $kegiatan->id,
        ]);
    }

    public function test_admin_cannot_delete_approved_kegiatan_with_periode_alokasi(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
        ]);

        PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'status' => 'draft',
            'bulan' => '03',
            'tahun' => (int) date('Y'),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->delete("/kegiatan/{$kegiatan->hashed_id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_approve_kegiatan_draft(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/approve");

        $response->assertForbidden();
        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_cannot_reject_kegiatan_draft(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/reject", [
                'catatan' => 'Masih draft.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_approve_kegiatan_diajukan(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'status' => 'divalidasi',
        ]);
    }
}
