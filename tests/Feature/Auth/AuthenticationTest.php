<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_shows_sso_mode_when_application_is_active()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.register_url', 'http://localhost:8000/register');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::response(['is_active' => true]),
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/login')
            ->where('ssoEnabled', true)
            ->where('ssoActive', true)
            ->where('ssoLoginUrl', route('sso.redirect'))
            ->where('ssoRegisterUrl', 'http://localhost:8000/register')
            ->where('canResetPassword', false)
            ->where('canRegister', false)
        );
    }

    public function test_login_screen_can_be_rendered_when_sso_is_not_configured()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/login')
            ->where('ssoEnabled', false)
            ->where('ssoActive', false)
            ->where('ssoLoginUrl', null)
            ->where('canResetPassword', true)
            ->where('canRegister', true)
        );
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);

        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'Login',
            'type' => 'auth',
            'status' => 'success',
        ]);
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);

        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);

        $user = User::factory()->create();

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Login Gagal',
            'type' => 'auth',
            'status' => 'warning',
        ]);
    }

    public function test_login_post_redirects_to_sso_when_native_login_is_disabled()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::response(['is_active' => true]),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'local-user',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('sso.redirect'));
    }

    public function test_login_screen_switches_status_immediately_without_cache()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::sequence()
                ->push(['is_active' => false])
                ->push(['is_active' => true]),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/login')
                ->where('ssoActive', false)
            );

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/login')
                ->where('ssoActive', true)
            );
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        $throttleKey = Str::transliterate(
            Str::lower($user->username).'|127.0.0.1'
        );
        RateLimiter::increment($throttleKey, amount: 5);

        $response = $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('username');
    }

    public function test_sso_callback_creates_local_user_on_first_login()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.client_secret', 'test-client-secret');

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
            ]),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 777,
                'name' => 'SSO First Login',
                'username' => 'sso-first-login',
                'email' => 'sso-first@example.com',
                'organization_type' => 'internal',
            ]),
        ]);

        $response = $this->withSession(['sso_oauth_state' => 'valid-state'])
            ->get(route('sso.callback', [
                'code' => 'valid-code',
                'state' => 'valid-state',
            ]));

        $response->assertRedirect(route('dashboard'));

        $localUser = User::query()->where('sso_user_id', 777)->first();

        $this->assertNotNull($localUser);
        $this->assertSame('SSO First Login', $localUser->name);
        $this->assertSame('sso-first-login', $localUser->username);
        $this->assertSame('sso-first@example.com', $localUser->email);
        $this->assertTrue($localUser->is_active);
        $this->assertNotNull($localUser->email_verified_at);
        $this->assertTrue($localUser->hasRole('guest'));
        $this->assertAuthenticatedAs($localUser);
    }

    public function test_sso_callback_matches_existing_local_user_by_email_and_syncs_sso_user_id()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.client_secret', 'test-client-secret');

        $existingUser = User::factory()->withoutTwoFactor()->create([
            'username' => 'local-user',
            'email' => 'existing@example.com',
            'sso_user_id' => null,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
            ]),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 991,
                'name' => 'Existing From SSO',
                'username' => 'sso-existing-user',
                'email' => 'existing@example.com',
                'email_verified_at' => now()->toISOString(),
                'organization_type' => 'internal',
            ]),
        ]);

        $response = $this->withSession(['sso_oauth_state' => 'valid-state'])
            ->get(route('sso.callback', [
                'code' => 'valid-code',
                'state' => 'valid-state',
            ]));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);

        $existingUser->refresh();

        $this->assertSame(991, $existingUser->sso_user_id);
        $this->assertSame('Existing From SSO', $existingUser->name);
        $this->assertSame('sso-existing-user', $existingUser->username);
        $this->assertAuthenticatedAs($existingUser);
    }

    public function test_sso_callback_rejects_user_with_non_allowed_organization_type()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.client_secret', 'test-client-secret');
        config()->set('services.sso.allowed_organization_types', ['internal']);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
            ]),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 1234,
                'name' => 'External User',
                'username' => 'external-user',
                'email' => 'external@example.com',
                'organization_type' => 'vendor',
            ]),
        ]);

        $response = $this->withSession(['sso_oauth_state' => 'valid-state'])
            ->get(route('sso.callback', [
                'code' => 'valid-code',
                'state' => 'valid-state',
            ]));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'sso_user_id' => 1234,
        ]);
    }

    public function test_sso_callback_syncs_two_factor_data_from_sso_profile()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.client_secret', 'test-client-secret');

        $confirmedAt = Carbon::parse('2026-04-19 08:30:00');
        $passwordHash = Hash::make('SsoPassword#2026');
        $existingUser = User::factory()->withoutTwoFactor()->create([
            'username' => 'local-user',
            'email' => 'existing@example.com',
            'sso_user_id' => 991,
        ]);

        Http::fake([
            'http://localhost:8000/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
            ]),
            'http://localhost:8000/api/user' => Http::response([
                'id' => 991,
                'name' => 'Existing From SSO',
                'username' => 'sso-existing-user',
                'email' => 'existing@example.com',
                'email_verified_at' => now()->toISOString(),
                'organization_type' => 'internal',
                'password_hash' => $passwordHash,
                'two_factor' => [
                    'secret' => 'shared-secret',
                    'recovery_codes' => ['code-1', 'code-2'],
                    'confirmed_at' => $confirmedAt->toISOString(),
                ],
            ]),
        ]);

        $response = $this->withSession(['sso_oauth_state' => 'valid-state'])
            ->get(route('sso.callback', [
                'code' => 'valid-code',
                'state' => 'valid-state',
            ]));

        $response->assertRedirect(route('dashboard'));

        $existingUser->refresh();

        $this->assertNotNull($existingUser->two_factor_secret);
        $this->assertNotNull($existingUser->two_factor_recovery_codes);
        $this->assertNotNull($existingUser->two_factor_confirmed_at);
        $this->assertSame('shared-secret', decrypt($existingUser->two_factor_secret));
        $this->assertSame(['code-1', 'code-2'], json_decode(decrypt($existingUser->two_factor_recovery_codes), true));
        $this->assertTrue(Hash::check('SsoPassword#2026', $existingUser->password));
        $this->assertSame(
            $confirmedAt->clone()->utc()->toDateTimeString(),
            $existingUser->two_factor_confirmed_at->toDateTimeString(),
        );
    }
}
