<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewPetugas extends Model
{
    use HasFactory;

    protected $table = 'review_petugas';

    protected $fillable = [
        'kegiatan_id',
        'petugas_id',
        'periode_alokasi_id',
        'reviewer_user_id',
        'rating',
        'ulasan',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function periodeAlokasi(): BelongsTo
    {
        return $this->belongsTo(PeriodeAlokasi::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
