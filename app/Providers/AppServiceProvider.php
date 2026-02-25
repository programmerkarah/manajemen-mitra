<?php

namespace App\Providers;

use App\Models\Kegiatan;
use App\Policies\KegiatanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        Kegiatan::class => KegiatanPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom user resolver for broadcasting to support "view as" feature
        \Illuminate\Support\Facades\Broadcast::resolveAuthenticatedUserUsing(function ($request) {
            // Use effectiveUser() which returns view_as user if in view-as mode
            return effectiveUser($request);
        });

        // Register policies
        Gate::policy(Kegiatan::class, KegiatanPolicy::class);

        // Handle view_as feature globally for all authorization checks
        Gate::before(function ($user, $ability) {
            // If in view_as mode, pass effectiveUser to policy checks
            if (session()->has('view_as_user_id')) {
                $effectiveUser = effectiveUser();

                // If effectiveUser is different from authenticated user
                // Policy will handle the authorization using effectiveUser via getEffectiveUser() method
                if ($effectiveUser && $effectiveUser->id !== $user->id) {
                    // Return null to continue to policy methods
                    // Policy methods will use getEffectiveUser() to get the right user
                    return null;
                }
            }

            // Return null to continue with normal authorization flow
            return null;
        });

        // Define gates for role-based access
        Gate::define('admin', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('operator', function ($user) {
            return $user->hasRole('operator');
        });

        Gate::define('pj', function ($user) {
            return $user->hasRole('pj');
        });

        Gate::define('approver', function ($user) {
            return $user->hasRole('approver');
        });

        Gate::define('admin-or-pj', function ($user) {
            return $user->hasAnyRole(['admin', 'pj']);
        });

        Gate::define('admin-or-approver', function ($user) {
            return $user->hasAnyRole(['admin', 'approver']);
        });

        Gate::define('operator-or-approver', function ($user) {
            return $user->hasAnyRole(['operator', 'approver']);
        });
    }
}
