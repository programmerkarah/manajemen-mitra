<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KepalaBps extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kepala_bps';

    protected $fillable = [
        'nama',
        'nip',
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
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
