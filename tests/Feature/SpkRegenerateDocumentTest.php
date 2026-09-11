<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkRegenerateDocumentTest extends TestCase
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

    public function test_regenerate_document_updates_existing_row_and_requires_signed_upload_again(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        Penandatangan::create([
            'nama' => 'PPK Test',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => '2026-01-01',
            'periode_selesai' => '2026-12-31',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'John Doe']);

        $periode = PeriodeAlokasi::factory()->for($kegiatan)->create([
            'bulan' => 5,
            'tahun' => 2026,
            'status' => 'dikirim',
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
            'tanggal_spk' => '2026-05-01',
            'tanggal_mulai_kerja' => '2026-05-01',
            'tanggal_selesai_kerja' => '2026-05-31',
            'uraian_pekerjaan' => 'Test SPK Main',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/05/SPK_137_John_Doe_Mei.pdf',
            'signed_file_path' => 'spk-export/2026/05/SPK_137_John_Doe_Mei_signed.pdf',
            'status' => 'diterbitkan',
            'created_by' => $admin->id,
        ]);

        $publicDir = public_path('spk-export/2026/05');
        if (! is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $signedFile = public_path($spk->signed_file_path);
        file_put_contents($signedFile, '%PDF-1.4 dummy signed file');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/spk/'.$spk->hashed_id.'/regenerate-document', ['mode' => 'main']);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('spk', 1);

        $spk->refresh();
        $this->assertNull($spk->signed_file_path);
        $this->assertSame('draft', $spk->status);
        $this->assertSame('spk-export/2026/05/SPK_137_John_Doe_Mei.pdf', $spk->previous_file_path);
        $this->assertStringContainsString('SPK_137_John_Doe_Mei.pdf', $spk->file_path);
        $this->assertFileDoesNotExist($signedFile);
    }

    public function test_regenerate_document_accepts_addendum_mode_for_selected_addendum_only(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();

        Penandatangan::create([
            'nama' => 'PPK Test',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => '2026-01-01',
            'periode_selesai' => '2026-12-31',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create();
        $petugas = Petugas::factory()->create(['nama' => 'Jane Doe']);

        $periode = PeriodeAlokasi::factory()->for($kegiatan)->create([
            'bulan' => 6,
            'tahun' => 2026,
            'status' => 'dikirim',
        ]);

        $alokasi = $periode->alokasiPetugas()->create([
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 6,
            'total_honor' => 600000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        $mainSpk = Spk::create([
            'nomor_spk' => 'PPIS/13730/137/K/2026',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'addendum_number' => 0,
            'tanggal_spk' => '2026-06-01',
            'tanggal_mulai_kerja' => '2026-06-01',
            'tanggal_selesai_kerja' => '2026-06-30',
            'uraian_pekerjaan' => 'Test Main PK',
            'nilai_kontrak' => 1000000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/06/SPK_137_Jane_Doe_Juni.pdf',
            'signed_file_path' => 'spk-export/2026/06/SPK_137_Jane_Doe_Juni_signed.pdf',
            'status' => 'diterbitkan',
            'created_by' => $admin->id,
        ]);

        $addendum = Spk::create([
            'nomor_spk' => 'PPIS/13730/137/K/2026/ADD-1',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'parent_spk_id' => $mainSpk->id,
            'addendum_number' => 1,
            'tanggal_spk' => '2026-06-15',
            'tanggal_mulai_kerja' => '2026-06-01',
            'tanggal_selesai_kerja' => '2026-06-30',
            'uraian_pekerjaan' => 'Test Addendum PK',
            'nilai_kontrak' => 500000,
            'nama_ppk' => 'Test PPK',
            'nip_ppk' => '198001012010011001',
            'file_path' => 'spk-export/2026/06/SPK_137_Jane_Doe_Juni_Addendum_1.pdf',
            'signed_file_path' => 'spk-export/2026/06/SPK_137_Jane_Doe_Juni_Addendum_1_signed.pdf',
            'status' => 'diterbitkan',
            'created_by' => $admin->id,
        ]);

        $publicDir = public_path('spk-export/2026/06');
        if (! is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        file_put_contents(public_path($addendum->signed_file_path), '%PDF-1.4 dummy signed addendum');
        file_put_contents(public_path($mainSpk->signed_file_path), '%PDF-1.4 dummy signed main');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/spk/'.$addendum->hashed_id.'/regenerate-document', ['mode' => 'addendum']);

        $response->assertSessionHas('success');
        $addendum->refresh();
        $mainSpk->refresh();

        $this->assertNull($addendum->signed_file_path);
        $this->assertSame('draft', $addendum->status);
        $this->assertNotNull($mainSpk->signed_file_path);
        $this->assertFileDoesNotExist(public_path($addendum->signed_file_path));
    }
}
