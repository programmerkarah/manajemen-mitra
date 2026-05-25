<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $viewAsUser = null;
        $originalUser = null;

        // If session has view_as_user_id, set viewAsUser and originalUser
        if (session()->has('view_as_user_id')) {
            $viewAsUser = User::find(session('view_as_user_id'));
            $originalUser = $user;
        }

        // Use viewAsUser if available, otherwise use actual logged-in user
        $displayUser = $viewAsUser ?? $user;

        // Force refresh user data from database to get latest roles
        if ($displayUser) {
            $displayUser->refresh();
            $displayUser->load(['roles']);
        }

        $ssoSyncSetting = Cache::get('settings:sso_sync_enabled');

        if (! is_bool($ssoSyncSetting)) {
            $ssoSyncSetting = (bool) config('services.sso.sync_enabled', true);
        }

        $ssoSyncEnabled = (bool) config('services.sso.active', true)
            && $ssoSyncSetting
            && filled(config('services.sso.base_url'))
            && filled(config('services.sso.client_id'))
            && $displayUser !== null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $displayUser ? array_merge(
                    $displayUser->toArray(),
                    ['active_role' => $displayUser->active_role]
                ) : null,
                // Always share the full Role object for activeRole, never just the name
                'activeRole' => $displayUser && $displayUser->getActiveRole() ? $displayUser->getActiveRole()->toArray() : null,
                'userRoles' => $displayUser ? $displayUser->roles->map->toArray()->all() : [],
                'emailVerified' => $displayUser?->hasVerifiedEmail() ?? false,
                'twoFactorEnabled' => $displayUser?->hasEnabledTwoFactorAuthentication() ?? false,
                'isViewingAsUser' => $originalUser !== null && $viewAsUser !== null,
                'originalUser' => $originalUser ? [
                    'id' => $originalUser->id,
                    'name' => $originalUser->name,
                    'username' => $originalUser->username,
                ] : null,
                'canViewAsUser' => ($user?->username ?? null) === 'rhmtzikri' || ($user?->username ?? null) === 'rahmat.zikri',
            ],
            'activeYear' => ActiveYearService::get(),
            'availableYears' => ActiveYearService::getAvailableYears(),
            'hasAvailableYears' => ActiveYearService::hasAvailableYears(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->pull('success'),
                'error' => $request->session()->pull('error'),
                'warning' => $request->session()->pull('warning'),
                'info' => $request->session()->pull('info'),
            ],
            'ssoSync' => [
                'enabled' => $ssoSyncEnabled,
                'focusCooldownSeconds' => max((int) config('services.sso.sync_focus_cooldown_seconds', 120), 30),
                'intervalSeconds' => max((int) config('services.sso.sync_interval_seconds', 600), 60),
            ],
        ];
    }
}
