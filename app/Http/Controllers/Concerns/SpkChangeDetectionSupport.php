<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use App\Services\SensusEkonomiReplacementReadService;
use App\Services\SpkActionDecisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Vinkla\Hashids\Facades\Hashids;

trait SpkChangeDetectionSupport
{
    private function hasNewKegiatanAfterSpk(int $tahun, int $bulan, $monthPeriodes): bool
    {
        return $this->spkActionDecisionService->resolveRegenerateCandidatesForMonth($tahun, $bulan)->isNotEmpty();
    }

    private function hasNewRevisionAfterAddendum(int $tahun, int $bulan, iterable $monthPeriodes): bool
    {

        $latestAddendumCreatedAt = null;

        foreach ($monthPeriodes as $periode) {
            $latestAddendum = $periode->spk()
                ->where('addendum_number', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestAddendum && (! $latestAddendumCreatedAt || $latestAddendum->created_at > $latestAddendumCreatedAt)) {
                $latestAddendumCreatedAt = $latestAddendum->created_at;
            }
        }

        if (! $latestAddendumCreatedAt) {
            return false;
        }

        foreach ($monthPeriodes as $periode) {
            if (! in_array($periode->status, ['perubahan', 'direvisi'])) {
                continue;
            }

            $nonOrganikAlokasi = $periode->alokasiPetugas()
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->where(function ($query) {
                    $query->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->get();

            foreach ($nonOrganikAlokasi as $alokasi) {
                $hasAddendum = Spk::where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', '>', 0)
                    ->exists();

                $isLaterThanAddendum = $periode->updated_at && $periode->updated_at > $latestAddendumCreatedAt;

                if (! $hasAddendum || $isLaterThanAddendum) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasIncompleteAddendum(int $tahun, int $bulan, $monthPeriodes): bool
    {
        $candidateSummary = $this->spkActionDecisionService->resolveAddendumCandidatesForMonth($tahun, $bulan);

        return $candidateSummary->contains(fn (array $item): bool => ! (bool) ($item['has_addendum'] ?? false));
    }

    private function hasAddendumChanges(int $tahun, int $bulan, $monthPeriodes): bool
    {
        $candidateSummary = $this->spkActionDecisionService->resolveAddendumCandidatesForMonth($tahun, $bulan);

        if ($candidateSummary->isEmpty()) {
            return false;
        }

        if ($candidateSummary->contains(fn (array $item): bool => (bool) ($item['has_addendum'] ?? false))) {
            return true;
        }

        return false;
    }

    private function analyzeAllocationDeltaForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        // Scope reference document to this specific month/year
        $baseQuery = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', (int) $bulanFormatted);

        if ($referenceType === 'latest_addendum') {
            // Find original SPK for this month first, then find latest addendum via parent_spk_id.
            // This correctly handles addendums whose tanggal_spk is outside the contract month.
            $originalSpkForMonth = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', '>', 0)
                ->where(function ($q) use ($originalSpkForMonth, $tahun, $bulanFormatted) {
                    $q->where(function ($q2) use ($tahun, $bulanFormatted) {
                        $q2->whereYear('tanggal_spk', $tahun)
                            ->whereMonth('tanggal_spk', (int) $bulanFormatted);
                    });
                    if ($originalSpkForMonth) {
                        $q->orWhere('parent_spk_id', $originalSpkForMonth->id);
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
            // Original SPK is NOT month-scoped: cross-month revisions reference a prior month's SPK.
            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
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
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($tahun) {
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
                // Apply same priority as buildEffectiveAllocationSnapshotForPetugas:
                // perubahan > disetujui > dikirim
                // This prevents a perpetual delta loop when a document stores both
                // dikirim and perubahan alokasi IDs for the same kegiatan.
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

    private function detectMeaningfulPerubahanChange(Collection $alokasiGroup): bool
    {
        // Group allocations by kegiatan_id
        $byKegiatan = $alokasiGroup->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi?->kegiatan_id;
        });

        foreach ($byKegiatan as $kegiatanAlokasi) {
            // Find perubahan allocation for this kegiatan
            $perubahan = $kegiatanAlokasi->first(function ($alokasi) {
                return ($alokasi->periodeAlokasi?->status ?? '') === 'perubahan';
            });

            if (! $perubahan) {
                continue;
            }

            // Use PK reference only from dikirim, then perubahan
            $reference = $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim')
                ?? $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'perubahan');

            if (! $reference) {
                // perubahan exists but no reference - this is a new kegiatan via perubahan
                // which should also trigger addendum
                continue;
            }

            // Compare perubahan vs reference (dikirim/perubahan only)
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

    private function snapshotsMatch(array $snapshot1, array $snapshot2): bool
    {
        // Different kegiatan sets = not matching (compare sorted keys to ignore order)
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

            // Compare effective values (ignore alokasi_id and periode_alokasi_id which may differ)
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

    private function hasAllocationDeltaAfterReferenceForPetugas(int $petugasId, string $bulanFormatted, int $tahun, DateTimeInterface|string|null $referenceCreatedAt): bool
    {
        $referenceSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            $referenceCreatedAt,
        );

        $currentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if (empty($currentSnapshot)) {
            return false;
        }

        if (array_keys($referenceSnapshot) !== array_keys($currentSnapshot)) {
            return true;
        }

        foreach ($currentSnapshot as $kegiatanId => $current) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;

            if (! $reference) {
                return true;
            }

            if (
                $current['alokasi_id'] !== $reference['alokasi_id'] ||
                $current['peran'] !== $reference['peran'] ||
                $current['jumlah_satuan'] !== $reference['jumlah_satuan'] ||
                $current['jumlah_satuan_listing'] !== $reference['jumlah_satuan_listing'] ||
                abs($current['total_honor'] - $reference['total_honor']) > 0.01 ||
                abs($current['total_honor_listing'] - $reference['total_honor_listing']) > 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    private function buildEffectiveAllocationSnapshotForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $upToCreatedAt,
    ): array {
        // Get all allocations for this petugas in this month (reference PK statuses only)
        $alokasiQuery = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun, $upToCreatedAt) {
                $q->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->whereHas('kegiatan', fn ($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));

                // When checking reference state, only get allocations that existed before
                if ($upToCreatedAt) {
                    $q->where('created_at', '<=', $upToCreatedAt);
                }
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($alokasiQuery->isEmpty()) {
            return [];
        }

        // Group by kegiatan and get effective allocation per kegiatan.
        // Priority: dikirim > perubahan.
        $snapshot = $alokasiQuery
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Apply priority: dikirim > perubahan
                $effective = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim')
                    ?? $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');

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

        return $snapshot;
    }
}
