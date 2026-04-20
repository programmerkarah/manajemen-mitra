<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Database\Factories\AlokasiPetugasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlokasiPetugas extends Model
{
    /** @use HasFactory<AlokasiPetugasFactory> */
    use HasFactory, HasHashedRouteKey;

    protected $table = 'alokasi_petugas';

    protected $appends = ['hashed_id'];

    protected function casts(): array
    {
        return [
            'jumlah_satuan' => 'integer',
            'total_honor' => 'decimal:2',
            'is_partial_payment' => 'boolean',
            'partial_jumlah_satuan' => 'integer',
            'estimasi_honor_partial' => 'decimal:2',
            'jumlah_satuan_listing' => 'integer',
            'total_honor_listing' => 'decimal:2',
            'is_partial_payment_listing' => 'boolean',
            'partial_jumlah_satuan_listing' => 'integer',
            'estimasi_honor_partial_listing' => 'decimal:2',
            'non_response' => 'integer',
            'non_response_listing' => 'integer',
        ];
    }

    protected $fillable = [
        'kegiatan_id',
        'bulan',
        'tahun',
        'periode_alokasi_id',
        'petugas_id',
        'jumlah_satuan',
        'total_honor',
        'is_partial_payment',
        'partial_jumlah_satuan',
        'estimasi_honor_partial',
        'jumlah_satuan_listing',
        'total_honor_listing',
        'is_partial_payment_listing',
        'partial_jumlah_satuan_listing',
        'estimasi_honor_partial_listing',
        'peran',
        'status_kepegawaian',
        'catatan',
        'non_response',
        'non_response_listing',
    ];

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function spk(): HasMany
    {
        return $this->hasMany(Spk::class);
    }

    public function getEffectiveJumlahSatuan(): int
    {
        if ($this->is_partial_payment && $this->partial_jumlah_satuan !== null) {
            return (int) $this->partial_jumlah_satuan;
        }

        return (int) ($this->jumlah_satuan ?? 0);
    }

    public function getEffectiveJumlahSatuanListing(): int
    {
        if ($this->is_partial_payment_listing && $this->partial_jumlah_satuan_listing !== null) {
            return (int) $this->partial_jumlah_satuan_listing;
        }

        return (int) ($this->jumlah_satuan_listing ?? 0);
    }

    public function getEffectiveTotalHonor(): float
    {
        if ($this->is_partial_payment && $this->estimasi_honor_partial !== null) {
            return (float) $this->estimasi_honor_partial;
        }

        return (float) ($this->total_honor ?? 0);
    }

    public function getEffectiveTotalHonorListing(): float
    {
        if ($this->is_partial_payment_listing && $this->estimasi_honor_partial_listing !== null) {
            return (float) $this->estimasi_honor_partial_listing;
        }

        return (float) ($this->total_honor_listing ?? 0);
    }

    public function getEffectiveCombinedHonor(): float
    {
        return $this->getEffectiveTotalHonor() + $this->getEffectiveTotalHonorListing();
    }
}
