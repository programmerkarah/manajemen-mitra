<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PreventMaintenanceModeRequests extends Middleware
{
    /**
     * The URIs that should be excluded from maintenance mode verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'bypass',
        'bypass/*',
        'up',
        'up/*',
        'health',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        
        // ALWAYS allow bypass and up routes during maintenance
        if ($path === 'bypass' || $path === 'up' || 
            str_starts_with($path, 'bypass/') || 
            str_starts_with($path, 'up/')) {
            return $next($request);
        }

        if (! $this->app->maintenanceMode()->active()) {
            return $next($request);
        }

        // Check if path is excluded via parent's method
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        $data = $this->getMaintenanceData();

        // Check for valid bypass cookie
        if ($this->hasValidBypassCookie($request, $data)) {
            return $next($request);
        }

        // Allow Admin users to bypass automatically
        if ($this->isAdminUser($request)) {
            return $next($request);
        }

        // Check for bypass secret in URL
        if (isset($data['secret']) && $request->path() === $data['secret']) {
            return $this->bypassResponse($data['secret']);
        }

        throw new HttpException(503, $data['message'] ?? 'Service Unavailable', null, $data['retry'] ?? []);
    }

    /**
     * Check if user is logged in but not an Admin.
     */
    protected function isLoggedInButNotAdmin($request): bool
    {
        $userId = null;

        if ($request->hasSession()) {
            $session = $request->session();
            $userId = $session->get('login_web_'.sha1(config('app.key')));

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

        // User is logged in, check if NOT admin
        return ! $this->checkUserHasAdminRole($userId);
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

            // Debug log
            \Log::info('isAdminUser check', [
                'userId' => $userId,
                'sessionKeys' => array_keys($session->all()),
            ]);
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
     * Get the maintenance mode data.
     */
    protected function getMaintenanceData(): array
    {
        return json_decode(file_get_contents($this->app->storagePath().'/framework/down'), true);
    }
}
