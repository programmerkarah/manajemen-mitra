<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\ReviewPetugas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPenilaianMitraStatistikTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_penilaian_mitra_can_be_opened_by_operator(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => now()->year,
        ]);

        $petugas = Petugas::factory()->create();

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'status' => 'dikirim',
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'periode_alokasi_id' => $periode->id,
            'reviewer_user_id' => $user->id,
            'rating' => 4,
            'ulasan' => 'Kinerja baik.',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->where('summary.total_reviews', 1)
            ->has('hall_of_fame_table', 1)
            ->where('hall_of_fame_table.0.kegiatan_count', 1)
            ->where('hall_of_fame_table.0.review_count', 1)
        );
    }

    public function test_guest_role_can_open_monitoring_penilaian_mitra(): void
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
            ->get('/monitoring-penilaian-mitra');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
        );
    }

    public function test_kegiatan_options_stay_available_when_specific_kegiatan_filter_is_active(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $petugas = Petugas::factory()->create();

        $kegiatanA = Kegiatan::factory()->create([
            'tahun_anggaran' => now()->year,
            'nama_kegiatan' => 'Kegiatan A',
        ]);
        $kegiatanB = Kegiatan::factory()->create([
            'tahun_anggaran' => now()->year,
            'nama_kegiatan' => 'Kegiatan B',
        ]);

        $periodeA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'tahun' => now()->year,
            'bulan' => str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'status' => 'dikirim',
        ]);

        $periodeB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'tahun' => now()->year,
            'bulan' => str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'status' => 'dikirim',
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatanA->id,
            'petugas_id' => $petugas->id,
            'periode_alokasi_id' => $periodeA->id,
            'reviewer_user_id' => $user->id,
            'rating' => 4,
            'ulasan' => 'Review kegiatan A',
            'reviewed_at' => now(),
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatanB->id,
            'petugas_id' => $petugas->id,
            'periode_alokasi_id' => $periodeB->id,
            'reviewer_user_id' => $user->id,
            'rating' => 5,
            'ulasan' => 'Review kegiatan B',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra?kegiatan_id='.$kegiatanA->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->has('kegiatan_options', 2)
            ->where('filters.kegiatan_id', (string) $kegiatanA->id)
        );
    }

    public function test_top_bottom_mitra_apply_rating_threshold_and_limit_by_kegiatan_filter(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => now()->year,
            'nama_kegiatan' => 'Kegiatan Rating Test',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'status' => 'dikirim',
        ]);

        $ratings = [5, 4, 3, 2, 1, 2, 5];

        foreach ($ratings as $index => $rating) {
            $petugas = Petugas::factory()->create([
                'nama' => 'Petugas '.$index,
            ]);

            ReviewPetugas::query()->create([
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $petugas->id,
                'periode_alokasi_id' => $periode->id,
                'reviewer_user_id' => $user->id,
                'rating' => $rating,
                'ulasan' => 'Test rating '.$rating,
                'reviewed_at' => now(),
            ]);
        }

        $defaultResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra');

        $defaultResponse->assertOk();
        $defaultResponse->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->has('top_petugas', 4)
            ->has('bottom_petugas', 3)
            ->where('top_petugas.0.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('top_petugas.1.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('top_petugas.2.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('top_petugas.3.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('bottom_petugas.0.avg_rating', fn ($value) => (float) $value < 3.0)
            ->where('bottom_petugas.1.avg_rating', fn ($value) => (float) $value < 3.0)
            ->where('bottom_petugas.2.avg_rating', fn ($value) => (float) $value < 3.0)
        );

        $filteredResponse = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra?kegiatan_id='.$kegiatan->id);

        $filteredResponse->assertOk();
        $filteredResponse->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->has('top_petugas', 3)
            ->has('bottom_petugas', 3)
            ->where('top_petugas.0.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('top_petugas.1.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('top_petugas.2.avg_rating', fn ($value) => (float) $value >= 3.0)
            ->where('bottom_petugas.0.avg_rating', fn ($value) => (float) $value < 3.0)
            ->where('bottom_petugas.1.avg_rating', fn ($value) => (float) $value < 3.0)
            ->where('bottom_petugas.2.avg_rating', fn ($value) => (float) $value < 3.0)
        );
    }

    public function test_petugas_filter_controls_kegiatan_rank_card_and_hall_of_fame_ignores_petugas_filter(): void
    {
        $operatorRole = Role::query()->firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => 'Operator']
        );

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$operatorRole->id]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => now()->year,
            'nama_kegiatan' => 'Kegiatan Hall Of Fame',
        ]);

        $petugasFiltered = Petugas::factory()->create([
            'nama' => 'Petugas Filtered',
        ]);

        $petugasBalanced = Petugas::factory()->create([
            'nama' => 'Petugas Balanced',
        ]);

        // Petugas terfilter: hanya 1 episode dengan rating tinggi.
        $periodeFiltered = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => now()->year,
            'bulan' => '01',
            'status' => 'dikirim',
        ]);

        ReviewPetugas::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasFiltered->id,
            'periode_alokasi_id' => $periodeFiltered->id,
            'reviewer_user_id' => $user->id,
            'rating' => 5,
            'ulasan' => 'Sangat baik',
            'reviewed_at' => now(),
        ]);

        // Kandidat balanced: 6 episode rating konsisten, harus unggul di Hall of Fame.
        foreach (['02', '03', '04', '05', '06', '07'] as $bulan) {
            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'tahun' => now()->year,
                'bulan' => $bulan,
                'status' => 'dikirim',
            ]);

            ReviewPetugas::query()->create([
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $petugasBalanced->id,
                'periode_alokasi_id' => $periode->id,
                'reviewer_user_id' => $user->id,
                'rating' => 4,
                'ulasan' => 'Konsisten baik',
                'reviewed_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra?petugas_id='.$petugasFiltered->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->where('show_kegiatan_rank_for_petugas', false)
            ->where('hall_of_fame.petugas_id', $petugasBalanced->id)
            ->where('hall_of_fame.review_count', 6)
        );

        // Tambah episode untuk petugas terfilter agar > 5 episode, card kegiatan harus muncul.
        foreach (['08', '09', '10', '11', '12'] as $bulan) {
            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'tahun' => now()->year,
                'bulan' => $bulan,
                'status' => 'dikirim',
            ]);

            ReviewPetugas::query()->create([
                'kegiatan_id' => $kegiatan->id,
                'petugas_id' => $petugasFiltered->id,
                'periode_alokasi_id' => $periode->id,
                'reviewer_user_id' => $user->id,
                'rating' => 5,
                'ulasan' => 'Tambahan episode',
                'reviewed_at' => now(),
            ]);
        }

        $responseAfterMoreEpisodes = $this->actingAs($user)
            ->withSession([
                'active_role_id' => $operatorRole->id,
                'active_year' => now()->year,
            ])
            ->get('/monitoring-penilaian-mitra?petugas_id='.$petugasFiltered->id);

        $responseAfterMoreEpisodes->assertOk();
        $responseAfterMoreEpisodes->assertInertia(fn ($page) => $page
            ->component('Monitoring/PenilaianMitraStatistik')
            ->where('show_kegiatan_rank_for_petugas', true)
        );
    }
}
