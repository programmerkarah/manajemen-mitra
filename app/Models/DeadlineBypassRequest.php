<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineBypassRequest extends Model
{
    protected $table = 'deadline_bypass_requests';

    protected $fillable = [
        'deadline_rule_id',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'kegiatan_id',
        'periode_alokasi_id',
        'year',
        'month',
        'status',
        'route_name',
        'http_method',
        'target_url',
        'reason',
        'review_note',
        'max_uses',
        'expires_at',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'max_uses' => 'integer',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function deadlineRule(): BelongsTo
    {
        return $this->belongsTo(DeadlineRule::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
