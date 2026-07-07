<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensusEkonomiPkppContract extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'sensus_ekonomi_pkpp_contracts';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'replacement_id',
        'periode_alokasi_id',
        'petugas_id',
        'spk_id',
        'nomor_pkpp',
        'tanggal_kontrak',
        'tanggal_mulai_lapangan',
        'skema_kode',
        'termin_count',
        'honor_ob',
        'persentase_termin_1',
        'persentase_termin_2',
        'target_termin_1',
        'target_termin_2',
        'target_total',
        'waktu_penyelesaian_termin_1',
        'waktu_penyelesaian_termin_akhir',
        'periode_pasal_7',
        'biaya_ganti_rugi',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kontrak' => 'date:Y-m-d',
            'tanggal_mulai_lapangan' => 'date:Y-m-d',
            'termin_count' => 'integer',
            'honor_ob' => 'decimal:2',
            'persentase_termin_1' => 'integer',
            'persentase_termin_2' => 'integer',
            'target_termin_1' => 'array',
            'target_termin_2' => 'array',
            'target_total' => 'array',
            'biaya_ganti_rugi' => 'decimal:2',
        ];
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(SensusEkonomiPetugasReplacement::class, 'replacement_id');
    }

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class, 'periode_alokasi_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'spk_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
