<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_role_cannot_access_petugas_review_page(): void
    {
        $guestRole = Role::query()->firstOrCreate(
            ['name' => 'guest'],
            ['display_name' => 'Guest', 'description' => 'Guest']
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$guestRole->id]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $guestRole->id,
                'active_year' => now()->year,
            ])
            ->get('/petugas/review');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_pml_user_can_submit_review_after_kegiatan_finished(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'name' => 'Reviewer PML',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $reviewerPetugas = Petugas::factory()->create([
            'nama' => 'Reviewer PML',
        ]);
        $targetPetugas = Petugas::factory()->create();

        $kegiatan = Kegiatan::factory()->create([
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
            'tahun_anggaran' => now()->year,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periode->id,
                'rating' => 5,
                'ulasan' => 'Kinerja sangat baik.',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('review_petugas', [
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $targetPetugas->id,
            'reviewer_user_id' => $user->id,
            'rating' => 5,
            'ulasan' => 'Kinerja sangat baik.',
        ]);
    }

    public function test_non_pml_and_non_ketua_tim_cannot_submit_review(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'name' => 'Reviewer Biasa',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $reviewerPetugas = Petugas::factory()->create([
            'nama' => 'Reviewer Biasa',
        ]);
        $targetPetugas = Petugas::factory()->create();

        $kegiatan = Kegiatan::factory()->create([
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
            'tahun_anggaran' => now()->year,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periode->id,
                'rating' => 3,
                'ulasan' => 'Cukup baik.',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('review_petugas', [
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $targetPetugas->id,
            'reviewer_user_id' => $user->id,
        ]);
    }

    public function test_review_is_final_per_periode_but_can_review_next_period_episode(): void
    {
        $this->travelTo(Carbon::create((int) now()->year, 8, 15, 10, 0, 0));

        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'name' => 'Reviewer PML',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $reviewerPetugas = Petugas::factory()->create([
            'nama' => 'Reviewer PML',
        ]);
        $targetPetugas = Petugas::factory()->create();

        $kegiatan = Kegiatan::factory()->create([
            'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
            'tahun_anggaran' => now()->year,
        ]);

        $periodeA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => 4,
            'status' => 'dikirim',
        ]);

        $periodeB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => 6,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periodeA->bulan,
            'tahun' => $periodeA->tahun,
            'periode_alokasi_id' => $periodeA->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periodeA->bulan,
            'tahun' => $periodeA->tahun,
            'periode_alokasi_id' => $periodeA->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periodeB->bulan,
            'tahun' => $periodeB->tahun,
            'periode_alokasi_id' => $periodeB->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periodeB->bulan,
            'tahun' => $periodeB->tahun,
            'periode_alokasi_id' => $periodeB->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        $firstResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periodeA->id,
                'rating' => 5,
                'ulasan' => 'Periode pertama bagus.',
            ]);

        $firstResponse->assertSessionHas('success');

        $secondResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periodeA->id,
                'rating' => 1,
                'ulasan' => 'Mencoba mengubah review.',
            ]);

        $secondResponse->assertSessionHas('error');

        $thirdResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periodeB->id,
                'rating' => 4,
                'ulasan' => 'Periode kedua juga baik.',
            ]);

        $thirdResponse->assertSessionHas('success');

        $this->assertDatabaseCount('review_petugas', 2);
        $this->assertDatabaseHas('review_petugas', [
            'reviewer_user_id' => $user->id,
            'periode_alokasi_id' => $periodeA->id,
            'rating' => 5,
            'ulasan' => 'Periode pertama bagus.',
        ]);
        $this->assertDatabaseHas('review_petugas', [
            'reviewer_user_id' => $user->id,
            'periode_alokasi_id' => $periodeB->id,
            'rating' => 4,
            'ulasan' => 'Periode kedua juga baik.',
        ]);

        $this->travelBack();
    }

    public function test_review_cannot_be_submitted_for_non_last_period_in_same_episode(): void
    {
        $this->travelTo(Carbon::create((int) now()->year, 3, 15, 10, 0, 0));

        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'name' => 'Reviewer PML',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $reviewerPetugas = Petugas::factory()->create([
            'nama' => 'Reviewer PML',
        ]);
        $targetPetugas = Petugas::factory()->create();

        $kegiatan = Kegiatan::factory()->create([
            'tanggal_selesai' => now()->addMonths(6)->format('Y-m-d'),
            'tahun_anggaran' => now()->year,
        ]);

        $periodeJan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => 1,
            'status' => 'dikirim',
        ]);

        $periodeFeb = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => 2,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => $periodeJan->tahun,
            'periode_alokasi_id' => $periodeJan->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 2,
            'tahun' => $periodeFeb->tahun,
            'periode_alokasi_id' => $periodeFeb->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 1,
            'tahun' => $periodeJan->tahun,
            'periode_alokasi_id' => $periodeJan->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 2,
            'tahun' => $periodeFeb->tahun,
            'periode_alokasi_id' => $periodeFeb->id,
            'petugas_id' => $targetPetugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        $janResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periodeJan->id,
                'rating' => 4,
                'ulasan' => 'Coba review Januari.',
            ]);

        $janResponse->assertSessionHas('error');

        $febResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugas->id,
                'periode_alokasi_id' => $periodeFeb->id,
                'rating' => 5,
                'ulasan' => 'Review periode terakhir episode.',
            ]);

        $febResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('review_petugas', [
            'reviewer_user_id' => $user->id,
            'periode_alokasi_id' => $periodeJan->id,
        ]);
        $this->assertDatabaseHas('review_petugas', [
            'reviewer_user_id' => $user->id,
            'periode_alokasi_id' => $periodeFeb->id,
            'rating' => 5,
        ]);

        $this->travelBack();
    }

    public function test_two_petugas_in_same_kegiatan_can_be_reviewed_independently(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'name' => 'Reviewer PML',
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $reviewerPetugas = Petugas::factory()->create([
            'nama' => 'Reviewer PML',
        ]);
        $targetPetugasA = Petugas::factory()->create();
        $targetPetugasB = Petugas::factory()->create();

        $kegiatan = Kegiatan::factory()->create([
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
            'tahun_anggaran' => now()->year,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $reviewerPetugas->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'jumlah_satuan' => 10,
            'total_honor' => 100000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugasA->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 8,
            'total_honor' => 90000,
        ]);

        AlokasiPetugas::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugasB->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 7,
            'total_honor' => 85000,
        ]);

        $responseA = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugasA->id,
                'periode_alokasi_id' => $periode->id,
                'rating' => 5,
                'ulasan' => 'Petugas A baik.',
            ]);

        $responseA->assertSessionHas('success');

        $responseB = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->post('/petugas/review', [
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $targetPetugasB->id,
                'periode_alokasi_id' => $periode->id,
                'rating' => 4,
                'ulasan' => 'Petugas B baik.',
            ]);

        $responseB->assertSessionHas('success');

        $this->assertDatabaseHas('review_petugas', [
            'reviewer_user_id' => $user->id,
            'kegiatan_id' => $kegiatan->id,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugasA->id,
            'rating' => 5,
        ]);
        $this->assertDatabaseHas('review_petugas', [
            'reviewer_user_id' => $user->id,
            'kegiatan_id' => $kegiatan->id,
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $targetPetugasB->id,
            'rating' => 4,
        ]);
    }
}
