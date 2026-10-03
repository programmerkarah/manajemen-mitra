<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Spk;
use App\Services\Spk\SpkChangeDetectionService;
use DateTimeInterface;
use Illuminate\Support\Collection;

trait SpkChangeDetectionSupport
{
    private function hasNewKegiatanAfterSpk(
        int $tahun,
        int $bulan,
        $monthPeriodes,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->hasNewKegiatanAfterSpk($tahun, $bulan);
    }

    private function hasNewRevisionAfterAddendum(
        int $tahun,
        int $bulan,
        iterable $monthPeriodes,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->hasNewRevisionAfterAddendum($monthPeriodes);
    }

    private function hasIncompleteAddendum(
        int $tahun,
        int $bulan,
        $monthPeriodes,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->hasIncompleteAddendum($tahun, $bulan);
    }

    private function hasAddendumChanges(
        int $tahun,
        int $bulan,
        $monthPeriodes,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->hasAddendumChanges($tahun, $bulan);
    }

    private function analyzeAllocationDeltaForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        return app(SpkChangeDetectionService::class)
            ->analyzeAllocationDelta(
                $petugasId,
                $bulanFormatted,
                $tahun,
                $referenceType,
            );
    }

    private function buildEffectiveAllocationSnapshotForPetugasFromDocument(
        int $petugasId,
        Spk $document,
        string $bulanFormatted,
        int $tahun,
    ): array {
        return app(SpkChangeDetectionService::class)
            ->snapshotFromDocument(
                $petugasId,
                $document,
                $tahun,
            );
    }

    private function detectMeaningfulPerubahanChange(
        Collection $alokasiGroup,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->detectMeaningfulPerubahanChange($alokasiGroup);
    }

    private function snapshotsMatch(
        array $snapshot1,
        array $snapshot2,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->snapshotsMatch($snapshot1, $snapshot2);
    }

    private function hasAllocationDeltaAfterReferenceForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $referenceCreatedAt,
    ): bool {
        return app(SpkChangeDetectionService::class)
            ->hasAllocationDeltaAfterReference(
                $petugasId,
                $bulanFormatted,
                $tahun,
                $referenceCreatedAt,
            );
    }

    private function buildEffectiveAllocationSnapshotForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $upToCreatedAt,
    ): array {
        return app(SpkChangeDetectionService::class)
            ->snapshotForPetugas(
                $petugasId,
                $bulanFormatted,
                $tahun,
                $upToCreatedAt,
            );
    }
}
