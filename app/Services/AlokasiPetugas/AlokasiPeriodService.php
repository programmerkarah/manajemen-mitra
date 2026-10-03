<?php

namespace App\Services\AlokasiPetugas;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\PeriodeAlokasi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AlokasiPeriodService
{
    public function displayMonth(PeriodeAlokasi $periode): string
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return Carbon::create()
                ->month((int) $periode->bulan)
                ->translatedFormat('F')
                .' '
                .$periode->tahun;
        }

        $start = $periode->tanggal_mulai->copy()->startOfDay();
        $end = $periode->tanggal_selesai->copy()->startOfDay();

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->translatedFormat('F Y');
        }

        if ($start->year === $end->year) {
            return $start->translatedFormat('F')
                .' - '
                .$end->translatedFormat('F Y');
        }

        return $start->translatedFormat('F Y')
            .' - '
            .$end->translatedFormat('F Y');
    }

    public function sortIndexData(Collection $items): Collection
    {
        return $items
            ->sort(function (array $left, array $right): int {
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

                if (
                    $leftCreatedAt instanceof Carbon
                    && $rightCreatedAt instanceof Carbon
                ) {
                    return $rightCreatedAt->getTimestamp()
                        <=> $leftCreatedAt->getTimestamp();
                }

                return strcmp(
                    (string) $rightCreatedAt,
                    (string) $leftCreatedAt,
                );
            })
            ->values();
    }

    public function sumEffectiveCombinedHonor(
        Collection $allocations,
    ): float {
        return (float) $allocations->sum(
            fn (AlokasiPetugas $allocation): float =>
                $allocation->getEffectiveCombinedHonor(),
        );
    }

    public function effectiveBudgetPeriod(
        Collection $periods,
    ): ?PeriodeAlokasi {
        return $periods
            ->filter(
                fn (PeriodeAlokasi $periode): bool =>
                    in_array(
                        $periode->status,
                        [
                            'draft',
                            'dikirim',
                            'perubahan',
                            'disetujui',
                        ],
                        true,
                    ),
            )
            ->sort(function (
                PeriodeAlokasi $left,
                PeriodeAlokasi $right,
            ): int {
                $leftRevision = (int) ($left->revision_number ?? 0);
                $rightRevision = (int) ($right->revision_number ?? 0);

                if ($leftRevision !== $rightRevision) {
                    return $rightRevision <=> $leftRevision;
                }

                $leftCreatedAt =
                    $left->created_at?->getTimestamp() ?? 0;
                $rightCreatedAt =
                    $right->created_at?->getTimestamp() ?? 0;

                if ($leftCreatedAt !== $rightCreatedAt) {
                    return $rightCreatedAt <=> $leftCreatedAt;
                }

                return $right->id <=> $left->id;
            })
            ->first();
    }

    /**
     * @return array<int, string>
     */
    public function filterMonths(PeriodeAlokasi $periode): array
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return [
                str_pad(
                    (string) ((int) $periode->bulan),
                    2,
                    '0',
                    STR_PAD_LEFT,
                ),
            ];
        }

        $cursor = $periode->tanggal_mulai->copy()->startOfMonth();
        $end = $periode->tanggal_selesai->copy()->startOfMonth();
        $months = [];

        while ($cursor->lte($end)) {
            if ((int) $cursor->year === (int) $periode->tahun) {
                $months[] = $cursor->format('m');
            }

            $cursor->addMonth();
        }

        if ($months === []) {
            $months[] = str_pad(
                (string) ((int) $periode->bulan),
                2,
                '0',
                STR_PAD_LEFT,
            );
        }

        return array_values(array_unique($months));
    }

    public function resolveKegiatanFromPeriodRoute(
        string $routeKey,
        int $year,
        string $month,
    ): Kegiatan {
        $periode = $this->resolvePeriodBinding($routeKey);
        $normalizedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        if (
            $periode instanceof PeriodeAlokasi
            && (int) $periode->tahun === $year
            && str_pad(
                (string) $periode->bulan,
                2,
                '0',
                STR_PAD_LEFT,
            ) === $normalizedMonth
        ) {
            if (
                $periode->relationLoaded('kegiatan')
                && $periode->kegiatan instanceof Kegiatan
            ) {
                return $periode->kegiatan;
            }

            return $periode->kegiatan()->firstOrFail();
        }

        $kegiatan = $this->resolveKegiatanBinding($routeKey);

        if ($kegiatan instanceof Kegiatan) {
            return $kegiatan;
        }

        abort(404);
    }

    public function monthCandidates(string $month): array
    {
        $normalized = str_pad(
            (string) ((int) $month),
            2,
            '0',
            STR_PAD_LEFT,
        );

        return array_values(
            array_unique([
                $month,
                (string) ((int) $month),
                $normalized,
            ]),
        );
    }

    public function validateSampleFrameAllocations(
        array $items,
        Kegiatan $kegiatan,
    ): array {
        if ($kegiatan->jenis_kegiatan !== 'survei') {
            return [];
        }

        $isPurposive =
            $kegiatan->metode_sampling
            === Kegiatan::METODE_SAMPLING_PURPOSSIVE;

        $frameById = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->get(['id', 'tahapan', 'target_unit_sampel'])
            ->keyBy('id');

        if ($frameById->isEmpty()) {
            return [];
        }

        $errors = [];

        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $frameIds = collect($item['frame_sampel_ids'] ?? [])
                ->filter(
                    fn ($value) =>
                        $value !== null && $value !== '',
                )
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            $unitCount = (int) ($item['jumlah_unit_sampel'] ?? 0);

            if ($frameIds->isEmpty()) {
                $errors[] =
                    "Baris #{$rowNumber}: frame sampel wajib dipilih.";
                continue;
            }

            $invalidIds = $frameIds->filter(
                fn ($frameId) => ! $frameById->has($frameId),
            );

            if ($invalidIds->isNotEmpty()) {
                $errors[] =
                    "Baris #{$rowNumber}: terdapat frame sampel tidak valid untuk kegiatan ini.";
                continue;
            }

            $expectedPhase = match ($item['tahapan'] ?? 'both') {
                'listing_only' => 'listing',
                'pencacahan_only' => 'pencacahan',
                default => null,
            };

            if ($expectedPhase !== null) {
                $invalidPhaseFrame = $frameIds->first(
                    fn ($frameId) =>
                        ($frameById->get($frameId)?->tahapan ?? null)
                        !== $expectedPhase,
                );

                if ($invalidPhaseFrame !== null) {
                    $errors[] =
                        "Baris #{$rowNumber}: frame sampel harus sesuai tahapan {$expectedPhase}.";
                    continue;
                }
            }

            if ($unitCount <= 0 && $isPurposive) {
                $unitCount = $frameIds->count();
            }

            if ($unitCount <= 0) {
                $errors[] =
                    "Baris #{$rowNumber}: jumlah unit sampel wajib lebih dari 0.";
                continue;
            }

            $maxUnitCount = (int) $frameIds->sum(
                fn ($frameId) => array_sum(
                    (array) (
                        $frameById->get($frameId)
                            ?->target_unit_sampel
                        ?? []
                    ),
                ),
            );

            if ($maxUnitCount <= 0 && $isPurposive) {
                $maxUnitCount = $frameIds->count();
            }

            if ($unitCount > $maxUnitCount) {
                $errors[] =
                    "Baris #{$rowNumber}: jumlah unit sampel ({$unitCount}) melebihi kumulatif target frame terpilih ({$maxUnitCount}).";
            }
        }

        return $errors;
    }

    public function syncFrameAllocations(
        AlokasiPetugas $allocation,
        array $frameIds,
    ): void {
        $normalized = collect($frameIds)
            ->filter(
                fn ($value) => $value !== null && $value !== '',
            )
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $allocation->frameSampelAllocations()->delete();

        if ($normalized->isEmpty()) {
            return;
        }

        $allocation->frameSampelAllocations()->createMany(
            $normalized
                ->map(
                    fn ($frameId) => [
                        'kegiatan_frame_sampel_id' => $frameId,
                    ],
                )
                ->all(),
        );
    }

    public function mergeRowsForStorage(array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            $month = (string) ($item['bulan'] ?? '');
            $normalizedMonth = str_pad(
                (string) ((int) $month),
                2,
                '0',
                STR_PAD_LEFT,
            );

            $key = implode('|', [
                (string) ($item['petugas_id'] ?? ''),
                Str::lower((string) ($item['peran'] ?? '')),
                $normalizedMonth,
                (string) ($item['tahun'] ?? ''),
                (string) ($item['jenis_kegiatan'] ?? ''),
                (string) ($item['tahapan'] ?? 'both'),
            ]);

            $item['bulan'] = $normalizedMonth;

            if (! isset($merged[$key])) {
                $merged[$key] = $item;
                $merged[$key]['frame_sampel_ids'] =
                    array_values(
                        array_unique(
                            array_map(
                                'intval',
                                $item['frame_sampel_ids'] ?? [],
                            ),
                        ),
                    );
                continue;
            }

            $existing = $merged[$key];

            $merged[$key]['jumlah_satuan'] =
                (float) ($existing['jumlah_satuan'] ?? 0)
                + (float) ($item['jumlah_satuan'] ?? 0);
            $merged[$key]['jumlah_satuan_listing'] =
                (int) ($existing['jumlah_satuan_listing'] ?? 0)
                + (int) ($item['jumlah_satuan_listing'] ?? 0);
            $merged[$key]['jumlah_unit_sampel'] =
                (int) ($existing['jumlah_unit_sampel'] ?? 0)
                + (int) ($item['jumlah_unit_sampel'] ?? 0);
            $merged[$key]['partial_jumlah_satuan'] =
                (float) ($existing['partial_jumlah_satuan'] ?? 0)
                + (float) ($item['partial_jumlah_satuan'] ?? 0);
            $merged[$key]['partial_jumlah_satuan_listing'] =
                (int) (
                    $existing['partial_jumlah_satuan_listing']
                    ?? 0
                )
                + (int) (
                    $item['partial_jumlah_satuan_listing']
                    ?? 0
                );
            $merged[$key]['is_partial_payment'] =
                (bool) ($existing['is_partial_payment'] ?? false)
                || (bool) ($item['is_partial_payment'] ?? false);
            $merged[$key]['is_partial_payment_listing'] =
                (bool) (
                    $existing['is_partial_payment_listing']
                    ?? false
                )
                || (bool) (
                    $item['is_partial_payment_listing']
                    ?? false
                );
            $merged[$key]['frame_sampel_ids'] =
                array_values(
                    array_unique(
                        array_merge(
                            array_map(
                                'intval',
                                $existing['frame_sampel_ids'] ?? [],
                            ),
                            array_map(
                                'intval',
                                $item['frame_sampel_ids'] ?? [],
                            ),
                        ),
                    ),
                );

            $existingNote = trim(
                (string) ($existing['catatan'] ?? ''),
            );
            $newNote = trim((string) ($item['catatan'] ?? ''));

            $merged[$key]['catatan'] = implode(
                '; ',
                array_values(
                    array_unique(
                        array_filter(
                            [$existingNote, $newNote],
                            fn (string $text): bool => $text !== '',
                        ),
                    ),
                ),
            );
        }

        return array_values($merged);
    }

    public function resolveKegiatanBinding(
        string $routeKey,
    ): ?Kegiatan {
        return (new Kegiatan)->resolveRouteBinding($routeKey);
    }

    public function resolvePeriodBinding(
        string $routeKey,
    ): ?PeriodeAlokasi {
        return (new PeriodeAlokasi)->resolveRouteBinding($routeKey);
    }
}
