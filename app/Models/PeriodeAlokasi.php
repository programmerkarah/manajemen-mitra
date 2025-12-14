<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeriodeAlokasi extends Model
{
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'periode_alokasi';

    protected $fillable = [
        'kegiatan_id',
        'bulan',
        'tahun',
        'jenis_kegiatan',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'catatan',
    ];

    protected $casts = [
        'bulan' => 'string',
        'tahun' => 'integer',
        'jenis_kegiatan' => 'string',
        'status' => 'string',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function alokasiPetugas(): HasMany
    {
        return $this->hasMany(AlokasiPetugas::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getTotalHonorAttribute(): float
    {
        return $this->alokasiPetugas()->sum('total_honor');
    }

    public function getJumlahPetugasAttribute(): int
    {
        return $this->alokasiPetugas()->count();
    }
}
