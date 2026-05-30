<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KegiatanFrameSampel extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'kegiatan_frame_sampel';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'kegiatan_id',
        'frame_sampel_id',
        'tahapan',
        'nama_frame',
        'kode_kecamatan',
        'kode_desa',
        'kode_sls',
        'kode_sub_sls',
        'kode_segmen',
        'identitas_tambahan',
        'target_unit_sampel',
    ];

    protected function casts(): array
    {
        return [
            'identitas_tambahan' => 'array',
            'target_unit_sampel' => 'integer',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function frameSampel(): BelongsTo
    {
        return $this->belongsTo(MasterFrameSampel::class, 'frame_sampel_id');
    }

    public function alokasiFrames(): HasMany
    {
        return $this->hasMany(AlokasiPetugasFrameSampel::class, 'kegiatan_frame_sampel_id');
    }
}
