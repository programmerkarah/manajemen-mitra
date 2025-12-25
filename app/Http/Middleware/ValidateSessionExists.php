<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ValidateSessionExists
{
    /**
     * Handle an incoming request.
     * Check if session still exists in database (not deleted by another device login).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $currentSessionId = $request->session()->getId();
            $userId = Auth::id();

            // Check if this session exists in database
            $sessionExists = DB::table('sessions')
                ->where('id', $currentSessionId)
                ->where('user_id', $userId)
                ->exists();

            if (! $sessionExists) {
                // Session was deleted (probably by another device login)
                // Force logout and redirect to login
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session has been terminated by another device login.',
                    ], 401);
                }

                return redirect()->route('login')->with('status', 'Your session has been terminated by another device login.');
            }
        }

        return $next($request);
    }
}
