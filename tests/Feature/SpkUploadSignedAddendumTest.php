<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SpkUploadSignedAddendumTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminUser(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => '']
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_upload_signed_main_spk(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'John Doe']);

        $periode = PeriodeAlokasi::factory()
            ->for($kegiatan)
            ->create([
                'bulan' => 5,
                'tahun' => 2026,
            ]);

        $alokasi = $periode->alokasiPetugas()->create([
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'total_honor' => 500000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $spk = Spk::create([
            'nomor_spk' => 'PPIS/13730/137/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'addendum_number' => 0,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Main',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/05/SPK_137_John_Doe_Mei_draft.pdf',
            'signed_file_path' => null,
            'created_by' => $admin->id,
        ]);

        // Create directory for test files
        $signedDir = public_path('spk-export/2026/05');
        if (! is_dir($signedDir)) {
            mkdir($signedDir, 0755, true);
        }

        $file = UploadedFile::fake()->create('SPK_137_John_Doe_Mei_signed.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/spk/{$spk->hashed_id}/upload-signed", [
                'file' => $file,
            ]);

        $response->assertRedirect(route('spk.show-by-month-get', [
            'bulan' => 5,
            'tahun' => 2026,
            'spk' => $spk->hashed_id,
        ]));

        $spk->refresh();

        // Verify signed file path for main SPK
        $this->assertNotNull($spk->signed_file_path);
        $this->assertStringContainsString('SPK_137_John_Doe_Mei_signed.pdf', $spk->signed_file_path);
        $this->assertStringContainsString('spk-export/2026/05/', $spk->signed_file_path);
        $this->assertEquals('diterbitkan', $spk->status);
        $this->assertTrue(file_exists(public_path($spk->signed_file_path)));
    }

    public function test_upload_signed_addendum_uses_different_filename(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'Jane Smith']);

        $periode = PeriodeAlokasi::factory()
            ->for($kegiatan)
            ->create([
                'bulan' => 6,
                'tahun' => 2026,
            ]);

        $alokasi = $periode->alokasiPetugas()->create([
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'total_honor' => 500000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $mainSpk = Spk::create([
            'nomor_spk' => 'PPIS/13730/138/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'addendum_number' => 0,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Main',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/06/SPK_138_Jane_Smith_Juni_draft.pdf',
            'signed_file_path' => 'spk-export/2026/06/SPK_138_Jane_Smith_Juni_signed.pdf',
            'created_by' => $admin->id,
        ]);

        $addendumSpk = Spk::create([
            'nomor_spk' => 'PPIS/13730/138/ADD-1/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'parent_spk_id' => $mainSpk->id,
            'addendum_number' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Addendum',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/06/SPK-ADDENDUM-1-Jane_Smith-06-2026.pdf',
            'signed_file_path' => null,
            'created_by' => $admin->id,
        ]);

        // Create directory for test files
        $signedDir = public_path('spk-export/2026/06');
        if (! is_dir($signedDir)) {
            mkdir($signedDir, 0755, true);
        }

        $file = UploadedFile::fake()->create('SPK-ADDENDUM-1-Jane_Smith-06-2026.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/spk/{$addendumSpk->hashed_id}/upload-signed", [
                'file' => $file,
            ]);

        $response->assertRedirect(route('spk.show-by-month-get', [
            'bulan' => 6,
            'tahun' => 2026,
            'spk' => $addendumSpk->hashed_id,
        ]));

        $addendumSpk->refresh();

        // Verify signed file path for addendum uses ADDENDUM format
        $this->assertNotNull($addendumSpk->signed_file_path);
        $this->assertStringContainsString('SPK-ADDENDUM-1-Jane_Smith-06-2026.pdf', $addendumSpk->signed_file_path);
        $this->assertStringContainsString('spk-export/2026/06/', $addendumSpk->signed_file_path);
        $this->assertStringNotContainsString('SPK_138_', $addendumSpk->signed_file_path);
        $this->assertEquals('diterbitkan', $addendumSpk->status);
        $this->assertTrue(file_exists(public_path($addendumSpk->signed_file_path)));
    }

    public function test_upload_signed_multiple_addendums_different_paths(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'Bob Johnson']);

        $periode = PeriodeAlokasi::factory()
            ->for($kegiatan)
            ->create([
                'bulan' => 7,
                'tahun' => 2026,
            ]);

        $alokasi = $periode->alokasiPetugas()->create([
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'total_honor' => 500000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $mainSpk = Spk::create([
            'nomor_spk' => 'PPIS/13730/139/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'addendum_number' => 0,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Main',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/07/SPK_139_Bob_Johnson_Juli_draft.pdf',
            'signed_file_path' => 'spk-export/2026/07/SPK_139_Bob_Johnson_Juli_signed.pdf',
            'created_by' => $admin->id,
        ]);

        $addendum1 = Spk::create([
            'nomor_spk' => 'PPIS/13730/139/ADD-1/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'parent_spk_id' => $mainSpk->id,
            'addendum_number' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Addendum 1',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/07/SPK-ADDENDUM-1-Bob_Johnson-07-2026.pdf',
            'signed_file_path' => null,
            'created_by' => $admin->id,
        ]);

        $addendum2 = Spk::create([
            'nomor_spk' => 'PPIS/13730/139/ADD-2/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'parent_spk_id' => $mainSpk->id,
            'addendum_number' => 2,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Addendum 2',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/07/SPK-ADDENDUM-2-Bob_Johnson-07-2026.pdf',
            'signed_file_path' => null,
            'created_by' => $admin->id,
        ]);

        // Create directory for test files
        $signedDir = public_path('spk-export/2026/07');
        if (! is_dir($signedDir)) {
            mkdir($signedDir, 0755, true);
        }

        // Upload addendum 1
        $file1 = UploadedFile::fake()->create('SPK-ADDENDUM-1-Bob_Johnson-07-2026.pdf', 100, 'application/pdf');

        $response1 = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/spk/{$addendum1->hashed_id}/upload-signed", [
                'file' => $file1,
            ]);

        $addendum1->refresh();

        // Upload addendum 2
        $file2 = UploadedFile::fake()->create('SPK-ADDENDUM-2-Bob_Johnson-07-2026.pdf', 100, 'application/pdf');

        $response2 = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/spk/{$addendum2->hashed_id}/upload-signed", [
                'file' => $file2,
            ]);

        $addendum2->refresh();

        // Verify different paths for addendum 1 and 2
        $this->assertStringContainsString('SPK-ADDENDUM-1-Bob_Johnson-07-2026.pdf', $addendum1->signed_file_path);
        $this->assertStringContainsString('SPK-ADDENDUM-2-Bob_Johnson-07-2026.pdf', $addendum2->signed_file_path);
        $this->assertNotEquals($addendum1->signed_file_path, $addendum2->signed_file_path);
        $this->assertTrue(file_exists(public_path($addendum1->signed_file_path)));
        $this->assertTrue(file_exists(public_path($addendum2->signed_file_path)));
    }

    public function test_upload_signed_with_special_characters_in_name(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'Ahmad/Nur:Special"<>|']);

        $periode = PeriodeAlokasi::factory()
            ->for($kegiatan)
            ->create([
                'bulan' => 8,
                'tahun' => 2026,
            ]);

        $alokasi = $periode->alokasiPetugas()->create([
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'total_honor' => 500000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $addendumSpk = Spk::create([
            'nomor_spk' => 'PPIS/13730/140/ADD-1/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'addendum_number' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Test SPK Addendum',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/08/SPK-ADDENDUM-1-AhmadNurSpecial-08-2026.pdf',
            'signed_file_path' => null,
            'created_by' => $admin->id,
        ]);

        // Create directory for test files
        $signedDir = public_path('spk-export/2026/08');
        if (! is_dir($signedDir)) {
            mkdir($signedDir, 0755, true);
        }

        $file = UploadedFile::fake()->create('addendum-signed.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/spk/{$addendumSpk->hashed_id}/upload-signed", [
                'file' => $file,
            ]);

        $addendumSpk->refresh();

        // Verify that special characters are removed from filename
        $this->assertNotNull($addendumSpk->signed_file_path);
        $this->assertStringContainsString('SPK-ADDENDUM-1-AhmadNurSpecial-08-2026.pdf', $addendumSpk->signed_file_path);
    }
}
