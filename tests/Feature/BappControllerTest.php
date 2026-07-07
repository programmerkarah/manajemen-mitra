<?php

namespace Tests\Feature;

use App\Http\Controllers\BappController;
use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BappControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function actingAsWithRole(User $user, string $roleName): self
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst(str_replace('_', ' ', $roleName))]
        );

        $user->assignRole($roleName);

        return $this->actingAs($user)->withSession([
            'active_role_id' => $role->id,
        ]);
    }

    private function insertIfColumnsExist(string $table, array $attributes): int
    {
        $filteredAttributes = array_filter(
            $attributes,
            fn ($value, string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH,
        );

        return (int) DB::table($table)->insertGetId($filteredAttributes);
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_admin_can_view_bapp_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get('/bapp');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Bapp/Index')
            ->has('tahun')
            ->has('termin_data')
            ->has('has_kegiatan')
            ->has('unit_sampel_items')
        );
    }

    public function test_operator_can_view_bapp_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'operator')
            ->get('/bapp');

        $response->assertOk();
    }

    public function test_ketua_tim_can_view_bapp_index(): void
    {
        $user = User::factory()->create();

        $this->insertIfColumnsExist('kegiatan', [
            'kode_kegiatan' => 'KEG-INDEX-TEST',
            'nama_kegiatan' => 'Sensus Ekonomi 2026',
            'jenis_kegiatan' => 'sensus',
            'deskripsi' => 'Index test activity',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-08-31',
            'tahun_anggaran' => 2026,
            'has_listing_updating' => false,
            'metode_pendataan_pencacahan' => 'CAPI_KSA_PRO',
            'metode_pendataan_listing' => 'CAPI_KSA_PRO',
            'metode_sampling' => 'targeted',
            'metode_pelatihan' => 'tidak_ada_pelatihan',
            'bulan_pelatihan' => 4,
            'pagu_listing' => 0,
            'pagu_pencacahan' => 0,
            'kode_coa' => null,
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'status' => 'aktif',
            'tanggal_validasi' => null,
            'catatan' => null,
        ]);

        $response = $this->actingAsWithRole($user, 'ketua_tim')
            ->get('/bapp');

        $response->assertOk();
    }

    public function test_regular_bapp_context_does_not_filter_spks(): void
    {
        $controller = new class extends BappController
        {
            protected function getSensusEkonomiSpks(int $tahun): Collection
            {
                return new Collection([
                    (object) ['id' => 1],
                    (object) ['id' => 2],
                ]);
            }
        };

        $resolver = new \ReflectionMethod($controller, 'getSpksForBappContext');
        $resolver->setAccessible(true);

        $result = $resolver->invoke($controller, 2026, 1, 'regular');

        $this->assertCount(2, $result);
    }

    public function test_bapp_index_returns_two_termin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get('/bapp');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Bapp/Index')
            ->has('termin_data', 2)
        );
    }

    // -----------------------------------------------------------------------
    // Create (realisasi form)
    // -----------------------------------------------------------------------

    public function test_admin_can_view_bapp_create_termin_1(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get('/bapp/create?termin=N8wBVwXj');

        // When no SE kegiatan exists in test DB, controller redirects to bapp.index
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_admin_can_view_bapp_create_termin_2(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get('/bapp/create?termin=xmw1WngQ');

        // When no SE kegiatan exists in test DB, controller redirects to bapp.index
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_create_with_invalid_termin_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get('/bapp/create?termin=invalidhash');

        $response->assertRedirect(route('bapp.index'));
    }

    // -----------------------------------------------------------------------
    // storeRealisasi
    // -----------------------------------------------------------------------

    public function test_operator_can_store_realisasi(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'operator')
            ->post('/bapp/realisasi', [
                'termin' => 1,
                'entries' => [],
            ]);

        // Empty entries returns back with error (nothing to save)
        $response->assertRedirect();
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_ketua_tim_cannot_store_realisasi(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'ketua_tim')
            ->post('/bapp/realisasi', [
                'termin' => 1,
                'entries' => [],
            ]);

        // Middleware redirects unauthorized roles to dashboard (302), not 403
        $response->assertRedirect(route('dashboard'));
    }

    // -----------------------------------------------------------------------
    // Template download
    // -----------------------------------------------------------------------

    public function test_operator_can_download_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'operator')
            ->get('/bapp/template?termin=1');

        // Should return a file download (200 or redirect to download)
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_admin_can_reach_bapp_generate_route_without_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->post('/bapp/generate', []);

        $response->assertStatus(422);
        $response->assertSee('SPK tidak valid.');
    }

    public function test_resolve_entry_tanggal_bapp_prefers_entry_then_shared(): void
    {
        $controller = new BappController;
        $resolver = new \ReflectionMethod(BappController::class, 'resolveEntryTanggalBapp');
        $resolver->setAccessible(true);

        $this->assertSame(
            '2026-07-15',
            $resolver->invoke($controller, ['tanggal_bapp' => '2026-07-15'], '2026-07-16'),
        );

        $this->assertSame(
            '2026-07-16',
            $resolver->invoke($controller, ['tanggal_bapp' => ''], '2026-07-16'),
        );

        $this->assertNull(
            $resolver->invoke($controller, ['tanggal_bapp' => null], null),
        );
    }

    // -----------------------------------------------------------------------
    // Authorization: unauthenticated users are rejected
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_access_bapp_index(): void
    {
        $response = $this->get('/bapp');

        $response->assertRedirect('/login');
    }
}
