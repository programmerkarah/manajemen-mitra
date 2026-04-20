<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\SessionConcurrencyManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BypassTwoFactorIfTrustedDevice
{
    private const TRUSTED_DEVICE_DAYS = 14;

    private const TRUSTED_DEVICE_MINUTES = 60 * 24 * self::TRUSTED_DEVICE_DAYS;

    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a two-factor authentication attempt
        $loginId = $request->session()->get('login.id');

        if ($loginId) {
            $deviceToken = $request->cookie('trusted_device');

            if ($deviceToken) {
                $trustedDevice = TrustedDevice::where('device_token', $deviceToken)
                    ->where('user_id', $loginId)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($trustedDevice) {
                    $trustedDevice->update([
                        'last_used_at' => now(),
                        'user_agent' => $request->userAgent(),
                        'ip_address' => $request->ip(),
                        'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                    ]);

                    $user = User::find($loginId);

                    if ($user) {
                        Auth::login($user, $request->session()->get('login.remember', false));

                        $request->session()->forget(['login.id', 'login.remember']);
                        $request->session()->regenerate();

                        $this->sessionConcurrencyManager->activateLatestSession($request, $user->id);

                        cookie()->queue(
                            'trusted_device',
                            $trustedDevice->device_token,
                            self::TRUSTED_DEVICE_MINUTES,
                            null,
                            null,
                            true,
                            true,
                            false,
                            'strict'
                        );

                        return redirect()->intended(config('fortify.home'));
                    }
                }
            }
        }

        return $next($request);
    }
}
