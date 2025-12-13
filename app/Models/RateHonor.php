<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateHonor extends Model
{
    /** @use HasFactory<\Database\Factories\RateHonorFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'rate_honor';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'tahun_berlaku' => 'integer',
        ];
    }

    protected $fillable = [
        'posisi',
        'deskripsi',
        'satuan_id',
        'rate',
        'tahun_berlaku',
        'status',
    ];

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    public function alokasi(): HasMany
    {
        return $this->hasMany(AlokasiMitra::class);
    }
}
