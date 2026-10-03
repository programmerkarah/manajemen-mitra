<?php

namespace App\Services\Bast;

use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\Kegiatan;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Http\Request;

class BastAccessService
{
    public function requestUser(Request $request): ?User
    {
        return effectiveUser($request) ?? $request->user();
    }

    public function resolveReferencePeriod(
        Request $request,
        Bast $bast,
    ): array {
        $requestedMonth = (int) $request->input('bulan', 0);
        $requestedYear = (int) $request->input('tahun', 0);

        if (
            $requestedMonth >= 1
            && $requestedMonth <= 12
            && $requestedYear >= 2000
        ) {
            return $this->periodPayload($requestedMonth, $requestedYear);
        }

        $filters = $request->session()->get('bast_open_detail_filters');

        if (
            is_array($filters)
            && isset($filters['bulan'], $filters['tahun'])
            && (int) $filters['bulan'] >= 1
            && (int) $filters['bulan'] <= 12
            && (int) $filters['tahun'] >= 2000
        ) {
            return $this->periodPayload(
                (int) $filters['bulan'],
                (int) $filters['tahun'],
            );
        }

        $periode = $bast->periodeAlokasi;

        return $this->periodPayload(
            (int) $periode->bulan,
            (int) $periode->tahun,
        );
    }

    public function resolveFromHashedId(string $hashedId): ?Bast
    {
        return (new Bast)->resolveRouteBinding($hashedId);
    }

    public function canManageMain(Request $request): bool
    {
        $user = $this->requestUser($request);

        return (bool) $user
            && $user->hasAnyRole(['admin', 'operator']);
    }

    public function canManageLampiran(
        Request $request,
        BastKegiatan $bastKegiatan,
    ): bool {
        $user = $this->requestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        return (int) $bastKegiatan->kegiatan?->ketua_tim_user_id
                === (int) $user->id
            || (int) $bastKegiatan->kegiatan?->pj_lainnya_id
                === (int) $user->id;
    }

    public function canAccess(Request $request, Bast $bast): bool
    {
        $user = $this->requestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        return $user->active_role === 'ketua_tim';
    }

    public function canAccessSensusMode(
        ?User $user,
        ?int $budgetYear = null,
    ): bool {
        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        $activeYear = $budgetYear ?? ActiveYearService::get();

        return Kegiatan::query()
            ->where('tahun_anggaran', $activeYear)
            ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%')
            ->where(function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id)
                    ->orWhere('pj_lainnya_id', $user->id);
            })
            ->exists();
    }

    private function periodPayload(int $month, int $year): array
    {
        return [
            'bulan' => $month,
            'tahun' => $year,
            'bulan_label' => $this->monthLabel($month),
        ];
    }

    private function monthLabel(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '';
    }
}
