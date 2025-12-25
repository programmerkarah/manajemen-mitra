<?php

use App\Http\Middleware\BypassTwoFactorIfTrustedDevice;
use App\Http\Middleware\CheckActiveRole;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SetCacheHeaders;
use App\Http\Middleware\ValidateSessionExists;
use App\Http\Middleware\ViewAsUserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            SetCacheHeaders::class,
            ValidateSessionExists::class, // Check session validity on every request
            ViewAsUserMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SanitizeInput::class,
            BypassTwoFactorIfTrustedDevice::class,
            EnsureTwoFactorEnabled::class,
        ]);

        $middleware->alias([
            'sanitize' => SanitizeInput::class,
            'active.role' => CheckActiveRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
