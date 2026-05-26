<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_touch_session_via_heartbeat(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post(route('session.heartbeat'));

        $response->assertNoContent();
        $response->assertSessionHas('last_heartbeat_at');
        $response->assertSessionHas('last_user_activity_at');
    }
}
