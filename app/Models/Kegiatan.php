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
            'anggaran' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'kode_kegiatan',
        'nama_kegiatan',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'tahun_anggaran',
        'anggaran',
        'pagu_anggaran', // alias for anggaran
        'pj_user_id',
        'status',
        'tanggal_validasi',
        'catatan',
    ];

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pj_user_id');
    }

    // Accessor untuk pagu_anggaran (alias untuk anggaran)
    public function getPaguAnggaranAttribute(): ?float
    {
        return $this->anggaran;
    }

    // Mutator untuk pagu_anggaran (simpan ke anggaran)
    public function setPaguAnggaranAttribute($value): void
    {
        $this->attributes['anggaran'] = $value;
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function alokasi(): HasMany
    {
        return $this->hasMany(AlokasiMitra::class);
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
