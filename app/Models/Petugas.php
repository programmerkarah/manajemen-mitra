<?php

namespace App\Models;

use App\Traits\EncryptsAttributes;
use App\Traits\HasHashedRouteKey;
use Database\Factories\PetugasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Petugas extends Model
{
    /** @use HasFactory<PetugasFactory> */
    use EncryptsAttributes, HasFactory, HasHashedRouteKey, SoftDeletes;

    protected $table = 'petugas';

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
            'tanggal_lahir' => 'date',
        ];
    }

    protected $fillable = [
        'nama',
        'nik',
        'email',
        'telepon',
        'alamat',
        'jenis_kelamin',
        'kecamatan',
        'desa_kelurahan',
        'tanggal_lahir',
        'pendidikan',
        'tahun_bergabung',
        'jenis_petugas',
        'jabatan',
        'golongan',
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
        return $this->hasMany(AlokasiPetugas::class);
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

    /**
     * Get data for edit form (includes encrypted fields).
     */
    public function toEditArray(): array
    {
        return [
            'id' => $this->id,
            'hashed_id' => $this->hashed_id,
            'nama' => $this->getAttribute('nama'),
            'nik' => $this->getAttribute('nik'),
            'email' => $this->getAttribute('email'),
            'telepon' => $this->getAttribute('telepon'),
            'alamat' => $this->getAttribute('alamat'),
            'jenis_kelamin' => $this->getAttribute('jenis_kelamin'),
            'kecamatan' => $this->getAttribute('kecamatan'),
            'desa_kelurahan' => $this->getAttribute('desa_kelurahan'),
            'tanggal_lahir' => $this->getAttribute('tanggal_lahir')?->format('Y-m-d'),
            'pendidikan' => $this->getAttribute('pendidikan'),
            'tahun_bergabung' => $this->getAttribute('tahun_bergabung'),
            'jenis_petugas' => $this->getAttribute('jenis_petugas'),
            'jabatan' => $this->getAttribute('jabatan'),
            'golongan' => $this->getAttribute('golongan'),
            'npwp' => $this->getAttribute('npwp'),
            'bank' => $this->getAttribute('bank'),
            'no_rekening' => $this->getAttribute('no_rekening'),
            'nama_rekening' => $this->getAttribute('nama_rekening'),
            'status' => $this->getAttribute('status'),
            'catatan' => $this->getAttribute('catatan'),
        ];
    }
}
