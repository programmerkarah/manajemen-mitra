<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Spk extends Model
{
    /** @use HasFactory<\Database\Factories\SpkFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'spk';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'tanggal_spk' => 'date:Y-m-d',
            'tanggal_mulai_kerja' => 'date:Y-m-d',
            'tanggal_selesai_kerja' => 'date:Y-m-d',
            'nilai_kontrak' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'nomor_spk',
        'alokasi_petugas_id',
        'parent_spk_id',
        'addendum_number',
        'tanggal_spk',
        'tanggal_mulai_kerja',
        'tanggal_selesai_kerja',
        'uraian_pekerjaan',
        'nilai_kontrak',
        'nama_ppk',
        'nip_ppk',
        'file_path',
        'status',
        'created_by',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function alokasiPetugas(): BelongsTo
    {
        return $this->belongsTo(AlokasiPetugas::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bast(): HasMany
    {
        return $this->hasMany(Bast::class);
    }

    public function parentSpk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'parent_spk_id');
    }

    public function addendums(): HasMany
    {
        return $this->hasMany(Spk::class, 'parent_spk_id')->orderBy('addendum_number');
    }
}
