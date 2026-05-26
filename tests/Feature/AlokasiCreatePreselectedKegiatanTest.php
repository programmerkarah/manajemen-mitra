<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiCreatePreselectedKegiatanTest extends TestCase
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

    public function test_create_alokasi_with_preselected_kegiatan_provides_budget_info_and_required_fields(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'pagu_pencacahan' => 1250000,
            'pagu_listing' => 450000,
            'has_listing_updating' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where('selectedKegiatan.id', $kegiatan->id)
            ->where('selectedKegiatan.pagu_pencacahan', fn ($value) => (float) $value === 1250000.0)
            ->where('selectedKegiatan.pagu_listing', fn ($value) => (float) $value === 450000.0)
            ->where('selectedKegiatan.has_listing_updating', true)
            ->has("budget_info.{$kegiatan->id}")
            ->where("budget_info.{$kegiatan->id}.pagu_pencacahan", fn ($value) => (float) $value === 1250000.0)
            ->where("budget_info.{$kegiatan->id}.pagu_listing", fn ($value) => (float) $value === 450000.0)
        );
    }

    public function test_create_alokasi_provides_petugas_suggestions_for_previous_period_and_smallest_allocation(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
        ]);

        $kegiatanLain = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$activeYear,
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
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        $petugasA = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $petugasB = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $petugasC = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $periodeKegiatan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $activeYear,
            'bulan' => '02',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeKegiatan->id,
            'petugas_id' => $petugasA->id,
            'total_honor' => 1000,
            'total_honor_listing' => 0,
        ]);

        $periodeKegiatanLain = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanLain->id,
            'tahun' => $activeYear,
            'bulan' => '03',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeKegiatanLain->id,
            'petugas_id' => $petugasB->id,
            'total_honor' => 100,
            'total_honor_listing' => 0,
        ]);

        $periodeKegiatanLainBulanBerikutnya = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanLain->id,
            'tahun' => $activeYear,
            'bulan' => '04',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeKegiatanLain->id,
            'petugas_id' => $petugasC->id,
            'total_honor' => 1,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeKegiatanLainBulanBerikutnya->id,
            'petugas_id' => $petugasC->id,
            'total_honor' => 1,
            'total_honor_listing' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where("petugas_suggestions.{$kegiatan->id}.previous_allocations.0.petugas_id", $petugasA->id)
            ->where("petugas_suggestions.{$kegiatan->id}.previous_allocations.0.bulan", 2)
            ->where("petugas_suggestions.{$kegiatan->id}.smallest_allocation_petugas_ids.0", $petugasC->id)
            ->where("petugas_suggestions.{$kegiatan->id}.smallest_allocation_petugas_ids.1", $petugasB->id)
            ->where("petugas_suggestions.{$kegiatan->id}.smallest_allocation_petugas_ids.2", $petugasA->id)
            ->where("petugas_unique_kegiatan_counts.{$petugasA->id}", 1)
            ->where("petugas_unique_kegiatan_counts.{$petugasB->id}", 1)
            ->where("petugas_unique_kegiatan_counts.{$petugasC->id}", 1)
            ->where("petugas_allocation_counts.{$petugasA->id}", 1)
            ->where("petugas_allocation_counts.{$petugasB->id}", 1)
            ->where("petugas_allocation_counts.{$petugasC->id}", 1)
        );
    }
}
