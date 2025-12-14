<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BypassTwoFactorIfTrustedDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a two-factor authentication attempt
        $loginId = $request->session()->get('login.id');
        
        if ($loginId) {
            $currentUserAgent = $request->userAgent();
            $currentIp = $request->ip();
            $deviceToken = $request->cookie('trusted_device');
            
            // Check if user has any trusted devices with different fingerprint
            // If device changed (different user agent OR IP), expire all old trusted devices
            $existingDevices = TrustedDevice::where('user_id', $loginId)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get();

            foreach ($existingDevices as $device) {
                // If user agent OR IP changed, this is a different device
                if ($device->user_agent !== $currentUserAgent || $device->ip_address !== $currentIp) {
                    // Expire the old device
                    $device->update([
                        'expires_at' => now()->subDay(), // Set to past date to expire
                    ]);
                }
            }
            
            if ($deviceToken) {
                // Check if current device is trusted and matches fingerprint
                $trustedDevice = TrustedDevice::where('device_token', $deviceToken)
                    ->where('user_id', $loginId)
                    ->where('user_agent', $currentUserAgent)
                    ->where('ip_address', $currentIp)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($trustedDevice) {
                    // Update last used timestamp
                    $trustedDevice->updateLastUsed();
                    
                    // Get the user
                    $user = \App\Models\User::find($loginId);
                    
                    if ($user) {
                        // Log the user in directly, bypassing 2FA
                        Auth::login($user, $request->session()->get('login.remember', false));
                        
                        // Clear the login session data
                        $request->session()->forget(['login.id', 'login.remember']);
                        
                        // Regenerate session
                        $request->session()->regenerate();
                        
                        // Redirect to intended location or home
                        return redirect()->intended(config('fortify.home'));
                    }
                }
            }
        }

        return $next($request);
    }
}
