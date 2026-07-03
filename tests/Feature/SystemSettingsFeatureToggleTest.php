<?php

namespace Tests\Feature;

use App\Models\FeatureToggle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsFeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_feature_toggle_setting(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        FeatureToggle::updateState('kegiatan', true);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->postJson('/admin/system-settings/feature-toggle', [
                'key' => 'kegiatan',
                'enabled' => false,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('feature_toggle.key', 'kegiatan');
        $response->assertJsonPath('feature_toggle.enabled', false);

        $this->assertFalse(FeatureToggle::isEnabled('kegiatan'));
    }

    public function test_feature_toggle_state_persists_after_refreshing_settings_page(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        FeatureToggle::updateState('kegiatan', false);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/admin/system-settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SystemSettings')
            ->where('feature_toggles', function (array $featureToggles): bool {
                foreach ($featureToggles as $featureToggle) {
                    if (($featureToggle['key'] ?? null) === 'kegiatan') {
                        return ($featureToggle['enabled'] ?? true) === false;
                    }
                }

                return false;
            })
        );

        $this->assertFalse(FeatureToggle::isEnabled('kegiatan'));
    }

    public function test_disabled_feature_route_is_blocked_for_users(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator']
        );

        $operator = User::factory()->create();
        $operator->roles()->attach($operatorRole->id);

        FeatureToggle::updateState('kegiatan', false);

        $response = $this->actingAs($operator)
            ->withSession(['active_role_id' => $operatorRole->id])
            ->get('/kegiatan/create');

        $response->assertStatus(403);
        $response->assertSee('Fitur kegiatan sedang dinonaktifkan oleh administrator.');
    }

    public function test_admin_can_access_disabled_feature_route(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        FeatureToggle::updateState('kegiatan', false);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get('/kegiatan/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Kegiatan/Create'));
    }
}
