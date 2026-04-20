<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Database\Factories\RateHonorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateHonor extends Model
{
    /** @use HasFactory<RateHonorFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'rate_honor';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'rate' => 'integer',
            'rate_listing' => 'integer',
            'tahun_berlaku' => 'integer',
        ];
    }

    protected $fillable = [
        'kegiatan_id',
        'posisi',
        'jenis_kegiatan',
        'jenis_penugasan',
        'status_kepegawaian',
        'deskripsi',
        'satuan_id',
        'rate',
        'rate_listing',
        'satuan_listing_id',
        'tahapan',
        'tahun_berlaku',
        'status',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    public function satuanListing(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_listing_id');
    }

    public function alokasi(): HasMany
    {
        return $this->hasMany(AlokasiPetugas::class);
    }
}
