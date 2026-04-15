<?php

namespace App\Listeners;

use App\Models\TrustedDevice;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Str;

class SaveTrustedDevice
{
    private const TRUSTED_DEVICE_DAYS = 14;

    private const TRUSTED_DEVICE_MINUTES = 60 * 24 * self::TRUSTED_DEVICE_DAYS;

    /**
     * Handle the event.
     */
    public function handle(Validated $event): void
    {
        $request = request();
        $user = $event->user;

        // Only process if this is a 2FA authentication (has code or recovery_code)
        if (! $request->has('code') && ! $request->has('recovery_code')) {
            return;
        }

        // Check if user wants to remember this device
        $rememberDevice = $request->boolean('remember_device', false);

        if ($rememberDevice && $user) {
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

                cookie()->queue(
                    'trusted_device',
                    $existingDevice->device_token,
                    self::TRUSTED_DEVICE_MINUTES,
                    null,
                    null,
                    true,
                    true,
                    false,
                    'strict'
                );

                return;
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

            cookie()->queue(
                'trusted_device',
                $deviceToken,
                self::TRUSTED_DEVICE_MINUTES,
                null,
                null,
                true, // secure
                true, // httpOnly
                false,
                'strict'
            );
        }
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
