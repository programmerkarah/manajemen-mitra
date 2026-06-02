<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BastSensusRealisasiImport extends Model
{
    use HasFactory;

    protected $table = 'bast_sensus_realisasi_imports';

    protected $fillable = [
        'spk_id',
        'petugas_id',
        'bulan',
        'tahun',
        'nomor_spk',
        'nik_petugas',
        'nama_petugas',
        'muatan_prelist_keluarga',
        'muatan_prelist_usaha',
        'realisasi_keluarga',
        'realisasi_usaha',
        'realisasi_unit_sampel',
        'fasih_screenshot_path',
        'fasih_screenshot_uploaded_at',
        'imported_by',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'muatan_prelist_keluarga' => 'integer',
            'muatan_prelist_usaha' => 'integer',
            'realisasi_keluarga' => 'integer',
            'realisasi_usaha' => 'integer',
            'realisasi_unit_sampel' => 'array',
            'fasih_screenshot_uploaded_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
