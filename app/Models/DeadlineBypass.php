<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineBypass extends Model
{
    protected $table = 'deadline_bypasses';

    protected $fillable = [
        'deadline_rule_id',
        'kegiatan_id',
        'periode_alokasi_id',
        'year',
        'month',
        'approved_by_user_id',
        'granted_for_user_id',
        'reason',
        'max_uses',
        'uses_count',
        'is_active',
        'expires_at',
        'consumed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function deadlineRule(): BelongsTo
    {
        return $this->belongsTo(DeadlineRule::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function grantedFor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_for_user_id');
    }
}
