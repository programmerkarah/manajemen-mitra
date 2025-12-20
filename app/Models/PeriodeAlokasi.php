<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PeriodeAlokasi extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'periode_alokasi';

    protected $fillable = [
        'kegiatan_id',
        'bulan',
        'tahun',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_mulai_listing',
        'tanggal_selesai_listing',
        'jenis_kegiatan',
        'tahapan',
        'status',
        'sisa_pagu',
        'sisa_pagu_listing',
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
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_mulai_listing' => 'date',
        'tanggal_selesai_listing' => 'date',
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

    public function spk(): HasManyThrough
    {
        return $this->hasManyThrough(
            Spk::class,
            AlokasiPetugas::class,
            'periode_alokasi_id', // Foreign key on alokasi_petugas table
            'alokasi_mitra_id',   // Foreign key on spk table
            'id',                 // Local key on periode_alokasi table
            'id'                  // Local key on alokasi_petugas table
        );
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
