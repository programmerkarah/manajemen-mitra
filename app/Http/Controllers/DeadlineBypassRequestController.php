<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DeadlineBypassRequest;
use App\Models\DeadlineRule;
use App\Services\DeadlineAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DeadlineBypassRequestController extends Controller
{
    public function __construct(private DeadlineAccessService $deadlineAccessService) {}

    public function store(Request $request): JsonResponse
    {
        if (! DeadlineRule::supportsStorage() || ! Schema::hasTable('deadline_bypass_requests')) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur request bypass belum aktif. Jalankan migrasi database terlebih dahulu.',
            ], 422);
        }

        $validated = $request->validate([
            'rule_key' => ['required', 'string', 'exists:deadline_rules,key'],
            'kegiatan_id' => ['nullable', 'integer', 'exists:kegiatan,id'],
            'periode_alokasi_id' => ['nullable', 'integer', 'exists:periode_alokasi,id'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'reason' => ['required', 'string', 'max:2000'],
            'route_name' => ['nullable', 'string', 'max:200'],
            'http_method' => ['nullable', 'string', 'max:10'],
            'target_url' => ['nullable', 'string', 'max:500'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $effectiveUser = effectiveUser($request);

        if ($effectiveUser === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terdeteksi. Silakan login ulang.',
            ], 401);
        }

        $rule = DeadlineRule::query()->where('key', (string) $validated['rule_key'])->firstOrFail();

        $scope = [
            'kegiatan_id' => $validated['kegiatan_id'] ?? null,
            'periode_alokasi_id' => $validated['periode_alokasi_id'] ?? null,
            'year' => $validated['year'] ?? null,
            'month' => $validated['month'] ?? null,
        ];

        $evaluation = $this->deadlineAccessService->evaluate($rule->key, $scope, $effectiveUser);

        if ($evaluation['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Aksi ini saat ini tidak memerlukan bypass.',
            ], 422);
        }

        $existingPendingRequest = DeadlineBypassRequest::query()
            ->where('deadline_rule_id', $rule->id)
            ->where('requested_by_user_id', $effectiveUser->id)
            ->where('status', 'pending')
            ->where('kegiatan_id', $scope['kegiatan_id'])
            ->where('periode_alokasi_id', $scope['periode_alokasi_id'])
            ->where('year', $scope['year'])
            ->where('month', $scope['month'])
            ->first();

        if ($existingPendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request bypass untuk konteks ini sudah diajukan dan menunggu persetujuan admin.',
            ], 422);
        }

        $bypassRequest = DeadlineBypassRequest::query()->create([
            'deadline_rule_id' => $rule->id,
            'requested_by_user_id' => $effectiveUser->id,
            'kegiatan_id' => $scope['kegiatan_id'],
            'periode_alokasi_id' => $scope['periode_alokasi_id'],
            'year' => $scope['year'],
            'month' => $scope['month'],
            'status' => 'pending',
            'route_name' => $validated['route_name'] ?? null,
            'http_method' => isset($validated['http_method']) ? strtoupper((string) $validated['http_method']) : null,
            'target_url' => $validated['target_url'] ?? null,
            'reason' => (string) $validated['reason'],
            'max_uses' => (int) ($validated['max_uses'] ?? 1),
            'expires_at' => $validated['expires_at'] ?? null,
            'metadata' => [
                'source' => 'deadline_block_prompt',
            ],
        ]);

        ActivityLog::logSystem(
            'Request Bypass Deadline Diajukan',
            'User mengajukan bypass untuk '.$rule->label.'.',
            'warning',
            [
                'deadline_bypass_request_id' => $bypassRequest->id,
                'deadline_rule_key' => $rule->key,
                'requested_by_user_id' => $effectiveUser->id,
                'kegiatan_id' => $bypassRequest->kegiatan_id,
                'periode_alokasi_id' => $bypassRequest->periode_alokasi_id,
                'year' => $bypassRequest->year,
                'month' => $bypassRequest->month,
                'target_url' => $bypassRequest->target_url,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Request bypass berhasil dikirim. Menunggu persetujuan admin.',
            'request_id' => $bypassRequest->id,
        ]);
    }
}
