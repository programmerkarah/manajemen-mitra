<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Services\AlokasiPetugas\AlokasiPeriodService;
use Illuminate\Support\Collection;

trait AlokasiPetugasPeriodSupport
{
    private function resolvePeriodeDisplayBulan(
        PeriodeAlokasi $periode,
    ): string {
        return app(AlokasiPeriodService::class)
            ->displayMonth($periode);
    }

    private function sortAlokasiIndexData(
        Collection $items,
    ): Collection {
        return app(AlokasiPeriodService::class)
            ->sortIndexData($items);
    }

    private function sumEffectiveCombinedHonor(
        Collection $alokasiPetugas,
    ): float {
        return app(AlokasiPeriodService::class)
            ->sumEffectiveCombinedHonor($alokasiPetugas);
    }

    private function resolveEffectiveBudgetPeriode(
        Collection $periodesByMonth,
    ): ?PeriodeAlokasi {
        return app(AlokasiPeriodService::class)
            ->effectiveBudgetPeriod($periodesByMonth);
    }

    private function resolvePeriodeFilterBulans(
        PeriodeAlokasi $periode,
    ): array {
        return app(AlokasiPeriodService::class)
            ->filterMonths($periode);
    }

    private function resolveKegiatanFromPeriodeRoute(
        string $kegiatanRouteKey,
        int $tahun,
        string $bulan,
    ): Kegiatan {
        return app(AlokasiPeriodService::class)
            ->resolveKegiatanFromPeriodRoute(
                $kegiatanRouteKey,
                $tahun,
                $bulan,
            );
    }

    private function resolveBulanCandidates(string $bulan): array
    {
        return app(AlokasiPeriodService::class)
            ->monthCandidates($bulan);
    }

    private function validateSampleFrameAllocations(
        array $alokasiItems,
        Kegiatan $kegiatan,
    ): array {
        return app(AlokasiPeriodService::class)
            ->validateSampleFrameAllocations(
                $alokasiItems,
                $kegiatan,
            );
    }

    private function syncAlokasiFrameSampel(
        AlokasiPetugas $alokasiPetugas,
        array $frameIds,
    ): void {
        app(AlokasiPeriodService::class)
            ->syncFrameAllocations($alokasiPetugas, $frameIds);
    }

    private function mergeAlokasiRowsForStorage(
        array $alokasiItems,
    ): array {
        return app(AlokasiPeriodService::class)
            ->mergeRowsForStorage($alokasiItems);
    }

    protected function resolveKegiatanRouteBinding(
        string $kegiatanRouteKey,
    ): ?Kegiatan {
        return app(AlokasiPeriodService::class)
            ->resolveKegiatanBinding($kegiatanRouteKey);
    }

    protected function resolvePeriodeRouteBinding(
        string $kegiatanRouteKey,
    ): ?PeriodeAlokasi {
        return app(AlokasiPeriodService::class)
            ->resolvePeriodBinding($kegiatanRouteKey);
    }
}
