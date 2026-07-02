<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_redirect_route_generates_oauth_authorize_url_without_forcing_relogin(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.prompt', '');

        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location', '');

        $this->assertStringStartsWith('http://localhost:8000/oauth/authorize?', $location);
        $this->assertTrue(session()->has('sso_oauth_state'));
        $this->assertStringNotContainsString('prompt=', $location);
    }

    public function test_sso_redirect_route_can_include_prompt_when_explicitly_configured(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.prompt', 'consent');

        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location', '');

        $this->assertStringContainsString('prompt=consent', $location);
    }

    public function test_sso_redirect_route_still_works_when_user_is_authenticated(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.prompt', '');

        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('sso.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location', '');

        $this->assertStringStartsWith('http://localhost:8000/oauth/authorize?', $location);
        $this->assertTrue(session()->has('sso_oauth_state'));
    }

    public function test_sso_redirect_sync_request_forces_prompt_none(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.active', true);
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.prompt', 'consent');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::response([
                'is_active' => true,
            ], 200),
        ]);

        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('sso.redirect', [
            'sync' => 1,
            'return_to' => '/dashboard?from=test',
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location', '');

        $this->assertStringContainsString('prompt=none', $location);
        $this->assertSame(true, session('sso_oauth_context.sync'));
        $this->assertSame('/dashboard?from=test', session('sso_oauth_context.return_to'));
    }

    public function test_sso_redirect_sync_request_returns_back_without_hitting_oauth_when_sso_inactive(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.active', true);
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::response([
                'is_active' => false,
            ], 200),
        ]);

        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('sso.redirect', [
            'sync' => 1,
            'return_to' => '/dashboard?from=sync',
        ]));

        $response->assertRedirect('/dashboard?from=sync');
        $this->assertFalse(session()->has('sso_oauth_state'));
        $this->assertFalse(session()->has('sso_oauth_context'));
    }

    public function test_sso_callback_can_login_existing_local_user(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'User Lokal',
            'username' => 'userlokal',
            'email' => 'userlokal@example.com',
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'access-token-abc',
            ], 200),
            'http://localhost:8000/api/user' => Http::response([
                'name' => 'User Dari SSO',
                'username' => 'userlokal',
                'email' => 'userlokal@example.com',
                'organization_type' => 'internal',
                'email_verified_at' => now()->toDateTimeString(),
            ], 200),
        ]);

        $response = $this
            ->withSession(['sso_oauth_state' => 'state-123'])
            ->get(route('sso.callback', [
                'code' => 'code-abc',
                'state' => 'state-123',
            ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('User Dari SSO', $user->fresh()->name);
    }

    public function test_sso_callback_keeps_email_unverified_when_sso_profile_email_verified_at_is_null(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'User Lokal',
            'username' => 'userlokal',
            'email' => 'userlokal@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'access-token-abc',
            ], 200),
            'http://localhost:8000/api/user' => Http::response([
                'name' => 'User Dari SSO',
                'username' => 'userlokal',
                'email' => 'userlokal@example.com',
                'organization_type' => 'internal',
                'email_verified_at' => null,
            ], 200),
        ]);

        $response = $this
            ->withSession(['sso_oauth_state' => 'state-123'])
            ->get(route('sso.callback', [
                'code' => 'code-abc',
                'state' => 'state-123',
            ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_sso_callback_rejects_invalid_state(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');

        $response = $this
            ->withSession(['sso_oauth_state' => 'state-expected'])
            ->get(route('sso.callback', [
                'code' => 'code-abc',
                'state' => 'state-other',
            ]));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Login SSO Gagal',
            'type' => 'auth',
            'status' => 'warning',
        ]);
    }

    public function test_sso_callback_replaces_authenticated_session_when_sso_user_differs(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        $authenticatedUser = User::factory()->withoutTwoFactor()->create([
            'name' => 'User Lama',
            'username' => 'user-lama',
            'email' => 'lama@example.com',
            'is_active' => true,
        ]);

        $ssoResolvedUser = User::factory()->withoutTwoFactor()->create([
            'name' => 'User Baru',
            'username' => 'user-baru',
            'email' => 'baru@example.com',
            'sso_user_id' => 2002,
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'access-token-xyz',
            ], 200),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 2002,
                'name' => 'User Baru Dari SSO',
                'username' => 'user-baru',
                'email' => 'baru@example.com',
                'organization_type' => 'internal',
                'email_verified_at' => now()->toDateTimeString(),
            ], 200),
        ]);

        $response = $this
            ->actingAs($authenticatedUser)
            ->withSession(['sso_oauth_state' => 'state-abc'])
            ->get(route('sso.callback', [
                'code' => 'code-xyz',
                'state' => 'state-abc',
            ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($ssoResolvedUser->fresh());
    }

    public function test_sso_callback_sync_request_does_not_log_out_local_session_on_login_required_error(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');

        $user = User::factory()->withoutTwoFactor()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'sso_oauth_state' => 'sync-state',
                'sso_oauth_context' => [
                    'sync' => true,
                    'return_to' => '/dashboard',
                ],
            ])
            ->get(route('sso.callback', [
                'state' => 'sync-state',
                'error' => 'login_required',
            ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('warning', 'Sinkronisasi sesi SSO belum berhasil.');
        $this->assertAuthenticatedAs($user);
    }

    public function test_sso_callback_sync_request_logs_out_local_session_when_sso_session_expired(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');

        $user = User::factory()->withoutTwoFactor()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'sso_oauth_state' => 'sync-state',
                'sso_oauth_context' => [
                    'sync' => true,
                    'return_to' => '/dashboard',
                ],
            ])
            ->get(route('sso.callback', [
                'state' => 'sync-state',
                'error' => 'session_expired',
            ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Sesi SSO Anda sudah berakhir. Silakan login ulang.');
        $this->assertGuest();
    }

    public function test_sso_callback_sync_request_redirects_back_to_requested_path_on_success(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'User Sync',
            'username' => 'user-sync',
            'email' => 'user-sync@example.com',
            'sso_user_id' => 555,
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'sync-token-123',
            ], 200),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 555,
                'name' => 'User Sync SSO',
                'username' => 'user-sync',
                'email' => 'user-sync@example.com',
                'organization_type' => 'internal',
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'sso_oauth_state' => 'sync-state',
                'sso_oauth_context' => [
                    'sync' => true,
                    'return_to' => '/kegiatan?sync=1',
                ],
            ])
            ->get(route('sso.callback', [
                'code' => 'sync-code',
                'state' => 'sync-state',
            ]));

        $response->assertRedirect('/kegiatan?sync=1');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_sso_callback_sync_request_preserves_session_id_and_does_not_broadcast_invalidation(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'Rahmat Zikri',
            'username' => 'rhmtzikri',
            'email' => 'rhmtzikri@example.com',
            'sso_user_id' => 999,
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'sync-token-999',
            ], 200),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 999,
                'name' => 'Rahmat Zikri',
                'username' => 'rhmtzikri',
                'email' => 'rhmtzikri@example.com',
                'organization_type' => 'internal',
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'sso_oauth_state' => 'preserved-state',
                'sso_oauth_context' => [
                    'sync' => true,
                    'return_to' => '/dashboard',
                ],
            ])
            ->get(route('sso.callback', [
                'code' => 'preserved-code',
                'state' => 'preserved-state',
            ]));

        // User must still be authenticated as the same user — session was not replaced.
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user->fresh());

        // Profile data was updated from SSO without invalidating the session.
        $this->assertSame('Rahmat Zikri', $user->fresh()->name);
    }
}
