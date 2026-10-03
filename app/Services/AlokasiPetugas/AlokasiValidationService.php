<?php

namespace App\Services\AlokasiPetugas;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Petugas;
use App\Models\Sbml;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AlokasiValidationService
{
    public function validateDecimalSatuanRules(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            if (($item['jenis_kegiatan'] ?? null) === 'sensus') {
                continue;
            }

            if ($this->hasDecimalPart($item['jumlah_satuan'] ?? null)) {
                $errors[] = 'Baris alokasi #'.($index + 1)
                    .': jumlah satuan desimal hanya diperbolehkan untuk kegiatan sensus.';
            }

            $isPartial = (bool) ($item['is_partial_payment'] ?? false);

            if (
                $isPartial
                && $this->hasDecimalPart(
                    $item['partial_jumlah_satuan'] ?? null,
                )
            ) {
                $errors[] = 'Baris alokasi #'.($index + 1)
                    .': jumlah satuan parsial desimal hanya diperbolehkan untuk kegiatan sensus.';
            }
        }

        return $errors;
    }

    public function hasDecimalPart(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $numericValue = (float) $value;

        return abs($numericValue - round($numericValue)) > 0.000001;
    }

    public function normalizeSatuan(mixed $value): int|float
    {
        $numericValue = (float) ($value ?? 0);

        if (abs($numericValue - round($numericValue)) <= 0.000001) {
            return (int) round($numericValue);
        }

        return $numericValue;
    }

    public function checkSbmlConstraint(
        int $year,
        string $activityType,
        string $employmentStatus,
        string $assignmentType,
        float $totalHonor,
        ?Kegiatan $kegiatan = null,
    ): ?string {
        $sbml = Sbml::query()
            ->where('tahun_anggaran', $year)
            ->where('jenis_kegiatan', $activityType)
            ->where('status_kepegawaian', $employmentStatus)
            ->where('jenis_penugasan', $assignmentType)
            ->where('status', 'aktif')
            ->first();

        if (! $sbml) {
            return 'SBML untuk kombinasi ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $adjustedMax =
            (float) $sbml->honor_max
            * $this->sbmlLimitMultiplier($kegiatan);

        if ($totalHonor > $adjustedMax) {
            return 'Total honor (Rp '
                .number_format($totalHonor, 0, ',', '.')
                .') melebihi batas maksimal SBML (Rp '
                .number_format($adjustedMax, 0, ',', '.')
                .") untuk tahun {$year}.";
        }

        return null;
    }

    public function pencacahanWorkload(
        Kegiatan $kegiatan,
        float $jumlahSatuan,
    ): float {
        if ($jumlahSatuan <= 0) {
            return 0;
        }

        return $this->isSensusEkonomi2026($kegiatan)
            ? $jumlahSatuan * 2.5
            : $jumlahSatuan;
    }

    public function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return $kegiatan->jenis_kegiatan === 'sensus'
            && mb_strtolower(
                trim((string) $kegiatan->nama_kegiatan),
            ) === 'sensus ekonomi';
    }

    public function sbmlLimitMultiplier(?Kegiatan $kegiatan): float
    {
        return $kegiatan && $this->isSensusEkonomi2026($kegiatan)
            ? 2.5
            : 1.0;
    }

    public function checkPetugasTotalHonorInMonth(
        int $petugasId,
        int $year,
        int $month,
        float $newHonor,
        ?int $excludePeriodeId = null,
        ?string $newPeran = null,
        ?string $newJenisKegiatan = null,
        ?string $newStatusKepegawaian = null,
        ?Kegiatan $kegiatan = null,
    ): ?string {
        if (($newJenisKegiatan ?? $kegiatan?->jenis_kegiatan) === 'sensus') {
            return null;
        }

        $petugas = Petugas::find($petugasId);

        if (! $petugas) {
            return 'Petugas tidak ditemukan.';
        }

        $monthCandidates = $this->monthCandidates($month);

        $existingAllocations = AlokasiPetugas::with([
            'periodeAlokasi.kegiatan',
        ])
            ->whereHas(
                'periodeAlokasi',
                function ($query) use (
                    $year,
                    $monthCandidates,
                    $excludePeriodeId,
                ) {
                    $query
                        ->where('tahun', $year)
                        ->whereIn('bulan', $monthCandidates)
                        ->whereIn(
                            'status',
                            ['draft', 'dikirim', 'perubahan'],
                        );

                    if ($excludePeriodeId) {
                        $query->where('id', '!=', $excludePeriodeId);
                    }
                },
            )
            ->where('petugas_id', $petugasId)
            ->get();

        $existingTotalHonor = $existingAllocations->sum(
            function ($allocation) {
                $pencacahanHonor =
                    $allocation->is_partial_payment
                    && $allocation->estimasi_honor_partial !== null
                        ? (float) $allocation->estimasi_honor_partial
                        : (float) ($allocation->total_honor ?? 0);

                $listingHonor =
                    $allocation->is_partial_payment_listing
                    && $allocation->estimasi_honor_partial_listing !== null
                        ? (float) $allocation
                            ->estimasi_honor_partial_listing
                        : (float) ($allocation->total_honor_listing ?? 0);

                return $pencacahanHonor + $listingHonor;
            },
        );

        $totalHonorInMonth = $existingTotalHonor + $newHonor;
        $employmentStatus = $this->employmentStatus($petugas);

        $combinations = $existingAllocations->map(
            fn ($allocation) => [
                'jenis_kegiatan' =>
                    $allocation->periodeAlokasi->jenis_kegiatan ?? null,
                'jenis_penugasan' => $allocation->peran,
                'status_kepegawaian' =>
                    $allocation->status_kepegawaian,
            ],
        );

        if ($newPeran) {
            $combinations->push([
                'jenis_kegiatan' =>
                    $newJenisKegiatan
                    ?? $existingAllocations->first()
                        ?->periodeAlokasi?->jenis_kegiatan
                    ?? null,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' =>
                    $newStatusKepegawaian ?? $employmentStatus,
            ]);
        }

        if (
            $combinations->isEmpty()
            && $newPeran
            && $newJenisKegiatan
            && $newStatusKepegawaian
        ) {
            $combinations->push([
                'jenis_kegiatan' => $newJenisKegiatan,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' => $newStatusKepegawaian,
            ]);
        }

        $uniqueCombinations = $combinations->unique(
            fn ($item) => $item['jenis_kegiatan']
                .'|'
                .$item['jenis_penugasan']
                .'|'
                .$item['status_kepegawaian'],
        );

        $honorMaxList = $uniqueCombinations
            ->map(function ($combination) use ($year) {
                $sbml = Sbml::query()
                    ->where('tahun_anggaran', $year)
                    ->where(
                        'jenis_kegiatan',
                        $combination['jenis_kegiatan'],
                    )
                    ->where(
                        'status_kepegawaian',
                        $combination['status_kepegawaian'],
                    )
                    ->where(
                        'jenis_penugasan',
                        $combination['jenis_penugasan'],
                    )
                    ->where('status', 'aktif')
                    ->first();

                return $sbml ? $sbml->honor_max : null;
            })
            ->filter();

        if ($honorMaxList->isEmpty()) {
            return 'SBML untuk penugasan yang diberikan ke petugas ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $minimumAllowed = $honorMaxList->min();

        if ($totalHonorInMonth > $minimumAllowed) {
            return sprintf(
                'Total honor petugas %s di bulan %s %d (Rp %s) melebihi batas maksimal SBML terendah (Rp %s). Honor yang sudah dialokasikan: Rp %s, Honor baru: Rp %s.',
                $petugas->nama,
                Carbon::create()
                    ->month($month)
                    ->translatedFormat('F'),
                $year,
                number_format($totalHonorInMonth, 0, ',', '.'),
                number_format($minimumAllowed, 0, ',', '.'),
                number_format($existingTotalHonor, 0, ',', '.'),
                number_format($newHonor, 0, ',', '.'),
            );
        }

        return null;
    }

    public function employmentStatus(Petugas $petugas): string
    {
        $normalized = Str::of((string) $petugas->jenis_petugas)
            ->lower()
            ->replace('_', '-')
            ->trim()
            ->value();

        return $normalized === 'organik'
            ? 'organik'
            : 'non_organik';
    }

    private function monthCandidates(int $month): array
    {
        $raw = (string) $month;
        $normalized = str_pad($raw, 2, '0', STR_PAD_LEFT);

        return array_values(
            array_unique([$raw, (string) ((int) $raw), $normalized]),
        );
    }
}
