<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPulsaTest extends TestCase
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

    public function test_index_is_accessible_by_admin(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_operator(): void
    {
        [$user, $role] = $this->makeUserWithRole('operator');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_ketua_tim(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_forbidden_for_unauthenticated_user(): void
    {
        $response = $this->get('/monitoring-pulsa');

        $response->assertRedirect();
    }

    public function test_index_is_forbidden_for_unauthorized_role(): void
    {
        [$user, $role] = $this->makeUserWithRole('pj');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertRedirect();
    }

    public function test_index_renders_inertia_component(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/monitoring-pulsa');

        $response->assertInertia(fn ($page) => $page->component('MonitoringPulsa/Index'));
    }

    public function test_index_accepts_post_filter_payload(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/monitoring-pulsa', ['bulan' => '06']);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('MonitoringPulsa/Index'));
    }

    public function test_index_excludes_draft_submissions(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
        $petugas = Petugas::factory()->create();
        $bulan = now()->format('m');
        $tahun = ActiveYearService::get();

        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);
        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pelatihan',
            'nominal' => 30000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get("/monitoring-pulsa?bulan={$bulan}");

        $response->assertStatus(200);
    }

    public function test_ketua_tim_sees_all_kegiatan_data(): void
    {
        [$ketuaTim, $role] = $this->makeUserWithRole('ketua_tim');
        [$otherKetuaTim] = $this->makeUserWithRole('ketua_tim');

        $ownKegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
        $otherKegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $otherKetuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugas = Petugas::factory()->create();
        $bulan = now()->format('m');
        $tahun = ActiveYearService::get();

        PengajuanPulsa::create([
            'kegiatan_id' => $ownKegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);
        PengajuanPulsa::create([
            'kegiatan_id' => $otherKegiatan->id,
            'petugas_id' => $petugas->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $otherKetuaTim->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $role->id])
            ->get("/monitoring-pulsa?bulan={$bulan}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('MonitoringPulsa/Index')
            ->where('pengajuanList.encrypted', fn ($encrypted) => count(decryptData($encrypted)) === 2)
        );
    }

    public function test_export_pdf_uses_only_approved_data_and_allocated_nominal(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugasApproved = Petugas::factory()->create([
            'nama' => 'Petugas Disetujui',
        ]);

        $petugasPending = Petugas::factory()->create([
            'nama' => 'Petugas Menunggu',
        ]);

        $bulan = now()->format('m');
        $tahun = ActiveYearService::get();

        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasApproved->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 100000,
            'nominal_disetujui' => 75000,
            'status' => 'diterima',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        PengajuanPulsa::create([
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugasPending->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'jenis_pulsa' => 'pelatihan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($petugasApproved, $tahun, $bulan) {
                $this->assertSame('monitoring-pulsa-rekap-pdf', $view);
                $this->assertSame((string) $tahun, (string) $data['tahun']);
                $this->assertSame($bulan, $data['bulan']);
                $this->assertCount(1, $data['rows']);
                $this->assertSame($petugasApproved->nama, $data['rows'][0]['nama_petugas']);
                $this->assertSame(75000.0, (float) $data['rows'][0]['jumlah_pulsa']);

                return true;
            })
            ->andReturn(
                tap(\Mockery::mock(DomPdfWrapper::class), function ($pdfMock): void {
                    $pdfMock->shouldReceive('setPaper')->once()->andReturnSelf();
                    $pdfMock->shouldReceive('download')->once()->andReturnUsing(function (string $filename) {
                        return response('mock-pdf', 200, [
                            'content-type' => 'application/pdf',
                            'content-disposition' => 'attachment; filename="'.$filename.'"',
                        ]);
                    });
                })
            );

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get("/monitoring-pulsa/export-pdf?bulan={$bulan}");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
