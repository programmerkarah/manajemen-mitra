<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'status',
        'meta',
    ];

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log activity helper method
     */
    public static function log(string $action, string $type, string $description, string $status = 'success', ?array $metadata = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Combine action and type for the action field
        $actionText = $action;

        // Build meta data
        $meta = [
            'type' => $type,
            'description' => $description,
            'user_name' => $user?->name ?? 'System',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Merge additional metadata if provided
        if ($metadata) {
            $meta = array_merge($meta, $metadata);
        }

        self::create([
            'user_id' => $user?->id,
            'action' => $actionText,
            'status' => $status,
            'meta' => $meta,
        ]);
    }
}
