<?php

namespace App\Models;

use App\Traits\EncryptsAttributes;
use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mitra extends Model
{
    /** @use HasFactory<\Database\Factories\MitraFactory> */
    use EncryptsAttributes, HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'mitra';

    /**
     * Attributes that should be encrypted.
     */
    protected array $encrypted = [
        'nik',
        'npwp',
        'no_rekening',
    ];

    protected function casts(): array
    {
        return [
            'tahun_bergabung' => 'integer',
        ];
    }

    protected $fillable = [
        'nama',
        'nik',
        'email',
        'telepon',
        'alamat',
        'pendidikan',
        'tahun_bergabung',
        'status',
        'npwp',
        'bank',
        'no_rekening',
        'nama_rekening',
        'catatan',
    ];

    /**
     * Attributes to hide from JSON serialization.
     */
    protected $hidden = [
        'nik',
        'npwp',
        'no_rekening',
    ];

    /**
     * Append computed attributes.
     */
    protected $appends = [
        'hashed_id',
        'nik_masked',
        'npwp_masked',
        'no_rekening_masked',
    ];

    public function alokasi(): HasMany
    {
        return $this->hasMany(AlokasiMitra::class);
    }

    /**
     * Get masked NIK for display.
     */
    public function getNikMaskedAttribute(): string
    {
        $nik = $this->nik;

        return $nik ? substr($nik, 0, 4).'********'.substr($nik, -4) : '';
    }

    /**
     * Get masked NPWP for display.
     */
    public function getNpwpMaskedAttribute(): ?string
    {
        $npwp = $this->npwp;

        return $npwp ? substr($npwp, 0, 4).'********'.substr($npwp, -4) : null;
    }

    /**
     * Get masked no rekening for display.
     */
    public function getNoRekeningMaskedAttribute(): ?string
    {
        $noRek = $this->no_rekening;

        return $noRek ? '****'.substr($noRek, -4) : null;
    }
}
