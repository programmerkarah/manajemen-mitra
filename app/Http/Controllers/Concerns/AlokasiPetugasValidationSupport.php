<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Kegiatan;
use App\Models\Petugas;
use App\Services\AlokasiPetugas\AlokasiValidationService;

trait AlokasiPetugasValidationSupport
{
    private function validateDecimalSatuanRules(array $alokasiItems): array
    {
        return app(AlokasiValidationService::class)
            ->validateDecimalSatuanRules($alokasiItems);
    }

    private function hasDecimalPart(mixed $value): bool
    {
        return app(AlokasiValidationService::class)
            ->hasDecimalPart($value);
    }

    private function normalizeSatuanForResponse(
        mixed $value,
    ): int|float {
        return app(AlokasiValidationService::class)
            ->normalizeSatuan($value);
    }

    private function checkSbmlConstraint(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan,
        float $totalHonor,
        ?Kegiatan $kegiatan = null,
    ): ?string {
        return app(AlokasiValidationService::class)
            ->checkSbmlConstraint(
                $tahun,
                $jenisKegiatan,
                $statusKepegawaian,
                $jenisPenugasan,
                $totalHonor,
                $kegiatan,
            );
    }

    private function resolvePencacahanWorkload(
        Kegiatan $kegiatan,
        float $jumlahSatuan,
    ): float {
        return app(AlokasiValidationService::class)
            ->pencacahanWorkload($kegiatan, $jumlahSatuan);
    }

    private function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return app(AlokasiValidationService::class)
            ->isSensusEkonomi2026($kegiatan);
    }

    private function getSbmlLimitMultiplier(
        ?Kegiatan $kegiatan,
    ): float {
        return app(AlokasiValidationService::class)
            ->sbmlLimitMultiplier($kegiatan);
    }

    private function checkPetugasTotalHonorInMonth(
        int $petugasId,
        int $tahun,
        int $bulan,
        float $newHonor,
        ?int $excludePeriodeId = null,
        ?string $newPeran = null,
        ?string $newJenisKegiatan = null,
        ?string $newStatusKepegawaian = null,
        ?Kegiatan $kegiatan = null,
    ): ?string {
        return app(AlokasiValidationService::class)
            ->checkPetugasTotalHonorInMonth(
                $petugasId,
                $tahun,
                $bulan,
                $newHonor,
                $excludePeriodeId,
                $newPeran,
                $newJenisKegiatan,
                $newStatusKepegawaian,
                $kegiatan,
            );
    }

    private function resolveStatusKepegawaianFromPetugas(
        Petugas $petugas,
    ): string {
        return app(AlokasiValidationService::class)
            ->employmentStatus($petugas);
    }
}
