<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BastPetugas extends Model
{
    use HasFactory;

    protected $table = 'bast_petugas';

    protected $fillable = [
        'bast_id',
        'petugas_id',
        'spk_id',
        'nomor_spk',
        'nama_petugas',
        'hasil_listing',
        'satuan_listing',
        'hasil_pendataan_lapangan',
        'satuan_pendataan_lapangan',
        'hasil_pengolahan',
        'satuan_pengolahan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'hasil_listing' => 'integer',
            'hasil_pendataan_lapangan' => 'integer',
            'hasil_pengolahan' => 'integer',
        ];
    }

    public function bast(): BelongsTo
    {
        return $this->belongsTo(Bast::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }
}
