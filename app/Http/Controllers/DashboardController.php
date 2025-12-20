<?php

namespace App\Http\Controllers;

use App\Models\Bast;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\SkKpa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $stats = [
            'total_petugas' => Petugas::where('status', 'aktif')->count(),
            'total_kegiatan' => Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->count(),
            'alokasi_pending' => PeriodeAlokasi::where('status', 'diajukan')->count(),
            'bast_pending' => Bast::where('status', 'draft')->count(),
        ];

        // Get recent activities based on user role
        $recentAlokasi = PeriodeAlokasi::query()
            ->with(['kegiatan', 'petugas', 'rateHonor.satuan'])
            ->when($user->isOperator(), function ($query) use ($user) {
                $query->where('submitted_by', $user->id);
            })
            ->when($user->isApprover(), function ($query) {
                $query->where('status', 'diajukan');
            })
            ->latest()
            ->limit(5)
            ->get();

        // Get kegiatan bulan ini with details
        $kegiatanBulanIni = Kegiatan::query()
            ->with(['ketuaTim'])
            ->whereIn('status', ['aktif', 'divalidasi'])
            ->where(function ($query) use ($currentMonth, $currentYear) {
                $query->whereYear('tanggal_mulai', '<=', $currentYear)
                    ->whereMonth('tanggal_mulai', '<=', $currentMonth)
                    ->whereYear('tanggal_selesai', '>=', $currentYear)
                    ->whereMonth('tanggal_selesai', '>=', $currentMonth);
            })
            ->when($user->isKetuaTim(), function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id);
            })
            ->get()
            ->map(function ($kegiatan) use ($currentMonth, $currentYear) {
                // Get periode alokasi for current month
                $periodeAlokasi = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonth)
                    ->where('tahun', $currentYear)
                    ->first();

                // Get SK for current month
                $sk = SkKpa::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonth)
                    ->where('tahun', $currentYear)
                    ->first();

                // Count SPK if SK exists
                $spkCount = $sk ? $sk->spk()->count() : 0;
                $totalPetugasAlokasi = $periodeAlokasi ? $periodeAlokasi->alokasiPetugas()->count() : 0;

                return [
                    'id' => $kegiatan->id,
                    'hashed_id' => $kegiatan->hashed_id,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'status' => $kegiatan->status,
                    'periode_alokasi' => $periodeAlokasi ? [
                        'id' => $periodeAlokasi->id,
                        'hashed_id' => $periodeAlokasi->hashed_id,
                        'status' => $periodeAlokasi->status,
                        'jumlah_petugas' => $totalPetugasAlokasi,
                        'has_alokasi' => $totalPetugasAlokasi > 0,
                    ] : null,
                    'sk' => $sk ? [
                        'id' => $sk->id,
                        'hashed_id' => $sk->hashed_id,
                        'nomor_sk' => $sk->nomor_sk,
                        'status' => $sk->status,
                        'is_signed' => $sk->is_signed,
                    ] : null,
                    'spk' => [
                        'count' => $spkCount,
                        'has_spk' => $spkCount > 0,
                    ],
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentAlokasi' => $recentAlokasi,
            'kegiatanBulanIni' => $kegiatanBulanIni,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'userRole' => $user->role,
        ]);
    }
}
