<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasExistingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_existing_petugas_data(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        Petugas::factory()->create([
            'nama' => 'Petugas Uji',
            'nik' => '1234567890123456',
            'email' => 'petugas-uji@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get(route('petugas.existing'));

        $response->assertOk();
        $response->assertDownload('data_existing_petugas.xlsx');
    }
}
