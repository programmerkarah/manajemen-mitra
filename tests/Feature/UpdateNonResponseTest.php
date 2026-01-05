<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateNonResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function ketua_tim_can_update_non_response()
    {
        // Create users
        $ketuaTim = User::factory()->create();
        $ketuaTim->assignRole('ketua_tim');

        // Create kegiatan with ketua tim
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
        ]);

        // Create periode
        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
        ]);

        // Create alokasi petugas
        $alokasi1 = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'peran' => 'pcl',
            'non_response' => 0,
            'non_response_listing' => 0,
        ]);

        $alokasi2 = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'peran' => 'pml',
            'non_response' => 0,
            'non_response_listing' => 0,
        ]);

        // Act as ketua tim and send request
        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTim->roles()->first()->id])
            ->post('/alokasi/update-non-response', [
                'alokasi_petugas' => [
                    [
                        'id' => $alokasi1->id,
                        'non_response' => 5,
                        'non_response_listing' => 3,
                    ],
                    [
                        'id' => $alokasi2->id,
                        'non_response' => 10,
                        'non_response_listing' => 7,
                    ],
                ],
            ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('alokasi_petugas', [
            'id' => $alokasi1->id,
            'non_response' => 5,
            'non_response_listing' => 3,
        ]);

        $this->assertDatabaseHas('alokasi_petugas', [
            'id' => $alokasi2->id,
            'non_response' => 10,
            'non_response_listing' => 7,
        ]);
    }

    /** @test */
    public function non_ketua_tim_cannot_update_non_response()
    {
        // Create users
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $ketuaTim = User::factory()->create();
        $ketuaTim->assignRole('ketua_tim');

        // Create kegiatan with different ketua tim
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
        ]);

        // Create periode
        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
        ]);

        // Create alokasi petugas
        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'peran' => 'pcl',
        ]);

        // Act as operator and send request (should fail)
        $response = $this->actingAs($operator)
            ->withSession(['active_role_id' => $operator->roles()->first()->id])
            ->post('/alokasi/update-non-response', [
                'alokasi_petugas' => [
                    [
                        'id' => $alokasi->id,
                        'non_response' => 5,
                        'non_response_listing' => 3,
                    ],
                ],
            ]);

        // Assert unauthorized
        $response->assertStatus(403);
    }

    /** @test */
    public function ketua_tim_cannot_update_other_ketua_tim_alokasi()
    {
        // Create users
        $ketuaTim1 = User::factory()->create();
        $ketuaTim1->assignRole('ketua_tim');
        $ketuaTim2 = User::factory()->create();
        $ketuaTim2->assignRole('ketua_tim');

        // Create kegiatan with ketua tim 2
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim2->id,
        ]);

        // Create periode
        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
        ]);

        // Create alokasi petugas
        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'peran' => 'pcl',
        ]);

        // Act as ketua tim 1 (different from kegiatan's ketua tim)
        $response = $this->actingAs($ketuaTim1)
            ->withSession(['active_role_id' => $ketuaTim1->roles()->first()->id])
            ->post('/alokasi/update-non-response', [
                'alokasi_petugas' => [
                    [
                        'id' => $alokasi->id,
                        'non_response' => 5,
                        'non_response_listing' => 3,
                    ],
                ],
            ]);

        // Assert unauthorized
        $response->assertStatus(403);
    }
}
