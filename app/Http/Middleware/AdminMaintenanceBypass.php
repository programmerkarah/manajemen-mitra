<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminMaintenanceBypass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika aplikasi dalam maintenance mode dan user adalah Admin
        if (app()->isDownForMaintenance() && $this->isAdminUser($request)) {
            $secret = config('app.maintenance_bypass_secret');

            // Jika belum ada bypass cookie, set cookie
            if ($secret && ! $this->hasValidBypassCookie($request, $secret)) {
                $cookie = MaintenanceModeBypassCookie::create($secret);

                // Redirect ke URL yang sama dengan bypass cookie
                return redirect($request->fullUrl())->withCookie($cookie);
            }
        }

        return $next($request);
    }

    /**
     * Check if the current user is an Admin by reading session.
     */
    protected function isAdminUser($request): bool
    {
        // Try getting user ID from session
        $userId = null;

        if ($request->hasSession()) {
            $session = $request->session();

            // Try standard Laravel auth session key
            $userId = $session->get('login_web_'.sha1(config('app.key')));

            // If not found, try getting from session data directly
            if (! $userId) {
                // Try alternative keys
                $sessionData = $session->all();
                foreach ($sessionData as $key => $value) {
                    if (str_starts_with($key, 'login_web_')) {
                        $userId = $value;
                        break;
                    }
                }
            }
        }

        if (! $userId) {
            return false;
        }

        return $this->checkUserHasAdminRole($userId);
    }

    /**
     * Check if user has Admin role from database.
     */
    protected function checkUserHasAdminRole(int $userId): bool
    {
        try {
            return DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->where('role_user.user_id', $userId)
                ->where('roles.name', 'admin')
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if request has valid bypass cookie.
     */
    protected function hasValidBypassCookie(Request $request, string $secret): bool
    {
        $cookie = $request->cookie('laravel_maintenance');

        if (! $cookie) {
            return false;
        }

        try {
            $payload = json_decode(base64_decode($cookie), true);

            return is_array($payload) &&
                   isset($payload['expires_at'], $payload['mac']) &&
                   hash_equals(hash_hmac('sha256', $payload['expires_at'], $secret), $payload['mac']) &&
                   $payload['expires_at'] > time();
        } catch (\Exception $e) {
            return false;
        }
    }
}
