<?php

namespace App\Services;

use App\Models\DeadlineBypass;
use App\Models\DeadlineRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DeadlineAccessService
{
    /**
     * @param  array<string, int|string|null>  $scope
     * @return array{allowed:bool,message:?string,bypass:?DeadlineBypass,rule:?DeadlineRule}
     */
    public function evaluate(string $ruleKey, array $scope = [], ?User $user = null): array
    {
        if (! DeadlineRule::supportsStorage() || ! Schema::hasTable('deadline_bypasses')) {
            return [
                'allowed' => true,
                'message' => null,
                'bypass' => null,
                'rule' => null,
            ];
        }

        if ($user && ($user->hasActiveRole('admin') || $user->isAdmin())) {
            return [
                'allowed' => true,
                'message' => null,
                'bypass' => null,
                'rule' => null,
            ];
        }

        DeadlineRule::ensureDefaults();

        $rule = DeadlineRule::query()->where('key', $ruleKey)->first();

        if (! $rule || ! $rule->is_enforced) {
            return [
                'allowed' => true,
                'message' => null,
                'bypass' => null,
                'rule' => $rule,
            ];
        }

        $isDeadlineExceeded = false;
        $deadlineMessage = null;

        if ($rule->scope_type === 'monthly' && $rule->cutoff_day !== null) {
            $monthlyEvaluation = $this->evaluateMonthlyCutoff($rule, $scope);
            $isDeadlineExceeded = ! $monthlyEvaluation['allowed'];
            $deadlineMessage = $monthlyEvaluation['message'];
        } elseif ($rule->deadline_at && now()->greaterThan($rule->deadline_at)) {
            $isDeadlineExceeded = true;
            $deadlineMessage = $this->buildDeadlineExceededMessage($rule);
        }

        if (! $isDeadlineExceeded) {
            return [
                'allowed' => true,
                'message' => null,
                'bypass' => null,
                'rule' => $rule,
            ];
        }

        if (! $rule->allow_manual_bypass) {
            return [
                'allowed' => false,
                'message' => $deadlineMessage,
                'bypass' => null,
                'rule' => $rule,
            ];
        }

        $userId = $user?->id;

        $query = DeadlineBypass::query()
            ->where('deadline_rule_id', $rule->id)
            ->where('is_active', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        $this->applyScopeMatcher($query, 'kegiatan_id', $scope['kegiatan_id'] ?? null);
        $this->applyScopeMatcher($query, 'periode_alokasi_id', $scope['periode_alokasi_id'] ?? null);
        $this->applyScopeMatcher($query, 'year', $scope['year'] ?? null);
        $this->applyScopeMatcher($query, 'month', $scope['month'] ?? null);

        $query->where(function (Builder $builder) use ($userId) {
            $builder->whereNull('granted_for_user_id');

            if ($userId !== null) {
                $builder->orWhere('granted_for_user_id', $userId);
            }
        });

        $bypass = $query->orderByDesc('id')->first();

        if (! $bypass) {
            return [
                'allowed' => false,
                'message' => $deadlineMessage,
                'bypass' => null,
                'rule' => $rule,
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
            'bypass' => $bypass,
            'rule' => $rule,
        ];
    }

    public function consumeBypass(DeadlineBypass $bypass): void
    {
        $nextUses = (int) $bypass->uses_count + 1;
        $expiresAt = $bypass->expires_at;
        $isStillValid = $expiresAt === null || $expiresAt->greaterThan(now());

        $bypass->forceFill([
            'uses_count' => $nextUses,
            'consumed_at' => now(),
            'is_active' => $isStillValid,
        ])->save();
    }

    /**
     * @param  array<string, int|string|null>  $scope
     * @return array{allowed:bool,message:?string}
     */
    private function evaluateMonthlyCutoff(DeadlineRule $rule, array $scope): array
    {
        $cutoffDay = (int) ($rule->cutoff_day ?? 0);

        if ($cutoffDay < 1 || $cutoffDay > 31) {
            return [
                'allowed' => true,
                'message' => null,
            ];
        }

        $targetYear = isset($scope['year']) && is_numeric((string) $scope['year'])
            ? (int) $scope['year']
            : null;
        $targetMonth = isset($scope['month']) && is_numeric((string) $scope['month'])
            ? (int) $scope['month']
            : null;

        if ($targetYear === null || $targetMonth === null || $targetMonth < 1 || $targetMonth > 12) {
            return [
                'allowed' => true,
                'message' => null,
            ];
        }

        if ($rule->key === 'alokasi.revisi' || $rule->action_key === 'revisi') {
            return $this->evaluateAlokasiRevisiCutoff($rule, $cutoffDay, $targetYear, $targetMonth);
        }

        $currentTime = now();
        $targetPeriodStart = $currentTime->copy()
            ->setDate($targetYear, $targetMonth, 1)
            ->startOfDay();

        $cutoffMonth = $targetPeriodStart->copy()->subMonthNoOverflow();
        $effectiveCutoffDay = min($cutoffDay, (int) $cutoffMonth->daysInMonth);
        $cutoffAt = $cutoffMonth->copy()->day($effectiveCutoffDay)->endOfDay();

        if ($currentTime->lessThanOrEqualTo($cutoffAt)) {
            return [
                'allowed' => true,
                'message' => null,
            ];
        }

        return [
            'allowed' => false,
            'message' => $this->buildMonthlyCutoffExceededMessage($rule, $targetYear, $targetMonth, $cutoffAt->translatedFormat('d F Y')),
        ];
    }

    /**
     * Aturan revisi alokasi:
     * - Bulan berjalan selalu diperbolehkan.
     * - Bulan sebelumnya diperbolehkan hanya sampai tanggal cutoff di bulan berjalan.
     * - Periode yang lebih lama dari bulan sebelumnya ditolak.
     *
     * @return array{allowed:bool,message:?string}
     */
    private function evaluateAlokasiRevisiCutoff(DeadlineRule $rule, int $cutoffDay, int $targetYear, int $targetMonth): array
    {
        $currentTime = now();
        $currentPeriod = $currentTime->copy()->startOfMonth();
        $targetPeriod = $currentTime->copy()->setDate($targetYear, $targetMonth, 1)->startOfMonth();

        if ($targetPeriod->greaterThanOrEqualTo($currentPeriod)) {
            return [
                'allowed' => true,
                'message' => null,
            ];
        }

        $previousPeriod = $currentPeriod->copy()->subMonthNoOverflow();

        if ($targetPeriod->equalTo($previousPeriod)) {
            $effectiveCutoffDay = min($cutoffDay, (int) $currentPeriod->daysInMonth);
            $cutoffAt = $currentPeriod->copy()->day($effectiveCutoffDay)->endOfDay();

            if ($currentTime->lessThanOrEqualTo($cutoffAt)) {
                return [
                    'allowed' => true,
                    'message' => null,
                ];
            }

            return [
                'allowed' => false,
                'message' => $this->buildAlokasiRevisiCutoffExceededMessage(
                    $rule,
                    $targetYear,
                    $targetMonth,
                    $cutoffAt->translatedFormat('d F Y')
                ),
            ];
        }

        return [
            'allowed' => false,
            'message' => $this->buildAlokasiRevisiCutoffExceededMessage(
                $rule,
                $targetYear,
                $targetMonth,
                $currentPeriod->copy()->day(min($cutoffDay, (int) $currentPeriod->daysInMonth))->translatedFormat('d F Y')
            ),
        ];
    }

    private function buildDeadlineExceededMessage(DeadlineRule $rule): string
    {
        $formattedDeadline = $rule->deadline_at?->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i');

        return $formattedDeadline
            ? sprintf('Batas waktu %s telah berakhir pada %s.', $rule->label, $formattedDeadline)
            : sprintf('Batas waktu %s telah berakhir.', $rule->label);
    }

    private function buildMonthlyCutoffExceededMessage(DeadlineRule $rule, int $targetYear, int $targetMonth, string $cutoffDateLabel): string
    {
        $periodeLabel = now()
            ->setDate($targetYear, $targetMonth, 1)
            ->translatedFormat('F Y');

        return sprintf(
            'Aksi %s untuk periode %s ditutup setelah %s. Setelah cutoff, periode ini hanya dapat diproses dengan persetujuan admin.',
            $rule->label,
            $periodeLabel,
            $cutoffDateLabel,
        );
    }

    private function buildAlokasiRevisiCutoffExceededMessage(DeadlineRule $rule, int $targetYear, int $targetMonth, string $cutoffDateLabel): string
    {
        $periodeLabel = now()
            ->setDate($targetYear, $targetMonth, 1)
            ->translatedFormat('F Y');

        return sprintf(
            'Aksi %s untuk periode %s ditutup. Revisi hanya diperbolehkan untuk bulan berjalan, atau bulan sebelumnya maksimal sampai %s.',
            $rule->label,
            $periodeLabel,
            $cutoffDateLabel,
        );
    }

    private function applyScopeMatcher(Builder $query, string $column, int|string|null $value): void
    {
        $query->where(function (Builder $builder) use ($column, $value) {
            $builder->whereNull($column);

            if ($value !== null && $value !== '') {
                $builder->orWhere($column, $value);
            }
        });
    }
}
