<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanPulsaTest extends TestCase
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

    public function test_index_is_accessible_by_ketua_tim(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_operator(): void
    {
        [$user, $role] = $this->makeUserWithRole('operator');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa');

        $response->assertStatus(200);
    }

    public function test_index_is_accessible_by_admin(): void
    {
        [$user, $role] = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa');

        $response->assertStatus(200);
    }

    public function test_create_page_is_accessible_by_ketua_tim(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create');

        $response->assertStatus(200);
    }

    public function test_store_rejects_non_capi_kegiatan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $papi_kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'PAPI',
            'metode_pendataan_listing' => null,
            'has_listing_updating' => false,
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $papi_kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pendataan',
                        'nominal' => 50000,
                    ],
                ],
            ]);

        // The store should return an error because the kegiatan is PAPI, not CAPI
        $response->assertSessionHasErrors();
    }

    public function test_store_validates_nominal_max_per_item(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $capi_kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $capi_kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pendataan',
                        'nominal' => 150000, // Exceeds 100k limit per item
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.nominal');
    }

    public function test_review_requires_admin_or_operator(): void
    {
        [$ketuaTim, $ketuaTimRole] = $this->makeUserWithRole('ketua_tim');
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugas = Petugas::factory()->create();

        $pengajuan = PengajuanPulsa::create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);

        // Ketua tim cannot review — middleware redirects them to dashboard
        $responseKetuaTim = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTimRole->id])
            ->post("/pengajuan-pulsa/{$pengajuan->hashed_id}/review", ['action' => 'diterima']);

        $responseKetuaTim->assertRedirect(route('dashboard'));
        // Status must still be 'dikirim' since no review was actually applied
        $this->assertDatabaseHas('pengajuan_pulsa', ['id' => $pengajuan->id, 'status' => 'dikirim']);

        // Admin can review
        $responseAdmin = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/pengajuan-pulsa/{$pengajuan->hashed_id}/review", [
                'action' => 'diterima',
                'nominal_disetujui' => 50000,
            ]);

        $responseAdmin->assertStatus(302); // redirect after review
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'diterima',
            'nominal_disetujui' => 50000,
        ]);
    }

    public function test_detail_page_is_accessible_by_ketua_tim_for_own_kegiatan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get("/pengajuan-pulsa/detail?kegiatan_id={$kegiatan->id}&bulan=06");

        $response->assertStatus(200);
    }

    public function test_detail_page_is_accessible_by_admin(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        [$ketuaTim] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get("/pengajuan-pulsa/detail?kegiatan_id={$kegiatan->id}&bulan=06");

        $response->assertStatus(200);
    }

    public function test_detail_page_is_forbidden_for_ketua_tim_of_other_kegiatan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');
        [$otherUser] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $otherUser->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get("/pengajuan-pulsa/detail?kegiatan_id={$kegiatan->id}&bulan=06");

        $response->assertStatus(403);
    }

    public function test_store_accepts_pelatihan_pulsa_for_luring_on_bulan_pelatihan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pelatihan' => 'luring',
            'bulan_pelatihan' => 6,
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pelatihan',
                        'nominal' => 50000,
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'jenis_pulsa' => 'pelatihan',
            'nominal' => 50000,
            'bulan' => '06',
        ]);
    }

    public function test_store_rejects_pelatihan_pulsa_for_tidak_ada_pelatihan_kegiatan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pelatihan' => 'tidak_ada_pelatihan',
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pelatihan',
                        'nominal' => 50000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_store_rejects_pelatihan_pulsa_outside_bulan_pelatihan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pelatihan' => 'daring',
            'bulan_pelatihan' => 7,
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pelatihan',
                        'nominal' => 50000,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_store_accepts_pelatihan_pulsa_on_bulan_pelatihan(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pelatihan' => 'hybrid',
            'bulan_pelatihan' => 6,
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/pengajuan-pulsa', [
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'petugas_id' => $petugas->id,
                        'jenis_pulsa' => 'pelatihan',
                        'nominal' => 50000,
                    ],
                ],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'kegiatan_id' => $kegiatan->id,
            'petugas_id' => $petugas->id,
            'jenis_pulsa' => 'pelatihan',
            'nominal' => 50000,
            'bulan' => '06',
        ]);
    }

    public function test_review_all_approves_all_dikirim_items(): void
    {
        [$ketuaTim] = $this->makeUserWithRole('ketua_tim');
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugas1 = Petugas::factory()->create();
        $petugas2 = Petugas::factory()->create();

        $item1 = PengajuanPulsa::create([
            'petugas_id' => $petugas1->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);

        $item2 = PengajuanPulsa::create([
            'petugas_id' => $petugas2->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 75000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    ['id' => $item1->id, 'action' => 'diterima', 'nominal_disetujui' => 50000],
                    ['id' => $item2->id, 'action' => 'diterima', 'nominal_disetujui' => 75000],
                ],
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pengajuan_pulsa', ['id' => $item1->id, 'status' => 'diterima', 'nominal_disetujui' => 50000]);
        $this->assertDatabaseHas('pengajuan_pulsa', ['id' => $item2->id, 'status' => 'diterima', 'nominal_disetujui' => 75000]);
    }

    public function test_review_all_rejects_all_dikirim_items_with_catatan(): void
    {
        [$ketuaTim] = $this->makeUserWithRole('ketua_tim');
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $petugas = Petugas::factory()->create();

        $pengajuan = PengajuanPulsa::create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'dikirim',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '07',
                'tahun' => date('Y'),
                'catatan_penolakan' => 'Nominal tidak sesuai.',
                'items' => [
                    ['id' => $pengajuan->id, 'action' => 'ditolak'],
                ],
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'ditolak',
            'catatan_penolakan' => 'Nominal tidak sesuai.',
        ]);
    }

    public function test_review_all_requires_items_array(): void
    {
        [$ketuaTim] = $this->makeUserWithRole('ketua_tim');
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
                // no items
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_review_all_requires_admin_or_operator(): void
    {
        [$ketuaTim, $ketuaTimRole] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTimRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'action' => 'diterima',
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
            ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_review_all_returns_error_when_no_dikirim_items(): void
    {
        [$ketuaTim] = $this->makeUserWithRole('ketua_tim');
        [$admin, $adminRole] = $this->makeUserWithRole('admin');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);

        $pengajuanAlreadyApproved = PengajuanPulsa::create([
            'petugas_id' => Petugas::factory()->create()->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'diterima',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now(),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
                'items' => [
                    ['id' => $pengajuanAlreadyApproved->id, 'action' => 'diterima', 'nominal_disetujui' => 50000],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_create_uses_nominal_disetujui_for_approved_existing_nominal_display(): void
    {
        [$user, $role] = $this->makeUserWithRole('ketua_tim');
        $tahun = (string) ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => (int) $tahun,
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => $tahun,
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
        ]);

        PengajuanPulsa::create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'periode_alokasi_id' => $periode->id,
            'bulan' => '06',
            'tahun' => (int) $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'nominal_disetujui' => 30000,
            'status' => 'diterima',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->get('/pengajuan-pulsa/create?bulan=06');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PengajuanPulsa/Create')
            ->where("existingPerKegiatan.{$kegiatan->id}_{$petugas->id}_pendataan", 30000)
            ->where("existingTotals.{$petugas->id}", 30000)
        );
    }

    public function test_ketua_tim_can_resubmit_rejected_pengajuan_with_revised_nominal(): void
    {
        [$ketuaTim, $ketuaTimRole] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $pengajuan = PengajuanPulsa::create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '08',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'nominal_disetujui' => null,
            'status' => 'ditolak',
            'submitted_by' => $ketuaTim->id,
            'submitted_at' => now()->subDay(),
            'reviewed_by' => $ketuaTim->id,
            'reviewed_at' => now()->subDay(),
            'catatan_penolakan' => 'Nominal perlu diperbaiki.',
        ]);

        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTimRole->id])
            ->post("/pengajuan-pulsa/{$pengajuan->hashed_id}/resubmit", [
                'nominal' => 45000,
                'catatan' => 'Revisi nominal sesuai arahan reviewer.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'dikirim',
            'nominal' => 45000,
            'catatan_penolakan' => null,
            'nominal_disetujui' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function test_ketua_tim_cannot_resubmit_rejected_pengajuan_from_other_kegiatan(): void
    {
        [$ketuaTim, $ketuaTimRole] = $this->makeUserWithRole('ketua_tim');
        [$otherKetua] = $this->makeUserWithRole('ketua_tim');

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $otherKetua->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'tahun_anggaran' => date('Y'),
        ]);

        $petugas = Petugas::factory()->create();

        $pengajuan = PengajuanPulsa::create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '08',
            'tahun' => date('Y'),
            'jenis_pulsa' => 'pendataan',
            'nominal' => 50000,
            'status' => 'ditolak',
            'submitted_by' => $otherKetua->id,
            'submitted_at' => now()->subDay(),
            'reviewed_by' => $otherKetua->id,
            'reviewed_at' => now()->subDay(),
            'catatan_penolakan' => 'Perlu perbaikan.',
        ]);

        $response = $this->actingAs($ketuaTim)
            ->withSession(['active_role_id' => $ketuaTimRole->id])
            ->post("/pengajuan-pulsa/{$pengajuan->hashed_id}/resubmit", [
                'nominal' => 40000,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'ditolak',
            'nominal' => 50000,
        ]);
    }
}
