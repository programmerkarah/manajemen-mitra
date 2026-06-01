<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Database\Factories\KegiatanFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kegiatan extends Model
{
    /** @use HasFactory<KegiatanFactory> */
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
            'unit_sampel_pencacahan_ids' => 'array',
            'unit_sampel_listing_ids' => 'array',
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
        'frame_sampel_listing_id',
        'frame_sampel_pencacahan_id',
        'unit_sampel_listing_ids',
        'unit_sampel_pencacahan_ids',
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

    public function frameSampelListing(): BelongsTo
    {
        return $this->belongsTo(MasterFrameSampel::class, 'frame_sampel_listing_id');
    }

    public function frameSampelPencacahan(): BelongsTo
    {
        return $this->belongsTo(MasterFrameSampel::class, 'frame_sampel_pencacahan_id');
    }

    /**
     * @return Collection<int, MasterUnitSampel>
     */
    public function unitSampelListingItems(): Collection
    {
        $ids = $this->unit_sampel_listing_ids ?? [];

        return MasterUnitSampel::query()->whereIn('id', $ids)->get();
    }

    /**
     * @return Collection<int, MasterUnitSampel>
     */
    public function unitSampelPencacahanItems(): Collection
    {
        $ids = $this->unit_sampel_pencacahan_ids ?? [];

        return MasterUnitSampel::query()->whereIn('id', $ids)->get();
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

    public function kegiatanFrameSampel(): HasMany
    {
        return $this->hasMany(KegiatanFrameSampel::class);
    }

    public function bast(): HasMany
    {
        return $this->hasMany(Bast::class);
    }
}
