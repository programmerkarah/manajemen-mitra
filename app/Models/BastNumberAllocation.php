<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BastNumberAllocation extends Model
{
    protected $table = 'bast_number_allocations';

    protected $fillable = [
        'spk_id',
        'nomor_bast',
        'tahun',
        'bulan',
        'status',
        'allocated_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
