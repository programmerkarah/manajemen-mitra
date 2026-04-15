<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandleTrustedDevice
{
    private const TRUSTED_DEVICE_DAYS = 14;

    private const TRUSTED_DEVICE_MINUTES = 60 * 24 * self::TRUSTED_DEVICE_DAYS;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process after successful 2FA login
        if ($request->user() &&
            $request->session()->has('auth.password_confirmed_at') &&
            ($request->has('code') || $request->has('recovery_code'))) {

            $rememberDevice = $request->boolean('remember_device', false);

            if ($rememberDevice) {
                $user = $request->user();

                $deviceToken = $request->cookie('trusted_device');
                $existingDevice = $deviceToken
                    ? TrustedDevice::where('user_id', $user->id)
                        ->where('device_token', $deviceToken)
                        ->first()
                    : null;

                if ($existingDevice) {
                    $existingDevice->update([
                        'last_used_at' => now(),
                        'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                        'user_agent' => $request->userAgent(),
                        'ip_address' => $request->ip(),
                    ]);

                    return $response->withCookie(
                        cookie(
                            'trusted_device',
                            $existingDevice->device_token,
                            self::TRUSTED_DEVICE_MINUTES,
                            null,
                            null,
                            true,
                            true,
                            false,
                            'strict'
                        )
                    );
                }

                $deviceToken = Str::random(64);

                TrustedDevice::create([
                    'user_id' => $user->id,
                    'device_token' => $deviceToken,
                    'device_name' => $this->getDeviceName($request->userAgent()),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'last_used_at' => now(),
                    'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                ]);

                return $response->withCookie(
                    cookie(
                        'trusted_device',
                        $deviceToken,
                        self::TRUSTED_DEVICE_MINUTES,
                        null,
                        null,
                        true, // secure
                        true, // httpOnly
                        false,
                        'strict'
                    )
                );
            }
        }

        return $response;
    }

    private function getDeviceName(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown Device';
        }

        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone')) {
            return 'Mobile Device';
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows PC';
        }

        if (str_contains($userAgent, 'Mac')) {
            return 'Mac';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux PC';
        }

        return 'Desktop';
    }
}
