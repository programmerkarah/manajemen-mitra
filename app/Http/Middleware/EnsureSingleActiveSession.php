<?php

namespace App\Http\Middleware;

use App\Services\SessionConcurrencyManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $lifetimeMinutes = (int) config('session.lifetime', 120);
        $authStartedAt = (int) $request->session()->get('auth_started_at', 0);

        if ($authStartedAt <= 0) {
            $request->session()->put('auth_started_at', Carbon::now()->timestamp);
        } elseif ($lifetimeMinutes > 0) {
            $expiresAt = Carbon::createFromTimestamp($authStartedAt)->addMinutes($lifetimeMinutes);

            if (Carbon::now()->greaterThanOrEqualTo($expiresAt)) {
                return $this->logoutWithExpiredMessage($request, 'Sesi Anda telah kedaluwarsa. Silakan login kembali.');
            }
        }

        $userId = (int) Auth::id();
        $this->sessionConcurrencyManager->ensureSessionRegistered($request, $userId);

        if ($this->sessionConcurrencyManager->isCurrentSessionActive($request, $userId)) {
            return $next($request);
        }

        return $this->logoutWithExpiredMessage($request, 'Sesi Anda telah berakhir karena akun digunakan pada perangkat lain.');
    }

    private function logoutWithExpiredMessage(Request $request, string $message): Response
    {
        $userId = Auth::id();

        if (is_int($userId)) {
            $this->sessionConcurrencyManager->forgetIfCurrentSession($request, $userId);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 401);
        }

        return redirect()->route('login')->withErrors([
            'username' => $message,
        ]);
    }
}
