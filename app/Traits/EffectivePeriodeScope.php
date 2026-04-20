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
}
