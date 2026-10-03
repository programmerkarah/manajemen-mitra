<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasPeriodSupport
{
    private function resolvePeriodeDisplayBulan(PeriodeAlokasi $periode): string
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return Carbon::create()->month((int) $periode->bulan)->translatedFormat('F').' '.$periode->tahun;
        }

        $tanggalMulai = $periode->tanggal_mulai->copy()->startOfDay();
        $tanggalSelesai = $periode->tanggal_selesai->copy()->startOfDay();

        if ($tanggalMulai->format('Y-m') === $tanggalSelesai->format('Y-m')) {
            return $tanggalMulai->translatedFormat('F Y');
        }

        if ($tanggalMulai->year === $tanggalSelesai->year) {
            return $tanggalMulai->translatedFormat('F').' - '.$tanggalSelesai->translatedFormat('F Y');
        }

        return $tanggalMulai->translatedFormat('F Y').' - '.$tanggalSelesai->translatedFormat('F Y');
    }

    private function sortAlokasiIndexData(Collection $items): Collection
    {
        return $items->sort(function (array $left, array $right): int {
            $leftYear = (int) ($left['tahun'] ?? 0);
            $rightYear = (int) ($right['tahun'] ?? 0);

            if ($leftYear !== $rightYear) {
                return $rightYear <=> $leftYear;
            }

            $leftMonth = (int) ($left['bulan'] ?? 0);
            $rightMonth = (int) ($right['bulan'] ?? 0);

            if ($leftMonth !== $rightMonth) {
                return $rightMonth <=> $leftMonth;
            }

            $leftCreatedAt = $left['latest_created_at'] ?? null;
            $rightCreatedAt = $right['latest_created_at'] ?? null;

            if ($leftCreatedAt instanceof Carbon && $rightCreatedAt instanceof Carbon) {
                return $rightCreatedAt->getTimestamp() <=> $leftCreatedAt->getTimestamp();
            }

            return strcmp((string) $rightCreatedAt, (string) $leftCreatedAt);
        })->values();
    }

    private function sumEffectiveCombinedHonor(Collection $alokasiPetugas): float
    {
        return (float) $alokasiPetugas->sum(function (AlokasiPetugas $alokasi): float {
            return $alokasi->getEffectiveCombinedHonor();
        });
    }

    private function resolveEffectiveBudgetPeriode(Collection $periodesByMonth): ?PeriodeAlokasi
    {
        $activePeriodes = $periodesByMonth
            ->filter(function (PeriodeAlokasi $periode): bool {
                return in_array($periode->status, ['draft', 'dikirim', 'perubahan', 'disetujui'], true);
            })
            ->sort(function (PeriodeAlokasi $left, PeriodeAlokasi $right): int {
                $leftRevision = (int) ($left->revision_number ?? 0);
                $rightRevision = (int) ($right->revision_number ?? 0);

                if ($leftRevision !== $rightRevision) {
                    return $rightRevision <=> $leftRevision;
                }

                $leftCreatedAt = $left->created_at?->getTimestamp() ?? 0;
                $rightCreatedAt = $right->created_at?->getTimestamp() ?? 0;

                if ($leftCreatedAt !== $rightCreatedAt) {
                    return $rightCreatedAt <=> $leftCreatedAt;
                }

                return $right->id <=> $left->id;
            });

        return $activePeriodes->first();
    }

    private function resolvePeriodeFilterBulans(PeriodeAlokasi $periode): array
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return [str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT)];
        }

        $tanggalMulai = $periode->tanggal_mulai->copy()->startOfMonth();
        $tanggalSelesai = $periode->tanggal_selesai->copy()->startOfMonth();
        $filterBulans = [];

        while ($tanggalMulai->lte($tanggalSelesai)) {
            if ((int) $tanggalMulai->year === (int) $periode->tahun) {
                $filterBulans[] = $tanggalMulai->format('m');
            }

            $tanggalMulai->addMonth();
        }

        if ($filterBulans === []) {
            $filterBulans[] = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);
        }

        return array_values(array_unique($filterBulans));
    }

    private function resolveKegiatanFromPeriodeRoute(string $kegiatanRouteKey, int $tahun, string $bulan): Kegiatan
    {
        // Try PeriodeAlokasi first so that a periode hash takes priority over a kegiatan hash
        // when both decode to the same numeric ID (hash collision between the two tables).
        $resolvedPeriode = $this->resolvePeriodeRouteBinding($kegiatanRouteKey);
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        if (
            $resolvedPeriode instanceof PeriodeAlokasi
            && (int) $resolvedPeriode->tahun === $tahun
            && str_pad((string) $resolvedPeriode->bulan, 2, '0', STR_PAD_LEFT) === $bulanFormatted
        ) {
            if ($resolvedPeriode->relationLoaded('kegiatan') && $resolvedPeriode->kegiatan instanceof Kegiatan) {
                return $resolvedPeriode->kegiatan;
            }

            return $resolvedPeriode->kegiatan()->firstOrFail();
        }

        // Fallback: the route key is a kegiatan hash (older links / direct navigation)
        $resolvedKegiatan = $this->resolveKegiatanRouteBinding($kegiatanRouteKey);

        if ($resolvedKegiatan instanceof Kegiatan) {
            return $resolvedKegiatan;
        }

        abort(404);
    }

    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }

    private function validateSampleFrameAllocations(array $alokasiItems, Kegiatan $kegiatan): array
    {
        if ($kegiatan->jenis_kegiatan !== 'survei') {
            return [];
        }

        $isPurposiveSampling = $kegiatan->metode_sampling === Kegiatan::METODE_SAMPLING_PURPOSSIVE;

        $frameById = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->get(['id', 'tahapan', 'target_unit_sampel'])
            ->keyBy('id');

        if ($frameById->isEmpty()) {
            return [];
        }

        $errors = [];

        foreach ($alokasiItems as $index => $item) {
            $rowNumber = $index + 1;

            $frameIds = collect($item['frame_sampel_ids'] ?? [])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            $jumlahUnitSampel = (int) ($item['jumlah_unit_sampel'] ?? 0);

            if ($frameIds->isEmpty()) {
                $errors[] = "Baris #{$rowNumber}: frame sampel wajib dipilih.";

                continue;
            }

            $invalidFrameIds = $frameIds->filter(fn ($frameId) => ! $frameById->has($frameId));
            if ($invalidFrameIds->isNotEmpty()) {
                $errors[] = "Baris #{$rowNumber}: terdapat frame sampel tidak valid untuk kegiatan ini.";

                continue;
            }

            $expectedTahapan = match ($item['tahapan'] ?? 'both') {
                'listing_only' => 'listing',
                'pencacahan_only' => 'pencacahan',
                default => null,
            };

            if ($expectedTahapan !== null) {
                $invalidTahapanFrame = $frameIds->first(function ($frameId) use ($frameById, $expectedTahapan) {
                    return ($frameById->get($frameId)?->tahapan ?? null) !== $expectedTahapan;
                });

                if ($invalidTahapanFrame !== null) {
                    $errors[] = "Baris #{$rowNumber}: frame sampel harus sesuai tahapan {$expectedTahapan}.";

                    continue;
                }
            }

            if ($jumlahUnitSampel <= 0 && $isPurposiveSampling) {
                $jumlahUnitSampel = $frameIds->count();
            }

            if ($jumlahUnitSampel <= 0) {
                $errors[] = "Baris #{$rowNumber}: jumlah unit sampel wajib lebih dari 0.";

                continue;
            }

            $maxUnitSampel = (int) $frameIds->sum(fn ($frameId) => array_sum((array) ($frameById->get($frameId)?->target_unit_sampel ?? [])));

            if ($maxUnitSampel <= 0 && $isPurposiveSampling) {
                $maxUnitSampel = $frameIds->count();
            }

            if ($jumlahUnitSampel > $maxUnitSampel) {
                $errors[] = "Baris #{$rowNumber}: jumlah unit sampel ({$jumlahUnitSampel}) melebihi kumulatif target frame terpilih ({$maxUnitSampel}).";
            }
        }

        return $errors;
    }

    private function syncAlokasiFrameSampel(AlokasiPetugas $alokasiPetugas, array $frameIds): void
    {
        $normalizedFrameIds = collect($frameIds)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $alokasiPetugas->frameSampelAllocations()->delete();

        if ($normalizedFrameIds->isEmpty()) {
            return;
        }

        $alokasiPetugas->frameSampelAllocations()->createMany(
            $normalizedFrameIds
                ->map(fn ($frameId) => ['kegiatan_frame_sampel_id' => $frameId])
                ->all()
        );
    }

    private function mergeAlokasiRowsForStorage(array $alokasiItems): array
    {
        $merged = [];

        foreach ($alokasiItems as $item) {
            $bulan = (string) ($item['bulan'] ?? '');
            $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

            $key = implode('|', [
                (string) ($item['petugas_id'] ?? ''),
                Str::lower((string) ($item['peran'] ?? '')),
                $normalizedBulan,
                (string) ($item['tahun'] ?? ''),
                (string) ($item['jenis_kegiatan'] ?? ''),
                (string) ($item['tahapan'] ?? 'both'),
            ]);

            $item['bulan'] = $normalizedBulan;

            if (! isset($merged[$key])) {
                $merged[$key] = $item;
                $merged[$key]['frame_sampel_ids'] = array_values(array_unique(array_map('intval', $item['frame_sampel_ids'] ?? [])));

                continue;
            }

            $existing = $merged[$key];
            $mergedFrameIds = array_values(array_unique(array_merge(
                array_map('intval', $existing['frame_sampel_ids'] ?? []),
                array_map('intval', $item['frame_sampel_ids'] ?? [])
            )));

            $merged[$key]['jumlah_satuan'] = (float) ($existing['jumlah_satuan'] ?? 0) + (float) ($item['jumlah_satuan'] ?? 0);
            $merged[$key]['jumlah_satuan_listing'] = (int) ($existing['jumlah_satuan_listing'] ?? 0) + (int) ($item['jumlah_satuan_listing'] ?? 0);
            $merged[$key]['jumlah_unit_sampel'] = (int) ($existing['jumlah_unit_sampel'] ?? 0) + (int) ($item['jumlah_unit_sampel'] ?? 0);
            $merged[$key]['partial_jumlah_satuan'] = (float) ($existing['partial_jumlah_satuan'] ?? 0) + (float) ($item['partial_jumlah_satuan'] ?? 0);
            $merged[$key]['partial_jumlah_satuan_listing'] = (int) ($existing['partial_jumlah_satuan_listing'] ?? 0) + (int) ($item['partial_jumlah_satuan_listing'] ?? 0);
            $merged[$key]['is_partial_payment'] = (bool) ($existing['is_partial_payment'] ?? false) || (bool) ($item['is_partial_payment'] ?? false);
            $merged[$key]['is_partial_payment_listing'] = (bool) ($existing['is_partial_payment_listing'] ?? false) || (bool) ($item['is_partial_payment_listing'] ?? false);
            $merged[$key]['frame_sampel_ids'] = $mergedFrameIds;

            $existingCatatan = trim((string) ($existing['catatan'] ?? ''));
            $newCatatan = trim((string) ($item['catatan'] ?? ''));
            $catatanParts = array_values(array_unique(array_filter([$existingCatatan, $newCatatan], fn (string $text): bool => $text !== '')));
            $merged[$key]['catatan'] = implode('; ', $catatanParts);
        }

        return array_values($merged);
    }

    protected function resolveKegiatanRouteBinding(string $kegiatanRouteKey): ?Kegiatan
    {
        return (new Kegiatan)->resolveRouteBinding($kegiatanRouteKey);
    }

    protected function resolvePeriodeRouteBinding(string $kegiatanRouteKey): ?PeriodeAlokasi
    {
        return (new PeriodeAlokasi)->resolveRouteBinding($kegiatanRouteKey);
    }
}
