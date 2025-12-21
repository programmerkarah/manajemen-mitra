<?php

namespace App\Http\Middleware;

use App\Services\ActiveYearService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
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
        $originalUser = $request->attributes->get('original_user');
        $viewAsUser = $request->attributes->get('view_as_user');

        // Use viewAsUser if available, otherwise use actual logged-in user
        $displayUser = $viewAsUser ?? $user;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $displayUser ? array_merge(
                    $displayUser->load('roles')->toArray(),
                    ['active_role' => $displayUser->active_role]
                ) : null,
                'activeRole' => $displayUser ? $displayUser->getActiveRole() : null,
                'userRoles' => $displayUser ? $displayUser->roles : [],
                'emailVerified' => $displayUser?->hasVerifiedEmail() ?? false,
                'twoFactorEnabled' => $displayUser?->hasEnabledTwoFactorAuthentication() ?? false,
                'isViewingAsUser' => $originalUser && $viewAsUser,
                'originalUser' => $originalUser ? [
                    'id' => $originalUser->id,
                    'name' => $originalUser->name,
                    'username' => $originalUser->username,
                ] : null,
                'canViewAsUser' => ($originalUser?->username ?? $user?->username) === 'rhmtzikri',
            ],
            'activeYear' => ActiveYearService::get(),
            'availableYears' => ActiveYearService::getAvailableYears(),
            'hasAvailableYears' => ActiveYearService::hasAvailableYears(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
