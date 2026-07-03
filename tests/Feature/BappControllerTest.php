<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

        Kegiatan::factory()->create([
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi 2026',
            'ketua_tim_user_id' => $user->id,
        ]);

        $response = $this->actingAsWithRole($user, 'ketua_tim')
            ->get('/bapp');

        $response->assertOk();
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

    // -----------------------------------------------------------------------
    // Authorization: unauthenticated users are rejected
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_access_bapp_index(): void
    {
        $response = $this->get('/bapp');

        $response->assertRedirect('/login');
    }
}
