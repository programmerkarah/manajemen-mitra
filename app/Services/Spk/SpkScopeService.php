<?php

namespace App\Services\Spk;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SpkScopeService
{
    public function nextSequence(int $year): int
    {
        $usedNumbers = Spk::query()
            ->where('nomor_spk', 'like', "PPIS/13730/%/K/{$year}")
            ->whereNull('deleted_at')
            ->get()
            ->map(
                fn (Spk $spk): int => $this->extractSequence(
                    (string) $spk->nomor_spk,
                ),
            )
            ->filter(fn (int $number): bool => $number > 0)
            ->unique()
            ->sort()
            ->values();

        if ($usedNumbers->isEmpty()) {
            return 1;
        }

        $maxNumber = (int) $usedNumbers->last();

        for ($candidate = 1; $candidate <= $maxNumber + 1; $candidate++) {
            if (! $usedNumbers->contains($candidate)) {
                return $candidate;
            }
        }

        return $maxNumber + 1;
    }

    public function nextSequenceForPeriod(PeriodeAlokasi $periode): int
    {
        if (! $this->usesPeriodBasedFlow($periode)) {
            return $this->nextSequence((int) $periode->tahun);
        }

        $lastSpk = Spk::query()
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', (int) $periode->tahun)
            ->whereHas(
                'alokasiPetugas.periodeAlokasi',
                fn ($query) => $query->where(
                    'kegiatan_id',
                    $periode->kegiatan_id,
                ),
            )
            ->orderByDesc('nomor_urut_base')
            ->first();

        if (! $lastSpk) {
            return 1;
        }

        $lastSequence = (int) ($lastSpk->nomor_urut_base ?? 0);

        if ($lastSequence <= 0) {
            $lastSequence = $this->extractSequence(
                (string) $lastSpk->nomor_spk,
            );
        }

        return $lastSequence + 1;
    }

    public function formatNumber(
        PeriodeAlokasi $periode,
        int $sequence,
    ): string {
        if ($this->usesPeriodBasedFlow($periode)) {
            return sprintf(
                'B-%03d/SPK-SE2026/1373/PL.200/%d',
                $sequence,
                (int) $periode->tahun,
            );
        }

        return 'PPIS/13730/'.$sequence.'/K/'.$periode->tahun;
    }

