<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanMetodePendataanTest extends TestCase
{
    use RefreshDatabase;

    private function makeKetuaTim(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_store_kegiatan_requires_metode_pendataan_pencacahan(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Test',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                // metode_pendataan_pencacahan intentionally omitted
            ]);

        $response->assertSessionHasErrors('metode_pendataan_pencacahan');
    }

    public function test_store_kegiatan_accepts_valid_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Test',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Test',
            'metode_pendataan_pencacahan' => 'CAPI',
        ]);
    }

    public function test_store_kegiatan_accepts_papi_metode_pendataan(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei PAPI',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'luring',
                'bulan_pelatihan' => 7,
            ]);

        $response->assertSessionDoesntHaveErrors('metode_pendataan_pencacahan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei PAPI',
            'metode_pendataan_pencacahan' => 'PAPI',
        ]);
    }

    public function test_store_kegiatan_requires_metode_listing_when_has_listing_is_true(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Sensus Listing',
                'jenis_kegiatan' => 'sensus',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => true,
                // metode_pendataan_listing intentionally omitted
            ]);

        $response->assertSessionHasErrors('metode_pendataan_listing');
    }

    public function test_store_kegiatan_accepts_listing_capi(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Sensus Listing CAPI',
                'jenis_kegiatan' => 'sensus',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'PAPI',
                'has_listing_updating' => true,
                'metode_pendataan_listing' => 'CAPI',
                'pagu_listing' => 1000000,
                'metode_pelatihan' => 'hybrid',
                'bulan_pelatihan' => 8,
            ]);

        $response->assertSessionDoesntHaveErrors(['metode_pendataan_pencacahan', 'metode_pendataan_listing']);
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Sensus Listing CAPI',
            'metode_pendataan_pencacahan' => 'PAPI',
            'metode_pendataan_listing' => 'CAPI',
        ]);
    }

    public function test_metode_pendataan_rejects_invalid_value(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Test Invalid',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'FASIH', // Invalid - only PAPI/CAPI allowed
                'metode_pelatihan' => 'daring',
                'bulan_pelatihan' => 6,
            ]);

        $response->assertSessionHasErrors('metode_pendataan_pencacahan');
    }

    public function test_kegiatan_interface_includes_metode_pendataan_fields(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'metode_pendataan_pencacahan' => 'CAPI',
            'metode_pendataan_listing' => null,
        ]);

        $this->assertNotNull($kegiatan->metode_pendataan_pencacahan);
        $this->assertSame('CAPI', $kegiatan->metode_pendataan_pencacahan);
        $this->assertNull($kegiatan->metode_pendataan_listing);
    }

    public function test_store_kegiatan_requires_bulan_pelatihan_when_metode_is_not_tidak_ada(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Pelatihan',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'daring',
            ]);

        $response->assertSessionHasErrors('bulan_pelatihan');
    }

    public function test_store_kegiatan_allows_empty_bulan_pelatihan_when_metode_tidak_ada(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/store', [
                'nama_kegiatan' => 'Survei Tanpa Pelatihan',
                'jenis_kegiatan' => 'survei',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_selesai' => '2025-12-31',
                'tahun_anggaran' => 2025,
                'ketua_tim_user_id' => $user->id,
                'metode_pendataan_pencacahan' => 'CAPI',
                'has_listing_updating' => false,
                'metode_pelatihan' => 'tidak_ada_pelatihan',
            ]);

        $response->assertSessionDoesntHaveErrors('bulan_pelatihan');
        $this->assertDatabaseHas('kegiatan', [
            'nama_kegiatan' => 'Survei Tanpa Pelatihan',
            'metode_pelatihan' => 'tidak_ada_pelatihan',
            'bulan_pelatihan' => null,
        ]);
    }
}
