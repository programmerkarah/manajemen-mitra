<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\SessionConcurrencyManager;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(protected SessionConcurrencyManager $sessionConcurrencyManager) {}

    public function create(): Response
    {
        $ssoActive = $this->resolveSsoActive();
        $ssoRegisterUrl = $ssoActive ? config('services.sso.register_url') : null;

        return Inertia::render('auth/register', [
            'ssoActive' => $ssoActive,
            'ssoRegisterUrl' => is_string($ssoRegisterUrl) && $ssoRegisterUrl !== '' ? $ssoRegisterUrl : null,
        ]);
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        if ($this->resolveSsoActive()) {
            return redirect()->route('sso.redirect');
        }

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'username' => $request->string('username')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();
        $this->sessionConcurrencyManager->activateLatestSession($request, $user->id);

        return redirect()->route('dashboard');
    }

    private function resolveSsoActive(): bool
    {
        $clientId = config('services.sso.client_id');
        $baseUrl = config('services.sso.base_url');

        if (! $clientId || ! $baseUrl) {
            return false;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(5)
                ->get(rtrim($baseUrl, '/').'/api/application/status', [
                    'client_id' => $clientId,
                ]);

            if ($response->successful()) {
                return (bool) $response->json('is_active', false);
            }
        } catch (\Throwable $e) {
            Log::warning('SSO application status check failed on register.', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
