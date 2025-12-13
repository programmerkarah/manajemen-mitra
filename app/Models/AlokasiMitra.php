<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlokasiMitra extends Model
{
    /** @use HasFactory<\Database\Factories\AlokasiMitraFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'alokasi_mitra';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'jumlah_satuan' => 'integer',
            'total_honor' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'kegiatan_id',
        'mitra_id',
        'rate_honor_id',
        'bulan',
        'tahun',
        'jumlah_satuan',
        'total_honor',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'catatan_approval',
        'catatan',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function rateHonor(): BelongsTo
    {
        return $this->belongsTo(RateHonor::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function spk(): HasMany
    {
        return $this->hasMany(Spk::class);
    }
}
