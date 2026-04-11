<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
