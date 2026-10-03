<?php

namespace App\Services\Spk;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use App\Services\SpkActionDecisionService;
use DateTimeInterface;
use Illuminate\Support\Collection;

class SpkChangeDetectionService
{
    public function __construct(
        private readonly SpkActionDecisionService $decisionService,
    ) {}

    public function hasNewKegiatanAfterSpk(
        int $tahun,
        int $bulan,
    ): bool {
        return $this->decisionService
            ->resolveRegenerateCandidatesForMonth($tahun, $bulan)
            ->isNotEmpty();
    }

    public function hasNewRevisionAfterAddendum(
        iterable $monthPeriodes,
    ): bool {
        $latestAddendumCreatedAt = null;

        foreach ($monthPeriodes as $periode) {
            $latestAddendum = $periode->spk()
                ->where('addendum_number', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if (
                $latestAddendum
                && (
                    ! $latestAddendumCreatedAt
                    || $latestAddendum->created_at
                        > $latestAddendumCreatedAt
                )
            ) {
                $latestAddendumCreatedAt = $latestAddendum->created_at;
            }
        }

        if (! $latestAddendumCreatedAt) {
            return false;
        }

        foreach ($monthPeriodes as $periode) {
            if (! in_array(
                $periode->status,
                ['perubahan', 'direvisi'],
                true,
            )) {
                continue;
            }

            $nonOrganikAlokasi = $periode->alokasiPetugas()
                ->whereHas(
                    'petugas',
                    fn ($query) => $query
                        ->where('jenis_petugas', 'non-organik'),
                )
                ->where(function ($query) {
                    $query
                        ->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->get();

            foreach ($nonOrganikAlokasi as $alokasi) {
                $hasAddendum = Spk::query()
                    ->where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', '>', 0)
                    ->exists();

                $isLaterThanAddendum =
                    $periode->updated_at
                    && $periode->updated_at > $latestAddendumCreatedAt;

                if (! $hasAddendum || $isLaterThanAddendum) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasIncompleteAddendum(
        int $tahun,
        int $bulan,
    ): bool {
        return $this->decisionService
            ->resolveAddendumCandidatesForMonth($tahun, $bulan)
            ->contains(
                fn (array $item): bool =>
                    ! (bool) ($item['has_addendum'] ?? false),
            );
    }

    public function hasAddendumChanges(
        int $tahun,
        int $bulan,
    ): bool {
        $summary = $this->decisionService
            ->resolveAddendumCandidatesForMonth($tahun, $bulan);

        if ($summary->isEmpty()) {
            return false;
        }

        return $summary->contains(
            fn (array $item): bool =>
                (bool) ($item['has_addendum'] ?? false),
        );
    }

    public function analyzeAllocationDelta(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        $baseQuery = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', (int) $bulanFormatted);

        if ($referenceType === 'latest_addendum') {
            $originalSpkForMonth = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', '>', 0)
                ->where(function ($query) use (
                    $originalSpkForMonth,
                    $tahun,
                    $bulanFormatted,
                ) {
                    $query->where(
                        function ($inner) use (
                            $tahun,
                            $bulanFormatted,
                        ) {
                            $inner
                                ->whereYear('tanggal_spk', $tahun)
                                ->whereMonth(
                                    'tanggal_spk',
                                    (int) $bulanFormatted,
                                );
                        },
                    );

                    if ($originalSpkForMonth) {
                        $query->orWhere(
                            'parent_spk_id',
                            $originalSpkForMonth->id,
                        );
                    }
                })
                ->orderBy('addendum_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        } elseif ($referenceType === 'same_month_original_spk') {
            $referenceDocument = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        } else {
            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        }

        if (! $referenceDocument) {
            return $this->emptyDelta();
        }

        $referenceSnapshot = $this->snapshotFromDocument(
            $petugasId,
            $referenceDocument,
            $tahun,
        );
        $currentSnapshot = $this->snapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if ($currentSnapshot === []) {
            return $this->emptyDelta();
        }

        $currentTotalHonor = collect($currentSnapshot)->sum(
            fn (array $item): float =>
                (float) ($item['total_honor'] ?? 0)
                + (float) ($item['total_honor_listing'] ?? 0),
        );

        $hasHonorMismatch = abs(
            $currentTotalHonor
            - (float) $referenceDocument->nilai_kontrak,
        ) > 0.01;

        $referenceKeys = array_keys($referenceSnapshot);
        $currentKeys = array_keys($currentSnapshot);
        $hasNewKegiatanAdded =
            array_diff($currentKeys, $referenceKeys) !== [];
        $hasAllocationChange = false;
        $hasPerubahanStatus = false;

        foreach (
            array_intersect($referenceKeys, $currentKeys)
            as $kegiatanId
        ) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $reference || ! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey(
                    (int) ($current['periode_alokasi_id'] ?? 0),
                )
                ->value('status');

            if ($currentStatus === 'perubahan') {
                $hasPerubahanStatus = true;
            }

            if (
                $current['alokasi_id'] !== $reference['alokasi_id']
                && $currentStatus !== 'perubahan'
            ) {
                $hasNewKegiatanAdded = true;
            }

            if (
                $currentStatus === 'perubahan'
                && $this->allocationValuesDiffer(
                    $reference,
                    $current,
                )
            ) {
                $hasAllocationChange = true;
            }
        }

        foreach (
            array_diff($currentKeys, $referenceKeys)
            as $kegiatanId
        ) {
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey(
                    (int) ($current['periode_alokasi_id'] ?? 0),
                )
                ->value('status');

            if ($currentStatus === 'perubahan') {
                $hasPerubahanStatus = true;
            }

            if ($currentStatus !== 'perubahan') {
                $hasNewKegiatanAdded = true;
            }
        }

        return [
            'has_new_kegiatan_added' => $hasNewKegiatanAdded,
            'has_allocation_change' => $hasAllocationChange,
            'has_perubahan_status' => $hasPerubahanStatus,
            'is_allocation_incomplete' => $hasNewKegiatanAdded,
            'has_honor_mismatch' => $hasHonorMismatch,
        ];
    }

    public function snapshotFromDocument(
        int $petugasId,
        Spk $document,
        int $tahun,
    ): array {
        $alokasiIds = $document->alokasi_petugas_ids ?? [];

        if ($alokasiIds === []) {
            $alokasiIds = [$document->alokasi_petugas_id];
        }

        $allocations = AlokasiPetugas::query()
            ->whereIn('id', $alokasiIds)
            ->where('petugas_id', $petugasId)
            ->whereHas(
                'petugas',
                fn ($query) => $query
                    ->where('jenis_petugas', 'non-organik'),
            )
            ->whereHas(
                'periodeAlokasi',
                fn ($query) => $query
                    ->where('tahun', $tahun)
                    ->whereIn(
                        'status',
                        [
                            'dikirim',
                            'disetujui',
                            'direvisi',
                            'perubahan',
                        ],
                    ),
            )
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($allocations->isEmpty()) {
            return [];
        }

        return $allocations
            ->groupBy(
                fn ($item) => $item->periodeAlokasi?->kegiatan_id,
            )
            ->map(function ($group) {
                $effective = $group->first(
                    fn ($item) =>
                        ($item->periodeAlokasi->status ?? '')
                        === 'perubahan',
                )
                    ?? $group->first(
                        fn ($item) =>
                            ($item->periodeAlokasi->status ?? '')
                            === 'disetujui',
                    )
                    ?? $group->first(
                        fn ($item) =>
                            ($item->periodeAlokasi->status ?? '')
                            === 'dikirim',
                    )
                    ?? $group->first();

                return $this->snapshotItem($effective);
            })
            ->filter()
            ->sortKeys()
            ->all();
    }

    public function detectMeaningfulPerubahanChange(
        Collection $alokasiGroup,
    ): bool {
        $byKegiatan = $alokasiGroup->groupBy(
            fn ($alokasi) =>
                $alokasi->periodeAlokasi?->kegiatan_id,
        );

        foreach ($byKegiatan as $kegiatanAlokasi) {
            $perubahan = $kegiatanAlokasi->first(
                fn ($alokasi) =>
                    ($alokasi->periodeAlokasi?->status ?? '')
                    === 'perubahan',
            );

            if (! $perubahan) {
                continue;
            }

            $reference = $kegiatanAlokasi->first(
                fn ($alokasi) =>
                    ($alokasi->periodeAlokasi?->status ?? '')
                    === 'dikirim',
            )
                ?? $kegiatanAlokasi->first(
                    fn ($alokasi) =>
                        ($alokasi->periodeAlokasi?->status ?? '')
                        === 'perubahan',
                );

            if (! $reference) {
                continue;
            }

            if (
                $perubahan->peran !== $reference->peran
                || (int) ($perubahan->jumlah_satuan ?? 0)
                    !== (int) ($reference->jumlah_satuan ?? 0)
                || (int) ($perubahan->jumlah_satuan_listing ?? 0)
                    !== (int) ($reference->jumlah_satuan_listing ?? 0)
                || abs(
                    (float) ($perubahan->total_honor ?? 0)
                    - (float) ($reference->total_honor ?? 0),
                ) > 0.01
                || abs(
                    (float) ($perubahan->total_honor_listing ?? 0)
                    - (float) ($reference->total_honor_listing ?? 0),
                ) > 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    public function snapshotsMatch(
        array $left,
        array $right,
    ): bool {
        $leftKeys = array_keys($left);
        $rightKeys = array_keys($right);
        sort($leftKeys);
        sort($rightKeys);

        if ($leftKeys !== $rightKeys) {
            return false;
        }

        foreach ($left as $kegiatanId => $leftData) {
            $rightData = $right[$kegiatanId] ?? null;

            if (! $rightData) {
                return false;
            }

            if ($this->allocationValuesDiffer($leftData, $rightData)) {
                return false;
            }
        }

        return true;
    }

    public function hasAllocationDeltaAfterReference(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $referenceCreatedAt,
    ): bool {
        $reference = $this->snapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            $referenceCreatedAt,
        );
        $current = $this->snapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if ($current === []) {
            return false;
        }

        if (array_keys($reference) !== array_keys($current)) {
            return true;
        }

        foreach ($current as $kegiatanId => $currentData) {
            $referenceData = $reference[$kegiatanId] ?? null;

            if (! $referenceData) {
                return true;
            }

            if (
                $currentData['alokasi_id']
                    !== $referenceData['alokasi_id']
                || $this->allocationValuesDiffer(
                    $referenceData,
                    $currentData,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    public function snapshotForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $upToCreatedAt,
    ): array {
        $query = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas(
                'petugas',
                fn ($builder) => $builder
                    ->where('jenis_petugas', 'non-organik'),
            )
            ->whereHas(
                'periodeAlokasi',
                function ($builder) use (
                    $bulanFormatted,
                    $tahun,
                    $upToCreatedAt,
                ) {
                    $builder
                        ->whereRaw(
                            "LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?",
                            [$bulanFormatted],
                        )
                        ->where('tahun', $tahun)
                        ->whereIn(
                            'status',
                            ['dikirim', 'perubahan'],
                        )
                        ->whereHas(
                            'kegiatan',
                            fn ($query) => $query
                                ->where(
                                    'jenis_kegiatan',
                                    '!=',
                                    'sensus',
                                ),
                        );

                    if ($upToCreatedAt) {
                        $builder->where(
                            'created_at',
                            '<=',
                            $upToCreatedAt,
                        );
                    }
                },
            )
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($query->isEmpty()) {
            return [];
        }

        return $query
            ->groupBy(
                fn ($allocation) =>
                    $allocation->periodeAlokasi?->kegiatan_id,
            )
            ->map(function ($group) {
                $effective = $group->first(
                    fn ($item) =>
                        $item->periodeAlokasi->status === 'dikirim',
                )
                    ?? $group->first(
                        fn ($item) =>
                            $item->periodeAlokasi->status
                            === 'perubahan',
                    );

                return $this->snapshotItem($effective);
            })
            ->filter()
            ->sortKeys()
            ->all();
    }

    private function snapshotItem(?object $allocation): ?array
    {
        if (
            ! $allocation
            || ! $this->isMeaningfulAllocation($allocation)
        ) {
            return null;
        }

        return [
            'alokasi_id' => (int) ($allocation->id ?? 0),
            'periode_alokasi_id' =>
                (int) ($allocation->periode_alokasi_id ?? 0),
            'peran' => $allocation->peran,
            'jumlah_satuan' =>
                (int) ($allocation->jumlah_satuan ?? 0),
            'jumlah_satuan_listing' =>
                (int) ($allocation->jumlah_satuan_listing ?? 0),
            'total_honor' =>
                (float) ($allocation->total_honor ?? 0),
            'total_honor_listing' =>
                (float) ($allocation->total_honor_listing ?? 0),
        ];
    }

    private function isMeaningfulAllocation(object $allocation): bool
    {
        $unitSampleVolume =
            (int) ($allocation->jumlah_unit_sampel ?? 0);
        $totalVolume = $unitSampleVolume > 0
            ? $unitSampleVolume
            : (int) ($allocation->jumlah_satuan ?? 0)
                + (int) ($allocation->jumlah_satuan_listing ?? 0);
        $totalHonor =
            (float) ($allocation->total_honor ?? 0)
            + (float) ($allocation->total_honor_listing ?? 0);

        return $totalVolume > 0 && $totalHonor > 0;
    }

    private function allocationValuesDiffer(
        array $reference,
        array $current,
    ): bool {
        return $current['peran'] !== $reference['peran']
            || $current['jumlah_satuan']
                !== $reference['jumlah_satuan']
            || $current['jumlah_satuan_listing']
                !== $reference['jumlah_satuan_listing']
            || abs(
                $current['total_honor']
                - $reference['total_honor'],
            ) > 0.01
            || abs(
                $current['total_honor_listing']
                - $reference['total_honor_listing'],
            ) > 0.01;
    }

    private function emptyDelta(): array
    {
        return [
            'has_new_kegiatan_added' => false,
            'has_allocation_change' => false,
            'has_perubahan_status' => false,
            'is_allocation_incomplete' => false,
            'has_honor_mismatch' => false,
        ];
    }
}
