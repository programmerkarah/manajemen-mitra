<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Events\SessionInvalidated;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\SessionConcurrencyManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public const TRUSTED_DEVICE_DAYS = 14;

    public const TRUSTED_DEVICE_MINUTES = 60 * 24 * self::TRUSTED_DEVICE_DAYS;

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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureTwoFactorChallenge();
        $this->configureTwoFactorLoginResponse();
        $this->configureRegularLoginResponse();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Authenticate using username instead of email
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('username', $request->username)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // Throw validation exception with Indonesian message
            throw ValidationException::withMessages([
                Fortify::username() => ['Username atau password yang Anda masukkan salah.'],
            ]);
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'ssoEnabled' => filled(config('services.sso.base_url')),
            'ssoLoginUrl' => route('sso.redirect'),
            'ssoRegisterUrl' => config('services.sso.register_url'),
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure two-factor challenge with device remember functionality.
     */
    private function configureTwoFactorChallenge(): void
    {
        Fortify::twoFactorChallengeView(function (Request $request) {
            $deviceToken = $request->cookie('trusted_device');
            $user = $request->session()->get('login.id')
                ? User::find($request->session()->get('login.id'))
                : null;

            if ($user && $deviceToken) {
                $trustedDevice = $user->trustedDevices()
                    ->where('device_token', $deviceToken)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->first();

                if ($trustedDevice) {
                    $trustedDevice->update([
                        'last_used_at' => now(),
                        'user_agent' => $request->userAgent(),
                        'ip_address' => $request->ip(),
                        'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                    ]);

                    return Inertia::render('auth/two-factor-challenge', [
                        'isTrustedDevice' => true,
                    ]);
                }
            }

            return Inertia::render('auth/two-factor-challenge', [
                'isTrustedDevice' => false,
            ]);
        });
    }

    /**
     * Configure two-factor login response to save trusted device.
     */
    private function configureTwoFactorLoginResponse(): void
    {
        $this->app->singleton(TwoFactorLoginResponse::class, function () {
            return new class implements TwoFactorLoginResponse
            {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if ($user) {
                        // SINGLE DEVICE LOGIN: Invalidate ALL other sessions for this user
                        // This ensures user can only be logged in on ONE device at a time
                        $currentSessionId = $request->session()->getId();

                        // Delete all other sessions except current one
                        DB::table('sessions')
                            ->where('user_id', $user->id)
                            ->where('id', '!=', $currentSessionId)
                            ->delete();

                        // Broadcast session invalidation to all devices
                        broadcast(new SessionInvalidated($user->id))->toOthers();

                        $deviceToken = $request->cookie('trusted_device');
                        $existingDevice = $deviceToken
                            ? TrustedDevice::where('user_id', $user->id)
                                ->where('device_token', $deviceToken)
                                ->first()
                            : null;

                        TrustedDevice::where('user_id', $user->id)
                            ->when($existingDevice, function ($query) use ($existingDevice) {
                                $query->where('device_token', '!=', $existingDevice->device_token);
                            })
                            ->update([
                                'expires_at' => now()->subDay(),
                            ]);

                        if ($existingDevice) {
                            $existingDevice->update([
                                'last_used_at' => now(),
                                'expires_at' => now()->addDays(FortifyServiceProvider::TRUSTED_DEVICE_DAYS),
                                'user_agent' => $request->userAgent(),
                                'ip_address' => $request->ip(),
                            ]);

                            cookie()->queue(
                                'trusted_device',
                                $existingDevice->device_token,
                                FortifyServiceProvider::TRUSTED_DEVICE_MINUTES,
                                null,
                                null,
                                true,
                                true,
                                false,
                                'strict'
                            );
                        } else {
                            $deviceToken = Str::random(64);

                            TrustedDevice::create([
                                'user_id' => $user->id,
                                'device_token' => $deviceToken,
                                'device_name' => $this->getDeviceName($request->userAgent()),
                                'user_agent' => $request->userAgent(),
                                'ip_address' => $request->ip(),
                                'last_used_at' => now(),
                                'expires_at' => now()->addDays(FortifyServiceProvider::TRUSTED_DEVICE_DAYS),
                            ]);

                            cookie()->queue(
                                'trusted_device',
                                $deviceToken,
                                FortifyServiceProvider::TRUSTED_DEVICE_MINUTES,
                                null,
                                null,
                                true, // secure
                                true, // httpOnly
                                false,
                                'strict'
                            );
                        }

                        app(SessionConcurrencyManager::class)->activateLatestSession($request, $user->id);
                    }

                    return redirect()->intended(config('fortify.home'));
                }

                private function getDeviceName(?string $userAgent): string
                {
                    if (! $userAgent) {
                        return 'Unknown Device';
                    }

                    if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone')) {
                        return 'Mobile Device';
                    }

                    if (str_contains($userAgent, 'Windows')) {
                        return 'Windows PC';
                    }

                    if (str_contains($userAgent, 'Mac')) {
                        return 'Mac';
                    }

                    if (str_contains($userAgent, 'Linux')) {
                        return 'Linux PC';
                    }

                    return 'Desktop';
                }
            };
        });
    }

    /**
     * Configure regular login response (for users WITHOUT 2FA enabled).
     * This ensures single device login even for non-2FA users.
     */
    private function configureRegularLoginResponse(): void
    {
        // Use Laravel's Login event to handle post-authentication for regular logins
        Event::listen(
            Login::class,
            function ($event) {
                $user = $event->user;
                $request = request();

                // Only handle if user doesn't have 2FA enabled
                // (2FA users are handled by TwoFactorLoginResponse)
                if ($user && ! $user->two_factor_secret) {
                    // SINGLE DEVICE LOGIN: Invalidate ALL other sessions
                    $currentSessionId = $request->session()->getId();

                    DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->where('id', '!=', $currentSessionId)
                        ->delete();

                    // Broadcast session invalidation to all devices
                    broadcast(new SessionInvalidated($user->id))->toOthers();

                    $deviceToken = $request->cookie('trusted_device');
                    $existingDevice = $deviceToken
                        ? TrustedDevice::where('user_id', $user->id)
                            ->where('device_token', $deviceToken)
                            ->first()
                        : null;

                    TrustedDevice::where('user_id', $user->id)
                        ->when($existingDevice, function ($query) use ($existingDevice) {
                            $query->where('device_token', '!=', $existingDevice->device_token);
                        })
                        ->update([
                            'expires_at' => now()->subDay(),
                        ]);

                    if ($existingDevice) {
                        $existingDevice->update([
                            'last_used_at' => now(),
                            'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                            'user_agent' => $request->userAgent(),
                            'ip_address' => $request->ip(),
                        ]);

                        cookie()->queue(
                            'trusted_device',
                            $existingDevice->device_token,
                            self::TRUSTED_DEVICE_MINUTES,
                            null,
                            null,
                            true,
                            true,
                            false,
                            'strict'
                        );
                    } else {
                        $deviceToken = Str::random(64);

                        TrustedDevice::create([
                            'user_id' => $user->id,
                            'device_token' => $deviceToken,
                            'device_name' => $this->getDeviceNameFromUserAgent($request->userAgent()),
                            'user_agent' => $request->userAgent(),
                            'ip_address' => $request->ip(),
                            'last_used_at' => now(),
                            'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
                        ]);

                        cookie()->queue(
                            'trusted_device',
                            $deviceToken,
                            self::TRUSTED_DEVICE_MINUTES,
                            null,
                            null,
                            true, // secure
                            true, // httpOnly
                            false, // raw
                            'strict' // sameSite
                        );
                    }

                    app(SessionConcurrencyManager::class)->activateLatestSession($request, $user->id);
                }
            }
        );
    }

    /**
     * Get device name from user agent string.
     */
    private function getDeviceNameFromUserAgent(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown Device';
        }

        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone')) {
            return 'Mobile Device';
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows PC';
        }

        if (str_contains($userAgent, 'Mac')) {
            return 'Mac';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux PC';
        }

        return 'Desktop';
    }
}
