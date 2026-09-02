<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityLogExport;
use App\Http\Requests\Settings\UpdateFeatureToggleRequest;
use App\Http\Requests\Settings\UpdateMaintenanceRequest;
use App\Models\ActivityLog;
use App\Models\DeadlineBypass;
use App\Models\DeadlineBypassRequest;
use App\Models\DeadlineRule;
use App\Models\FeatureToggle;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

class SystemSettingsController
{
    private const SSO_SYNC_CACHE_KEY = 'settings:sso_sync_enabled';

    public function __construct(
        private DatabaseBackupService $backupService
    ) {}

    public function index(): Response
    {
        $maintenance = app()->isDownForMaintenance();
        $message = Storage::exists('framework/maintenance-message.txt')
            ? Storage::get('framework/maintenance-message.txt')
            : Config::get('app.maintenance_message');
        $ssoSyncEnabled = Cache::get(self::SSO_SYNC_CACHE_KEY);
        $featureToggles = FeatureToggle::ordered()->map(fn (FeatureToggle $toggle) => [
            'key' => $toggle->key,
            'label' => $toggle->label,
            'description' => $toggle->description,
            'enabled' => $toggle->enabled,
            'sort_order' => $toggle->sort_order,
        ]);
        $deadlineStorageReady = DeadlineRule::supportsStorage();
        $users = User::query()
            ->select(['id', 'name'])
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);
        $deadlineRules = collect();
        $recentBypasses = collect();
        $pendingBypassRequests = collect();

        if ($deadlineStorageReady) {
            $deadlineRules = DeadlineRule::ordered()->map(fn (DeadlineRule $rule) => [
                'id' => $rule->id,
                'key' => $rule->key,
                'feature_key' => $rule->feature_key,
                'action_key' => $rule->action_key,
                'label' => $rule->label,
                'description' => $rule->description,
                'deadline_at' => $rule->deadline_at?->format('Y-m-d H:i:s'),
                'cutoff_day' => $rule->cutoff_day,
                'is_enforced' => $rule->is_enforced,
                'allow_manual_bypass' => $rule->allow_manual_bypass,
                'scope_type' => $rule->scope_type,
                'sort_order' => $rule->sort_order,
            ]);
        } else {
            $deadlineRules = collect(DeadlineRule::defaultDefinitions())->map(fn (array $definition) => [
                'id' => 0,
                'key' => $definition['key'],
                'feature_key' => $definition['feature_key'],
                'action_key' => $definition['action_key'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'deadline_at' => null,
                'cutoff_day' => $definition['cutoff_day'],
                'is_enforced' => true,
                'allow_manual_bypass' => true,
                'scope_type' => $definition['scope_type'],
                'sort_order' => $definition['sort_order'],
            ]);
        }

        if (Schema::hasTable('deadline_bypasses')) {
            $recentBypasses = DeadlineBypass::query()
                ->with(['deadlineRule:id,key,label', 'approvedBy:id,name', 'grantedFor:id,name'])
                ->orderByDesc('id')
                ->limit(25)
                ->get()
                ->map(fn (DeadlineBypass $bypass) => [
                    'id' => $bypass->id,
                    'deadline_rule_id' => $bypass->deadline_rule_id,
                    'rule_key' => $bypass->deadlineRule?->key,
                    'rule_label' => $bypass->deadlineRule?->label,
                    'granted_for_user_id' => $bypass->granted_for_user_id,
                    'approved_by_user_id' => $bypass->approved_by_user_id,
                    'kegiatan_id' => $bypass->kegiatan_id,
                    'periode_alokasi_id' => $bypass->periode_alokasi_id,
                    'year' => $bypass->year,
                    'month' => $bypass->month,
                    'approved_by' => $bypass->approvedBy?->name,
                    'granted_for' => $bypass->grantedFor?->name,
                    'reason' => $bypass->reason,
                    'max_uses' => $bypass->max_uses,
                    'uses_count' => $bypass->uses_count,
                    'is_active' => $bypass->is_active,
                    'expires_at' => $bypass->expires_at?->format('Y-m-d H:i:s'),
                    'consumed_at' => $bypass->consumed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $bypass->created_at?->format('Y-m-d H:i:s'),
                    'metadata' => $bypass->metadata ?? null,
                ]);
        }

        if (Schema::hasTable('deadline_bypass_requests')) {
            $pendingBypassRequests = DeadlineBypassRequest::query()
                ->with([
                    'deadlineRule:id,key,label',
                    'requestedBy:id,name',
                    'reviewedBy:id,name',
                ])
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (DeadlineBypassRequest $request) => [
                    'id' => $request->id,
                    'rule_key' => $request->deadlineRule?->key,
                    'rule_label' => $request->deadlineRule?->label,
                    'requested_by' => $request->requestedBy?->name,
                    'reviewed_by' => $request->reviewedBy?->name,
                    'kegiatan_id' => $request->kegiatan_id,
                    'periode_alokasi_id' => $request->periode_alokasi_id,
                    'year' => $request->year,
                    'month' => $request->month,
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'route_name' => $request->route_name,
                    'http_method' => $request->http_method,
                    'target_url' => $request->target_url,
                    'max_uses' => $request->max_uses,
                    'expires_at' => $request->expires_at?->format('Y-m-d H:i:s'),
                    'review_note' => $request->review_note,
                    'reviewed_at' => $request->reviewed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $request->created_at?->format('Y-m-d H:i:s'),
                ]);
        }

