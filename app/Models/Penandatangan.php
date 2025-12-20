<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penandatangan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'penandatangan';

    protected $fillable = [
        'nama',
        'nip',
        'jenis_penandatangan',
        'jabatan',
        'periode_mulai',
        'periode_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'is_active' => 'boolean',
            'jenis_penandatangan' => 'string',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeKepala($query)
    {
        return $query->where('jenis_penandatangan', 'kepala');
    }

    public function scopePpk($query)
    {
        return $query->where('jenis_penandatangan', 'ppk');
    }
}
