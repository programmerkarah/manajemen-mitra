<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSingleActiveSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionAbsoluteLifetimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_not_logged_out_by_absolute_login_age(): void
    {
        $this->withMiddleware(EnsureSingleActiveSession::class);

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $expiredAuthStartedAt = now()->subMinutes((int) config('session.lifetime', 120) + 1)->timestamp;

        $response = $this->actingAs($admin)
            ->withSession([
                'active_role_id' => $adminRole->id,
                'auth_started_at' => $expiredAuthStartedAt,
            ])
            ->get('/dashboard');

        $response->assertOk();
        $this->assertAuthenticatedAs($admin, 'web');
    }
}
