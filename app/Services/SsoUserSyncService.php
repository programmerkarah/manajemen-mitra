<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SsoUserSyncService
{
    /**
     * Synchronize every SSO user that is eligible for this OAuth application.
     *
     * Existing local users are updated in-place (preserving IDs and relations).
     * Users no longer returned by SSO are soft-deleted, never physically deleted.
     *
     * @return array{created:int,updated:int,restored:int,deleted:int,total:int}
     */
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.sso.base_url'), '/');
        $clientId = (string) config('services.sso.client_id');
        $clientSecret = (string) config('services.sso.client_secret');
        $endpoint = (string) config('services.sso.eligible_users_endpoint', '/api/applications/eligible-users');

        if ($baseUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Konfigurasi SSO belum lengkap.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($baseUrl.$endpoint, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('SSO menolak sinkronisasi user (HTTP '.$response->status().').');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Format daftar user dari SSO tidak valid.');
        }

        $eligible = collect($payload)
            ->filter(fn ($row) => is_array($row) && isset($row['id']) && is_numeric($row['id']))
            ->values();

        return DB::transaction(function () use ($eligible): array {
            $created = 0;
            $updated = 0;
            $restored = 0;
            $eligibleIds = $eligible->pluck('id')->map(fn ($id) => (int) $id)->all();
            $guestRoleId = Role::query()->where('name', 'guest')->value('id');

            foreach ($eligible as $profile) {
                $ssoId = (int) $profile['id'];
                $email = trim((string) ($profile['email'] ?? ''));
                $username = trim((string) ($profile['username'] ?? ''));
                $name = trim((string) ($profile['name'] ?? $username ?: $email));
                $organizationType = trim((string) ($profile['organization_type'] ?? ''));

                $user = User::withTrashed()
                    ->where('sso_user_id', $ssoId)
                    ->first();

                if (! $user && $email !== '') {
                    $user = User::withTrashed()->where('email', $email)->first();
                }

                if (! $user && $username !== '') {
                    $user = User::withTrashed()->where('username', $username)->first();
                }

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                        $restored++;
                    }

                    $user->forceFill([
                        'sso_user_id' => $ssoId,
                        'sso_organization_type' => $organizationType !== '' ? $organizationType : null,
                        'name' => $name !== '' ? $name : $user->name,
                        'username' => $username !== '' ? $username : $user->username,
                        'email' => $email !== '' ? $email : $user->email,
                        'email_verified_at' => $profile['email_verified_at'] ?? $user->email_verified_at,
                        'is_active' => true,
                    ])->save();
                    $updated++;

                    continue;
                }

                $user = User::create([
                    'sso_user_id' => $ssoId,
                    'sso_organization_type' => $organizationType !== '' ? $organizationType : null,
                    'name' => $name !== '' ? $name : 'Pengguna SSO',
                    'username' => $username !== '' ? $username : 'sso-'.$ssoId,
                    'email' => $email !== '' ? $email : 'sso-'.$ssoId.'@invalid.local',
                    'email_verified_at' => $profile['email_verified_at'] ?? now(),
                    'password' => Hash::make(Str::random(64)),
                    'is_active' => true,
                ]);

                if ($guestRoleId) {
                    $user->roles()->syncWithoutDetaching([$guestRoleId]);
                }

                $created++;
            }

            $toDelete = User::query()
                ->whereNotNull('sso_user_id')
                ->when($eligibleIds !== [], fn ($query) => $query->whereNotIn('sso_user_id', $eligibleIds))
                ->get();

            $deleted = $toDelete->count();

            foreach ($toDelete as $user) {
                $user->forceFill(['is_active' => false])->save();
                $user->delete();
            }

            ActivityLog::logSystem(
                'Sinkronisasi User SSO',
                sprintf(
                    'Sinkronisasi selesai: %d dibuat, %d diperbarui, %d dipulihkan, %d dinonaktifkan.',
                    $created,
                    $updated,
                    $restored,
                    $deleted,
                ),
                'success',
                [
                    'created' => $created,
                    'updated' => $updated,
                    'restored' => $restored,
                    'deleted' => $deleted,
                    'eligible_total' => $eligible->count(),
                ],
            );

            return [
                'created' => $created,
                'updated' => $updated,
                'restored' => $restored,
                'deleted' => $deleted,
                'total' => $eligible->count(),
            ];
        });
    }
}
