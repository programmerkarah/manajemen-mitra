<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PreserveSessionLastActivityForSsoSync
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((string) config('session.driver') !== 'database') {
            return $next($request);
        }

        if ($this->shouldPreserve($request)) {
            $sessionId = (string) $request->session()->getId();

            if ($sessionId !== '') {
                $table = (string) config('session.table', 'sessions');
                $connection = config('session.connection');

                $originalLastActivity = DB::connection($connection)
                    ->table($table)
                    ->where('id', $sessionId)
                    ->value('last_activity');

                $request->attributes->set('preserve_sso_sync_last_activity', [
                    'session_id' => $sessionId,
                    'last_activity' => is_null($originalLastActivity) ? null : (int) $originalLastActivity,
                ]);
            }
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ((string) config('session.driver') !== 'database') {
            return;
        }

        $context = $request->attributes->get('preserve_sso_sync_last_activity');

        if (! is_array($context)) {
            return;
        }

        $sessionId = (string) ($context['session_id'] ?? '');

        if ($sessionId === '') {
            return;
        }

        $lastActivity = $context['last_activity'] ?? null;

        if (! is_int($lastActivity)) {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        $connection = config('session.connection');

        DB::connection($connection)
            ->table($table)
            ->where('id', $sessionId)
            ->update(['last_activity' => $lastActivity]);
    }

    private function shouldPreserve(Request $request): bool
    {
        if ($request->routeIs('sso.redirect') && $request->boolean('sync')) {
            return true;
        }

        if ($request->routeIs('sso.callback')) {
            $oauthContext = $request->session()->get('sso_oauth_context', []);

            return (bool) data_get($oauthContext, 'sync', false);
        }

        return false;
    }
}
