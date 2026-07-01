<?php

namespace App\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * When a kegiatan+bulan has both 'dikirim' and 'perubahan' periode_alokasi,
 * only the 'perubahan' record should be used.
 */
trait EffectivePeriodeScope
{
    /**
     * Apply effective periode filter to a query that has joined 'periode_alokasi'.
     * Replaces: whereIn('periode_alokasi.status', ['dikirim', 'perubahan'])
     */
    protected function applyEffectivePeriode(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('periode_alokasi.status', 'perubahan')
                ->orWhere(function ($q2) {
                    $q2->where('periode_alokasi.status', 'dikirim')
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('periode_alokasi as pa2')
                                ->whereColumn('pa2.kegiatan_id', 'periode_alokasi.kegiatan_id')
                                ->whereColumn('pa2.bulan', 'periode_alokasi.bulan')
                                ->whereColumn('pa2.tahun', 'periode_alokasi.tahun')
                                ->where('pa2.status', 'perubahan');
                        });
                });
        });
    }

    protected function isSensusEkonomiKegiatan(?string $jenisKegiatan, ?string $namaKegiatan): bool
    {
        return $jenisKegiatan === 'sensus'
            && str_contains(mb_strtolower((string) $namaKegiatan), 'sensus ekonomi');
    }

    protected function sensusEkonomiHonorWeight(int $bulan): float
    {
        return match ($bulan) {
            6 => 0.0,
            7 => 0.4,
            8 => 0.6,
            default => 1.0,
        };
    }

    protected function sensusEkonomiHonorSqlCase(string $kegiatanAlias = 'kegiatan'): string
    {
        return "CASE
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND CAST(periode_alokasi.bulan AS UNSIGNED) = 6 THEN 0
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND CAST(periode_alokasi.bulan AS UNSIGNED) = 7 THEN (COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)) * 0.4
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND CAST(periode_alokasi.bulan AS UNSIGNED) = 8 THEN (COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)) * 0.6
            ELSE COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)
        END";
    }

    protected function sensusEkonomiHonorSqlCaseForMonth(int $bulan, string $kegiatanAlias = 'kegiatan'): string
    {
        return "CASE
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND {$bulan} = 6 THEN 0
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND {$bulan} = 7 THEN (COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)) * 0.4
            WHEN {$kegiatanAlias}.jenis_kegiatan = 'sensus'
                AND LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE '%sensus ekonomi%'
                AND {$bulan} = 8 THEN (COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)) * 0.6
            ELSE COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0)
        END";
    }

    protected function applySensusEkonomiMonthFilter(Builder $query, int $bulan, string $kegiatanAlias = 'kegiatan'): Builder
    {
        return $query->where(function ($where) use ($bulan, $kegiatanAlias) {
            $where->whereRaw('CAST(periode_alokasi.bulan AS UNSIGNED) = ?', [$bulan])
                ->orWhere(function ($sensusQuery) use ($bulan, $kegiatanAlias) {
                    $sensusQuery->where("{$kegiatanAlias}.jenis_kegiatan", 'sensus')
                        ->whereRaw("LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE ?", ['%sensus ekonomi%'])
                        ->whereRaw('? BETWEEN MONTH(kegiatan.tanggal_mulai) AND MONTH(kegiatan.tanggal_selesai)', [$bulan]);
                });
        });
    }

    protected function applySensusEkonomiAllocationFilter(Builder $query, int $bulan, string $kegiatanAlias = 'kegiatan'): Builder
    {
        return $query->where(function ($where) use ($bulan, $kegiatanAlias) {
            $where->whereRaw($this->allocationOrHonorExistsClause());

            if (in_array($bulan, [6, 7, 8], true)) {
                $where->orWhere(function ($sensusQuery) use ($kegiatanAlias) {
                    $sensusQuery->where("{$kegiatanAlias}.jenis_kegiatan", 'sensus')
                        ->whereRaw("LOWER(COALESCE({$kegiatanAlias}.nama_kegiatan, '')) LIKE ?", ['%sensus ekonomi%']);
                });
            }
        });
    }

    private function allocationOrHonorExistsClause(): string
    {
        return '(
            COALESCE(alokasi_petugas.jumlah_satuan, 0) > 0
            OR COALESCE(alokasi_petugas.jumlah_satuan_listing, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor_listing, 0) > 0
            OR (
                CAST(periode_alokasi.bulan AS UNSIGNED) IN (6, 7, 8)
                AND COALESCE(kegiatan.jenis_kegiatan, \'\') = \'sensus\'
                AND LOWER(COALESCE(kegiatan.nama_kegiatan, \'\')) LIKE \'%sensus ekonomi%\'
            )
        )';
    }
}
