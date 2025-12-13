<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    /** @use HasFactory<\Database\Factories\SatuanFactory> */
    use HasFactory, HasHashedRouteKey;

    protected $table = 'satuan';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'status',
    ];

    public function rateHonor(): HasMany
    {
        return $this->hasMany(RateHonor::class);
    }
}
