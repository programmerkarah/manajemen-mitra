<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PetugasImportUpsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preview_shows_create_and_update_actions(): void
    {
        [$admin] = $this->createAdminUser();

        Petugas::factory()->create([
            'nama' => 'Petugas Existing',
            'nik' => '1234567890123456',
            'email' => 'existing@example.com',
            'telepon' => '081111111111',
        ]);

        $file = $this->makeCsvUpload(<<<'CSV'
nama,nik,email,telepon,alamat,pendidikan,tahun_bergabung,status,jenis_petugas,jabatan,golongan,jenis_kelamin,kecamatan,desa_kelurahan,tanggal_lahir,npwp,bank,no_rekening,nama_rekening,catatan
Petugas Existing,1234567890123456,existing@example.com,082222222222,Alamat Baru,S1,2024,aktif,non-organik,,,laki-laki,Silungkang,Silungkang Oso,1990-01-01,,,,,
Petugas Baru,2234567890123456,new@example.com,083333333333,Alamat Baru 2,S1,2025,aktif,organik,Statistisi Ahli Pertama,III/a,perempuan,Barangin,Rantih,1992-02-02,,,,,
CSV);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => Role::where('name', 'admin')->value('id')])
            ->withHeader('Accept', 'application/json')
            ->post(route('petugas.import-preview'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.created_count', 1);
        $response->assertJsonPath('summary.updated_count', 1);
        $response->assertJsonCount(2, 'rows');
        $response->assertJsonPath('rows.0.action', 'update');
        $response->assertJsonPath('rows.1.action', 'create');
    }

    public function test_import_updates_existing_petugas_instead_of_failing_duplicate(): void
    {
        [$admin] = $this->createAdminUser();

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Existing',
            'nik' => '1234567890123456',
            'email' => 'existing@example.com',
            'telepon' => '081111111111',
            'alamat' => 'Alamat Lama',
        ]);

        $file = $this->makeCsvUpload(<<<'CSV'
nama,nik,email,telepon,alamat,pendidikan,tahun_bergabung,status,jenis_petugas,jabatan,golongan,jenis_kelamin,kecamatan,desa_kelurahan,tanggal_lahir,npwp,bank,no_rekening,nama_rekening,catatan
Petugas Existing Update,1234567890123456,existing@example.com,082222222222,Alamat Baru,S1,2024,aktif,non-organik,,,laki-laki,Silungkang,Silungkang Oso,1990-01-01,,,,,
CSV);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => Role::where('name', 'admin')->value('id')])
            ->post(route('petugas.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('petugas.index'));
        $response->assertSessionHas('success');

        $petugas->refresh();

        $this->assertSame('Petugas Existing Update', $petugas->nama);
        $this->assertSame('082222222222', $petugas->telepon);
        $this->assertSame('Alamat Baru', $petugas->alamat);
        $this->assertDatabaseCount('petugas', 1);
    }

    public function test_import_preview_keeps_rows_with_validation_warnings(): void
    {
        [$admin] = $this->createAdminUser();

        $file = $this->makeCsvUpload(<<<'CSV'
nama,nik,email,telepon,alamat,pendidikan,tahun_bergabung,status,jenis_petugas,jabatan,golongan,jenis_kelamin,kecamatan,desa_kelurahan,tanggal_lahir,npwp,bank,no_rekening,nama_rekening,catatan
Preview Warning,3234567890123456,preview-warning@example.com,081234567890,Alamat Uji,S1,2024,aktif,organik,Statistisi,III/a,laki-laki,Silungkang,Silungkang Oso,32/13/2020,,,,,
CSV);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => Role::where('name', 'admin')->value('id')])
            ->withHeader('Accept', 'application/json')
            ->post(route('petugas.import-preview'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.valid_for_import', false);
        $response->assertJsonPath('summary.skipped_count', 1);
        $response->assertJsonPath('summary.success_count', 0);
        $response->assertJsonPath('rows.0.warnings.0', 'Tanggal lahir harus format tanggal yang valid');
    }

    public function test_import_preview_accepts_d2_pendidikan(): void
    {
        [$admin] = $this->createAdminUser();

        $file = $this->makeCsvUpload(<<<'CSV'
nama,nik,email,telepon,alamat,pendidikan,tahun_bergabung,status,jenis_petugas,jabatan,golongan,jenis_kelamin,kecamatan,desa_kelurahan,tanggal_lahir,npwp,bank,no_rekening,nama_rekening,catatan
Petugas D2,4234567890123456,petugas-d2@example.com,081234567891,Alamat D2,D2,2024,aktif,organik,Statistisi,III/a,laki-laki,Silungkang,Silungkang Oso,1991-05-10,,,,,
CSV);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => Role::where('name', 'admin')->value('id')])
            ->withHeader('Accept', 'application/json')
            ->post(route('petugas.import-preview'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonPath('summary.success_count', 1);
        $response->assertJsonPath('summary.skipped_count', 0);
        $response->assertJsonPath('rows.0.valid_for_import', true);
        $response->assertJsonCount(0, 'rows.0.warnings');
    }

    public function test_import_skips_existing_row_when_no_data_changes(): void
    {
        [$admin] = $this->createAdminUser();

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sama',
            'nik' => '5234567890123456',
            'email' => 'petugas-sama@example.com',
            'telepon' => '081111111199',
            'alamat' => 'Alamat Sama',
            'pendidikan' => 'D1',
            'tahun_bergabung' => 2024,
            'status' => 'aktif',
            'jenis_petugas' => 'organik',
            'jabatan' => 'Statistisi',
            'golongan' => 'III/a',
            'jenis_kelamin' => 'laki-laki',
            'kecamatan' => 'Silungkang',
            'desa_kelurahan' => 'Silungkang Oso',
            'tanggal_lahir' => '1990-01-01',
        ]);

        $petugas->forceFill(['updated_at' => now()->subDays(2)])->saveQuietly();
        $originalUpdatedAt = $petugas->fresh()->updated_at?->toDateTimeString();

        $file = $this->makeCsvUpload(<<<'CSV'
nama,nik,email,telepon,alamat,pendidikan,tahun_bergabung,status,jenis_petugas,jabatan,golongan,jenis_kelamin,kecamatan,desa_kelurahan,tanggal_lahir,npwp,bank,no_rekening,nama_rekening,catatan
Petugas Sama,5234567890123456,petugas-sama@example.com,081111111199,Alamat Sama,D1,2024,aktif,organik,Statistisi,III/a,laki-laki,Silungkang,Silungkang Oso,1990-01-01,,,,,
CSV);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => Role::where('name', 'admin')->value('id')])
            ->post(route('petugas.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('petugas.index'));

        $petugas->refresh();

        $this->assertSame($originalUpdatedAt, $petugas->updated_at?->toDateTimeString());
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createAdminUser(): array
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        return [$admin, $adminRole->id];
    }

    private function makeCsvUpload(string $content): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($content))));

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'petugas-import-');
        $xlsxPath = $path.'.xlsx';

        (new Xlsx($spreadsheet))->save($xlsxPath);

        return new UploadedFile(
            $xlsxPath,
            'petugas-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
