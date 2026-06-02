<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}
