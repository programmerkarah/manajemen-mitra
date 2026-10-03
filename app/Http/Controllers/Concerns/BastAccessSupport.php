<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\User;
use App\Services\Bast\BastAccessService;
use Illuminate\Http\Request;

trait BastAccessSupport
{
    private function getRequestUser(Request $request): ?User
    {
        return app(BastAccessService::class)->requestUser($request);
    }

    private function resolveBastDetailReferencePeriod(
        Request $request,
        Bast $bast,
    ): array {
        return app(BastAccessService::class)
            ->resolveReferencePeriod($request, $bast);
    }

    private function resolveBastFromHashedId(string $hashedId): ?Bast
    {
        return app(BastAccessService::class)
            ->resolveFromHashedId($hashedId);
    }

    private function userCanManageBastMain(Request $request): bool
    {
        return app(BastAccessService::class)->canManageMain($request);
    }

    private function userCanManageLampiran(
        Request $request,
        BastKegiatan $bastKegiatan,
    ): bool {
        return app(BastAccessService::class)
            ->canManageLampiran($request, $bastKegiatan);
    }

    private function userCanAccessBast(
        Request $request,
        Bast $bast,
    ): bool {
        return app(BastAccessService::class)->canAccess($request, $bast);
    }

    private function canAccessSensusMode(
        ?User $user,
        ?int $tahunAnggaran = null,
    ): bool {
        return app(BastAccessService::class)
            ->canAccessSensusMode($user, $tahunAnggaran);
    }
}
