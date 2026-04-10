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
            'tahun_anggaran' => 'integer',
            'pagu_pencacahan' => 'decimal:2',
            'has_listing_updating' => 'boolean',
            'pagu_listing' => 'decimal:2',
            'pj_lainnya_id' => 'integer',
            'metode_pendataan_pencacahan' => 'string',
            'metode_pendataan_listing' => 'string',
            'metode_pelatihan' => 'string',
            'bulan_pelatihan' => 'integer',
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
        'status',
        'tanggal_validasi',
        'catatan',
        'has_listing_updating',
        'pagu_listing',
        'pj_lainnya_id',
        'metode_pendataan_pencacahan',
        'metode_pendataan_listing',
        'metode_pelatihan',
        'bulan_pelatihan',
    ];

    public function pjLainnya(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pj_lainnya_id');
    }

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_tim_user_id');
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

    public function skKpa(): HasMany
    {
        return $this->hasMany(SkKpa::class);
    }

    public function bast(): HasMany
    {
        return $this->hasMany(Bast::class);
    }
}
