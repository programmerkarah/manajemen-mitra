<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_two_factor_is_redirected_to_prompt(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('two-factor.prompt'));
    }

    public function test_user_with_two_factor_can_access_dashboard(): void
    {
        $user = User::factory()->create(); // Has 2FA by default

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_two_factor_prompt_page_can_be_rendered(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('two-factor.prompt'));

        $response->assertOk();
    }

    public function test_unverified_user_cannot_access_two_factor_prompt(): void
    {
        $user = User::factory()->unverified()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('two-factor.prompt'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_user_without_two_factor_can_access_settings(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        // Settings pages redirect to home since they are managed via SSO
        $response->assertRedirect('/');
    }

    public function test_user_without_two_factor_can_access_two_factor_settings(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        // Password confirmation is required for 2FA settings
        $this->session(['auth.password_confirmed_at' => time()]);

        $response = $this->actingAs($user)->get(route('two-factor.show'));

        // two-factor.show redirects to home since it is managed via SSO
        $response->assertRedirect('/');
    }
}
