<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SensusEkonomiPetugasReplacement extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'sensus_ekonomi_petugas_replacements';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'periode_alokasi_id',
        'petugas_berhenti_id',
        'petugas_pengganti_id',
        'pml_cover_petugas_id',
        'spk_lama_id',
        'tanggal_berhenti',
        'tanggal_mulai_cover',
        'tanggal_mulai_pkpp',
        'target_awal',
        'realisasi_petugas_berhenti',
        'realisasi_pml_cover',
        'target_sisa',
        'status',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berhenti' => 'date:Y-m-d',
            'tanggal_mulai_cover' => 'date:Y-m-d',
            'tanggal_mulai_pkpp' => 'date:Y-m-d',
            'target_awal' => 'decimal:2',
            'realisasi_petugas_berhenti' => 'decimal:2',
            'realisasi_pml_cover' => 'decimal:2',
            'target_sisa' => 'decimal:2',
        ];
    }

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class, 'periode_alokasi_id');
    }

    public function petugasBerhenti(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_berhenti_id');
    }

    public function petugasPengganti(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_pengganti_id');
    }

    public function pmlCoverPetugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'pml_cover_petugas_id');
    }

    public function spkLama(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'spk_lama_id');
    }

    public function pkppContracts(): HasMany
    {
        return $this->hasMany(SensusEkonomiPkppContract::class, 'replacement_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SensusEkonomiReplacementDetail::class, 'replacement_id')->orderBy('urutan');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
