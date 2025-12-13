<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bast extends Model
{
    /** @use HasFactory<\Database\Factories\BastFactory> */
    use HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'bast';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'tanggal_bast' => 'date:Y-m-d',
            'tanggal_serah_terima' => 'date:Y-m-d',
        ];
    }

    protected $fillable = [
        'nomor_bast',
        'spk_id',
        'kegiatan_id',
        'tanggal_bast',
        'tanggal_serah_terima',
        'uraian_pekerjaan',
        'nama_ketua_tim',
        'nip_ketua_tim',
        'nama_ppk',
        'nip_ppk',
        'hasil_pekerjaan',
        'file_path',
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
}
