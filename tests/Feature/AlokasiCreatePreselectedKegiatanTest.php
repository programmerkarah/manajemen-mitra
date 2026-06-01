<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
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
            ->where('petugas_review_recommendations.has_review_data', false)
        );
    }

    public function test_create_alokasi_includes_frame_sampel_metadata_for_selected_kegiatan(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => true,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'FSM-'.$activeYear,
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
            'rate_listing' => 50000,
            'satuan_listing_id' => $satuan->id,
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        $masterFrameSampel = MasterFrameSampel::query()->create([
            'nama' => 'Frame Sampel Listing',
            'kode' => 'FSL-'.$activeYear,
            'deskripsi' => 'Frame sampel untuk pengujian alokasi',
            'is_active' => true,
        ]);

        $frameSampel = $kegiatan->kegiatanFrameSampel()->create([
            'frame_sampel_id' => $masterFrameSampel->id,
            'tahapan' => 'listing',
            'nama_frame' => 'Frame Blok Listing',
            'target_unit_sampel' => 12,
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Kecamatan Utara',
                'kddes' => '020',
                'kddes_label' => 'Desa Mekar',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where('selectedKegiatan.kegiatan_frame_sampel.0.id', $frameSampel->id)
            ->where('selectedKegiatan.kegiatan_frame_sampel.0.identitas_tambahan.kdkec', '010')
            ->where('selectedKegiatan.kegiatan_frame_sampel.0.identitas_tambahan.kdkec_label', 'Kecamatan Utara')
            ->where('selectedKegiatan.kegiatan_frame_sampel.0.identitas_tambahan.kddes', '020')
            ->where('selectedKegiatan.kegiatan_frame_sampel.0.identitas_tambahan.kddes_label', 'Desa Mekar')
        );
    }

    public function test_create_alokasi_includes_unit_sample_labels_for_selected_kegiatan(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $unitKeluarga = MasterUnitSampel::query()->create([
            'kode' => 'KLG-'.$activeYear,
            'nama' => 'keluarga',
            'deskripsi' => 'Unit keluarga',
            'is_active' => true,
        ]);

        $unitUsaha = MasterUnitSampel::query()->create([
            'kode' => 'USH-'.$activeYear,
            'nama' => 'usaha',
            'deskripsi' => 'Unit usaha',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'sensus',
            'unit_sampel_pencacahan_ids' => [$unitKeluarga->id, $unitUsaha->id],
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'SSE-'.$activeYear,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'sensus',
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

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where('selectedKegiatan.unit_sampel_pencacahan_items.0.nama', 'keluarga')
            ->where('selectedKegiatan.unit_sampel_pencacahan_items.1.nama', 'usaha')
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
            ->where("petugas_total_honor.{$petugasA->id}", 1000)
            ->where("petugas_total_honor.{$petugasB->id}", 100)
            ->where("petugas_total_honor.{$petugasC->id}", 2)
        );
    }

    public function test_create_alokasi_provides_review_based_petugas_recommendations_when_reviews_exist(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'SR'.$activeYear,
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

        $petugasRecommended = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $petugasNotRecommended = Petugas::factory()->create([
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $reviewer = User::factory()->create();
        $reviewerKedua = User::factory()->create();

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $activeYear,
            'bulan' => '01',
            'status' => 'draft',
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasRecommended->id,
            'periode_alokasi_id' => $periode->id,
            'reviewer_user_id' => $reviewer->id,
            'rating' => 5,
            'ulasan' => 'Sangat baik',
            'reviewed_at' => now(),
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasRecommended->id,
            'periode_alokasi_id' => $periode->id,
            'reviewer_user_id' => $reviewerKedua->id,
            'rating' => 4,
            'ulasan' => 'Baik',
            'reviewed_at' => now(),
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasNotRecommended->id,
            'periode_alokasi_id' => $periode->id,
            'reviewer_user_id' => $reviewer->id,
            'rating' => 1,
            'ulasan' => 'Kurang',
            'reviewed_at' => now(),
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasNotRecommended->id,
            'periode_alokasi_id' => $periode->id,
            'reviewer_user_id' => $reviewerKedua->id,
            'rating' => 2,
            'ulasan' => 'Perlu perbaikan',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/alokasi/create?kegiatan_id='.$kegiatan->hashed_id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Alokasi/Create')
            ->where('petugas_review_recommendations.has_review_data', true)
            ->where("petugas_review_recommendations.by_petugas.{$petugasRecommended->id}.status", 'recommended')
            ->where("petugas_review_recommendations.by_petugas.{$petugasNotRecommended->id}.status", 'not_recommended')
            ->where("petugas_review_recommendations.by_petugas.{$petugasRecommended->id}.review_count", 2)
            ->where("petugas_review_recommendations.by_petugas.{$petugasNotRecommended->id}.review_count", 2)
        );
    }
}
