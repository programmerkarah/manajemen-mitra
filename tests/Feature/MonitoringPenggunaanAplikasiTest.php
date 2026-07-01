<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonitoringPenggunaanAplikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_monthly_usage_report_for_the_active_year(): void
    {
        $this->withoutMiddleware();

        ActiveYearService::set(2026);
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 10, 0, 0));

        $userOne = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Satu']));
        $userTwo = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Dua']));
        $userThree = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Tiga']));
        $userFour = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Empat']));
        $userFive = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Lima']));

        ActivityLog::query()->insert([
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['browser' => 'Chrome'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
            ],
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Ubah Kegiatan',
                'type' => 'kegiatan',
                'description' => 'Memperbarui kegiatan',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 9, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 9, 0, 0),
            ],
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Ubah Kegiatan',
                'type' => 'kegiatan',
                'description' => 'Memperbarui kegiatan',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 2, 10, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 2, 10, 0, 0),
            ],
            [
                'user_id' => $userTwo->id,
                'user_name' => $userTwo->name,
                'action' => 'Kirim Alokasi',
                'type' => 'alokasi',
                'description' => 'Mengirim alokasi periode',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 10, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 10, 0, 0),
            ],
            [
                'user_id' => $userTwo->id,
                'user_name' => $userTwo->name,
                'action' => 'Lihat Dashboard',
                'type' => 'system',
                'description' => 'Melihat dashboard',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['section' => 'dashboard'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 3, 11, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 3, 11, 0, 0),
            ],
            [
                'user_id' => $userThree->id,
                'user_name' => $userThree->name,
                'action' => 'Export PDF',
                'type' => 'export',
                'description' => 'Ekspor laporan',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['format' => 'pdf'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 4, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 4, 8, 0, 0),
            ],
            [
                'user_id' => $userThree->id,
                'user_name' => $userThree->name,
                'action' => 'View As User',
                'type' => 'user',
                'description' => 'Viewing sebagai user lain',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['target_user_id' => $userFour->id], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 4, 9, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 4, 9, 0, 0),
            ],
            [
                'user_id' => $userThree->id,
                'user_name' => $userThree->name,
                'action' => 'Clear View As User',
                'type' => 'user',
                'description' => 'Kembali ke user asli',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 4, 9, 5, 0),
                'updated_at' => Carbon::create(2026, 6, 4, 9, 5, 0),
            ],
            [
                'user_id' => $userFour->id,
                'user_name' => $userFour->name,
                'action' => 'Ubah Mitra',
                'type' => 'mitra',
                'description' => 'Perubahan data mitra',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 5, 9, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 5, 9, 0, 0),
            ],
            [
                'user_id' => $userFour->id,
                'user_name' => $userFour->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['browser' => 'Firefox'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 5, 10, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 5, 10, 0, 0),
            ],
            [
                'user_id' => $userFive->id,
                'user_name' => $userFive->name,
                'action' => 'Lihat Data',
                'type' => 'user',
                'description' => 'Lihat data pengguna',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['section' => 'user'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 6, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 6, 8, 0, 0),
            ],
            [
                'user_id' => $userFive->id,
                'user_name' => $userFive->name,
                'action' => 'Kelola Akun',
                'type' => 'user',
                'description' => 'Kelola akun pengguna',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['section' => 'user'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 6, 9, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 6, 9, 0, 0),
            ],
        ]);

        $response = $this->get(route('monitoring.penggunaan-aplikasi', ['bulan' => '06']));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Monitoring/PenggunaanAplikasi')
                ->where('active_year', 2026)
                ->where('month_label', 'Juni')
                ->where('filters.bulan', '06')
                ->where('summary.active_users', 5)
                ->where('summary.total_logs', 10)
                ->where('summary.active_days', 6)
                ->where('summary.administrative_actions', 4)
                ->where('summary.system_actions', 5)
                ->has('user_name_options', 5)
                ->where('selected_user_name', 'Petugas Satu')
                ->where('selected_user_summary.user_name', 'Petugas Satu')
                ->where('selected_user_summary.total_logs', 3)
                ->where('selected_user_summary.active_days', 2)
                ->has('selected_user_daily_access', 30)
                ->where('selected_user_daily_access.0.total_logs', 2)
                ->where('selected_user_daily_access.1.total_logs', 1)
                ->has('selected_user_daily_access.0.activity_breakdown', 2)
                ->where('selected_user_daily_access.0.activity_breakdown.0.label', 'Autentikasi')
                ->where('selected_user_daily_access.0.activity_breakdown.1.label', 'Kelola Kegiatan')
                ->where('selected_user_daily_access.1.activity_breakdown.0.label', 'Kelola Kegiatan')
                ->has('top_users', 4)
                ->where('top_users.0.user_name', 'Petugas Satu')
                ->has('type_summary', 7)
                ->has('top_actions', 7)
            );
    }

    public function test_it_does_not_show_logs_from_other_years(): void
    {
        $this->withoutMiddleware();

        ActiveYearService::set(2026);
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 10, 0, 0));

        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas']));

        ActivityLog::query()->insert([
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Data tahun aktif',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
            ],
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Data tahun lain',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2025, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2025, 6, 1, 8, 0, 0),
            ],
        ]);

        $response = $this->get(route('monitoring.penggunaan-aplikasi', ['bulan' => '6']));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.total_logs', 1)
                ->where('summary.active_users', 1)
                ->where('daily_access.0.total_logs', 1)
                ->where('daily_access.0.unique_users', 1)
            );
    }

    public function test_it_exports_the_current_month_report_to_pdf(): void
    {
        $this->withoutMiddleware();

        ActiveYearService::set(2026);
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 10, 0, 0));

        $userOne = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Satu']));
        $userTwo = User::withoutEvents(fn () => User::factory()->create(['name' => 'Petugas Dua']));

        ActivityLog::query()->insert([
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['browser' => 'Chrome'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
            ],
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Ubah Kegiatan',
                'type' => 'kegiatan',
                'description' => 'Memperbarui kegiatan',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 9, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 9, 0, 0),
            ],
            [
                'user_id' => $userOne->id,
                'user_name' => $userOne->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ulang ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['browser' => 'Chrome'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 10, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 10, 0, 0),
            ],
            [
                'user_id' => $userTwo->id,
                'user_name' => $userTwo->name,
                'action' => 'Kirim Alokasi',
                'type' => 'alokasi',
                'description' => 'Mengirim alokasi periode',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['source' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 2, 10, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 2, 10, 0, 0),
            ],
            [
                'user_id' => $userTwo->id,
                'user_name' => $userTwo->name,
                'action' => 'Switch Role',
                'type' => 'system',
                'description' => 'Beralih peran aktif',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode(['role' => 'operator'], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 2, 11, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 2, 11, 0, 0),
            ],
        ]);

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                $this->assertSame('monitoring-penggunaan-aplikasi-pdf', $view);
                $this->assertSame(2026, $data['active_year']);
                $this->assertSame('06', $data['filters']['bulan']);
                $this->assertSame('Petugas Satu', $data['filters']['user_name']);
                $this->assertSame('Juni 2026', $data['report_period']);
                $this->assertSame(4, $data['summary']['total_logs']);
                $this->assertSame(2, $data['summary']['active_users']);
                $this->assertSame(3, $data['daily_access'][0]['total_logs']);
                $this->assertSame(1, $data['top_users'][0]['active_days']);
                $this->assertSame('Petugas Satu', $data['selected_user_summary']['user_name']);

                return true;
            })
            ->andReturn(
                tap(
                    \Mockery::mock(DomPdfWrapper::class),
                    function ($pdfMock): void {
                        $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();
                        $pdfMock->shouldReceive('download')->once()->andReturnUsing(function (string $filename) {
                            return response('mock-pdf', 200, [
                                'content-type' => 'application/pdf',
                                'content-disposition' => 'attachment; filename="'.$filename.'"',
                            ]);
                        });
                    },
                )
            );

        $response = $this->get(route('monitoring.penggunaan-aplikasi.export-pdf', ['bulan' => '06', 'user_name' => 'Petugas Satu']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'laporan_penggunaan_aplikasi_2026_06_',
            $response->headers->get('content-disposition') ?? '',
        );
    }
}
