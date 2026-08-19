<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Sbml;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiStatusKepegawaianPersistenceTest extends TestCase
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

    private function setupSurveiKegiatanWithOrganikRate(int $tahun): Kegiatan
    {
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-ORG-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'deskripsi' => 'Rate honor organik PCL',
            'satuan_id' => $satuan->id,
            'rate' => 250000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 9000000,
            'status' => 'aktif',
        ]);

        return $kegiatan;
    }

    public function test_store_multiple_persists_organik_status_kepegawaian(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        $kegiatan = $this->setupSurveiKegiatanWithOrganikRate($tahun);

        $petugasOrganik = Petugas::factory()->create([
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", [
                'alokasi' => [[
                    'petugas_id' => $petugasOrganik->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 1,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'pencacahan_only',
                ]],
            ]);

        $response->assertRedirect(route('alokasi.index'));

        $alokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasOrganik->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('organik', $alokasi->status_kepegawaian);
    }

    public function test_update_periode_persists_organik_status_kepegawaian(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        $kegiatan = $this->setupSurveiKegiatanWithOrganikRate($tahun);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $petugasLama = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugasLama->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'total_honor' => 100000,
        ]);

        $petugasOrganik = Petugas::factory()->create([
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03", [
                'alokasi' => [[
                    'petugas_id' => $petugasOrganik->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 1,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'pencacahan_only',
                ]],
            ]);

        $response->assertRedirect(route('alokasi.index'));

        $updatedAlokasi = AlokasiPetugas::query()
            ->where('periode_alokasi_id', $periode->id)
            ->where('petugas_id', $petugasOrganik->id)
            ->firstOrFail();

        $this->assertSame('organik', $updatedAlokasi->status_kepegawaian);
    }
}
