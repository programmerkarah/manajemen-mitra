<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sbml extends Model
{
    /** @use HasFactory<\Database\Factories\SbmlFactory> */
    use HasFactory, HasHashedRouteKey;

    protected $table = 'sbml';

    protected $fillable = [
        'tahun_anggaran',
        'jenis_kegiatan',
        'status_kepegawaian',
        'jenis_penugasan',
        'honor_max',
        'keterangan',
        'status',
    ];

    protected $appends = [
        'hashed_id',
    ];

    protected $casts = [
        'tahun_anggaran' => 'integer',
        'honor_max' => 'integer',
    ];

    /**
     * Get the maximum honor rate for specific criteria
     */
    public static function getMaxForCriteria(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan
    ): float {
        $sbml = self::where('tahun_anggaran', $tahun)
            ->where('jenis_kegiatan', $jenisKegiatan)
            ->where('status_kepegawaian', $statusKepegawaian)
            ->where('jenis_penugasan', $jenisPenugasan)
            ->where('status', 'aktif')
            ->first();

        return $sbml ? (float) $sbml->honor_max : 0;
    }
}
