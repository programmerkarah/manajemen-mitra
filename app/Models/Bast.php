<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Database\Factories\BastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bast extends Model
{
    /** @use HasFactory<BastFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'bast';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'tanggal_bast' => 'date:Y-m-d',
            'tanggal_serah_terima' => 'date:Y-m-d',
            'menggunakan_fasih' => 'boolean',
        ];
    }

    protected $fillable = [
        'nomor_bast',
        'spk_id',
        'periode_alokasi_id',
        'kegiatan_id',
        'tanggal_bast',
        'tanggal_serah_terima',
        'menggunakan_fasih',
        'uraian_pekerjaan',
        'nama_ketua_tim',
        'nip_ketua_tim',
        'nama_ppk',
        'nip_ppk',
        'hasil_pekerjaan',
        'file_path',
        'compiled_file_path',
        'main_signed_file_path',
        'signed_file_path',
        'lokasi_kegiatan',
        'status',
        'catatan',
        'created_by',
    ];

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class);
    }

    public function bastPetugas(): HasMany
    {
        return $this->hasMany(BastPetugas::class);
    }

    public function bastKegiatan(): HasMany
    {
        return $this->hasMany(BastKegiatan::class);
    }
}
