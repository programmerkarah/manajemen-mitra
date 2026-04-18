<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
