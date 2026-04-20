<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Vinkla\Hashids\Facades\Hashids;

class PetugasBatchUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        if (Role::count() === 0) {
            Role::create(['name' => 'guest', 'display_name' => 'Guest', 'description' => 'Guest']);
            Role::create(['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin']);
            Role::create(['name' => 'operator', 'display_name' => 'Operator', 'description' => 'Operator']);
            Role::create(['name' => 'pj', 'display_name' => 'PJ', 'description' => 'PJ']);
            Role::create(['name' => 'approver', 'display_name' => 'Approver', 'description' => 'Approver']);
        }
    }

    public function test_admin_can_batch_update_petugas(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();

        $petugas1 = Petugas::factory()->create(['nama' => 'Original 1', 'alamat' => 'Alamat Lama 1']);
        $petugas2 = Petugas::factory()->create(['nama' => 'Original 2', 'alamat' => 'Alamat Lama 2']);

        $response = $this->actingAs($admin)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => Hashids::encode($petugas1->id),
                    'nama' => 'Updated 1',
                    'telepon' => '081234567890',
                    'pendidikan' => 'S1',
                    'jenis_kelamin' => 'laki-laki',
                    'tanggal_lahir' => '1990-01-15',
                    'kecamatan' => 'Barangin',
                    'desa_kelurahan' => 'Lumindai',
                    'alamat' => 'Alamat Baru 1',
                ],
                [
                    'id' => Hashids::encode($petugas2->id),
                    'nama' => 'Updated 2',
                    'telepon' => '089876543210',
                    'pendidikan' => 'SMA',
                    'jenis_kelamin' => 'perempuan',
                    'tanggal_lahir' => '1995-06-20',
                    'kecamatan' => 'Talawi',
                    'desa_kelurahan' => 'Sikalang',
                    'alamat' => 'Alamat Baru 2',
                ],
            ],
        ]);

        $response->assertRedirect(route('petugas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('petugas', [
            'id' => $petugas1->id,
            'nama' => 'Updated 1',
            'telepon' => '081234567890',
            'pendidikan' => 'S1',
            'alamat' => 'Alamat Baru 1',
        ]);

        $this->assertDatabaseHas('petugas', [
            'id' => $petugas2->id,
            'nama' => 'Updated 2',
            'telepon' => '089876543210',
            'pendidikan' => 'SMA',
            'alamat' => 'Alamat Baru 2',
        ]);
    }

    public function test_non_admin_cannot_batch_update_petugas(): void
    {
        $this->seedRoles();
        $operator = User::factory()->operator()->create();
        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($operator)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => Hashids::encode($petugas->id),
                    'nama' => 'Updated',
                    'telepon' => '081234567890',
                    'pendidikan' => 'S1',
                    'alamat' => 'Alamat Baru',
                ],
            ],
        ]);

        $response->assertStatus(302);
    }

    public function test_unauthenticated_user_cannot_batch_update(): void
    {
        $petugas = Petugas::factory()->create();

        $response = $this->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => Hashids::encode($petugas->id),
                    'nama' => 'Updated',
                    'telepon' => '081234567890',
                    'pendidikan' => 'S1',
                    'alamat' => 'Alamat Baru',
                ],
            ],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_batch_update_requires_petugas_array(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/petugas/batch-update', []);

        $response->assertSessionHasErrors('petugas');
    }

    public function test_batch_update_validates_required_fields(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => 'someid',
                    'nama' => '',
                    'telepon' => '',
                    'pendidikan' => '',
                    'alamat' => '',
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'petugas.0.nama',
            'petugas.0.telepon',
            'petugas.0.pendidikan',
            'petugas.0.alamat',
        ]);
    }

    public function test_batch_update_validates_pendidikan_values(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($admin)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => Hashids::encode($petugas->id),
                    'nama' => 'Test',
                    'telepon' => '081234567890',
                    'pendidikan' => 'INVALID',
                    'alamat' => 'Alamat',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('petugas.0.pendidikan');
    }

    public function test_batch_update_skips_invalid_hashid(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => 'invalidhashid',
                    'nama' => 'Test',
                    'telepon' => '081234567890',
                    'pendidikan' => 'S1',
                    'alamat' => 'Alamat',
                ],
            ],
        ]);

        $response->assertRedirect(route('petugas.index'));
        $response->assertSessionHas('success', 'Berhasil memperbarui 0 data petugas.');
    }

    public function test_batch_update_with_nullable_fields(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($admin)->put('/petugas/batch-update', [
            'petugas' => [
                [
                    'id' => Hashids::encode($petugas->id),
                    'nama' => 'Test Nullable',
                    'telepon' => '081234567890',
                    'pendidikan' => 'D3',
                    'jenis_kelamin' => null,
                    'tanggal_lahir' => null,
                    'kecamatan' => null,
                    'desa_kelurahan' => null,
                    'alamat' => 'Some Address',
                ],
            ],
        ]);

        $response->assertRedirect(route('petugas.index'));
        $response->assertSessionHas('success', 'Berhasil memperbarui 1 data petugas.');

        $this->assertDatabaseHas('petugas', [
            'id' => $petugas->id,
            'nama' => 'Test Nullable',
            'pendidikan' => 'D3',
        ]);
    }
}
