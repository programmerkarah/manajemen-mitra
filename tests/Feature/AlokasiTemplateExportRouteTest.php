<?php

namespace Tests\Feature;

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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function test_import_preview_accepts_nama_nik_heading_format(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Andi Aktif',
            'nik' => '1111222233334444',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC',
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate test',
            'satuan_id' => $satuan->id,
            'rate' => 15000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $file = $this->makePreviewImportFile([
            ['Nama - NIK/NIP', 'Kode Penugasan', 'Jumlah Satuan Pencacahan', 'Pembayaran Parsial'],
            [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', 10, 'Tidak'],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonPath('rows.0.nik', $petugas->nik);
        $response->assertJsonPath('rows.0.petugas_nama', $petugas->nama);
        $response->assertJsonPath('rows.0.peran', 'PCL/PPL');
        $response->assertJsonPath('rows.0.jumlah_satuan', '10');
    }

    public function test_import_preview_ignores_reference_sheet_rows(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Faradina Isvandari',
            'nik' => '1373026105980001',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC2',
            'nama' => 'Dokumen 2',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate test 2',
            'satuan_id' => $satuan->id,
            'rate' => 15000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $file = $this->makePreviewImportFileWithReferenceSheet(
            [
                ['Nama - NIK', 'Kode Penugasan', 'Jumlah Satuan Pencacahan', 'Pembayaran Parsial'],
                [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', 10, 'Tidak'],
            ],
            [
                ['nip_nik', 'nama_petugas', 'pilihan_dropdown', 'kode_penugasan_dropdown'],
                [$petugas->nik, $petugas->nama, $petugas->nama.' - '.$petugas->nik, 'PCL/PPL'],
            ],
        );

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonCount(1, 'rows');
    }

    public function test_import_preview_maps_frame_sampel_by_metadata_and_auto_derives_satuan_for_survei(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Budi Frame',
            'nik' => '1373026105980002',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC3',
            'nama' => 'Dokumen 3',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate frame test',
            'satuan_id' => $satuan->id,
            'rate' => 10000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Uji',
            'kode' => 'F-UJI',
            'deskripsi' => 'Frame untuk uji import preview',
            'is_active' => true,
        ]);

        $frameOne = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'nama_frame' => 'Frame 1',
            'target_unit_sampel' => 3,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Kecamatan Utara',
            ],
        ]);

        $frameTwo = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'nama_frame' => 'Frame 2',
            'target_unit_sampel' => 2,
            'identitas_tambahan' => [
                'kdkec' => '020',
                'kdkec_label' => 'Kecamatan Selatan',
            ],
        ]);

        $file = $this->makePreviewImportFile([
            ['Nama - NIK/NIP', 'Kode Penugasan', 'kdkec', 'Jumlah Satuan Pencacahan', 'Pembayaran Parsial'],
            [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', '010', 0, 'Tidak'],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonPath('rows.0.nik', $petugas->nik);
        $response->assertJsonPath('rows.0.jumlah_satuan', '3');
        $response->assertJsonPath('rows.0.jumlah_unit_sampel', 3);
        $response->assertJsonPath('rows.0.frame_sampel_ids.0', $frameOne->id);
        $response->assertJsonPath('rows.0.frame_sampel_metadata.kdkec', '010');
        $response->assertJsonPath('frame_metadata_columns.0.code', 'kdkec');
    }

    public function test_export_template_adds_dropdown_and_text_format_for_frame_metadata_columns(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Dropdown',
            'kode' => 'FDROP',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
            'frame_sampel_pencacahan_id' => $masterFrame->id,
        ]);

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => 1,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Kecamatan',
            ],
        ]);

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => 2,
            'identitas_tambahan' => [
                'kdkec' => '020',
                'kdkec_label' => 'Kecamatan',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/export/create?kegiatan='.$kegiatan->hashed_id.'&tahapan=pencacahan_only');

        $response->assertOk();

        $tempPath = storage_path('framework/testing/alokasi-template-metadata-dropdown-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $mainSheet = $spreadsheet->getSheetByName('Alokasi Petugas');

        $this->assertNotNull($mainSheet);
        $this->assertSame('Kecamatan', (string) $mainSheet->getCell('C1')->getValue());

        $metadataValidation = $mainSheet->getCell('C2')->getDataValidation();
        $this->assertSame('list', $metadataValidation->getType());
        $this->assertSame('INDIRECT("DD_C_KDKEC_ROOT")', $metadataValidation->getFormula1());

        $this->assertSame('@', (string) $mainSheet->getStyle('C:C')->getNumberFormat()->getFormatCode());
    }

    public function test_export_template_for_sensus_also_adds_frame_metadata_columns_when_rows_exist(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Sensus',
            'kode' => 'FSENSUS',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'has_listing_updating' => false,
            'frame_sampel_pencacahan_id' => $masterFrame->id,
        ]);

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => 5,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Kecamatan',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/export/create?kegiatan='.$kegiatan->hashed_id.'&tahapan=pencacahan_only');

        $response->assertOk();

        $tempPath = storage_path('framework/testing/alokasi-template-sensus-metadata-dropdown-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $mainSheet = $spreadsheet->getSheetByName('Alokasi Petugas');

        $this->assertNotNull($mainSheet);
        $this->assertSame('Kecamatan', (string) $mainSheet->getCell('C1')->getValue());
    }

    public function test_export_template_for_sensus_economy_with_two_unit_types_has_two_pencacahan_columns(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Sensus Ekonomi',
            'kode' => 'FSE',
            'is_active' => true,
        ]);

        $unitUsaha = MasterUnitSampel::create([
            'nama' => 'Usaha',
            'kode' => 'USH',
            'is_active' => true,
        ]);

        $unitKeluarga = MasterUnitSampel::create([
            'nama' => 'Keluarga',
            'kode' => 'KLG',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'has_listing_updating' => false,
            'frame_sampel_pencacahan_id' => $masterFrame->id,
            'unit_sampel_pencacahan_ids' => [$unitUsaha->id, $unitKeluarga->id],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/export/create?kegiatan='.$kegiatan->hashed_id.'&tahapan=pencacahan_only');

        $response->assertOk();

        $tempPath = storage_path('framework/testing/alokasi-template-sensus-ekonomi-unit-columns-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $mainSheet = $spreadsheet->getSheetByName('Alokasi Petugas');

        $this->assertNotNull($mainSheet);
        $this->assertSame('Jumlah Usaha', (string) $mainSheet->getCell('C1')->getValue());
        $this->assertSame('Jumlah Keluarga', (string) $mainSheet->getCell('D1')->getValue());
    }

    public function test_import_preview_maps_frame_sampel_by_metadata_for_sensus_and_sets_pencacahan_from_frame_sample(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'has_listing_updating' => false,
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Siti Sensus',
            'nik' => '1373026105980099',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC4',
            'nama' => 'Dokumen 4',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'sensus',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate sensus',
            'satuan_id' => $satuan->id,
            'rate' => 11000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Sensus Import',
            'kode' => 'FSI',
            'deskripsi' => 'Frame untuk uji import sensus',
            'is_active' => true,
        ]);

        $frame = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'nama_frame' => 'Frame Sensus 1',
            'target_unit_sampel' => 7,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Kecamatan Utara',
            ],
        ]);

        $file = $this->makePreviewImportFile([
            ['Nama - NIK/NIP', 'Kode Penugasan', 'kdkec', 'Jumlah Satuan Pencacahan', 'Pembayaran Parsial'],
            [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', '010', 2.5, 'Tidak'],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('rows.0.frame_sampel_ids.0', $frame->id);
        $response->assertJsonPath('rows.0.jumlah_unit_sampel', 7);
        $response->assertJsonPath('rows.0.jumlah_satuan', '7');
        $response->assertJsonPath('rows.0.estimasi_honor', 77000);
    }

    public function test_import_preview_sensus_returns_error_when_metadata_matches_multiple_frames(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'has_listing_updating' => false,
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Asep Sensus',
            'nik' => '1373026105980011',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC5',
            'nama' => 'Dokumen 5',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'sensus',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate sensus agregasi',
            'satuan_id' => $satuan->id,
            'rate' => 10000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Sensus Agregasi',
            'kode' => 'FSA',
            'is_active' => true,
        ]);

        $frameOne = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => 3,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kddes' => '001',
            ],
        ]);

        $frameTwo = KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => 4,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kddes' => '002',
            ],
        ]);

        $file = $this->makePreviewImportFile([
            ['Nama - NIK/NIP', 'Kode Penugasan', 'kdkec', 'Jumlah Satuan Pencacahan', 'Pembayaran Parsial'],
            [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', '010', 1, 'Tidak'],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 0);
        $response->assertJsonPath('summary.error_count', 1);
        $response->assertJsonPath('errors.0', 'Baris 2: Metadata frame sampel ambigu, cocok ke lebih dari satu frame. Lengkapi kolom metadata hingga unik.');
    }

    public function test_import_preview_sensus_economy_reads_jumlah_usaha_and_jumlah_keluarga_columns(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'has_listing_updating' => false,
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $unitUsaha = MasterUnitSampel::create([
            'nama' => 'Usaha',
            'kode' => 'USH2',
            'is_active' => true,
        ]);

        $unitKeluarga = MasterUnitSampel::create([
            'nama' => 'Keluarga',
            'kode' => 'KLG2',
            'is_active' => true,
        ]);

        $kegiatan->update([
            'unit_sampel_pencacahan_ids' => [$unitUsaha->id, $unitKeluarga->id],
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Rina Sensus',
            'nik' => '1373026105980012',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $satuan = Satuan::create([
            'kode' => 'DOC6',
            'nama' => 'Dokumen 6',
            'status' => 'aktif',
        ]);

        RateHonor::create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL/PPL',
            'jenis_kegiatan' => 'sensus',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate sensus ekonomi',
            'satuan_id' => $satuan->id,
            'rate' => 10000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => 2026,
            'status' => 'aktif',
        ]);

        $file = $this->makePreviewImportFile([
            ['Nama - NIK/NIP', 'Kode Penugasan', 'Jumlah Usaha', 'Jumlah Keluarga', 'Pembayaran Parsial'],
            [$petugas->nama.' - '.$petugas->nik, 'PCL/PPL', 4, 6, 'Tidak'],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/kegiatan/'.$kegiatan->hashed_id.'/import-preview', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.error_count', 0);
        $response->assertJsonPath('rows.0.jumlah_satuan', '10');
        $response->assertJsonPath('rows.0.estimasi_honor', 100000);
    }

    public function test_export_template_frame_sheet_uses_total_target_for_multiple_unit_types(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $masterFrame = MasterFrameSampel::create([
            'nama' => 'Frame Multi Unit',
            'kode' => 'FMU',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
            'frame_sampel_pencacahan_id' => $masterFrame->id,
        ]);

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $masterFrame->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel' => [
                '1' => 3,
                '2' => 4,
            ],
            'identitas_tambahan' => [
                'kdkec' => '010',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/periode/export/create?kegiatan='.$kegiatan->hashed_id.'&tahapan=pencacahan_only');

        $response->assertOk();

        $tempPath = storage_path('framework/testing/alokasi-template-multi-unit-target-test.xlsx');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        file_put_contents($tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempPath);
        $frameSheet = $spreadsheet->getSheetByName('Daftar Frame Sampel');

        $this->assertNotNull($frameSheet);

        $targetColumn = null;
        $highestColumnIndex = Coordinate::columnIndexFromString($frameSheet->getHighestColumn());
        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            if ((string) $frameSheet->getCell($columnLetter.'1')->getValue() === 'target_unit_sampel') {
                $targetColumn = $columnLetter;
                break;
            }
        }

        $this->assertNotNull($targetColumn);
        $this->assertSame('7', (string) $frameSheet->getCell($targetColumn.'2')->getValue());
    }

    private function makePreviewImportFile(array $rows): UploadedFile
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

        $path = $directory.'/preview-import-'.uniqid().'.xlsx';

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'preview-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function makePreviewImportFileWithReferenceSheet(array $mainRows, array $referenceRows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $mainSheet = $spreadsheet->getActiveSheet();
        $mainSheet->setTitle('Alokasi Petugas');

        foreach ($mainRows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $mainSheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $referenceSheet = new Worksheet($spreadsheet, 'Daftar Petugas Aktif');
        $spreadsheet->addSheet($referenceSheet);

        foreach ($referenceRows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $referenceSheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.'/preview-import-reference-'.uniqid().'.xlsx';

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'preview-import-reference.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
