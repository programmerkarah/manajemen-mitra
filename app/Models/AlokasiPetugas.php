<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlokasiPetugas extends Model
{
    /** @use HasFactory<\Database\Factories\AlokasiPetugasFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'alokasi_petugas';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'jumlah_satuan' => 'integer',
            'total_honor' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'periode_alokasi_id',
        'petugas_id',
        'jumlah_satuan',
        'total_honor',
        'peran',
        'status_kepegawaian',
        'catatan',
    ];

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function spk(): HasMany
    {
        return $this->hasMany(Spk::class);
    }
}
