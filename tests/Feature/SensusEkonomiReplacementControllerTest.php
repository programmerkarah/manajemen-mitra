<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class SensusEkonomiReplacementControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

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

    public function test_admin_gets_validation_errors_when_storing_empty_replacement_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->post(route('se-replacements.store'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'petugas_berhenti_id',
            'spk_lama_id',
            'detail_rows',
            'tanggal_berhenti',
        ]);
    }

    public function test_ketua_tim_cannot_access_replacement_store_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'ketua_tim')
            ->post(route('se-replacements.store'), []);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_pk_petugas_pengganti_hub(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsWithRole($user, 'admin')
            ->get(route('se-replacements.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Spk/PetugasPengganti/Index')
        );
    }
}
