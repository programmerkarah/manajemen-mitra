<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_redirects_to_sso_registration_page()
    {
        config()->set('services.sso.register_url', 'http://localhost:8000/register');

        $response = $this->get(route('register'));

        $response->assertRedirect('http://localhost:8000/register');
    }

    public function test_local_registration_endpoint_is_not_available()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(405);
        $this->assertGuest();
    }
}
