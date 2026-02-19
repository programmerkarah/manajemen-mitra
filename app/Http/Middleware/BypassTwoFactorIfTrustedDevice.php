<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use App\Models\User;
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
            $currentIp = $request->ip();
            $deviceToken = $request->cookie('trusted_device');

            if ($deviceToken) {
                // Check if current device is trusted (based on IP only, not user agent)
                // This allows same device with different browsers to bypass 2FA
                $trustedDevice = TrustedDevice::where('device_token', $deviceToken)
                    ->where('user_id', $loginId)
                    ->where('ip_address', $currentIp)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($trustedDevice) {
                    // Update last used timestamp and current user agent
                    $trustedDevice->update([
                        'last_used_at' => now(),
                        'user_agent' => $request->userAgent(), // Update to current browser
                    ]);

                    // Get the user
                    $user = User::find($loginId);

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
