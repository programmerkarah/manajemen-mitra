<?php

namespace Tests\Feature\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered(): void
    {
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

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/two-factor-challenge')
            );
    }

    public function test_trusted_device_bypasses_two_factor_even_if_ip_changes(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Carbon::setTestNow('2026-04-15 10:00:00');

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        TrustedDevice::create([
            'user_id' => $user->id,
            'device_token' => 'trusted-token-123',
            'device_name' => 'Windows PC',
            'user_agent' => 'Old Browser',
            'ip_address' => '10.0.0.1',
            'last_used_at' => now()->subDays(3),
            'expires_at' => now()->addDays(2),
        ]);

        $response = $this
            ->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->withCookie('trusted_device', 'trusted-token-123')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.55', 'HTTP_USER_AGENT' => 'New Browser'])
            ->get(route('two-factor.login'));

        $response->assertRedirect(config('fortify.home'));

        $trustedDevice = TrustedDevice::where('device_token', 'trusted-token-123')->firstOrFail();
        $this->assertSame('203.0.113.55', $trustedDevice->ip_address);
        $this->assertSame('New Browser', $trustedDevice->user_agent);
        $this->assertTrue($trustedDevice->expires_at->greaterThan(now()->addDays(13)));
    }

    public function test_trusted_device_expires_after_fourteen_days_of_inactivity(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Carbon::setTestNow('2026-04-15 10:00:00');

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        TrustedDevice::create([
            'user_id' => $user->id,
            'device_token' => 'expired-token-123',
            'device_name' => 'Windows PC',
            'user_agent' => 'Browser',
            'ip_address' => '10.0.0.1',
            'last_used_at' => now()->subDays(15),
            'expires_at' => now()->subDay(),
        ]);

        $this
            ->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->withCookie('trusted_device', 'expired-token-123')
            ->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/two-factor-challenge')
                ->where('isTrustedDevice', false)
            );
    }
}
