<?php

use App\Http\Middleware\BypassTwoFactorIfTrustedDevice;
use App\Http\Middleware\CheckActiveRole;
use App\Http\Middleware\EnsureSingleActiveSession;
use App\Http\Middleware\EnsureSsoOrganizationAllowed;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\ViewAsUserMiddleware;
use App\Http\Middleware\PreserveSessionLastActivityForSsoSync;
use App\Http\Middleware\PreventMaintenanceModeRequests;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/health',
        then: function () {
            // Load maintenance routes with custom middleware
            Route::middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                PreventMaintenanceModeRequests::class,
                HandleAppearance::class,
                HandleInertiaRequests::class,
                AddLinkHeadersForPreloadedAssets::class,
            ])->group(base_path('routes/maintenance.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Remove global maintenance middlewares first
        $middleware->remove([
            CheckForMaintenanceMode::class,
            PreventRequestsDuringMaintenance::class,
        ]);

        // Append custom middleware AFTER all default web middlewares (including StartSession)
        $middleware->web(append: [
            LogRequests::class,
            PreventMaintenanceModeRequests::class,
            EnsureSingleActiveSession::class,
            PreserveSessionLastActivityForSsoSync::class,
            HandleAppearance::class,
            ViewAsUserMiddleware::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active.role' => CheckActiveRole::class,
            'bypass.2fa' => BypassTwoFactorIfTrustedDevice::class,
            'require.2fa' => EnsureTwoFactorEnabled::class,
            'sso.organization' => EnsureSsoOrganizationAllowed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log authorization failures
        $exceptions->reportable(function (AuthorizationException $e) {

            try {
                ActivityLog::log(
                    action: 'Akses Ditolak',
                    type: 'security',
                    description: 'Percobaan akses tanpa otorisasi: '.$e->getMessage(),
                    status: 'error',
                    metadata: [
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'ip' => request()->ip(),
                    ]
                );
            } catch (Exception $logError) {
                Log::error('Failed to log authorization error', ['error' => $logError->getMessage()]);
            }
        });

        // Log database errors to activity log for admin review
        $exceptions->reportable(function (QueryException $e) {
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

            ActivityLog::log(
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
        $exceptions->reportable(function (Throwable $e) {
            // Log 500 errors and other critical exceptions
            if ($e instanceof HttpException && $e->getStatusCode() >= 500) {
                ActivityLog::log(
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

        // Custom error page rendering for Inertia
        $exceptions->respond(function (HttpFoundationResponse $response) {
            // Skip redirects and non-Response types
            if ($response instanceof RedirectResponse) {
                return $response;
            }

            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with([
                    'error' => 'Halaman telah kedaluwarsa, silakan coba lagi.',
                ]);
            }

            // Only handle specific error codes with Inertia
            if (in_array($status, [404, 403, 500, 503]) && request()->wantsJson() === false) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse(request())
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
