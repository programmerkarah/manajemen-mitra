<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiPetugasFrameSampel extends Model
{
    use HasFactory;

    protected $table = 'alokasi_petugas_frame_sampel';

    protected $fillable = [
        'alokasi_petugas_id',
        'kegiatan_frame_sampel_id',
    ];

    public function alokasiPetugas(): BelongsTo
    {
        return $this->belongsTo(AlokasiPetugas::class);
    }

    public function kegiatanFrameSampel(): BelongsTo
    {
        return $this->belongsTo(KegiatanFrameSampel::class, 'kegiatan_frame_sampel_id');
    }
}
