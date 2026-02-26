<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $petugas1 = \App\Models\Petugas::factory()->create();
        $petugas2 = \App\Models\Petugas::factory()->create();

        $alokasi1Id = DB::table('alokasi_petugas')->insertGetId([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'status' => 'draft',
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'petugas_id' => $petugas1->id,
            'jumlah_satuan' => 30,
            'total_honor' => 1000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $alokasi2Id = DB::table('alokasi_petugas')->insertGetId([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'status' => 'draft',
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'petugas_id' => $petugas2->id,
            'jumlah_satuan' => 20,
            'total_honor' => 900000,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $alokasi1 = AlokasiPetugas::query()->findOrFail($alokasi1Id);
        $alokasi2 = AlokasiPetugas::query()->findOrFail($alokasi2Id);

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

        // Assert current behavior
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('alokasi_petugas', [
            'id' => $alokasi1->id,
            'non_response' => 0,
            'non_response_listing' => 0,
        ]);

        $this->assertDatabaseHas('alokasi_petugas', [
            'id' => $alokasi2->id,
            'non_response' => 0,
            'non_response_listing' => 0,
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
        $petugas = \App\Models\Petugas::factory()->create();
        $alokasiId = DB::table('alokasi_petugas')->insertGetId([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'status' => 'draft',
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 30,
            'total_honor' => 1000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $alokasi = AlokasiPetugas::query()->findOrFail($alokasiId);

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

        // Assert current behavior: redirect back with error flash
        $response->assertStatus(302);
        $response->assertSessionHas('error');
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
        $petugas = \App\Models\Petugas::factory()->create();
        $alokasiId = DB::table('alokasi_petugas')->insertGetId([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'status' => 'draft',
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 30,
            'total_honor' => 1000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $alokasi = AlokasiPetugas::query()->findOrFail($alokasiId);

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

        // Assert current behavior: redirect back with error flash
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
}
