<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/health',
        then: function () {
            // Load maintenance routes with custom middleware
            Route::middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \App\Http\Middleware\PreventMaintenanceModeRequests::class,
                \App\Http\Middleware\HandleAppearance::class,
                \App\Http\Middleware\HandleInertiaRequests::class,
                \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ])->group(base_path('routes/maintenance.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Remove global maintenance middlewares first
        $middleware->remove([
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        ]);

        // Append custom middleware AFTER all default web middlewares (including StartSession)
        $middleware->web(append: [
            \App\Http\Middleware\PreventMaintenanceModeRequests::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active.role' => \App\Http\Middleware\CheckActiveRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log database errors to activity log for admin review
        $exceptions->reportable(function (\Illuminate\Database\QueryException $e) {
            $errorMessage = $e->getMessage();

            // Simplify common database errors for non-technical admins
            $description = 'Terjadi kesalahan pada database';

            if (str_contains($errorMessage, 'Duplicate entry')) {
                // Extract field name if possible
                preg_match("/for key '(.+?)'/", $errorMessage, $matches);
                $field = $matches[1] ?? 'data';
                $description = "Data yang dimasukkan sudah ada (duplikat pada: {$field})";
            } elseif (str_contains($errorMessage, "doesn't have a default value")) {
                // Extract field name
                preg_match("/Field '(.+?)' doesn't/", $errorMessage, $matches);
                $field = $matches[1] ?? 'kolom';
                $description = "Kolom '{$field}' tidak boleh kosong - harap periksa data yang diinput";
            } elseif (str_contains($errorMessage, 'foreign key constraint')) {
                $description = 'Data tidak dapat dihapus karena masih digunakan oleh data lain';
            } elseif (str_contains($errorMessage, "doesn't exist") || str_contains($errorMessage, 'not found')) {
                preg_match("/Table '(.+?)' doesn't/", $errorMessage, $matches);
                $table = $matches[1] ?? 'data';
                $description = "Tabel '{$table}' tidak ditemukan di database";
            } elseif (str_contains($errorMessage, 'Data too long')) {
                preg_match("/column '(.+?)'/", $errorMessage, $matches);
                $field = $matches[1] ?? 'kolom';
                $description = "Data pada '{$field}' terlalu panjang - kurangi jumlah karakter";
            } elseif (str_contains($errorMessage, 'cannot be null')) {
                preg_match("/Column '(.+?)'/", $errorMessage, $matches);
                $field = $matches[1] ?? 'kolom';
                $description = "Kolom '{$field}' wajib diisi dan tidak boleh kosong";
            } else {
                $description = 'Terjadi kesalahan saat menyimpan data ke database';
            }

            \App\Models\ActivityLog::log(
                action: 'Kesalahan Database',
                type: 'database',
                description: $description,
                status: 'error',
                metadata: [
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'original_message' => $errorMessage,
                ]
            );
        });

        // Log other critical exceptions
        $exceptions->reportable(function (\Throwable $e) {
            // Log 500 errors and other critical exceptions
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() >= 500) {
                \App\Models\ActivityLog::log(
                    action: 'Kesalahan Sistem',
                    type: 'error',
                    description: 'Terjadi kesalahan pada server (Kode: '.$e->getStatusCode().')',
                    status: 'error',
                    metadata: [
                        'status_code' => $e->getStatusCode(),
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'url' => request()->fullUrl(),
                    ]
                );
            }
        });
    })->create();