        if (! is_bool($ssoSyncEnabled)) {
            $ssoSyncEnabled = (bool) config('services.sso.sync_enabled', true);
        }

        return Inertia::render('Admin/SystemSettings', [
            'maintenance' => $maintenance,
            'message' => $message,
            'sso_sync_enabled' => $ssoSyncEnabled,
            'session_lifetime' => (int) config('session.lifetime', 120),
            'feature_toggles' => $featureToggles,
            'deadline_rules' => $deadlineRules,
            'deadline_bypasses' => $recentBypasses,
            'deadline_bypass_requests' => $pendingBypassRequests,
            'deadline_storage_ready' => $deadlineStorageReady,
            'users' => $users,
        ]);
    }

    public function deadlineManagement(): Response
    {
        $deadlineStorageReady = DeadlineRule::supportsStorage();
        $users = User::query()
            ->select(['id', 'name'])
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        $deadlineRules = collect();
        $recentBypasses = collect();
        $pendingBypassRequests = collect();

        if ($deadlineStorageReady) {
            $deadlineRules = DeadlineRule::ordered()->map(fn (DeadlineRule $rule) => [
                'id' => $rule->id,
                'key' => $rule->key,
                'feature_key' => $rule->feature_key,
                'action_key' => $rule->action_key,
                'label' => $rule->label,
                'description' => $rule->description,
                'deadline_at' => $rule->deadline_at?->format('Y-m-d H:i:s'),
                'cutoff_day' => $rule->cutoff_day,
                'is_enforced' => $rule->is_enforced,
                'allow_manual_bypass' => $rule->allow_manual_bypass,
                'scope_type' => $rule->scope_type,
                'sort_order' => $rule->sort_order,
            ]);
        } else {
            $deadlineRules = collect(DeadlineRule::defaultDefinitions())->map(fn (array $definition) => [
                'id' => 0,
                'key' => $definition['key'],
                'feature_key' => $definition['feature_key'],
                'action_key' => $definition['action_key'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'deadline_at' => null,
                'cutoff_day' => $definition['cutoff_day'],
                'is_enforced' => true,
                'allow_manual_bypass' => true,
                'scope_type' => $definition['scope_type'],
                'sort_order' => $definition['sort_order'],
            ]);
        }

        if (Schema::hasTable('deadline_bypasses')) {
            $recentBypasses = DeadlineBypass::query()
                ->with(['deadlineRule:id,key,label', 'approvedBy:id,name', 'grantedFor:id,name'])
                ->orderByDesc('id')
                ->limit(25)
                ->get()
                ->map(fn (DeadlineBypass $bypass) => [
                    'id' => $bypass->id,
                    'deadline_rule_id' => $bypass->deadline_rule_id,
                    'rule_key' => $bypass->deadlineRule?->key,
                    'rule_label' => $bypass->deadlineRule?->label,
                    'granted_for_user_id' => $bypass->granted_for_user_id,
                    'approved_by_user_id' => $bypass->approved_by_user_id,
                    'kegiatan_id' => $bypass->kegiatan_id,
                    'periode_alokasi_id' => $bypass->periode_alokasi_id,
                    'year' => $bypass->year,
                    'month' => $bypass->month,
                    'approved_by' => $bypass->approvedBy?->name,
                    'granted_for' => $bypass->grantedFor?->name,
                    'reason' => $bypass->reason,
                    'max_uses' => $bypass->max_uses,
                    'uses_count' => $bypass->uses_count,
                    'is_active' => $bypass->is_active,
                    'expires_at' => $bypass->expires_at?->format('Y-m-d H:i:s'),
                    'consumed_at' => $bypass->consumed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $bypass->created_at?->format('Y-m-d H:i:s'),
                    'metadata' => $bypass->metadata ?? null,
                ]);
        }

        if (Schema::hasTable('deadline_bypass_requests')) {
            $pendingBypassRequests = DeadlineBypassRequest::query()
                ->with([
                    'deadlineRule:id,key,label',
                    'requestedBy:id,name',
                    'reviewedBy:id,name',
                ])
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (DeadlineBypassRequest $request) => [
                    'id' => $request->id,
                    'rule_key' => $request->deadlineRule?->key,
                    'rule_label' => $request->deadlineRule?->label,
                    'requested_by' => $request->requestedBy?->name,
                    'reviewed_by' => $request->reviewedBy?->name,
                    'kegiatan_id' => $request->kegiatan_id,
                    'periode_alokasi_id' => $request->periode_alokasi_id,
                    'year' => $request->year,
                    'month' => $request->month,
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'route_name' => $request->route_name,
                    'http_method' => $request->http_method,
                    'target_url' => $request->target_url,
                    'max_uses' => $request->max_uses,
                    'expires_at' => $request->expires_at?->format('Y-m-d H:i:s'),
                    'review_note' => $request->review_note,
                    'reviewed_at' => $request->reviewed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $request->created_at?->format('Y-m-d H:i:s'),
                ]);
        }

        return Inertia::render('Admin/DeadlineManagement', [
            'deadline_rules' => $deadlineRules,
            'deadline_bypasses' => $recentBypasses,
            'deadline_bypass_requests' => $pendingBypassRequests,
            'deadline_storage_ready' => $deadlineStorageReady,
            'users' => $users,
        ]);
    }

    public function updateFeatureToggle(UpdateFeatureToggleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $toggle = FeatureToggle::updateState(
            (string) $validated['key'],
            (bool) $validated['enabled']
        );

        ActivityLog::logSystem(
            'Pengaturan Fitur Diperbarui',
            'Fitur '.$toggle->label.' '.($toggle->enabled ? 'diaktifkan' : 'dinonaktifkan').'.',
            'info',
            [
                'feature_key' => $toggle->key,
                'enabled' => $toggle->enabled,
                'user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'feature_toggle' => [
                'key' => $toggle->key,
                'label' => $toggle->label,
                'description' => $toggle->description,
                'enabled' => $toggle->enabled,
                'sort_order' => $toggle->sort_order,
            ],
        ]);
    }

    public function updateSsoSync(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['enabled'];

        Cache::forever(self::SSO_SYNC_CACHE_KEY, $enabled);
        config()->set('services.sso.sync_enabled', $enabled);

        ActivityLog::logSystem(
            'Pengaturan SSO Sync Diperbarui',
            'Sinkronisasi sesi SSO '.($enabled ? 'diaktifkan' : 'dinonaktifkan').'.',
            'info',
            [
                'enabled' => $enabled,
                'user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'session_lifetime' => (int) config('session.lifetime', 120),
        ]);
    }

    public function updateDeadlineRule(Request $request): JsonResponse
    {
        if (! DeadlineRule::supportsStorage()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel deadline_rules belum tersedia. Jalankan migrasi terlebih dahulu.',
            ], 422);
        }

        DeadlineRule::ensureDefaults();

        $validated = $request->validate([
            'key' => ['required', 'string', 'exists:deadline_rules,key'],
            'cutoff_day' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        /** @var DeadlineRule $rule */
        $rule = DeadlineRule::query()->where('key', (string) $validated['key'])->firstOrFail();

        $rule->forceFill([
            'deadline_at' => null,
            'cutoff_day' => (int) $validated['cutoff_day'],
            'is_enforced' => true,
            'allow_manual_bypass' => true,
            'updated_by_user_id' => Auth::id(),
        ])->save();

        ActivityLog::logSystem(
            'Pengaturan Batas Waktu Diperbarui',
            'Batas waktu '.$rule->label.' diperbarui.',
            'info',
            [
                'deadline_rule_key' => $rule->key,
                'cutoff_day' => $rule->cutoff_day,
                'user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'deadline_rule' => [
                'id' => $rule->id,
                'key' => $rule->key,
                'label' => $rule->label,
                'cutoff_day' => $rule->cutoff_day,
                'is_enforced' => $rule->is_enforced,
                'allow_manual_bypass' => $rule->allow_manual_bypass,
            ],
        ]);
    }

    public function grantDeadlineBypass(Request $request): JsonResponse
    {
        if (! DeadlineRule::supportsStorage() || ! Schema::hasTable('deadline_bypasses')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel deadline belum tersedia. Jalankan migrasi terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validate([
            'rule_key' => ['nullable', 'string', 'exists:deadline_rules,key'],
            'rule_keys' => ['nullable', 'array', 'min:1'],
            'rule_keys.*' => ['string', 'exists:deadline_rules,key'],
            'kegiatan_id' => ['nullable', 'integer', 'exists:kegiatan,id'],
            'periode_alokasi_id' => ['nullable', 'integer', 'exists:periode_alokasi,id'],
            'granted_for_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'expires_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $minDate = now()->startOfDay()->toDateString();
                    $maxDate = now()->endOfMonth()->toDateString();
                    $selectedDate = Carbon::parse((string) $value)->toDateString();

                    if ($selectedDate < $minDate) {
                        $fail('Batas waktu bypass minimum adalah hari ini.');
                    }

                    if ($selectedDate > $maxDate) {
                        $fail('Batas waktu bypass maksimum adalah akhir bulan ini.');
                    }
                },
            ],
        ]);

        $ruleKeys = array_values(array_unique(array_filter([
            isset($validated['rule_key']) && $validated['rule_key'] !== '' ? (string) $validated['rule_key'] : null,
            ...((array) ($validated['rule_keys'] ?? [])),
        ], fn (?string $ruleKey) => $ruleKey !== null && $ruleKey !== '')));

        if ($ruleKeys === []) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu jenis akses untuk bypass.',
            ], 422);
        }

        $activeYear = now()->year;
        $activeMonth = now()->month;
        $effectiveMaxUses = 0;

        $rules = DeadlineRule::query()
            ->whereIn('key', $ruleKeys)
            ->orderBy('sort_order')
            ->get();

        if ($rules->count() !== count($ruleKeys)) {
            return response()->json([
                'success' => false,
                'message' => 'Satu atau lebih jenis akses tidak valid.',
            ], 422);
        }

        $createdBypasses = [];

        DB::transaction(function () use ($rules, $validated, $ruleKeys, $activeYear, $activeMonth, $effectiveMaxUses, &$createdBypasses) {
            foreach ($rules as $rule) {
                $createdBypasses[] = DeadlineBypass::query()->create([
                    'deadline_rule_id' => $rule->id,
                    'kegiatan_id' => $validated['kegiatan_id'] ?? null,
                    'periode_alokasi_id' => $validated['periode_alokasi_id'] ?? null,
                    'year' => $activeYear,
                    'month' => $activeMonth,
                    'approved_by_user_id' => (int) Auth::id(),
                    'granted_for_user_id' => (int) $validated['granted_for_user_id'],
                    'reason' => $validated['reason'] ?? null,
                    'max_uses' => $effectiveMaxUses,
                    'uses_count' => 0,
                    'is_active' => true,
                    'expires_at' => $validated['expires_at'] ? Carbon::parse((string) $validated['expires_at'])->endOfDay() : null,
                    'metadata' => [
                        'source' => 'manual_admin_grant',
                        'rule_keys' => $ruleKeys,
                    ],
                ]);
            }
        });

        ActivityLog::logSystem(
            'Bypass Batas Waktu Dibuat',
            'Bypass manual untuk '.count($createdBypasses).' jenis akses berhasil dibuat.',
            'info',
            [
                'deadline_rule_keys' => $ruleKeys,
                'kegiatan_id' => $validated['kegiatan_id'] ?? null,
                'periode_alokasi_id' => $validated['periode_alokasi_id'] ?? null,
                'year' => $activeYear,
                'month' => $activeMonth,
                'granted_for_user_id' => (int) $validated['granted_for_user_id'],
                'max_uses' => $effectiveMaxUses,
                'expires_at' => $validated['expires_at'] ? Carbon::parse((string) $validated['expires_at'])->endOfDay()->toDateTimeString() : null,
                'user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'deadline_bypass' => [
                'count' => count($createdBypasses),
                'rule_keys' => $ruleKeys,
                'granted_for_user_id' => (int) $validated['granted_for_user_id'],
                'year' => $activeYear,
                'month' => $activeMonth,
                'max_uses' => $effectiveMaxUses,
                'expires_at' => $validated['expires_at'] ? Carbon::parse((string) $validated['expires_at'])->endOfDay()->format('Y-m-d H:i:s') : null,
            ],
        ]);
    }

    public function approveDeadlineBypassRequest(Request $request, int $requestId): JsonResponse
    {
        if (! DeadlineRule::supportsStorage() || ! Schema::hasTable('deadline_bypasses') || ! Schema::hasTable('deadline_bypass_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel deadline belum tersedia. Jalankan migrasi terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var DeadlineBypassRequest $bypassRequest */
        $bypassRequest = DeadlineBypassRequest::query()
            ->with(['deadlineRule'])
            ->findOrFail($requestId);

        if ($bypassRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request bypass ini sudah diproses sebelumnya.',
            ], 422);
        }

        DB::transaction(function () use ($bypassRequest, $validated) {
            DeadlineBypass::query()->create([
                'deadline_rule_id' => $bypassRequest->deadline_rule_id,
                'kegiatan_id' => $bypassRequest->kegiatan_id,
                'periode_alokasi_id' => $bypassRequest->periode_alokasi_id,
                'year' => $bypassRequest->year,
                'month' => $bypassRequest->month,
                'approved_by_user_id' => (int) Auth::id(),
                'granted_for_user_id' => $bypassRequest->requested_by_user_id,
                'reason' => $bypassRequest->reason,
                'max_uses' => max(1, (int) $bypassRequest->max_uses),
                'uses_count' => 0,
                'is_active' => true,
                'expires_at' => $bypassRequest->expires_at,
                'metadata' => [
                    'source' => 'deadline_bypass_request',
                    'request_id' => $bypassRequest->id,
                ],
            ]);

            $bypassRequest->forceFill([
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ])->save();
        });

        ActivityLog::logSystem(
            'Request Bypass Deadline Disetujui',
            'Request bypass untuk '.$bypassRequest->deadlineRule?->label.' disetujui admin.',
            'success',
            [
                'deadline_bypass_request_id' => $bypassRequest->id,
                'deadline_rule_key' => $bypassRequest->deadlineRule?->key,
                'requested_by_user_id' => $bypassRequest->requested_by_user_id,
                'reviewed_by_user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Request bypass berhasil disetujui.',
        ]);
    }

    public function revokeDeadlineBypass(Request $request, int $bypassId): JsonResponse
    {
        if (! DeadlineRule::supportsStorage() || ! Schema::hasTable('deadline_bypasses')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel bypass belum tersedia. Jalankan migrasi terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var DeadlineBypass $bypass */
        $bypass = DeadlineBypass::query()->findOrFail($bypassId);

        if (! $bypass->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Bypass ini sudah tidak aktif.',
            ], 422);
        }

        $newReason = trim((string) ($validated['reason'] ?? ''));
        $baseReason = $bypass->reason ? trim((string) $bypass->reason) : null;
        $combinedReason = $newReason !== ''
            ? ($baseReason ? $baseReason.' | '.$newReason : $newReason)
            : $baseReason;

        $bypass->forceFill([
            'is_active' => false,
            'reason' => $combinedReason,
            'consumed_at' => now(),
            'metadata' => array_merge((array) $bypass->metadata, [
                'revoked_by_admin' => true,
                'revoked_at' => now()->toDateTimeString(),
                'revocation_reason' => $newReason !== '' ? $newReason : 'Dicabut oleh admin',
            ]),
        ])->save();

        ActivityLog::logSystem(
            'Bypass Batas Waktu Dicabut',
            'Bypass manual untuk '.($bypass->deadlineRule?->label ?? 'fitur').' dicabut oleh admin.',
            'warning',
            [
                'deadline_bypass_id' => $bypass->id,
                'deadline_rule_id' => $bypass->deadline_rule_id,
                'granted_for_user_id' => $bypass->granted_for_user_id,
                'revoked_by_user_id' => Auth::id(),
                'reason' => $newReason !== '' ? $newReason : 'Dicabut oleh admin',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bypass berhasil dicabut dan tidak dapat digunakan lagi.',
            'bypass_id' => $bypass->id,
        ]);
    }

    public function rejectDeadlineBypassRequest(Request $request, int $requestId): JsonResponse
    {
        if (! Schema::hasTable('deadline_bypass_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel request bypass belum tersedia. Jalankan migrasi terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        /** @var DeadlineBypassRequest $bypassRequest */
        $bypassRequest = DeadlineBypassRequest::query()->findOrFail($requestId);

        if ($bypassRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request bypass ini sudah diproses sebelumnya.',
            ], 422);
        }

        $bypassRequest->forceFill([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => (string) $validated['review_note'],
        ])->save();

        ActivityLog::logSystem(
            'Request Bypass Deadline Ditolak',
            'Request bypass ditolak admin.',
            'warning',
            [
                'deadline_bypass_request_id' => $bypassRequest->id,
                'deadline_rule_id' => $bypassRequest->deadline_rule_id,
                'requested_by_user_id' => $bypassRequest->requested_by_user_id,
                'reviewed_by_user_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Request bypass berhasil ditolak.',
        ]);
    }

    public function activityLog(Request $request): Response
    {
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters((string) $request->input('encrypted_filters'));
            if (! empty($decrypted)) {
                $request->merge($decrypted);
            }
        }

        $query = ActivityLog::with('user');
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'user' => $request->input('user'),
            'date' => $request->input('date'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $filters = $this->normalizeActivityLogDateFilters($filters);
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['user']) {
            $query->where('user_id', $filters['user']);
        }
        if ($filters['date_from'] || $filters['date_to']) {
            $dateFrom = $filters['date_from'] ?: $filters['date_to'];
            $dateTo = $filters['date_to'] ?: $filters['date_from'];

            if ($dateFrom && $dateTo && Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        } elseif ($filters['date']) {
            $query->whereDate('created_at', $filters['date']);
        }

        // Get page from request, default to 1
        $page = max(1, (int) $request->input('page', 1));

        // Paginate with 50 items per page, sorted by latest first
        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'page', $page)
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'System',
                    'user_id' => $log->user_id,
                    'user_hashed_id' => $log->user_id ? Hashids::encode((int) $log->user_id) : null,
                    'action' => $log->action,
                    'description' => $log->description,
                    'status' => $log->status,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'time' => $log->created_at?->format('d M Y H:i:s').' WIB',
                    'created_at' => $log->created_at?->toISOString(),
                    'properties' => $log->metadata,
                ];
            });
        $users = User::orderBy('name')->get(['id', 'name']);

        // Remove empty values for filter state
        $cleanFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        // Encrypt filter state for frontend
        $encryptedFilters = encryptFilters($cleanFilters);

        // Encrypt logs data for secure transmission
        $encryptedLogs = encryptData($logs->items());

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $encryptedLogs,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'filters' => [
                'encrypted' => $encryptedFilters,
                'decrypted' => $cleanFilters,
            ],
            'users' => $users,
        ]);
    }

    /**
     * Export activity log to Excel.
     */
    public function exportActivityLog(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'user' => $request->input('user'),
            'date' => $request->input('date'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $filters = $this->normalizeActivityLogDateFilters($filters);

        // Remove null values
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new ActivityLogExport($filters), $filename);
    }

    /**
     * @param  array{search?:mixed,status?:mixed,user?:mixed,date?:mixed,date_from?:mixed,date_to?:mixed}  $filters
     * @return array{search?:mixed,status?:mixed,user?:mixed,date?:mixed,date_from?:mixed,date_to?:mixed}
     */
    private function normalizeActivityLogDateFilters(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $date = $filters['date'] ?? null;

        if (! $dateFrom && ! $dateTo && $date) {
            return $filters;
        }

        if ($dateFrom && ! $dateTo) {
            $filters['date_to'] = now()->toDateString();
        }

        if ($dateTo && ! $dateFrom) {
            $filters['date_from'] = Carbon::parse((string) $dateTo)->startOfYear()->toDateString();
        }

        if (($filters['date_from'] ?? null) && ($filters['date_to'] ?? null)) {
            $normalizedFrom = Carbon::parse((string) $filters['date_from']);
            $normalizedTo = Carbon::parse((string) $filters['date_to']);

            if ($normalizedFrom->greaterThan($normalizedTo)) {
                $filters['date_from'] = $normalizedTo->toDateString();
                $filters['date_to'] = $normalizedFrom->toDateString();
            }
        }

        return $filters;
    }

    public function databaseStatus(): Response
    {
        // Get DB connection info
        $connection = Config::get('database.default');
        $status = 'Connected';
        $tables = [];
        $tableCount = 0;
        $dbSize = 0;
        $lastBackup = null;
        try {
            $dbName = DB::getDatabaseName();
            $tables = DB::select('SELECT table_name AS `name`, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `size_mb`, table_rows AS `rows` FROM information_schema.tables WHERE table_schema = ? ORDER BY (data_length + index_length) DESC', [$dbName]);
            $tableCount = count($tables);
            $dbSize = array_sum(array_map(fn ($t) => (float) ($t->size_mb), $tables));
        } catch (\Exception $e) {
            $status = 'Error: '.$e->getMessage();
        }
        // Find last backup file
        $backups = $this->backupService->listBackups();
        $lastBackup = $backups[0] ?? null;
        $lastBackupFile = $lastBackup['filename'] ?? null;

        return Inertia::render('Admin/DatabaseStatus', [
            'connection' => $connection,
            'status' => $status,
            'size' => round($dbSize, 2).' MB',
            'tables' => $tables,
            'tableCount' => $tableCount,
            'lastBackup' => $lastBackup['created_at'] ?? null,
            'lastBackupFile' => $lastBackupFile,
        ]);
    }

    /**
     * Trigger a database backup and return the result.
     * Using PHP-based backup (compatible with shared hosting)
     */
    public function backupDatabase(Request $request)
    {
        try {
            $result = $this->backupService->createBackup();

            if ($result['success']) {
                // Create log entry
                $log = ActivityLog::create([
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()?->name ?? 'System',
                    'action' => 'Backup Database',
                    'type' => 'system',
                    'description' => 'Database berhasil di-backup: '.$result['filename'].' ('.$result['size_formatted'].')',
                    'status' => 'success',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'filename' => $result['filename'],
                        'size' => $result['size'],
                        'method' => 'php_native',
                    ],
                ]);

                // Ensure log is committed to database before returning response
                if ($log) {
                    $log->refresh();
                }

                return response()->json([
                    'success' => true,
                    'file' => $result['filename'],
                    'size' => $result['size_formatted'],
                ]);
            }

            ActivityLog::logError(
                'Backup Database',
                'system',
                'Gagal membuat backup database: '.($result['error'] ?? 'Unknown error'),
                ['error' => $result['error'] ?? null]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            ActivityLog::logError(
                'Backup Database',
                'system',
                'Error saat backup database: '.$e->getMessage(),
                ['exception' => get_class($e)]
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore database from a backup file.
     * Using PHP-based restore (compatible with shared hosting)
     */
    public function restoreDatabase(Request $request)
    {
        $file = $request->input('file');

        try {
            $result = $this->backupService->restoreBackup($file);

            if ($result['success']) {
                ActivityLog::logSystem(
                    'Restore Database',
                    'Database berhasil di-restore dari backup: '.$file,
                    'success',
                    ['filename' => $file, 'method' => 'php_native']
                );

                return response()->json($result);
            }

            ActivityLog::logError(
                'Restore Database',
                'system',
                'Gagal restore database dari: '.$file.' - '.($result['error'] ?? 'Unknown error'),
                ['filename' => $file, 'error' => $result['error'] ?? null]
            );

            return response()->json($result);

        } catch (\Exception $e) {
            ActivityLog::logError(
                'Restore Database',
                'system',
                'Error saat restore database: '.$e->getMessage(),
                ['filename' => $file, 'exception' => get_class($e)]
            );

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update maintenance mode and message.
     */
    public function updateMaintenance(UpdateMaintenanceRequest $request)
    {
        $enabled = $request->boolean('enabled');
        $message = $request->input('message');

        // Save message to storage/framework/maintenance-message.txt for 503 page
        if ($message) {
            Storage::put('framework/maintenance-message.txt', $message);
        } else {
            if (Storage::exists('framework/maintenance-message.txt')) {
                Storage::delete('framework/maintenance-message.txt');
            }
        }

        // Set config value for fallback (optional, for config cache)
        // config(['app.maintenance_message' => $message]);

        // Enable/disable maintenance mode
        if ($enabled) {
            // Generate a bypass secret token
            $secret = Config::get('app.maintenance_bypass_secret') ?: Str::random(40);

            // Use Laravel's built-in maintenance mode with secret
            Artisan::call('down', [
                '--refresh' => 15, // allow refresh every 15s
                '--secret' => $secret,
            ]);

            // Remove prerendered template to allow middleware to work
            $this->removeMaintenancePrerenderTemplate();

            // Create bypass cookie for current admin user
            $bypassCookie = MaintenanceModeBypassCookie::create($secret);

            ActivityLog::logSystem(
                'Mode Maintenance Diaktifkan',
                'Sistem diubah ke mode maintenance'.($message ? ': '.$message : ''),
                'warning',
                ['message' => $message, 'user_id' => Auth::id()]
            );

            // Return response with bypass cookie
            return response()->json([
                'success' => true,
                'maintenance' => $enabled,
                'message' => $message,
            ])->withCookie($bypassCookie);
        } else {
            Artisan::call('up');

            ActivityLog::logSystem(
                'Mode Maintenance Dinonaktifkan',
                'Sistem kembali normal dan dapat diakses',
                'success',
                ['user_id' => Auth::id()]
            );
        }

        // Clear config cache if needed
        Cache::flush();

        return response()->json([
            'success' => true,
            'maintenance' => $enabled,
            'message' => $message,
        ]);
    }

    /**
     * Remove prerendered template from maintenance mode file
     */
    private function removeMaintenancePrerenderTemplate(): void
    {
        $downPath = storage_path('framework/down');

        if (! file_exists($downPath)) {
            return;
        }

        $data = json_decode(file_get_contents($downPath), true);

        if (! is_array($data) || ! array_key_exists('template', $data)) {
            return;
        }

        unset($data['template']);

        file_put_contents($downPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
