<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'type',
        'description',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log activity dengan format yang user-friendly
     *
     * @param  string  $action  Aksi yang dilakukan (contoh: "Login", "Tambah Mitra", "Ubah Data Kegiatan")
     * @param  string  $type  Tipe aktivitas (auth, mitra, kegiatan, user, system, alokasi, sbml, dipa, sk_kpa, bast, spk)
     * @param  string  $description  Deskripsi lengkap yang mudah dimengerti
     * @param  string  $status  Status: success, error, warning
     * @param  array|null  $metadata  Data tambahan yang relevan
     */
    public static function log(string $action, string $type, string $description, string $status = 'success', ?array $metadata = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'type' => $type,
            'description' => $description,
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Shorthand untuk log autentikasi
     */
    public static function logAuth(string $action, string $description, string $status = 'success', ?array $metadata = null): void
    {
        self::log($action, 'auth', $description, $status, $metadata);
    }

    /**
     * Shorthand untuk log sistem
     */
    public static function logSystem(string $action, string $description, string $status = 'success', ?array $metadata = null): void
    {
        self::log($action, 'system', $description, $status, $metadata);
    }

    /**
     * Shorthand untuk log error
     */
    public static function logError(string $action, string $type, string $description, ?array $metadata = null): void
    {
        self::log($action, $type, $description, 'error', $metadata);
    }
}
