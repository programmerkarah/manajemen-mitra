<?php

namespace App\Http\Middleware;

use App\Services\SessionConcurrencyManager;
use Closure;
use Illuminate\Http\Request;
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

        $lifetimeMinutes = max((int) config('session.lifetime', 120), 1);
        $lastUserActivityAt = (int) $request->session()->get('last_user_activity_at', 0);

        if ($lastUserActivityAt > 0) {
            $elapsedSeconds = now()->timestamp - $lastUserActivityAt;

            if ($elapsedSeconds >= ($lifetimeMinutes * 60)) {
                return $this->logoutWithExpiredMessage($request, 'Sesi Anda telah kedaluwarsa karena tidak ada aktivitas. Silakan login kembali.');
            }
        }

        $userId = (int) Auth::id();
        $this->sessionConcurrencyManager->ensureSessionRegistered($request, $userId);

        if (! $this->sessionConcurrencyManager->isCurrentSessionActive($request, $userId)) {
            return $this->logoutWithExpiredMessage($request, 'Sesi Anda telah berakhir karena akun digunakan pada perangkat lain.');
        }

        if ($this->shouldRefreshActivity($request)) {
            $request->session()->put('last_user_activity_at', now()->timestamp);
        }

        return $next($request);
    }

    private function shouldRefreshActivity(Request $request): bool
    {
        if ($request->routeIs('sso.redirect') && $request->boolean('sync')) {
            return false;
        }

        if ($request->routeIs('sso.callback')) {
            $oauthContext = $request->session()->get('sso_oauth_context', []);

            return ! (bool) data_get($oauthContext, 'sync', false);
        }

        return true;
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
