<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Password management is handled via SSO.
     * The local settings routes redirect to home.
     */
    public function test_password_settings_page_redirects_to_home(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('user-password.edit'));

        $response->assertRedirect('/');
    }

    public function test_password_update_route_does_not_exist(): void
    {
        $this->assertFalse(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getName() === 'user-password.update')
        );
    }
}
