<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dipa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nomor_dipa',
        'tahun',
        'tanggal_dipa',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tanggal_dipa' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
