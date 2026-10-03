<?php

namespace App\Http\Controllers\Concerns;

use App\Models\PeriodeAlokasi;
use App\Services\Spk\SpkScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait SpkScopeSupport
{
    private function getNextNomorUrut(int $tahun): int
    {
        return app(SpkScopeService::class)->nextSequence($tahun);
    }

    private function getNextNomorUrutForPeriode(
        PeriodeAlokasi $periode,
    ): int {
        return app(SpkScopeService::class)
            ->nextSequenceForPeriod($periode);
    }

    private function formatNomorSpkForPeriode(
        PeriodeAlokasi $periode,
        int $nomorUrut,
    ): string {
        return app(SpkScopeService::class)
            ->formatNumber($periode, $nomorUrut);
    }

    private function extractNomorUrut(string $nomorSpk): int
    {
        return app(SpkScopeService::class)->extractSequence($nomorSpk);
    }

    private function resolveDisplayNomorUrutSegment(
        string $nomorSpk,
        int $nomorUrutBase,
        ?string $fallbackSuffix = null,
    ): string {
        return app(SpkScopeService::class)->displaySequenceSegment(
            $nomorSpk,
            $nomorUrutBase,
            $fallbackSuffix,
        );
    }

    private function usesPeriodBasedSpkFlow(
        PeriodeAlokasi $periode,
    ): bool {
        return app(SpkScopeService::class)
            ->usesPeriodBasedFlow($periode);
    }

    private function resolveSpkScopePeriodeIds(
        PeriodeAlokasi $periode,
        array $statuses = [],
    ): Collection {
        return app(SpkScopeService::class)
            ->resolvePeriodIds($periode, $statuses);
    }

    private function hasDraftPeriodeInSpkScope(
        PeriodeAlokasi $periode,
    ): bool {
        return app(SpkScopeService::class)
            ->hasDraftPeriod($periode);
    }

    private function baseSpkScopeQuery(
        PeriodeAlokasi $periode,
    ): Builder {
        return app(SpkScopeService::class)->baseQuery($periode);
    }

    private function resolveSpkIndexGroupKey(
        PeriodeAlokasi $periode,
    ): string {
        return app(SpkScopeService::class)->indexGroupKey($periode);
    }

    private function resolveSpkIndexPrimaryPeriode(
        Collection $monthPeriodes,
    ): PeriodeAlokasi {
        return app(SpkScopeService::class)
            ->primaryPeriod($monthPeriodes);
    }

    private function resolveSpkIndexStatusPriority(string $status): int
    {
        return app(SpkScopeService::class)->statusPriority($status);
    }

    private function resolveSpkIndexDisplayLabel(
        PeriodeAlokasi $periode,
    ): string {
        return app(SpkScopeService::class)
            ->indexDisplayLabel($periode);
    }
}
