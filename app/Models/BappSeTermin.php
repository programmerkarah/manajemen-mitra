<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BappSeTermin extends Model
{
    use HasHashedRouteKey;

    protected $table = 'bapp_se_termin';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'spk_id',
        'replacement_id',
        'petugas_id',
        'termin',
        'document_type',
        'replacement_termin_count',
        'bulan',
        'tahun',
        'nomor_bapp',
        'tanggal_bapp',
        'nama_ketua_tim',
        'nip_ketua_tim',
        'nama_ppk',
        'nip_ppk',
        'jabatan_ppk',
        'nama_kabkota',
        'target_sls',
        'target_unit_sampel',
        'realisasi_sls',
        'realisasi_unit_sampel',
        'persentase',
        'nilai_perjanjian',
        'file_path',
        'signed_file_path',
        'signed_uploaded_at',
        'fasih_screenshot_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bapp' => 'date:Y-m-d',
            'termin' => 'integer',
            'replacement_termin_count' => 'integer',
            'bulan' => 'integer',
            'tahun' => 'integer',
            'target_sls' => 'integer',
            'target_unit_sampel' => 'array',
            'realisasi_sls' => 'integer',
            'realisasi_unit_sampel' => 'array',
            'persentase' => 'integer',
            'nilai_perjanjian' => 'decimal:2',
            'signed_uploaded_at' => 'datetime',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(SensusEkonomiPetugasReplacement::class, 'replacement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
