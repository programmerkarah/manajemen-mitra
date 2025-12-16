<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DasarHukum extends Model
{
    use SoftDeletes;

    protected $table = 'dasar_hukum';

    protected $fillable = [
        'kategori',
        'instansi',
        'nomor',
        'tentang',
        'tahun',
        'status',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];
}
