<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSingleActiveSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionSsoSyncActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_sync_request_does_not_keep_an_expired_session_alive(): void
    {
        $this->withMiddleware(EnsureSingleActiveSession::class);

        config()->set('services.sso.base_url', 'https://sso.example.test');
        config()->set('services.sso.client_id', 'client-id');

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $expiredActivityAt = now()->subMinutes((int) config('session.lifetime', 120) + 1)->timestamp;

        $response = $this->actingAs($admin)
            ->withSession([
                'active_role_id' => $adminRole->id,
                'last_user_activity_at' => $expiredActivityAt,
            ])
            ->get(route('sso.redirect', ['sync' => 1, 'return_to' => '/dashboard']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest('web');
    }

    public function test_sso_sync_request_does_not_update_session_last_activity_column(): void
    {
        config()->set('session.driver', 'database');
        config()->set('services.sso.active', false);

        $initialResponse = $this->withSession([
            'seed' => true,
        ])->get(route('sso.redirect', ['sync' => 1, 'return_to' => '/dashboard']));

        $initialResponse->assertRedirect('/dashboard');

        $sessionId = (string) app('session')->getId();
        $this->assertNotSame('', $sessionId);

        $forcedLastActivity = now()->subHours(8)->timestamp;

        DB::table(config('session.table', 'sessions'))->updateOrInsert(
            ['id' => $sessionId],
            [
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Laravel Test',
                'payload' => base64_encode(serialize([])),
                'last_activity' => $forcedLastActivity,
            ]
        );

        DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->update(['last_activity' => $forcedLastActivity]);

        $syncResponse = $this->get(route('sso.redirect', ['sync' => 1, 'return_to' => '/dashboard']));

        $syncResponse->assertRedirect('/dashboard');

        $actualLastActivity = DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->value('last_activity');

        $this->assertSame($forcedLastActivity, (int) $actualLastActivity);
    }
}
