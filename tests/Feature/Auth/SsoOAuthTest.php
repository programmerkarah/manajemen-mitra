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

    public function test_sso_callback_can_login_existing_local_user(): void
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'client-id-123');
        config()->set('services.sso.client_secret', 'secret-123');
        config()->set('services.sso.redirect_uri', 'http://localhost:8001/auth/sso/callback');
        config()->set('services.sso.user_endpoint', '/api/user');

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
    }
}
