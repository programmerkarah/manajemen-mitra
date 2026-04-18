<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_settings_page_redirects_to_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertRedirect('/');
    }

    public function test_two_factor_show_route_exists(): void
    {
        $this->assertTrue(collect(app('router')->getRoutes())->contains(
            fn ($route) => $route->getName() === 'two-factor.show'
        ));
    }
}
