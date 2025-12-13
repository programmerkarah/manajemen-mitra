<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
