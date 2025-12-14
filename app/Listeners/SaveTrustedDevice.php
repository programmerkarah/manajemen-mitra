<?php

namespace App\Listeners;

use App\Models\TrustedDevice;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Str;

class SaveTrustedDevice
{
    /**
     * Handle the event.
     */
    public function handle(Validated $event): void
    {
        $request = request();
        $user = $event->user;
        
        // Only process if this is a 2FA authentication (has code or recovery_code)
        if (!$request->has('code') && !$request->has('recovery_code')) {
            return;
        }

        // Check if user wants to remember this device
        $rememberDevice = $request->boolean('remember_device', false);

        if ($rememberDevice && $user) {
            // Check if device already exists
            $existingDevice = TrustedDevice::where('user_id', $user->id)
                ->where('user_agent', $request->userAgent())
                ->where('ip_address', $request->ip())
                ->first();

            if ($existingDevice) {
                // Update existing device
                $existingDevice->update([
                    'last_used_at' => now(),
                    'expires_at' => now()->addDays(30),
                ]);
                
                cookie()->queue(
                    'trusted_device',
                    $existingDevice->device_token,
                    60 * 24 * 30,
                    null,
                    null,
                    true,
                    true,
                    false,
                    'strict'
                );
                
                return;
            }

            // Generate unique device token
            $deviceToken = Str::random(64);

            // Save trusted device
            TrustedDevice::create([
                'user_id' => $user->id,
                'device_token' => $deviceToken,
                'device_name' => $this->getDeviceName($request->userAgent()),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'last_used_at' => now(),
                'expires_at' => now()->addDays(30), // Remember for 30 days
            ]);

            // Set cookie
            cookie()->queue(
                'trusted_device',
                $deviceToken,
                60 * 24 * 30, // 30 days
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
