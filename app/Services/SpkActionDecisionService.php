<?php

namespace App\Services;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use Illuminate\Support\Collection;

class SpkActionDecisionService
{
    /**
     * Build shared SPK action decisions for each petugas in a month.
     *
     * @return Collection<int, array{petugas_id:int,has_addendum:bool,final_action:string,should_regenerate:bool,should_addendum:bool}>
     */
    public function resolveForMonth(int $tahun, int $bulan): Collection
    {
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $allPeriodeInMonth = PeriodeAlokasi::query()
            ->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return collect();
        }

        $allAlokasi = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q): void {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query): void {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi): bool {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        if ($allAlokasi->isEmpty()) {
            return collect();
        }

        return $allAlokasi->groupBy('petugas_id')
            ->map(function (Collection $alokasiGroup) use ($tahun, $bulanFormatted) {
                $firstAlokasi = $alokasiGroup->first();

                if (! $firstAlokasi) {
                    return null;
                }

                $petugasId = (int) $firstAlokasi->petugas_id;

                $effectiveAlokasiByKegiatan = $this->getEffectiveAlokasiByKegiatan($alokasiGroup);
                $currentAllocationIds = $alokasiGroup
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->values();
                $existingAllocationIds = $this->getExistingOriginalAllocationIdsForPetugas($petugasId, $tahun, (int) $bulanFormatted);

                $candidateOriginalSpks = Spk::query()
                    ->where('petugas_id', $petugasId)
                    ->where('addendum_number', 0)
                    ->where(function ($query) use ($tahun, $bulanFormatted): void {
                        $query->whereYear('tanggal_spk', '<', $tahun)
                            ->orWhere(function ($query) use ($tahun, $bulanFormatted): void {
                                $query->whereYear('tanggal_spk', $tahun)
                                    ->whereRaw("LPAD(CAST(MONTH(tanggal_spk) AS UNSIGNED), 2, '0') <= ?", [$bulanFormatted]);
                            });
                    })
                    ->orderByDesc('tanggal_spk')
                    ->orderByDesc('created_at')
                    ->get();

                $existingSpk = $this->resolveBestMatchingOriginalSpk($candidateOriginalSpks, $currentAllocationIds);

                $hasExistingAddendum = $existingSpk
                    ? Spk::query()
                        ->where('parent_spk_id', $existingSpk->id)
                        ->where('addendum_number', '>', 0)
                        ->exists()
                    : false;

                if (! $existingSpk) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => false,
                        'final_action' => 'generate_pk',
                        'should_regenerate' => false,
                        'should_addendum' => false,
                    ];
                }

                $delta = $this->analyzeAllocationDeltaForPetugas(
                    $petugasId,
                    $bulanFormatted,
                    $tahun,
                    'original_spk',
                );

                $hasMissingAllocationIds = $existingAllocationIds->isNotEmpty()
                    && $currentAllocationIds->diff($existingAllocationIds)->isNotEmpty();

                $shouldRegenerate = ! $hasExistingAddendum
                    && $hasMissingAllocationIds
                    && $delta['is_allocation_incomplete']
                    && $delta['has_new_kegiatan_added']
                    && ! $delta['has_allocation_change'];

                $latestDocument = Spk::query()
                    ->where('petugas_id', $petugasId)
                    ->where(function ($q) use ($existingSpk): void {
                        $q->where('id', $existingSpk->id)
                            ->orWhere('parent_spk_id', $existingSpk->id);
                    })
                    ->orderBy('addendum_number', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (! $latestDocument) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => $hasExistingAddendum,
                        'final_action' => $shouldRegenerate ? 'regenerate_pk' : 'no_action',
                        'should_regenerate' => $shouldRegenerate,
                        'should_addendum' => false,
                    ];
                }

                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi): float {
                    return (float) ($alokasi->total_honor ?? 0) + (float) ($alokasi->total_honor_listing ?? 0);
                });

                if ($currentTotalHonor <= 0) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => $hasExistingAddendum,
                        'final_action' => 'no_action',
                        'should_regenerate' => false,
                        'should_addendum' => false,
                    ];
                }

                $currentSnapshot = $effectiveAlokasiByKegiatan
                    ->mapWithKeys(function ($alokasi, $kegiatanId): array {
                        return [
                            (int) $kegiatanId => [
                                'alokasi_id' => (int) ($alokasi->id ?? 0),
                                'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                                'peran' => $alokasi?->peran,
                                'jumlah_satuan' => (int) ($alokasi->jumlah_satuan ?? 0),
                                'jumlah_satuan_listing' => (int) ($alokasi->jumlah_satuan_listing ?? 0),
                                'total_honor' => (float) ($alokasi->total_honor ?? 0),
                                'total_honor_listing' => (float) ($alokasi->total_honor_listing ?? 0),
                            ],
                        ];
                    })
                    ->all();

                $hasMeaningfulPerubahanChange = $this->detectMeaningfulPerubahanChange($alokasiGroup);

                $isChangeAlreadyCovered = false;
                if ($hasExistingAddendum && $latestDocument->addendum_number > 0) {
                    $documentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
                        $petugasId,
                        $latestDocument,
                        $bulanFormatted,
                        $tahun,
                    );

                    $isChangeAlreadyCovered = $this->snapshotsMatch($documentSnapshot, $currentSnapshot);
                }

                $hasUncoveredPostAddendumChange = $hasExistingAddendum
                    && $delta['has_new_kegiatan_added']
                    && ! $isChangeAlreadyCovered;

                $isMeaningfulCurrentRevision = (
                    $hasMeaningfulPerubahanChange
                    || (bool) ($delta['has_allocation_change'] ?? false)
                    || $hasUncoveredPostAddendumChange
                ) && ! $isChangeAlreadyCovered;

                $hasReplacementChange = $hasMeaningfulPerubahanChange
                    || ($delta['has_allocation_change'] ?? false);
                $hasUncoveredReplacementChange = $hasReplacementChange && ! $isChangeAlreadyCovered;
                $hasUncoveredNewAllocation = (bool) ($delta['has_new_kegiatan_added'] ?? false)
                    && ! $isChangeAlreadyCovered;

                $finalAction = $this->determineFinalAction(
                    hasExistingSpk: true,
                    hasExistingAddendum: $hasExistingAddendum,
                    hasReplacementChange: $hasUncoveredReplacementChange,
                    hasNewAllocation: $hasUncoveredNewAllocation,
                    hasUncoveredPostAddendumChange: $hasUncoveredPostAddendumChange,
                    isChangeAlreadyCovered: $isChangeAlreadyCovered,
                );

                return [
                    'petugas_id' => $petugasId,
                    'has_addendum' => $hasExistingAddendum,
                    'final_action' => $finalAction,
                    'should_regenerate' => $finalAction === 'regenerate_pk',
                    'should_addendum' => in_array($finalAction, ['generate_addendum', 'regenerate_addendum'], true),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function resolveRegenerateCandidatesForMonth(int $tahun, int $bulan): Collection
    {
        return $this->resolveForMonth($tahun, $bulan)
            ->filter(fn (array $item): bool => (bool) ($item['should_regenerate'] ?? false))
            ->pluck('petugas_id')
            ->map(static fn ($petugasId): int => (int) $petugasId)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, array{petugas_id:int,has_addendum:bool}>
     */
    public function resolveAddendumCandidatesForMonth(int $tahun, int $bulan): Collection
    {
        return $this->resolveForMonth($tahun, $bulan)
            ->filter(fn (array $item): bool => in_array($item['final_action'] ?? null, ['generate_addendum', 'regenerate_addendum'], true))
            ->map(function (array $item): array {
                return [
                    'petugas_id' => (int) ($item['petugas_id'] ?? 0),
                    'has_addendum' => (bool) ($item['has_addendum'] ?? false),
                    'final_action' => (string) ($item['final_action'] ?? 'no_action'),
                ];
            })
            ->filter()
            ->values();
    }

    public function determineFinalAction(
        bool $hasExistingSpk,
        bool $hasExistingAddendum,
        bool $hasReplacementChange,
        bool $hasNewAllocation,
        bool $hasUncoveredPostAddendumChange,
        bool $isChangeAlreadyCovered = false,
    ): string {
        if (! $hasExistingSpk) {
            return 'generate_pk';
        }

        if ($isChangeAlreadyCovered) {
            return 'no_action';
        }

        if ($hasExistingAddendum) {
            if ($hasUncoveredPostAddendumChange || $hasNewAllocation) {
                return 'regenerate_addendum';
            }

            if ($hasReplacementChange) {
                return 'generate_addendum';
            }

            return 'no_action';
        }

        if ($hasReplacementChange) {
            return 'generate_addendum';
        }

        if ($hasNewAllocation) {
            return 'regenerate_pk';
        }

        return 'no_action';
    }

    /**
     * @param  Collection<int, Spk>  $candidateOriginalSpks
     */
    private function resolveBestMatchingOriginalSpk(Collection $candidateOriginalSpks, Collection $currentAllocationIds): ?Spk
    {
        if ($candidateOriginalSpks->isEmpty()) {
            return null;
        }

        return $candidateOriginalSpks
            ->sortByDesc(function (Spk $spk) use ($currentAllocationIds): string {
                $candidateAllocationIds = collect($spk->alokasi_petugas_ids ?? [$spk->alokasi_petugas_id])
                    ->filter()
                    ->map(static fn ($id): int => (int) $id);

                $overlapCount = $candidateAllocationIds->intersect($currentAllocationIds)->count();

                return sprintf(
                    '%04d|%s|%s',
                    $overlapCount,
                    optional($spk->tanggal_spk)->format('Y-m-d') ?? '',
                    optional($spk->created_at)->format('Y-m-d H:i:s') ?? ''
                );
            })
            ->first();
    }

    public function getEffectiveAlokasiByKegiatan(Collection $alokasiGroup): Collection
    {
        return $alokasiGroup
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                return $this->resolvePkReferenceAllocation($kegiatanGroup);
            })
            ->filter(function ($alokasi) {
                return $alokasi && $this->isMeaningfulAllocation($alokasi);
            });
    }

    public function getEffectiveAddendumAlokasiForPetugas(int $petugasId, int $tahun, int $bulan): Collection
    {
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $allPeriodeInMonth = PeriodeAlokasi::query()
            ->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return collect();
        }

        $allAlokasi = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q): void {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->with(['petugas', 'periodeAlokasi.kegiatan.rateHonors.satuan'])
            ->get()
            ->filter(function ($alokasi): bool {
                return $alokasi->getEffectiveCombinedHonor() > 0;
            });

        if ($allAlokasi->isEmpty()) {
            return collect();
        }

        return $this->getEffectiveAlokasiByKegiatan($allAlokasi)->values();
    }

    private function resolvePkReferenceAllocation(Collection $kegiatanGroup): ?AlokasiPetugas
    {
        return $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim')
            ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'perubahan');
    }

    private function isMeaningfulAllocation(object $alokasi): bool
    {
        $unitSampelVolume = (int) ($alokasi->jumlah_unit_sampel ?? 0);
        $totalVolume = $unitSampelVolume > 0
            ? $unitSampelVolume
            : (int) ($alokasi->jumlah_satuan ?? 0) + (int) ($alokasi->jumlah_satuan_listing ?? 0);
        $totalHonor = (float) ($alokasi->total_honor ?? 0) + (float) ($alokasi->total_honor_listing ?? 0);

        return $totalVolume > 0 && $totalHonor > 0;
    }

    /**
     * @return array{has_new_kegiatan_added:bool,has_allocation_change:bool,has_perubahan_status:bool,is_allocation_incomplete:bool,has_honor_mismatch:bool}
     */
    private function analyzeAllocationDeltaForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        $baseQuery = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', (int) $bulanFormatted);

        if ($referenceType === 'same_month_original_spk') {
            $referenceDocument = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        } else {
            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->where(function ($query) use ($tahun, $bulanFormatted): void {
                    $query->whereYear('tanggal_spk', '<', $tahun)
                        ->orWhere(function ($query) use ($tahun, $bulanFormatted): void {
                            $query->whereYear('tanggal_spk', $tahun)
                                ->whereRaw("LPAD(CAST(MONTH(tanggal_spk) AS UNSIGNED), 2, '0') <= ?", [$bulanFormatted]);
                        });
                })
                ->orderByDesc('tanggal_spk')
                ->orderByDesc('created_at')
                ->first();
        }

        if (! $referenceDocument) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
                'has_perubahan_status' => false,
                'is_allocation_incomplete' => false,
                'has_honor_mismatch' => false,
            ];
        }

        $referenceSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
            $petugasId,
            $referenceDocument,
            $bulanFormatted,
            $tahun,
        );

        $currentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if (empty($currentSnapshot)) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
                'has_perubahan_status' => false,
                'is_allocation_incomplete' => false,
                'has_honor_mismatch' => false,
            ];
        }

        $currentTotalHonor = collect($currentSnapshot)->sum(function (array $item): float {
            return (float) ($item['total_honor'] ?? 0) + (float) ($item['total_honor_listing'] ?? 0);
        });
        $hasHonorMismatch = abs($currentTotalHonor - (float) $referenceDocument->nilai_kontrak) > 0.01;
        $referenceKeys = array_keys($referenceSnapshot);
        $currentKeys = array_keys($currentSnapshot);

        $newKegiatanKeys = array_values(array_diff($currentKeys, $referenceKeys));
        $hasNewKegiatanAdded = ! empty($newKegiatanKeys);

        $hasAllocationChange = false;
        $hasPerubahanStatus = false;

        foreach (array_intersect($referenceKeys, $currentKeys) as $kegiatanId) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $reference || ! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey((int) ($current['periode_alokasi_id'] ?? 0))
                ->value('status');

            if ($currentStatus === 'perubahan') {
                $hasPerubahanStatus = true;
            }

            if (
                $current['alokasi_id'] !== $reference['alokasi_id'] &&
                $currentStatus !== 'perubahan'
            ) {
                $hasNewKegiatanAdded = true;
            }

            if (
                $currentStatus === 'perubahan' &&
                (
                    $current['peran'] !== $reference['peran'] ||
                    $current['jumlah_satuan'] !== $reference['jumlah_satuan'] ||
                    $current['jumlah_satuan_listing'] !== $reference['jumlah_satuan_listing'] ||
                    abs($current['total_honor'] - $reference['total_honor']) > 0.01 ||
                    abs($current['total_honor_listing'] - $reference['total_honor_listing']) > 0.01
                )
            ) {
                $hasAllocationChange = true;
            }
        }

        foreach (array_diff($currentKeys, $referenceKeys) as $kegiatanId) {
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey((int) ($current['periode_alokasi_id'] ?? 0))
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

    /**
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */
    private function buildEffectiveAllocationSnapshotForPetugasFromDocument(
        int $petugasId,
        Spk $document,
        string $bulanFormatted,
        int $tahun,
    ): array {
        $alokasiIds = $document->alokasi_petugas_ids ?? [];
        if (empty($alokasiIds)) {
            $alokasiIds = [$document->alokasi_petugas_id];
        }

        $alokasi = AlokasiPetugas::query()
            ->whereIn('id', $alokasiIds)
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q): void {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($tahun): void {
                $q->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($alokasi->isEmpty()) {
            return [];
        }

        return $alokasi
            ->groupBy(function ($item) {
                return $item->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                $effective = $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'perubahan')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'disetujui')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'dikirim')
                    ?? $kegiatanGroup->first();

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
                    'alokasi_id' => (int) ($effective->id ?? 0),
                    'periode_alokasi_id' => (int) ($effective->periode_alokasi_id ?? 0),
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
            ->filter()
            ->sortKeys()
            ->all();
    }

    /**
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */
    private function buildEffectiveAllocationSnapshotForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        mixed $upToCreatedAt,
    ): array {
        $alokasiQuery = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q): void {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun, $upToCreatedAt): void {
                $q->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'perubahan'])
                    ->whereHas('kegiatan', fn ($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));

                if ($upToCreatedAt) {
                    $q->where('created_at', '<=', $upToCreatedAt);
                }
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($alokasiQuery->isEmpty()) {
            return [];
        }

        return $alokasiQuery
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                $effective = $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'perubahan')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'disetujui')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'dikirim');

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
                    'alokasi_id' => (int) ($effective->id ?? 0),
                    'periode_alokasi_id' => (int) ($effective->periode_alokasi_id ?? 0),
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
            ->filter()
            ->sortKeys()
            ->all();
    }

    /**
     * Detect if there's a meaningful change between perubahan and direvisi allocations.
     */
    private function detectMeaningfulPerubahanChange(Collection $alokasiGroup): bool
    {
        $byKegiatan = $alokasiGroup->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi?->kegiatan_id;
        });

        foreach ($byKegiatan as $kegiatanAlokasi) {
            $perubahan = $kegiatanAlokasi->first(function ($alokasi) {
                return ($alokasi->periodeAlokasi?->status ?? '') === 'perubahan';
            });

            if (! $perubahan) {
                continue;
            }

            $direvisi = $kegiatanAlokasi->first(function ($alokasi) {
                return ($alokasi->periodeAlokasi?->status ?? '') === 'direvisi';
            });

            $reference = $direvisi
                ?? $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'disetujui')
                ?? $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim');

            if (! $reference) {
                continue;
            }

            if (
                $perubahan->peran !== $reference->peran ||
                (int) ($perubahan->jumlah_satuan ?? 0) !== (int) ($reference->jumlah_satuan ?? 0) ||
                (int) ($perubahan->jumlah_satuan_listing ?? 0) !== (int) ($reference->jumlah_satuan_listing ?? 0) ||
                abs((float) ($perubahan->total_honor ?? 0) - (float) ($reference->total_honor ?? 0)) > 0.01 ||
                abs((float) ($perubahan->total_honor_listing ?? 0) - (float) ($reference->total_honor_listing ?? 0)) > 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, int>
     */
    private function getExistingOriginalAllocationIdsForPetugas(int $petugasId, int $tahun, int $bulan): Collection
    {
        return Spk::query()
            ->where('petugas_id', $petugasId)
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->get()
            ->flatMap(function (Spk $spk): Collection {
                $alokasiIds = $spk->alokasi_petugas_ids ?? [];

                if (empty($alokasiIds)) {
                    $alokasiIds = [$spk->alokasi_petugas_id];
                }

                return collect($alokasiIds)->map(static fn ($alokasiId): int => (int) $alokasiId);
            })
            ->unique()
            ->values();
    }

    /**
     * Check if two allocation snapshots match (have same effective values).
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot1
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot2
     */
    private function snapshotsMatch(array $snapshot1, array $snapshot2): bool
    {
        $keys1 = array_keys($snapshot1);
        $keys2 = array_keys($snapshot2);
        sort($keys1);
        sort($keys2);
        if ($keys1 !== $keys2) {
            return false;
        }

        foreach ($snapshot1 as $kegiatanId => $data1) {
            $data2 = $snapshot2[$kegiatanId] ?? null;
            if (! $data2) {
                return false;
            }

            if (
                $data1['peran'] !== $data2['peran'] ||
                $data1['jumlah_satuan'] !== $data2['jumlah_satuan'] ||
                $data1['jumlah_satuan_listing'] !== $data2['jumlah_satuan_listing'] ||
                abs($data1['total_honor'] - $data2['total_honor']) > 0.01 ||
                abs($data1['total_honor_listing'] - $data2['total_honor_listing']) > 0.01
            ) {
                return false;
            }
        }

        return true;
    }
}
