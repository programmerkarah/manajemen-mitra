<?php

namespace App\Models;

use App\Traits\HasHashedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterFrameSampel extends Model
{
    use HasFactory, HasHashedRouteKey;

    protected $table = 'master_frame_sampel';

    protected $appends = ['hashed_id'];

    protected $fillable = [
        'nama',
        'kode',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function kegiatanFrameSampel(): HasMany
    {
        return $this->hasMany(KegiatanFrameSampel::class, 'frame_sampel_id');
    }
}
