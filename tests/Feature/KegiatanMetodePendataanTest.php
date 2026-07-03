<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KegiatanMetodePendataanTest extends TestCase
{
    use RefreshDatabase;

    private function makeKetuaTim(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    private function makeUnitSampel(): MasterUnitSampel
    {
        return MasterUnitSampel::create([
            'nama' => 'Rumah Tangga',
            'kode' => 'RT',
            'is_active' => true,
        ]);
    }

    public function test_store_kegiatan_requires_metode_pendataan_pencacahan(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Test',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                // metode_pendataan_pencacahan intentionally omitted
            ]);

        $response->assertSessionHasErrors('metode_pendataan_pencacahan');
    }

    public function test_store_kegiatan_accepts_valid_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Test',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Test',
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
    }

    public function test_store_kegiatan_survei_accepts_targeted_sampling_mode(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Targeted Sampling',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_sampling' => 'targeted',
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 5],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Targeted Sampling',
            'metode_sampling' => 'targeted',
        ]);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 5,
        ]);
    }

    public function test_store_kegiatan_survei_accepts_purpossive_sampling_mode(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Purpossive Sampling',
            'kode' => 'FPS-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Purpossive Sampling',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_sampling' => 'purpossive',
                'metode_pendataan_pencacahan' => 'CAPI_FASIH',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'nama_target' => 'Usaha Penggilingan A',
                        'sample_role' => 'utama',
                        'is_active' => true,
                        'target_unit_sampel' => ['target' => 1],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Purpossive Sampling',
            'metode_sampling' => 'purpossive',
        ]);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'nama_target' => 'Usaha Penggilingan A',
            'sample_role' => 'utama',
            'is_active' => 1,
            'target_unit_sampel->target' => 1,
        ]);
    }

    public function test_store_kegiatan_generates_next_kode_when_same_year_already_exists(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        Kegiatan::factory()->create([
            'kode_kegiatan' => 'KEG-2025-001',
            'nama_kegiatan' => 'Existing Kegiatan',
            'tahun_anggaran' => 2025,
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Next Kode',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Next Kode',
            'kode_kegiatan' => 'KEG-2025-002',
        ]);
    }

    public function test_store_kegiatan_accepts_capi_fasih_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei FASIH',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI_FASIH',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei FASIH',
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
    }

    public function test_store_kegiatan_accepts_capi_ksa_pro_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei KSA Pro',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI_KSA_PRO',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei KSA Pro',
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
    }

    public function test_store_kegiatan_accepts_papi_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei PAPI',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'luring',
                'bulan_pelatihan' => 7,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei PAPI',
            'metode_pendataan_pencacahan' => 'PAPI',
        ]);
    }

    public function test_store_kegiatan_sensus_forces_no_listing_updating(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Sensus Tanpa Listing',
                'jenis_kegiatan' => 'sensus',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => true,
                'metode_pendataan_listing' => 'CAPI',
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 9,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_listing');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Sensus Tanpa Listing',
            'has_listing_updating' => 0,
            'metode_pendataan_listing' => null,
        ]);
    }

    public function test_store_kegiatan_sensus_saves_frame_sampel_rows_without_listing(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Sensus Pencacahan',
            'kode' => 'FSP-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Sensus Ekonomi Frame',
                'jenis_kegiatan' => 'sensus',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI_FASIH',
                'has_listing_updating' => true,
                'metode_pendataan_listing' => 'CAPI',
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 7,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 12],
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kddes' => '002',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();

        $kegiatan = Kegiatan::query()->where('nama_kegiatan', 'Sensus Ekonomi Frame')->firstOrFail();

        $this->assertSame('draft', $kegiatan->status);
        $this->assertSame(0, (int) $kegiatan->has_listing_updating);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 12,
            'kode_kecamatan' => '010',
            'kode_desa' => '002',
        ]);
    }

    public function test_update_kegiatan_sensus_stores_legacy_metode_value_without_500(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Update Sensus',
            'kode' => 'FUS-1',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Sensus Ekonomi Update',
            'jenis_kegiatan' => 'sensus',
            'tahun_anggaran' => 2025,
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'status' => 'draft',
            'has_listing_updating' => false,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => null,
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 7,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'unit_sampel_pencacahan_ids' => [$unitSampel->id],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->put('/kegiatan/'.$kegiatan->hashed_id, [
                'nama_kegiatan' => 'Sensus Ekonomi Update',
                'jenis_kegiatan' => 'sensus',
                'deskripsi' => $kegiatan->deskripsi,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'pj_lainnya_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI_FASIH',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 7,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'status' => 'draft',
            ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
    }

    public function test_store_kegiatan_survei_accepts_listing_capi(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Listing CAPI',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => true,
                'metode_pendataan_listing' => 'CAPI',
                'pagu_listing' => 1000000,
                'metode_pelatihan' => 'hybrid',
                'bulan_pelatihan' => 8,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'unit_sampel_listing_ids' => [$unitSampel->id],
            ]);

        $response->assertSessionDoesntHaveErrors(['metode_pendataan_pencacahan', 'metode_pendataan_listing']);
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Listing CAPI',
            'metode_pendataan_pencacahan' => 'PAPI',
            'metode_pendataan_listing' => 'CAPI',
        ]);
    }

    public function test_metode_pendataan_rejects_invalid_value(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Test Invalid',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'FASIH', // Invalid - only PAPI/CAPI allowed
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
            ]);

        $response->assertSessionHasErrors('metode_pendataan_pencacahan');
    }

    public function test_kegiatan_interface_includes_metode_pendataan_fields(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => null,
        ]);

        $this->assertNotNull($kegiatan->metode_pendataan_pencacahan);
        $this->assertSame('CAPI', $kegiatan->metode_pendataan_pencacahan);
        $this->assertNull($kegiatan->metode_pendataan_listing);
    }

    public function test_store_kegiatan_requires_bulan_pelatihan_when_metode_is_not_tidak_ada(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Pelatihan',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
            ]);

        $response->assertSessionHasErrors('bulan_pelatihan');
    }

    public function test_store_kegiatan_accepts_tidak_ada_pelatihan_for_survei(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Tanpa Pelatihan',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'tidak_ada_pelatihan',
            ]);

        $response->assertSessionDoesntHaveErrors(['metode_pelatihan', 'bulan_pelatihan']);
        $response->assertRedirect();
    }

    public function test_store_kegiatan_persists_kegiatan_frame_sampel_rows(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan A',
            'kode' => 'FPA',
            'is_active' => true,
        ]);
        $frameListing = MasterFrameSampel::create([
            'nama' => 'Frame Listing A',
            'kode' => 'FLA',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei dengan Daftar Frame',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => true,
                'metode_pendataan_listing' => 'PAPI',
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'frame_sampel_listing_id' => $frameListing->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'unit_sampel_listing_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 120],
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kddes' => '002',
                            'kdsls' => '001',
                            'kdsubsls' => 'A',
                        ],
                    ],
                    [
                        'tahapan' => 'listing',
                        'target_unit_sampel' => [$unitSampel->id => 80],
                        'identitas_tambahan' => [
                            'kdkec' => '020',
                            'kddes' => '001',
                        ],
                    ],
                ],
            ]);
        $response->assertSessionDoesntHaveErrors('kegiatan_frame_sampel');

        $kegiatan = Kegiatan::query()->where('nama_kegiatan', 'Survei dengan Daftar Frame')->firstOrFail();

        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 120,
            'kode_kecamatan' => '010',
            'kode_desa' => '002',
            'kode_sls' => '001',
            'kode_sub_sls' => 'A',
        ]);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $frameListing->id,
            'tahapan' => 'listing',
            'target_unit_sampel->'.$unitSampel->id => 80,
        ]);

        $savedPencacahan = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tahapan', 'pencacahan')
            ->firstOrFail();

        $this->assertSame('010', $savedPencacahan->identitas_tambahan['kdkec']);
        $this->assertSame('002', $savedPencacahan->identitas_tambahan['kddes']);
    }

    public function test_store_kegiatan_preserves_zero_values_for_each_target_unit_key(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $unitSampelSatu = MasterUnitSampel::create([
            'nama' => 'Usaha',
            'kode' => 'USH-0',
            'is_active' => true,
        ]);

        $unitSampelDua = MasterUnitSampel::create([
            'nama' => 'Keluarga',
            'kode' => 'KLG-124',
            'is_active' => true,
        ]);

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan Zero Key',
            'kode' => 'FPZK',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Simpan Zero Unit',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'unit_sampel_pencacahan_ids' => [$unitSampelSatu->id, $unitSampelDua->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [
                            (string) $unitSampelSatu->id => 0,
                            (string) $unitSampelDua->id => 124,
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors('kegiatan_frame_sampel');

        $kegiatan = Kegiatan::query()->where('nama_kegiatan', 'Survei Simpan Zero Unit')->firstOrFail();

        $saved = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tahapan', 'pencacahan')
            ->firstOrFail();

        $this->assertSame(0, (int) ($saved->target_unit_sampel[(string) $unitSampelSatu->id] ?? null));
        $this->assertSame(124, (int) ($saved->target_unit_sampel[(string) $unitSampelDua->id] ?? null));
        $this->assertArrayHasKey((string) $unitSampelSatu->id, $saved->target_unit_sampel);
        $this->assertArrayHasKey((string) $unitSampelDua->id, $saved->target_unit_sampel);
    }

    public function test_store_kegiatan_rejects_frame_rows_when_master_frame_tahapan_missing(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->from('/kegiatan/create')
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Tanpa Master Frame Pencacahan',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 15],
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kddes' => '002',
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect('/kegiatan/create')
            ->assertSessionHasErrors(['frame_sampel_pencacahan_id', 'kegiatan_frame_sampel']);

        $this->assertDatabaseMissing('kegiatan', [
            'nama_kegiatan' => 'Survei Tanpa Master Frame Pencacahan',
        ]);
    }

    public function test_can_download_frame_sampel_detail_template_after_metadata_saved(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/frame-sampel/template', [
                'metadata' => json_encode([
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=detail-frame-sampel-template.xlsx');
    }

    public function test_update_kegiatan_accepts_frame_sampel_rows_without_sample_role_for_ksa(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame KSA Update',
            'kode' => 'KSA-1',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan KSA Update',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => 2025,
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => null,
            'status' => 'draft',
            'has_listing_updating' => false,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => null,
            'metode_sampling' => 'targeted',
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'unit_sampel_pencacahan_ids' => [$unitSampel->id],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->put('/kegiatan/'.$kegiatan->hashed_id, [
                'nama_kegiatan' => 'Kegiatan KSA Update',
                'jenis_kegiatan' => 'survei',
                'deskripsi' => $kegiatan->deskripsi,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'pj_lainnya_id' => null,
                'metode_sampling' => 'targeted',
                'metode_pendataan_pencacahan' => 'CAPI_KSA_PRO',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'id' => null,
                        'tahapan' => 'pencacahan',
                        'nama_target' => 'Usaha KSA A',
                        'is_active' => true,
                        'target_unit_sampel' => [$unitSampel->id => 3],
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kddes' => '002',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'nama_target' => 'Usaha KSA A',
            'sample_role' => 'utama',
            'is_active' => 1,
            'target_unit_sampel->'.$unitSampel->id => 3,
        ]);
    }

    public function test_can_download_frame_sampel_detail_template_with_existing_purpossive_rows(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/frame-sampel/template', [
                'metadata' => json_encode([
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ], JSON_THROW_ON_ERROR),
                'metode_sampling' => 'purpossive',
                'template_rows' => json_encode([
                    [
                        'sample_name' => 'Sampel Existing',
                        'sample_role' => 'utama',
                        'identitas_tambahan' => [
                            'kdkec' => '010',
                            'kdkec_label' => 'Kecamatan 010',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        $response->assertOk();

        $tempPath = storage_path('framework/testing/frame-sampel-template-route-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('010', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('Kecamatan 010', (string) $sheet->getCell('B2')->getValue());
        $this->assertSame('Sampel Existing', (string) $sheet->getCell('C2')->getValue());
        $this->assertSame('utama', (string) $sheet->getCell('D2')->getValue());

        $validation = $sheet->getCell('D2')->getDataValidation();

        $this->assertSame('list', $validation->getType());
        $this->assertSame('"utama,cadangan,lainnya"', $validation->getFormula1());
    }

    public function test_can_import_frame_sampel_detail_preview_from_excel(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $file = $this->makeFrameSampelImportFile([
            ['Kode Kecamatan', 'Kecamatan', 'Jumlah Sampel Dalam Frame'],
            ['010', 'Kec. Test', 12],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/frame-sampel/import-preview', [
                'metadata' => json_encode([
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ], JSON_THROW_ON_ERROR),
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonPath('rows.0.target_unit_sampel.0', '12');
        $response->assertJsonPath('rows.0.identitas_tambahan.kdkec', '010');
        $response->assertJsonPath('rows.0.identitas_tambahan.kdkec_label', 'Kec. Test');
    }

    private function makeFrameSampelImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.'/frame-sampel-import-'.uniqid().'.xlsx';

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'frame-sampel-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    public function test_update_kegiatan_replaces_kegiatan_frame_sampel_rows(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan B',
            'kode' => 'FPB',
            'is_active' => true,
        ]);
        $frameListing = MasterFrameSampel::create([
            'nama' => 'Frame Listing B',
            'kode' => 'FLB',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'has_listing_updating' => true,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => 'PAPI',
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'frame_sampel_listing_id' => $frameListing->id,
            'tahun_anggaran' => 2025,
        ]);

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => [$unitSampel->id => 50],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->put('/kegiatan/'.$kegiatan->hashed_id, [
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => 'survei',
                'deskripsi' => $kegiatan->deskripsi,
                'tanggal_mulai' => $kegiatan->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai' => $kegiatan->tanggal_selesai->format('Y-m-d'),
                'tahun_anggaran' => $kegiatan->tahun_anggaran,
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan,
                'ketua_tim_user_id' => $user->id,
                'has_listing_updating' => true,
                'metode_pendataan_pencacahan' => 'CAPI',
                'metode_pendataan_listing' => 'PAPI',
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'frame_sampel_listing_id' => $frameListing->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'unit_sampel_listing_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 130],
                        'identitas_tambahan' => [
                            'kdkec' => '030',
                            'kddes' => '002',
                            'kdsls' => '009',
                        ],
                    ],
                    [
                        'tahapan' => 'listing',
                        'target_unit_sampel' => [$unitSampel->id => 70],
                        'identitas_tambahan' => [
                            'kdkec' => '040',
                            'kddes' => '008',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors('kegiatan_frame_sampel');

        $this->assertDatabaseMissing('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 50,
        ]);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 130,
            'kode_kecamatan' => '030',
            'kode_desa' => '002',
            'kode_sls' => '009',
        ]);
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $frameListing->id,
            'tahapan' => 'listing',
            'target_unit_sampel->'.$unitSampel->id => 70,
        ]);

        $savedListing = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tahapan', 'listing')
            ->firstOrFail();

        $this->assertSame('040', $savedListing->identitas_tambahan['kdkec']);
        $this->assertSame('008', $savedListing->identitas_tambahan['kddes']);
    }

    public function test_update_kegiatan_keeps_allocation_pivot_for_persisted_frame_and_deletes_removed_frame_pivot(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan Persisted',
            'kode' => 'FPP',
            'is_active' => true,
        ]);
        $frameListing = MasterFrameSampel::create([
            'nama' => 'Frame Listing Persisted',
            'kode' => 'FLP',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'has_listing_updating' => true,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => 'PAPI',
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 6,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'frame_sampel_listing_id' => $frameListing->id,
            'tahun_anggaran' => 2025,
        ]);

        $existingPencacahanFrame = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'nama_target' => 'Frame A',
            'sample_role' => null,
            'is_active' => true,
            'nama_frame' => 'Frame A',
            'target_unit_sampel' => [$unitSampel->id => 50],
            'identitas_tambahan' => ['kdkec' => '030'],
        ]);

        $removedListingFrame = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $frameListing->id,
            'tahapan' => 'listing',
            'nama_target' => 'Frame B',
            'sample_role' => null,
            'is_active' => true,
            'nama_frame' => 'Frame B',
            'target_unit_sampel' => [$unitSampel->id => 40],
            'identitas_tambahan' => ['kddes' => '008'],
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-2025',
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 100000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2025,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => 2025,
            'bulan' => '01',
            'status' => 'draft',
        ]);

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $alokasi = AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 2,
            'total_honor' => 100000,
            'peran' => 'PCL',
            'status_kepegawaian' => 'non_organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'jumlah_frame_sampel' => 2,
            'jumlah_unit_sampel' => 2,
        ]);

        $pivotPersisted = AlokasiPetugasFrameSampel::query()->create([
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $existingPencacahanFrame->id,
        ]);

        $pivotRemoved = AlokasiPetugasFrameSampel::query()->create([
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $removedListingFrame->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->put('/kegiatan/'.$kegiatan->hashed_id, [
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => 'survei',
                'deskripsi' => $kegiatan->deskripsi,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan,
                'ketua_tim_user_id' => $user->id,
                'pj_lainnya_id' => $user->id,
                'has_listing_updating' => true,
                'metode_pendataan_pencacahan' => 'CAPI',
                'metode_pendataan_listing' => 'PAPI',
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'frame_sampel_listing_id' => $frameListing->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'unit_sampel_listing_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'id' => $existingPencacahanFrame->id,
                        'tahapan' => 'pencacahan',
                        'target_unit_sampel' => [$unitSampel->id => 130],
                        'identitas_tambahan' => [
                            'kdkec' => '030',
                            'kddes' => '002',
                        ],
                    ],
                    [
                        'tahapan' => 'listing',
                        'target_unit_sampel' => [$unitSampel->id => 70],
                        'identitas_tambahan' => [
                            'kdkec' => '040',
                            'kddes' => '008',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors('kegiatan_frame_sampel');

        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'id' => $existingPencacahanFrame->id,
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unitSampel->id => 130,
        ]);
        $this->assertDatabaseMissing('kegiatan_frame_sampel', [
            'id' => $removedListingFrame->id,
        ]);
        $this->assertDatabaseHas('alokasi_petugas_frame_sampel', [
            'id' => $pivotPersisted->id,
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $existingPencacahanFrame->id,
        ]);
        $this->assertDatabaseMissing('alokasi_petugas_frame_sampel', [
            'id' => $pivotRemoved->id,
        ]);
    }

    public function test_update_kegiatan_purpossive_persists_nama_target_and_sample_role(): void
    {
        [$user, $role] = $this->makeKetuaTim();
        $unitSampel = $this->makeUnitSampel();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Purpossive Update',
            'kode' => 'FPU-1',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei Purpossive Update',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => 2025,
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'status' => 'draft',
            'has_listing_updating' => false,
            'metode_sampling' => 'purpossive',
            'metode_pendataan_pencacahan' => 'CAPI_FASIH',
            'metode_pendataan_listing' => null,
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 7,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->put('/kegiatan/'.$kegiatan->hashed_id, [
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => 'survei',
                'deskripsi' => $kegiatan->deskripsi,
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan,
                'ketua_tim_user_id' => $user->id,
                'has_listing_updating' => false,
                'metode_sampling' => 'purpossive',
                'metode_pendataan_pencacahan' => 'CAPI_FASIH',
                'metode_pendataan_listing' => null,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 7,
                'frame_sampel_pencacahan_id' => $framePencacahan->id,
                'unit_sampel_pencacahan_ids' => [$unitSampel->id],
                'kegiatan_frame_sampel' => [
                    [
                        'tahapan' => 'pencacahan',
                        'nama_target' => 'Usaha Penggilingan B',
                        'sample_role' => 'utama',
                        'is_active' => true,
                        'target_unit_sampel' => ['target' => 1],
                        'identitas_tambahan' => [
                            'kdkec' => '040',
                            'kdkec_label' => 'TALAWI',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();

        $saved = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tahapan', 'pencacahan')
            ->firstOrFail();

        $this->assertSame('Usaha Penggilingan B', $saved->nama_target);
        $this->assertSame('utama', $saved->sample_role);
        $this->assertTrue($saved->is_active);
        $this->assertSame(1, (int) ($saved->target_unit_sampel['target'] ?? 0));
    }

    public function test_can_download_purpossive_frame_sampel_detail_template_with_name_and_role_columns(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/frame-sampel/template', [
                'metode_sampling' => 'purpossive',
                'metadata' => json_encode([
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=detail-frame-sampel-template.xlsx');
    }

    public function test_can_import_purpossive_frame_sampel_detail_preview_with_sample_type_and_name(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $file = $this->makeFrameSampelImportFile([
            ['Kode Kecamatan', 'Kecamatan', 'Jenis Sampel', 'Nama Sampel'],
            ['010', 'Kec. Test', 'utama', 'Usaha Penggilingan A'],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/frame-sampel/import-preview', [
                'metode_sampling' => 'purpossive',
                'metadata' => json_encode([
                    [
                        'code' => 'kdkec',
                        'label' => 'Kecamatan',
                        'description' => 'Kode wilayah kecamatan',
                    ],
                ], JSON_THROW_ON_ERROR),
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonPath('rows.0.nama_target', 'Usaha Penggilingan A');
        $response->assertJsonPath('rows.0.sample_role', 'utama');
        $response->assertJsonPath('rows.0.identitas_tambahan.kdkec', '010');
        $response->assertJsonPath('rows.0.identitas_tambahan.kdkec_label', 'Kec. Test');
    }
}
