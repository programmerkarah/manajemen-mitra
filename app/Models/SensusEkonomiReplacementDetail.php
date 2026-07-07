<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensusEkonomiReplacementDetail extends Model
{
    use HasFactory;

    protected $table = 'sensus_ekonomi_replacement_details';

    protected $fillable = [
        'replacement_id',
        'alokasi_petugas_frame_sampel_id',
        'kegiatan_frame_sampel_id',
        'metadata',
        'target_awal',
        'realisasi_petugas_berhenti',
        'realisasi_pml_cover',
        'target_sisa',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'target_awal' => 'decimal:2',
            'realisasi_petugas_berhenti' => 'decimal:2',
            'realisasi_pml_cover' => 'decimal:2',
            'target_sisa' => 'decimal:2',
        ];
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(SensusEkonomiPetugasReplacement::class, 'replacement_id');
    }

    public function alokasiPetugasFrameSampel(): BelongsTo
    {
        return $this->belongsTo(AlokasiPetugasFrameSampel::class, 'alokasi_petugas_frame_sampel_id');
    }

    public function kegiatanFrameSampel(): BelongsTo
    {
        return $this->belongsTo(KegiatanFrameSampel::class, 'kegiatan_frame_sampel_id');
    }
}
