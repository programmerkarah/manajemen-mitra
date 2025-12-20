<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kegiatan extends Model
{
    /** @use HasFactory<\Database\Factories\KegiatanFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'kegiatan';

    protected $appends = ['hashed_id', 'pagu_anggaran'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
            'tanggal_validasi' => 'date:Y-m-d',
            'rate_honor_approved_at' => 'datetime',
            'tahun_anggaran' => 'integer',
            'pagu_pencacahan' => 'decimal:2',
            'has_listing_updating' => 'boolean',
            'pagu_listing' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'kode_kegiatan',
        'nama_kegiatan',
        'jenis_kegiatan',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'tahun_anggaran',
        'pagu_pencacahan',
        'kode_coa',
        'ketua_tim_user_id',
        'rate_honor_id',
        'rate_honor_status',
        'rate_honor_approved_by',
        'rate_honor_approved_at',
        'rate_honor_notes',
        'status',
        'tanggal_validasi',
        'catatan',
        'has_listing_updating',
        'pagu_listing',
    ];

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_tim_user_id');
    }

    public function rateHonor(): BelongsTo
    {
        return $this->belongsTo(RateHonor::class);
    }

    public function rateHonors(): HasMany
    {
        return $this->hasMany(RateHonor::class, 'kegiatan_id');
    }

    public function satuanListing(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_listing_id');
    }

    // Accessor untuk pagu_anggaran (alias untuk pagu_pencacahan)
    public function getPaguAnggaranAttribute(): ?float
    {
        return $this->pagu_pencacahan;
    }

    // Mutator untuk pagu_anggaran (simpan ke pagu_pencacahan)
    public function setPaguAnggaranAttribute($value): void
    {
        $this->attributes['pagu_pencacahan'] = $value;
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function periodeAlokasi(): HasMany
    {
        return $this->hasMany(PeriodeAlokasi::class);
    }

    public function rateHonorApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rate_honor_approved_by');
    }

    public function skKpa(): HasMany
    {
        return $this->hasMany(SkKpa::class);
    }

    public function bast(): HasMany
    {
        return $this->hasMany(Bast::class);
    }
}
