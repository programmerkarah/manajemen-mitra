<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasShowRiwayatAlokasiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): array
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => ucfirst(str_replace('_', ' ', $roleName)),
                'description' => '',
            ]
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_petugas_show_returns_simplified_grouped_riwayat_alokasi(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $kegiatanA = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan A',
            'kode_kegiatan' => 'KGT-A',
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
        ]);

        $kegiatanB = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Kegiatan B',
            'kode_kegiatan' => 'KGT-B',
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
        ]);

        $periodeA1 = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'tahun' => $activeYear,
            'bulan' => '01',
            'status' => 'draft',
        ]);

        $periodeA2 = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'tahun' => $activeYear,
            'bulan' => '02',
            'status' => 'draft',
        ]);

        $periodeB1 = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'tahun' => $activeYear,
            'bulan' => '03',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeA1->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 100000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeA2->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeB1->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
            'peran' => 'pml',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/petugas/'.$petugas->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Petugas/Show')
            ->has('riwayat_alokasi_ringkas', 2)
            ->where('riwayat_alokasi_ringkas.0.kegiatan.kode_kegiatan', 'KGT-A')
            ->where('riwayat_alokasi_ringkas.0.jumlah_periode', 2)
            ->where('riwayat_alokasi_ringkas.0.total_honor', fn ($value) => (float) $value === 300000.0)
            ->where('riwayat_alokasi_ringkas.1.kegiatan.kode_kegiatan', 'KGT-B')
            ->where('riwayat_alokasi_ringkas.1.jumlah_periode', 1)
            ->where('riwayat_alokasi_ringkas.1.total_honor', fn ($value) => (float) $value === 300000.0)
        );
    }
}
