<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanRejectionIndicatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin']
        );
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        return [$admin, $adminRole];
    }

    private function makeKetuaTim(): array
    {
        $ketuaTimRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim']
        );
        $ketuaTim = User::factory()->create();
        $ketuaTim->roles()->attach($ketuaTimRole->id);

        return [$ketuaTim, $ketuaTimRole];
    }

    public function test_reject_sets_catatan_on_kegiatan(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'diajukan',
            'catatan' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/reject", [
                'catatan' => 'Pagu anggaran belum diisi dengan benar.',
            ]);

        $kegiatan->refresh();

        $this->assertEquals('draft', $kegiatan->status);
        $this->assertEquals('Pagu anggaran belum diisi dengan benar.', $kegiatan->catatan);
    }

    public function test_index_includes_catatan_in_encrypted_payload(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        Kegiatan::factory()->create([
            'status' => 'draft',
            'catatan' => 'Perlu diperbaiki segera.',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/kegiatan');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Kegiatan/Index'));

        $encryptedPayload = $response->json('props.kegiatans.encrypted');
        $this->assertNotNull($encryptedPayload);
    }

    public function test_submit_clears_catatan(): void
    {
        [$ketuaTim, $ketuaTimRole] = $this->makeKetuaTim();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
            'catatan' => 'Pagu anggaran belum diisi.',
            'ketua_tim_user_id' => $ketuaTim->id,
        ]);

        $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTimRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/submit");

        $kegiatan->refresh();

        $this->assertEquals('diajukan', $kegiatan->status);
        $this->assertNull($kegiatan->catatan);
    }

    public function test_submit_clears_catatan_for_admin(): void
    {
        [$admin, $adminRole] = $this->makeAdmin();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
            'catatan' => 'Revisi diperlukan.',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/kegiatan/{$kegiatan->hashed_id}/submit");

        $kegiatan->refresh();

        $this->assertEquals('diajukan', $kegiatan->status);
        $this->assertNull($kegiatan->catatan);
    }

    public function test_draft_without_catatan_is_not_rejected(): void
    {
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'draft',
            'catatan' => null,
        ]);

        $this->assertEquals('draft', $kegiatan->status);
        $this->assertNull($kegiatan->catatan);
    }
}
