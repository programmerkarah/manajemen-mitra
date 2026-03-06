<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\User;
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
            ->post("/pengajuan-pulsa/{$pengajuan->hashed_id}/review", ['action' => 'diterima']);

        $responseAdmin->assertStatus(302); // redirect after review
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'diterima',
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
                'action' => 'diterima',
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pengajuan_pulsa', ['id' => $item1->id, 'status' => 'diterima']);
        $this->assertDatabaseHas('pengajuan_pulsa', ['id' => $item2->id, 'status' => 'diterima']);
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
                'action' => 'ditolak',
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '07',
                'tahun' => date('Y'),
                'catatan_penolakan' => 'Nominal tidak sesuai.',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pengajuan_pulsa', [
            'id' => $pengajuan->id,
            'status' => 'ditolak',
            'catatan_penolakan' => 'Nominal tidak sesuai.',
        ]);
    }

    public function test_review_all_requires_catatan_when_ditolak(): void
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
                'action' => 'ditolak',
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
                // no catatan_penolakan
            ]);

        $response->assertSessionHasErrors('catatan_penolakan');
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

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/pengajuan-pulsa/review-all', [
                'action' => 'diterima',
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '06',
                'tahun' => date('Y'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
