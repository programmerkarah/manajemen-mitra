<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Profile management is handled via SSO.
     * The local settings routes redirect to home.
     */
    public function test_profile_page_redirects_to_home(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertRedirect('/');
    }

    public function test_profile_update_route_does_not_exist(): void
    {
        $this->assertFalse(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getName() === 'profile.update')
        );
    }

    public function test_profile_destroy_route_does_not_exist(): void
    {
        $this->assertFalse(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getName() === 'profile.destroy')
        );
    }
}
