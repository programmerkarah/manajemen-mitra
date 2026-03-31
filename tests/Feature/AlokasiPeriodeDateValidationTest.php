<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Sbml;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiPeriodeDateValidationTest extends TestCase
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

    private function setupKegiatanWithRateHonor(int $tahun, int $bulan): array
    {
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        $rateHonor = RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1083000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 3455000,
            'status' => 'aktif',
        ]);

        return [$kegiatan, $rateHonor];
    }

    public function test_store_multiple_rejects_tanggal_mulai_with_different_month(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = 2026;
        $bulan = 3; // Maret
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun, $bulan);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [
                    [
                        'petugas_id' => $petugas->id,
                        'peran' => 'PCL',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'jumlah_satuan' => 10,
                        'jenis_kegiatan' => 'survei',
                        'tahapan' => 'pencacahan_only',
                    ],
                ],
                'tanggal_mulai' => '2026-04-01', // April, beda bulan dengan periode (Maret)
                'tanggal_selesai' => '2026-04-15',
            ]);

        $response->assertSessionHasErrors(['date_validation']);
        $this->assertStringContainsString('Tanggal mulai harus dalam bulan yang sama dengan periode alokasi', session('errors')->first('date_validation'));
    }

    public function test_store_multiple_rejects_tanggal_selesai_with_different_month(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = 2026;
        $bulan = 3; // Maret
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun, $bulan);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [
                    [
                        'petugas_id' => $petugas->id,
                        'peran' => 'PCL',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'jumlah_satuan' => 10,
                        'jenis_kegiatan' => 'survei',
                        'tahapan' => 'pencacahan_only',
                    ],
                ],
                'tanggal_mulai' => '2026-03-01', // Maret, sesuai
                'tanggal_selesai' => '2026-04-15', // April, beda bulan dengan periode (Maret)
            ]);

        $response->assertSessionHasErrors(['date_validation']);
        $this->assertStringContainsString('Tanggal selesai harus dalam bulan yang sama dengan periode alokasi', session('errors')->first('date_validation'));
    }

    public function test_store_multiple_accepts_valid_dates_in_same_month(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = 2026;
        $bulan = 3; // Maret
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun, $bulan);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [
                    [
                        'petugas_id' => $petugas->id,
                        'peran' => 'PCL',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'jumlah_satuan' => 10,
                        'jenis_kegiatan' => 'survei',
                        'tahapan' => 'pencacahan_only',
                    ],
                ],
                'tanggal_mulai' => '2026-03-01', // Maret
                'tanggal_selesai' => '2026-03-31', // Maret
            ]);

        $response->assertSessionDoesntHaveErrors(['date_validation']);
        $response->assertRedirect();
    }

    public function test_store_multiple_rejects_listing_dates_with_different_month(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = 2026;
        $bulan = 3; // Maret
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun, $bulan);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [
                    [
                        'petugas_id' => $petugas->id,
                        'peran' => 'PCL',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'jumlah_satuan' => 10,
                        'jenis_kegiatan' => 'survei',
                        'tahapan' => 'listing_only',
                    ],
                ],
                'tanggal_mulai_listing' => '2026-04-01', // April, beda bulan dengan periode (Maret)
                'tanggal_selesai_listing' => '2026-04-15',
            ]);

        $response->assertSessionHasErrors(['date_validation']);
        $this->assertStringContainsString('Tanggal mulai listing harus dalam bulan yang sama dengan periode alokasi', session('errors')->first('date_validation'));
    }

    public function test_store_multiple_rejects_dates_with_different_year(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = 2026;
        $bulan = 3; // Maret
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun, $bulan);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [
                    [
                        'petugas_id' => $petugas->id,
                        'peran' => 'PCL',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'jumlah_satuan' => 10,
                        'jenis_kegiatan' => 'survei',
                        'tahapan' => 'pencacahan_only',
                    ],
                ],
                'tanggal_mulai' => '2025-03-01', // 2025, beda tahun dengan periode (2026)
                'tanggal_selesai' => '2025-03-31',
            ]);

        $response->assertSessionHasErrors(['date_validation']);
        $this->assertStringContainsString('Tanggal mulai harus dalam bulan yang sama dengan periode alokasi', session('errors')->first('date_validation'));
    }
}
