<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

trait BastAccessSupport
{
    private function getRequestUser(Request $request)
    {
        return effectiveUser($request) ?? $request->user();
    }

    private function resolveBastDetailReferencePeriod(Request $request, Bast $bast): array
    {
        $requestedBulan = (int) $request->input('bulan', 0);
        $requestedTahun = (int) $request->input('tahun', 0);

        if ($requestedBulan >= 1 && $requestedBulan <= 12 && $requestedTahun >= 2000) {
            return [
                'bulan' => $requestedBulan,
                'tahun' => $requestedTahun,
                'bulan_label' => $this->getBulanLabel($requestedBulan),
            ];
        }

        $sessionFilters = $request->session()->get('bast_open_detail_filters');
        if (
            is_array($sessionFilters)
            && isset($sessionFilters['bulan'], $sessionFilters['tahun'])
            && (int) $sessionFilters['bulan'] >= 1
            && (int) $sessionFilters['bulan'] <= 12
            && (int) $sessionFilters['tahun'] >= 2000
        ) {
            return [
                'bulan' => (int) $sessionFilters['bulan'],
                'tahun' => (int) $sessionFilters['tahun'],
                'bulan_label' => $this->getBulanLabel((int) $sessionFilters['bulan']),
            ];
        }

        $periode = $bast->periodeAlokasi;

        return [
            'bulan' => (int) $periode->bulan,
            'tahun' => (int) $periode->tahun,
            'bulan_label' => $this->getBulanLabel((int) $periode->bulan),
        ];
    }

    private function resolveBastFromHashedId(string $hashedId): ?Bast
    {
        return (new Bast)->resolveRouteBinding($hashedId);
    }

    private function userCanManageBastMain(Request $request): bool
    {
        $user = $this->getRequestUser($request);

        return $user && $user->hasAnyRole(['admin', 'operator']);
    }

    private function userCanManageLampiran(Request $request, BastKegiatan $bastKegiatan): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        return (int) $bastKegiatan->kegiatan?->ketua_tim_user_id === (int) $user->id
            || (int) $bastKegiatan->kegiatan?->pj_lainnya_id === (int) $user->id;
    }

    private function userCanAccessBast(Request $request, Bast $bast): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        // Ketua tim can open BAST detail across the period.
        // Lampiran actions are still restricted by userCanManageLampiran().
        return $user->active_role === 'ketua_tim';
    }

    private function canAccessSensusMode(?User $user, ?int $tahunAnggaran = null): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        $activeYear = $tahunAnggaran ?? ActiveYearService::get();

        return Kegiatan::query()
            ->where('tahun_anggaran', $activeYear)
            ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%')
            ->where(function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id)
                    ->orWhere('pj_lainnya_id', $user->id);
            })
            ->exists();
    }
}
