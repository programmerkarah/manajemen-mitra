<?php

namespace App\Http\Middleware;

use App\Models\FeatureToggle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDisabledFeatureRequests
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($this->shouldBypass($routeName)) {
            return $next($request);
        }

        if ($this->isAdminUser($request)) {
            return $next($request);
        }

        $featureKey = FeatureToggle::featureKeyForRouteName($routeName);

        if ($featureKey !== null && ! FeatureToggle::isEnabled($featureKey)) {
            $message = $this->buildDisabledMessage($featureKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'feature_key' => $featureKey,
                ], 423);
            }

            abort(403, $message);
        }

        return $next($request);
    }

    private function shouldBypass(?string $routeName): bool
    {
        if (! is_string($routeName) || trim($routeName) === '') {
            return true;
        }

        $bypassPrefixes = [
            'admin.system-settings',
            'admin.activity-log',
            'admin.database-status',
            'admin.database-backup',
            'admin.database-restore',
            'admin.database-list-backups',
            'logout',
            'login',
            'password.',
            'verification.',
            'profile.',
            'health',
        ];

        foreach ($bypassPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function buildDisabledMessage(string $featureKey): string
    {
        return match ($featureKey) {
            'kegiatan' => 'Fitur kegiatan sedang dinonaktifkan oleh administrator.',
            'alokasi' => 'Fitur alokasi sedang dinonaktifkan oleh administrator.',
            'spk' => 'Fitur SPK sedang dinonaktifkan oleh administrator.',
            'bast' => 'Fitur BAST sedang dinonaktifkan oleh administrator.',
            'pengajuan_pulsa' => 'Fitur pengajuan pulsa sedang dinonaktifkan oleh administrator.',
            'petugas' => 'Fitur petugas sedang dinonaktifkan oleh administrator.',
            default => 'Fitur ini sedang dinonaktifkan oleh administrator.',
        };
    }

    private function isAdminUser(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return $user->hasActiveRole('admin') || $user->isAdmin();
    }
}
