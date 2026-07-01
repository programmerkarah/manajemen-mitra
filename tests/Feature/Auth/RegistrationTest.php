<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_shows_sso_mode_when_application_is_active()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.register_url', 'http://localhost:8000/register');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::response(['is_active' => true]),
        ]);

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->where('ssoActive', true)
            ->where('ssoRegisterUrl', 'http://localhost:8000/register')
        );
    }

    public function test_registration_screen_can_be_rendered_when_sso_is_not_configured()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);
        config()->set('services.sso.register_url', '');

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->where('ssoActive', false)
            ->where('ssoRegisterUrl', null)
        );
    }

    public function test_registration_screen_switches_status_immediately_without_cache()
    {
        config()->set('services.sso.base_url', 'http://localhost:8000');
        config()->set('services.sso.client_id', 'test-client-id');
        config()->set('services.sso.register_url', 'http://localhost:8000/register');

        Http::fake([
            'http://localhost:8000/api/application/status*' => Http::sequence()
                ->push(['is_active' => false])
                ->push(['is_active' => true]),
        ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/register')
                ->where('ssoActive', false)
            );

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/register')
                ->where('ssoActive', true)
            );
    }

    public function test_new_users_can_register_when_sso_is_not_configured()
    {
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);
        config()->set('services.sso.register_url', '');

        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Registrasi',
            'type' => 'auth',
            'status' => 'success',
        ]);
        $response->assertRedirect(route('dashboard'));
    }
}
