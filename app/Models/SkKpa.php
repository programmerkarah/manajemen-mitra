<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Database\Factories\SkKpaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkKpa extends Model
{
    /** @use HasFactory<SkKpaFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'sk_kpa';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk' => 'date:Y-m-d',
            'bulan' => 'integer',
            'tahun' => 'integer',
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
            'revision_acknowledged_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'nomor_sk',
        'kegiatan_id',
        'bulan',
        'tahun',
        'tanggal_sk',
        'nama_kpa',
        'perihal',
        'dasar_hukum',
        'file_path',
        'signed_file_path',
        'is_signed',
        'signed_at',
        'signed_by',
        'revision_acknowledged_at',
        'revision_acknowledged_by',
        'status',
        'created_by',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    // public function spk(): HasMany
    // {
    //     return $this->hasMany(Spk::class);
    // }
}
