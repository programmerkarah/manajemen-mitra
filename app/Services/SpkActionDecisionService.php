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
                    ->orWhere('total_honor_listing', '>', 0)
                    ->orWhere('estimasi_honor_partial', '>', 0)
                    ->orWhere('estimasi_honor_partial_listing', '>', 0);
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
                if ($effectiveAlokasiByKegiatan->isEmpty()) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => false,
                        'final_action' => 'no_action',
                        'should_regenerate' => false,
                        'should_addendum' => false,
                    ];
                }

                $currentAllocationIds = $effectiveAlokasiByKegiatan
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->values();
                // Include historical rows in this activity month as document
                // linkage candidates. A generated PK may still point to the
                // old `direvisi` ID while the current effective allocation is
                // its newer `perubahan` replacement.
                $monthAllocationIds = $alokasiGroup
                    ->pluck('id')
                    ->filter()
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->values();

                $existingSpk = $this->resolveOriginalSpkForPetugasMonth(
                    $petugasId,
                    $tahun,
                    (int) $bulanFormatted,
                    $monthAllocationIds,
                    $currentAllocationIds,
                );

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

                $latestDocument = Spk::query()
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
                        'final_action' => 'no_action',
                        'should_regenerate' => false,
                        'should_addendum' => false,
                    ];
                }

                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi): float {
                    return method_exists($alokasi, 'getEffectiveCombinedHonor')
                        ? (float) $alokasi->getEffectiveCombinedHonor()
                        : (float) ($alokasi->total_honor ?? 0) + (float) ($alokasi->total_honor_listing ?? 0);
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

                $changes = $this->classifyChangesAgainstDocument(
                    $petugasId,
                    $latestDocument,
                    $effectiveAlokasiByKegiatan,
                );
                $isChangeAlreadyCovered = ! $changes['has_replacement'] && ! $changes['has_new'];

                $finalAction = $this->determineFinalAction(
                    hasExistingSpk: true,
                    hasExistingAddendum: $hasExistingAddendum,
                    hasReplacementChange: $changes['has_replacement'],
                    hasNewAllocation: $changes['has_new'],
                    hasUncoveredPostAddendumChange: $hasExistingAddendum
                        && ($changes['has_replacement'] || $changes['has_new']),
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

    /**
     * Resolve the original PK through its allocation snapshot. This method is
     * shared by Index, Generate, and Addendum so all entry points identify the
     * same document without relying on tanggal_spk.
     */
    public function resolveOriginalSpkForPetugasMonth(
        int $petugasId,
        int $tahun,
        int $bulan,
        ?Collection $monthAllocationIds = null,
        ?Collection $currentAllocationIds = null,
    ): ?Spk {
        if ($monthAllocationIds === null) {
            $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
            $periodeIds = PeriodeAlokasi::query()
                ->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                ->where('tahun', $tahun)
                ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
                ->pluck('id');

            $monthAllocationIds = AlokasiPetugas::query()
                ->whereIn('periode_alokasi_id', $periodeIds)
                ->where('petugas_id', $petugasId)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values();
        }

        if ($monthAllocationIds->isEmpty()) {
            return null;
        }

        $candidateOriginalSpks = Spk::query()
            ->where(function ($query) use ($petugasId): void {
                $query->where('petugas_id', $petugasId)
                    ->orWhereNull('petugas_id');
            })
            ->where(function ($query): void {
                $query->where('addendum_number', 0)
                    ->orWhereNull('addendum_number');
            })
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Spk $spk): bool => $this->getDocumentAllocationIds($spk)
                ->intersect($monthAllocationIds)
                ->isNotEmpty())
            ->values();

        return $this->resolveBestMatchingOriginalSpk(
            $candidateOriginalSpks,
            $currentAllocationIds ?? $monthAllocationIds,
        );
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
            // A replacement always creates the next numbered addendum. When a
            // replacement and a new allocation arrive together, replacement
            // takes priority and the resulting addendum snapshots both.
            if ($hasReplacementChange) {
                return 'generate_addendum';
            }

            // Re-generate Addendum is reserved for a pure new allocation after
            // at least one addendum already exists.
            if ($hasNewAllocation || $hasUncoveredPostAddendumChange) {
                return 'regenerate_addendum';
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
     * Compare the current effective allocation IDs with the snapshot stored in
     * the last document. A new ID is a replacement only when it is a
     * `perubahan` row for a kegiatan already represented by that snapshot.
     * Otherwise it is a genuine new allocation.
     *
     * @return array{has_new:bool,has_replacement:bool,new_ids:array<int,int>,replacement_ids:array<int,int>,summaries:array<int,string>}
     */
    private function classifyChangesAgainstDocument(
        int $petugasId,
        Spk $document,
        Collection $currentByKegiatan,
    ): array {
        $documentIds = $this->getDocumentAllocationIds($document);

        $documentByKegiatan = AlokasiPetugas::query()
            ->whereIn('id', $documentIds)
            ->where('petugas_id', $petugasId)
            ->with('periodeAlokasi:id,kegiatan_id,status')
            ->get()
            ->filter(fn (AlokasiPetugas $alokasi): bool => $alokasi->periodeAlokasi !== null)
            ->keyBy(fn (AlokasiPetugas $alokasi): int => (int) $alokasi->periodeAlokasi->kegiatan_id);

        $newIds = [];
        $replacementIds = [];
        $summaries = [];

        foreach ($currentByKegiatan as $kegiatanId => $current) {
            $currentId = (int) $current->id;
            if ($documentIds->contains($currentId)) {
                continue;
            }

            $status = (string) ($current->periodeAlokasi?->status ?? '');
            $hasDocumentAllocationForKegiatan = $documentByKegiatan->has((int) $kegiatanId);

            if ($status === 'perubahan' && $hasDocumentAllocationForKegiatan) {
                $documentAllocation = $documentByKegiatan->get((int) $kegiatanId);

                // A replacement ID by itself is not a meaningful amendment.
                // Only volume and/or honor deltas create an Addendum candidate.
                if ($this->hasMeaningfulVolumeOrHonorDelta($documentAllocation, $current)) {
                    $replacementIds[] = $currentId;
                    $oldValues = $this->contractualAllocationValues($documentAllocation);
                    $newValues = $this->contractualAllocationValues($current);
                    $deltaParts = [];

                    if ($oldValues['volume'] !== $newValues['volume']) {
                        $deltaParts[] = "volume {$oldValues['volume']} → {$newValues['volume']}";
                    }

                    if (abs($oldValues['honor'] - $newValues['honor']) > 0.01) {
                        $deltaParts[] = 'total honor '.$this->formatRupiah($oldValues['honor'])
                            .' → '.$this->formatRupiah($newValues['honor']);
                    }

                    $kegiatanName = (string) ($current->periodeAlokasi?->kegiatan?->nama_kegiatan ?? 'Kegiatan');
                    $summaries[] = 'Perubahan '.$kegiatanName.': '.implode('; ', $deltaParts).'.';
                }
            } else {
                $newIds[] = $currentId;
                $newValues = $this->contractualAllocationValues($current);
                $kegiatanName = (string) ($current->periodeAlokasi?->kegiatan?->nama_kegiatan ?? 'Kegiatan');
                $summaries[] = 'Penambahan kegiatan '.$kegiatanName
                    .": volume {$newValues['volume']}; total honor "
                    .$this->formatRupiah($newValues['honor']).'.';
            }
        }

        return [
            'has_new' => $newIds !== [],
            'has_replacement' => $replacementIds !== [],
            'new_ids' => $newIds,
            'replacement_ids' => $replacementIds,
            'summaries' => $summaries,
        ];
    }

    /**
     * Build UI-ready changes against the latest processed document snapshot.
     * This covers initial Addendum, numbered Addendum, and Re-generate
     * Addendum without reimplementing delta rules in the controller.
     *
     * @return array<int, string>
     */
    public function buildChangeSummariesForPetugasMonth(int $petugasId, int $tahun, int $bulan): array
    {
        $originalSpk = $this->resolveOriginalSpkForPetugasMonth($petugasId, $tahun, $bulan);
        if (! $originalSpk) {
            return [];
        }

        $latestDocument = Spk::query()
            ->where(function ($query) use ($originalSpk): void {
                $query->whereKey($originalSpk->id)
                    ->orWhere('parent_spk_id', $originalSpk->id);
            })
            ->orderByDesc('addendum_number')
            ->orderByDesc('created_at')
            ->first();

        if (! $latestDocument) {
            return [];
        }

        $currentByKegiatan = $this->getEffectiveAddendumAlokasiForPetugas($petugasId, $tahun, $bulan)
            ->keyBy(fn (AlokasiPetugas $allocation): int => (int) $allocation->periodeAlokasi->kegiatan_id);

        if ($currentByKegiatan->isEmpty()) {
            return [];
        }

        $changes = $this->classifyChangesAgainstDocument(
            $petugasId,
            $latestDocument,
            $currentByKegiatan,
        );

        return $changes['summaries'];
    }

    private function formatRupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    /**
     * Determine whether a replacement changes the contractual values.
     * Identity, period row, status, and role changes are deliberately ignored
     * when effective volume and honor remain identical.
     */
    private function hasMeaningfulVolumeOrHonorDelta(
        AlokasiPetugas $documentAllocation,
        AlokasiPetugas $currentAllocation,
    ): bool {
        $documentValues = $this->contractualAllocationValues($documentAllocation);
        $currentValues = $this->contractualAllocationValues($currentAllocation);

        return $documentValues['volume'] !== $currentValues['volume']
            || abs($documentValues['honor'] - $currentValues['honor']) > 0.01;
    }

    /**
     * Normalize storage variants into contractual totals. Some allocation
     * types store volume in jumlah_unit_sampel while others use regular and
     * listing columns. Moving the same value between those columns must not
     * create a false Addendum candidate.
     *
     * @return array{volume:int,honor:float}
     */
    private function contractualAllocationValues(AlokasiPetugas $allocation): array
    {
        $regularVolume = method_exists($allocation, 'getEffectiveJumlahSatuan')
            ? (int) $allocation->getEffectiveJumlahSatuan()
            : (int) ($allocation->jumlah_satuan ?? 0);
        $listingVolume = method_exists($allocation, 'getEffectiveJumlahSatuanListing')
            ? (int) $allocation->getEffectiveJumlahSatuanListing()
            : (int) ($allocation->jumlah_satuan_listing ?? 0);
        $unitSampelVolume = (int) ($allocation->jumlah_unit_sampel ?? 0);

        $regularHonor = method_exists($allocation, 'getEffectiveTotalHonor')
            ? (float) $allocation->getEffectiveTotalHonor()
            : (float) ($allocation->total_honor ?? 0);
        $listingHonor = method_exists($allocation, 'getEffectiveTotalHonorListing')
            ? (float) $allocation->getEffectiveTotalHonorListing()
            : (float) ($allocation->total_honor_listing ?? 0);

        return [
            'volume' => $unitSampelVolume > 0
                ? $unitSampelVolume
                : $regularVolume + $listingVolume,
            'honor' => $regularHonor + $listingHonor,
        ];
    }

    /**
     * Normalize the stored snapshot regardless of whether the model returns an
     * array, Collection, JSON string, scalar legacy value, or null.
     *
     * @return Collection<int, int>
     */
    private function getDocumentAllocationIds(Spk $document): Collection
    {
        $rawIds = $document->alokasi_petugas_ids;

        if ($rawIds instanceof Collection) {
            $ids = $rawIds;
        } elseif (is_array($rawIds)) {
            $ids = collect($rawIds);
        } elseif (is_string($rawIds) && trim($rawIds) !== '') {
            $decoded = json_decode($rawIds, true);
            $ids = collect(is_array($decoded) ? $decoded : [$rawIds]);
        } elseif ($rawIds !== null && $rawIds !== '') {
            $ids = collect([$rawIds]);
        } else {
            $ids = collect();
        }

        // The primary FK is part of the document even when a malformed or
        // incomplete legacy JSON snapshot is present. Always include it.
        if ($document->alokasi_petugas_id) {
            $ids->push($document->alokasi_petugas_id);
        }

        return $ids
            ->flatten()
            ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
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
                $candidateAllocationIds = $this->getDocumentAllocationIds($spk);

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
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan'])
            ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
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
        // A perubahan row is the active replacement. The direvisi/dikirim row
        // remains history and must never shadow it in the current snapshot.
        return $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'perubahan')
            ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'disetujui')
            ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim');
    }

    private function isMeaningfulAllocation(object $alokasi): bool
    {
        $unitSampelVolume = (int) ($alokasi->jumlah_unit_sampel ?? 0);
        $totalVolume = $unitSampelVolume > 0
            ? $unitSampelVolume
            : (int) ($alokasi->jumlah_satuan ?? 0) + (int) ($alokasi->jumlah_satuan_listing ?? 0);
        $totalHonor = method_exists($alokasi, 'getEffectiveCombinedHonor')
            ? (float) $alokasi->getEffectiveCombinedHonor()
            : (float) ($alokasi->total_honor ?? 0) + (float) ($alokasi->total_honor_listing ?? 0);

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
