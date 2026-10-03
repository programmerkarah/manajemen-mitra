<?php

namespace App\Http\Controllers\Analisis\Concerns;

trait BuildsAnalisisQueries
{
    private function calculateSensusWeightedHonor(int $bulan, float|int $baseHonor, ?string $jenisKegiatan): float
    {
        if ($jenisKegiatan !== 'sensus') {
            return (float) $baseHonor;
        }

        return $this->sensusEkonomiHonorWeight($bulan) * (float) $baseHonor;
    }

    private function nonZeroHonorClause(): string
    {
        return '(
            CASE
                WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL
                    THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0)
                ELSE COALESCE(alokasi_petugas.total_honor, 0)
            END
            + CASE
                WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL
                    THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0)
                ELSE COALESCE(alokasi_petugas.total_honor_listing, 0)
            END
        )';
    }

    private function allocationOrHonorExistsClause(): string
    {
        return '(
            COALESCE(alokasi_petugas.jumlah_satuan, 0) > 0
            OR COALESCE(alokasi_petugas.jumlah_satuan_listing, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor_listing, 0) > 0
            OR COALESCE(alokasi_petugas.estimasi_honor_partial, 0) > 0
            OR COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) > 0
            OR (
                CAST(periode_alokasi.bulan AS UNSIGNED) IN (6, 7, 8)
                AND COALESCE(kegiatan.jenis_kegiatan, \'\') = \'sensus\'
                AND LOWER(COALESCE(kegiatan.nama_kegiatan, \'\')) LIKE \'%sensus ekonomi%\'
            )
        )';
    }

    /**
     * @return array<int, string>
     */
    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }
}
