<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemSettingsSsoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_sso_sync_setting(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        Cache::forget('settings:sso_sync_enabled');

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->postJson('/admin/system-settings/sso-sync', [
                'enabled' => false,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'enabled' => false,
        ]);

        $this->assertFalse((bool) Cache::get('settings:sso_sync_enabled', true));
    }
}