    public function extractSequence(string $spkNumber): int
    {
        if (preg_match('/^B-(\d+)/i', $spkNumber, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        $parts = explode('/', $spkNumber);

        if (! isset($parts[2])) {
            return 0;
        }

        return (int) preg_replace('/[^0-9]/', '', $parts[2]);
    }

    public function displaySequenceSegment(
        string $spkNumber,
        int $baseSequence,
        ?string $fallbackSuffix = null,
    ): string {
        $spkNumber = trim($spkNumber);

        if ($spkNumber !== '') {
            foreach ([
                '/^B-(\d+)([A-Z])?(?:\/|$)/i',
                '/^PPIS\/13730\/(\d+)([A-Z])?(?:\/|$)/i',
                '/\/(\d+)([A-Z])?(?:\/|$)/',
            ] as $pattern) {
                if (preg_match($pattern, $spkNumber, $matches) === 1) {
                    $base = (string) ($matches[1] ?? $baseSequence);
                    $suffix = isset($matches[2]) && $matches[2] !== ''
                        ? strtoupper($matches[2])
                        : '';

                    return $base.$suffix;
                }
            }
        }

        $suffix = trim((string) ($fallbackSuffix ?? ''));

        return (string) $baseSequence
            .($suffix !== '' ? strtoupper($suffix) : '');
    }

    public function usesPeriodBasedFlow(PeriodeAlokasi $periode): bool
    {
        return $this->isSensusEkonomi2026($periode->kegiatan);
    }

    /**
     * @return Collection<int, int>
     */
    public function resolvePeriodIds(
        PeriodeAlokasi $periode,
        array $statuses = [],
    ): Collection {
        $query = PeriodeAlokasi::query();
        $month = str_pad(
            (string) ((int) $periode->bulan),
            2,
            '0',
            STR_PAD_LEFT,
        );

        if ($this->usesPeriodBasedFlow($periode)) {
            $query->whereKey($periode->id);
        } else {
            $query
                ->whereRaw(
                    "LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?",
                    [$month],
                )
                ->where('tahun', $periode->tahun)
                ->whereHas(
                    'kegiatan',
                    fn ($builder) => $builder
                        ->where('jenis_kegiatan', '!=', 'sensus'),
                );
        }

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        return $query->pluck('id');
    }

    public function hasDraftPeriod(PeriodeAlokasi $periode): bool
    {
        if ($this->usesPeriodBasedFlow($periode)) {
            return false;
        }

        $month = str_pad(
            (string) ((int) $periode->bulan),
            2,
            '0',
            STR_PAD_LEFT,
        );

        return PeriodeAlokasi::query()
            ->whereRaw(
                "LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?",
                [$month],
            )
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
            ->whereHas(
                'kegiatan',
                fn ($query) => $query
                    ->where('jenis_kegiatan', '!=', 'sensus'),
            )
            ->exists();
    }

    public function baseQuery(PeriodeAlokasi $periode): Builder
    {
        $query = Spk::query()->where(function ($builder) {
            $builder
                ->where('addendum_number', 0)
                ->orWhereNull('addendum_number');
        });

        if ($this->usesPeriodBasedFlow($periode)) {
            return $query->whereHas(
                'alokasiPetugas',
                fn ($builder) => $builder->where(
                    'periode_alokasi_id',
                    $periode->id,
                ),
            );
        }

        $month = str_pad(
            (string) ((int) $periode->bulan),
            2,
            '0',
            STR_PAD_LEFT,
        );

        return $query->whereHas(
            'alokasiPetugas.periodeAlokasi',
            function ($builder) use ($periode, $month) {
                $builder
                    ->whereRaw(
                        "LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?",
                        [$month],
                    )
                    ->where('tahun', $periode->tahun)
                    ->whereHas(
                        'kegiatan',
                        fn ($query) => $query
                            ->where('jenis_kegiatan', '!=', 'sensus'),
                    );
            },
        );
    }

    public function indexGroupKey(PeriodeAlokasi $periode): string
    {
        if ($this->usesPeriodBasedFlow($periode)) {
            return 'periode-'.$periode->id;
        }

        return sprintf(
            '%d-%02d',
            (int) $periode->tahun,
            (int) $periode->bulan,
        );
    }

    public function primaryPeriod(Collection $periods): PeriodeAlokasi
    {
        return $periods
            ->sort(function (
                PeriodeAlokasi $left,
                PeriodeAlokasi $right,
            ): int {
                $leftPriority = $this->statusPriority($left->status);
                $rightPriority = $this->statusPriority($right->status);

                if ($leftPriority !== $rightPriority) {
                    return $rightPriority <=> $leftPriority;
                }

                $leftCreatedAt = $left->created_at?->getTimestamp() ?? 0;
                $rightCreatedAt = $right->created_at?->getTimestamp() ?? 0;

                return $rightCreatedAt <=> $leftCreatedAt;
            })
            ->first();
    }

    public function statusPriority(string $status): int
    {
        return match ($status) {
            'perubahan' => 4,
            'direvisi' => 3,
            'disetujui' => 2,
            'dikirim' => 1,
            default => 0,
        };
    }

    public function indexDisplayLabel(PeriodeAlokasi $periode): string
    {
        if (! $this->usesPeriodBasedFlow($periode)) {
            return $this->monthLabel((int) $periode->bulan)
                .' '
                .$periode->tahun;
        }

        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
            $start = $periode->tanggal_mulai;
            $end = $periode->tanggal_selesai;

            if ($start->year === $end->year) {
                if ($start->month === $end->month) {
                    return $start->translatedFormat('d')
                        .'-'
                        .$end->translatedFormat('d F Y');
                }

                return $start->translatedFormat('F')
                    .' - '
                    .$end->translatedFormat('F Y');
            }

            return $start->translatedFormat('d F Y')
                .' - '
                .$end->translatedFormat('d F Y');
        }

        return $this->monthLabel((int) $periode->bulan)
            .' '
            .$periode->tahun;
    }

    private function isSensusEkonomi2026(?Kegiatan $kegiatan): bool
    {
        if (! $kegiatan) {
            return false;
        }

        return mb_strtolower((string) $kegiatan->jenis_kegiatan) === 'sensus'
            && str_contains(
                mb_strtolower(trim((string) $kegiatan->nama_kegiatan)),
                'sensus ekonomi',
            );
    }

    private function monthLabel(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '';
    }
}
