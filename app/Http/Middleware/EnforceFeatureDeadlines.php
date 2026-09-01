<?php

namespace App\Http\Middleware;

use App\Models\PeriodeAlokasi;
use App\Models\User;
use App\Services\DeadlineAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vinkla\Hashids\Facades\Hashids;

class EnforceFeatureDeadlines
{
    public function __construct(private DeadlineAccessService $deadlineAccessService) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $effectiveUser = effectiveUser($request);

        if (! is_string($routeName) || trim($routeName) === '') {
            return $next($request);
        }

        if ($this->isAdminUser($effectiveUser)) {
            return $next($request);
        }

        $scope = $this->resolveScope($request);
        $ruleKey = $this->resolveRuleKey($routeName, $request->method(), $request, $scope);

        if ($ruleKey === null) {
            return $next($request);
        }

        $requestContext = $this->buildBypassRequestContext($request, $ruleKey, $scope);

        $evaluation = $this->deadlineAccessService->evaluate($ruleKey, $scope, $effectiveUser);

        if (! $evaluation['allowed']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $evaluation['message'] ?? 'Aksi ditolak karena melewati batas waktu.',
                    'rule_key' => $ruleKey,
                    'request_context' => $requestContext,
                ], 423);
            }

            return back()
                ->with('warning', $evaluation['message'] ?? 'Aksi ditolak karena melewati batas waktu.')
                ->with('deadline_blocked', [
                    'message' => $evaluation['message'] ?? 'Aksi ditolak karena melewati batas waktu.',
                    'rule_key' => $ruleKey,
                    'request_context' => $requestContext,
                ]);
        }

        $response = $next($request);

        if (
            ($evaluation['bypass'] ?? null)
            && $response->getStatusCode() < 400
            && $this->shouldConsumeBypass($request, $ruleKey)
        ) {
            $this->deadlineAccessService->consumeBypass($evaluation['bypass']);
        }

        return $response;
    }

    private function shouldConsumeBypass(Request $request, string $ruleKey): bool
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '') {
            return false;
        }

        $terminalRoutes = [
            'alokasi.submit',
            'alokasi.periode.submit',
            'alokasi.approve',
            'alokasi.reject',
            'pengajuan-pulsa.store',
            'pengajuan-pulsa.resubmit',
            'pengajuan-pulsa.review',
            'pengajuan-pulsa.review-all',
            'spk.store',
            'spk.update',
            'bast.store',
            'bast.update',
            'sk-kpa.store',
            'sk-kpa.update',
        ];

        return in_array($routeName, $terminalRoutes, true);
    }

    /**
     * @param  array{kegiatan_id:int|null,periode_alokasi_id:int|null,year:int|null,month:int|null}  $scope
     */
    private function resolveRuleKey(string $routeName, string $httpMethod, Request $request, array $scope): ?string
    {
        if (in_array(strtoupper($httpMethod), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        if (str_starts_with($routeName, 'alokasi.')) {
            if ($this->isAlokasiRevisionAction($routeName, $request, $scope)) {
                return 'alokasi.revisi';
            }

            return 'alokasi.manage';
        }

        if (str_starts_with($routeName, 'pengajuan-pulsa.')) {
            return 'pengajuan_pulsa.manage';
        }

        if (str_starts_with($routeName, 'sk-kpa.')) {
            return 'sk.manage';
        }

        if (str_starts_with($routeName, 'spk.')) {
            return str_contains($routeName, 'addendum')
                ? 'spk.addendum'
                : 'spk.manage';
        }

        if (str_starts_with($routeName, 'bast.')) {
            return 'bast.manage';
        }

        return null;
    }

    /**
     * @param  array{kegiatan_id:int|null,periode_alokasi_id:int|null,year:int|null,month:int|null}  $scope
     */
    private function isAlokasiRevisionAction(string $routeName, Request $request, array $scope): bool
    {
        if (str_contains($routeName, '.revisi')) {
            return true;
        }

        if ((bool) $request->session()->get('is_revisi_mode', false)) {
            return true;
        }

        if (! in_array($routeName, ['alokasi.periode.submit', 'alokasi.periode.update'], true)) {
            return false;
        }

        $targetPeriode = $this->resolveTargetPeriodeForAlokasiAction($request, $scope);

        if (! $targetPeriode) {
            return false;
        }

        return $targetPeriode->parent_periode_id !== null || $targetPeriode->status === 'perubahan';
    }

    /**
     * @param  array{kegiatan_id:int|null,periode_alokasi_id:int|null,year:int|null,month:int|null}  $scope
     */
    private function resolveTargetPeriodeForAlokasiAction(Request $request, array $scope): ?PeriodeAlokasi
    {
        $year = $scope['year'];
        $month = $scope['month'];

        if ($year === null || $month === null || $month < 1 || $month > 12) {
            return null;
        }

        $bulanCandidates = $this->resolveBulanCandidates((string) $month);

        if ($scope['periode_alokasi_id'] !== null) {
            $fromPeriodeId = PeriodeAlokasi::query()
                ->select(['id', 'status', 'parent_periode_id', 'revision_number'])
                ->where('id', $scope['periode_alokasi_id'])
                ->where('tahun', $year)
                ->whereIn('bulan', $bulanCandidates)
                ->first();

            if ($fromPeriodeId) {
                return $fromPeriodeId;
            }
        }

        $routeKegiatan = $request->route('kegiatan');
        $candidateIds = [];

        if (is_object($routeKegiatan) && isset($routeKegiatan->id) && is_numeric((string) $routeKegiatan->id)) {
            $candidateIds[] = (int) $routeKegiatan->id;
        } elseif (is_numeric($routeKegiatan)) {
            $candidateIds[] = (int) $routeKegiatan;
        } elseif (is_string($routeKegiatan) && trim($routeKegiatan) !== '') {
            $decoded = Hashids::decode($routeKegiatan);
            if (! empty($decoded[0])) {
                $candidateIds[] = (int) $decoded[0];
            }
        }

        $candidateIds = array_values(array_unique(array_filter($candidateIds, fn (int $value) => $value > 0)));

        foreach ($candidateIds as $candidateId) {
            $byPeriodeId = PeriodeAlokasi::query()
                ->select(['id', 'status', 'parent_periode_id', 'revision_number'])
                ->where('id', $candidateId)
                ->where('tahun', $year)
                ->whereIn('bulan', $bulanCandidates)
                ->first();

            if ($byPeriodeId) {
                return $byPeriodeId;
            }

            $byKegiatan = PeriodeAlokasi::query()
                ->select(['id', 'status', 'parent_periode_id', 'revision_number'])
                ->where('kegiatan_id', $candidateId)
                ->where('tahun', $year)
                ->whereIn('bulan', $bulanCandidates)
                ->whereIn('status', ['draft', 'perubahan', 'dikirim'])
                ->orderByDesc('revision_number')
                ->first();

            if ($byKegiatan) {
                return $byKegiatan;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }

    private function isAdminUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasActiveRole('admin') || $user->isAdmin();
    }

    /**
     * @return array{kegiatan_id:int|null,periode_alokasi_id:int|null,year:int|null,month:int|null}
     */
    private function resolveScope(Request $request): array
    {
        $kegiatanId = $request->integer('kegiatan_id') ?: null;
        $periodeAlokasiId = $request->integer('periode_alokasi_id') ?: null;
        $year = $request->integer('tahun') ?: $request->integer('year') ?: null;
        $month = $request->integer('bulan') ?: $request->integer('month') ?: null;

        $routeYear = $request->route('tahun') ?: $request->route('year');
        $routeMonth = $request->route('bulan') ?: $request->route('month');

        if ($year === null && is_numeric($routeYear)) {
            $year = (int) $routeYear;
        }

        if ($month === null && is_numeric($routeMonth)) {
            $month = (int) $routeMonth;
        }

        if ($year === null || $month === null) {
            [$payloadYear, $payloadMonth] = $this->resolveScopeFromAlokasiPayload($request->input('alokasi'));

            $year ??= $payloadYear;
            $month ??= $payloadMonth;
        }

        $routeKegiatan = $request->route('kegiatan');
        if ($kegiatanId === null) {
            if (is_object($routeKegiatan) && isset($routeKegiatan->id)) {
                $kegiatanId = (int) $routeKegiatan->id;
            } elseif (is_numeric($routeKegiatan)) {
                $kegiatanId = (int) $routeKegiatan;
            }
        }

        $routePeriode = $request->route('periode');
        if ($periodeAlokasiId === null) {
            if (is_object($routePeriode) && isset($routePeriode->id)) {
                $periodeAlokasiId = (int) $routePeriode->id;
            } elseif (is_numeric($routePeriode)) {
                $periodeAlokasiId = (int) $routePeriode;
            }
        }

        if ($periodeAlokasiId === null) {
            $routePeriodeId = $request->route('periodeAlokasiId')
                ?? $request->route('periode_alokasi_id');

            if (is_numeric($routePeriodeId)) {
                $periodeAlokasiId = (int) $routePeriodeId;
            }
        }

        if ($periodeAlokasiId === null) {
            $periodeHashedId = $request->route('periodeHashedId');
            if (is_string($periodeHashedId) && $periodeHashedId !== '') {
                $decoded = Hashids::decode($periodeHashedId);
                if (! empty($decoded[0])) {
                    $periodeAlokasiId = (int) $decoded[0];
                }
            }
        }

        if ($periodeAlokasiId !== null && ($kegiatanId === null || $year === null || $month === null)) {
            $periode = PeriodeAlokasi::query()
                ->select('id', 'kegiatan_id', 'tahun', 'bulan')
                ->find($periodeAlokasiId);

            if ($periode) {
                $kegiatanId ??= (int) $periode->kegiatan_id;
                $year ??= (int) $periode->tahun;
                $month ??= (int) $periode->bulan;
            }
        }

        return [
            'kegiatan_id' => $kegiatanId,
            'periode_alokasi_id' => $periodeAlokasiId,
            'year' => $year,
            'month' => $month,
        ];
    }

    /**
     * @param  array{kegiatan_id:int|null,periode_alokasi_id:int|null,year:int|null,month:int|null}  $scope
     * @return array<string, int|string|null>
     */
    private function buildBypassRequestContext(Request $request, string $ruleKey, array $scope): array
    {
        return [
            'rule_key' => $ruleKey,
            'kegiatan_id' => $scope['kegiatan_id'],
            'periode_alokasi_id' => $scope['periode_alokasi_id'],
            'year' => $scope['year'],
            'month' => $scope['month'],
            'route_name' => $request->route()?->getName(),
            'http_method' => strtoupper($request->method()),
            'target_url' => $request->fullUrl(),
        ];
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function resolveScopeFromAlokasiPayload(mixed $alokasiPayload): array
    {
        if (! is_array($alokasiPayload) || $alokasiPayload === []) {
            return [null, null];
        }

        $oldestYearMonth = null;
        $resolvedYear = null;
        $resolvedMonth = null;

        foreach ($alokasiPayload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowYear = isset($row['tahun']) && is_numeric((string) $row['tahun'])
                ? (int) $row['tahun']
                : null;
            $rowMonth = isset($row['bulan']) && is_numeric((string) $row['bulan'])
                ? (int) $row['bulan']
                : null;

            if ($rowYear === null || $rowMonth === null || $rowMonth < 1 || $rowMonth > 12) {
                continue;
            }

            $rowYearMonth = ($rowYear * 100) + $rowMonth;
            if ($oldestYearMonth === null || $rowYearMonth < $oldestYearMonth) {
                $oldestYearMonth = $rowYearMonth;
                $resolvedYear = $rowYear;
                $resolvedMonth = $rowMonth;
            }
        }

        return [$resolvedYear, $resolvedMonth];
    }
}
