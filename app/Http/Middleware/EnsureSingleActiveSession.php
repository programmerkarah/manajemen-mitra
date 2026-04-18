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

        $userId = (int) Auth::id();
        $this->sessionConcurrencyManager->ensureSessionRegistered($request, $userId);

        if ($this->sessionConcurrencyManager->isCurrentSessionActive($request, $userId)) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesi Anda telah berakhir karena akun digunakan pada perangkat lain.',
            ], 401);
        }

        return redirect()->route('login')->withErrors([
            'username' => 'Sesi Anda telah berakhir karena akun digunakan pada perangkat lain.',
        ]);
    }
}
