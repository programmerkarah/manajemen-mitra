<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BastKegiatan extends Model
{
    use HasFactory;

    protected $table = 'bast_kegiatan';

    protected $fillable = [
        'bast_id',
        'spk_id',
        'kegiatan_id',
        'periode_alokasi_id',
        'kode_kegiatan',
        'nama_kegiatan',
        'bulan',
        'tahun',
        'jenis_kegiatan',
        'file_path',
        'signed_file_path',
        'fasih_screenshot_path',
        'generated_at',
        'signed_uploaded_at',
        'fasih_screenshot_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'signed_uploaded_at' => 'datetime',
            'fasih_screenshot_uploaded_at' => 'datetime',
        ];
    }

    public function bast(): BelongsTo
    {
        return $this->belongsTo(Bast::class);
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class);
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }
}
