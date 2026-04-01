<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiTemplateExportRouteTest extends TestCase
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

    public function test_admin_can_download_create_template_without_periode_id(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/export/create');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=alokasi-petugas-template-create.xlsx');
    }

    public function test_admin_can_download_edit_template_using_hashed_periode_id(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/'.$periode->hashed_id.'/export/edit');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=alokasi-petugas-template-edit.xlsx');
    }

    public function test_import_preview_requires_file(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->postJson('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }
}
