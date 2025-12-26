<?php

namespace App\Http\Controllers;

use App\Models\Bast;
use App\Models\DasarHukum;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Sbml;
use App\Models\SkKpa;
use App\Models\Spk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = effectiveUser($request);
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Basic stats
        $stats = [
            'total_petugas' => Petugas::where('status', 'aktif')->count(),
            'total_kegiatan' => Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->count(),
            'alokasi_pending' => PeriodeAlokasi::where('status', 'draft')->count(),
            'bast_pending' => Bast::where('status', 'draft')->count(),
        ];

        // Additional comprehensive stats
        $additionalStats = [
            // SBML Stats
            'sbml' => [
                'total' => Sbml::count(),
                'aktif' => Sbml::where('status', 'aktif')->count(),
                'nonaktif' => Sbml::where('status', 'nonaktif')->count(),
            ],
            // DIPA Stats
            'dipa' => [
                'total' => Dipa::count(),
                'aktif' => Dipa::where('is_active', true)->count(),
                'nonaktif' => Dipa::where('is_active', false)->count(),
            ],
            // Penandatangan Stats
            'penandatangan' => [
                'total' => Penandatangan::count(),
                'kepala' => Penandatangan::where('jenis_penandatangan', 'kepala')->count(),
                'ppk' => Penandatangan::where('jenis_penandatangan', 'ppk')->count(),
                'aktif' => Penandatangan::where('is_active', true)->count(),
            ],
            // Dasar Hukum Stats
            'dasar_hukum' => [
                'total' => DasarHukum::count(),
                'aktif' => DasarHukum::where('status', 'aktif')->count(),
            ],
            // SK Stats
            'sk' => [
                'total' => SkKpa::count(),
                'draft' => SkKpa::where('status', 'draft')->count(),
                'diterbitkan' => SkKpa::where('status', 'diterbitkan')->count(),
                'dibatalkan' => SkKpa::where('status', 'dibatalkan')->count(),
            ],
            // SPK Stats
            'spk' => [
                'total' => Spk::count(),
            ],
            // Petugas by Type
            'petugas_detail' => [
                'organik' => Petugas::where('jenis_petugas', 'organik')->where('status', 'aktif')->count(),
                'non_organik' => Petugas::where('jenis_petugas', 'non-organik')->where('status', 'aktif')->count(),
            ],
            // Kegiatan by Type
            'kegiatan_detail' => [
                'sensus' => Kegiatan::where('jenis_kegiatan', 'sensus')->whereIn('status', ['aktif', 'divalidasi'])->count(),
                'survei' => Kegiatan::where('jenis_kegiatan', 'survei')->whereIn('status', ['aktif', 'divalidasi'])->count(),
            ],
            // Alokasi by Status
            'alokasi_detail' => [
                'draft' => PeriodeAlokasi::where('status', 'draft')->count(),
                'dikirim' => PeriodeAlokasi::where('status', 'dikirim')->count(),
                'direvisi' => PeriodeAlokasi::where('status', 'direvisi')->count(),
            ],
        ];

        // Get recent activities based on user role
        $recentAlokasi = PeriodeAlokasi::query()
            ->select('periode_alokasi.*')
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan',
                'alokasiPetugas:id,periode_alokasi_id,petugas_id',
                'alokasiPetugas.petugas:id,jenis_petugas',
            ])
            ->when($user->isOperator(), function ($query) use ($user) {
                $query->where('submitted_by', $user->id);
            })
            ->when($user->isApprover(), function ($query) {
                $query->where('status', 'dikirim');
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($periode) {
                // Count organik and non-organik petugas
                $organikCount = $periode->alokasiPetugas->filter(function ($alokasi) {
                    return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'organik';
                })->count();

                $nonOrganikCount = $periode->alokasiPetugas->filter(function ($alokasi) {
                    return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
                })->count();

                return [
                    'id' => $periode->id,
                    'status' => $periode->status,
                    'bulan' => $periode->bulan,
                    'tahun' => $periode->tahun,
                    'kegiatan' => [
                        'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                        'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    ],
                    'jumlah_organik' => $organikCount,
                    'jumlah_non_organik' => $nonOrganikCount,
                    'total_petugas' => $organikCount + $nonOrganikCount,
                ];
            });

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

        // Chart data from January to current month
        $chartData = [];
        $petugasMonitoringData = [];

        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthName = Carbon::create($currentYear, $month, 1)->format('M');

            // Count total non-organik petugas allocated for this month
            $totalPetugasAlokasi = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $month)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            // Count kegiatan for this month
            $kegiatanCount = PeriodeAlokasi::where('bulan', $month)
                ->where('tahun', $currentYear)
                ->distinct('kegiatan_id')
                ->count('kegiatan_id');

            $chartData[] = [
                'month' => $monthName,
                'petugas_count' => $totalPetugasAlokasi,
                'kegiatan_count' => $kegiatanCount,
            ];

            // Petugas monitoring data - non-organik only
            $totalPetugasAktif = Petugas::where('status', 'aktif')
                ->where('jenis_petugas', 'non-organik')
                ->count();

            // Get all alokasi for this month with non-organik petugas only
            $alokasiThisMonth = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $month)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->select('alokasi_petugas.petugas_id', DB::raw('COUNT(*) as jumlah_kegiatan'))
                ->groupBy('alokasi_petugas.petugas_id')
                ->get();

            // Count by categories
            $petugasTidakDialokasikan = $totalPetugasAktif - $alokasiThisMonth->count();
            $petugas1_2Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan >= 1 && $p->jumlah_kegiatan <= 2)->count();
            $petugas3_5Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan >= 3 && $p->jumlah_kegiatan <= 5)->count();
            $petugasLebih5Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan > 5)->count();

            $petugasMonitoringData[] = [
                'month' => $monthName,
                'tidak_dialokasikan' => $petugasTidakDialokasikan,
                'kegiatan_1_2' => $petugas1_2Kegiatan,
                'kegiatan_3_5' => $petugas3_5Kegiatan,
                'kegiatan_lebih_5' => $petugasLebih5Kegiatan,
            ];
        }

        // Honor inequality analysis data
        $honorInequalityData = [];
        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthName = Carbon::create($currentYear, $month, 1)->format('M');

            // Get all honor data for this month
            $honorData = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $month)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->select(
                    'alokasi_petugas.petugas_id',
                    DB::raw('SUM(alokasi_petugas.total_honor + alokasi_petugas.total_honor_listing) as total_honor_bulan')
                )
                ->groupBy('alokasi_petugas.petugas_id')
                ->having('total_honor_bulan', '>', 0)
                ->get();

            if ($honorData->count() > 0) {
                $honors = $honorData->pluck('total_honor_bulan')->toArray();
                $totalHonor = array_sum($honors);
                $avgHonor = $totalHonor / count($honors);
                $maxHonor = max($honors);
                $minHonor = min($honors);

                // Calculate standard deviation
                $variance = 0;
                foreach ($honors as $honor) {
                    $variance += pow($honor - $avgHonor, 2);
                }
                $stdDev = sqrt($variance / count($honors));

                // Calculate coefficient of variation (CV) as inequality measure
                $coefficientVariation = $avgHonor > 0 ? ($stdDev / $avgHonor) * 100 : 0;

                // Count distribution brackets
                $honor0_500rb = collect($honors)->filter(fn ($h) => $h >= 0 && $h <= 500000)->count();
                $honor501rb_1500rb = collect($honors)->filter(fn ($h) => $h >= 501000 && $h <= 1500000)->count();
                $honor1501rb_2500rb = collect($honors)->filter(fn ($h) => $h >= 1501000 && $h <= 2500000)->count();
                $honor2501rb_3500rb = collect($honors)->filter(fn ($h) => $h >= 2501000 && $h <= 3500000)->count();
                $honorLebih3501rb = collect($honors)->filter(fn ($h) => $h >= 3501000)->count();

                $honorInequalityData[] = [
                    'month' => $monthName,
                    'rata_rata_honor' => round($avgHonor, 0),
                    'honor_tertinggi' => round($maxHonor, 0),
                    'honor_terendah' => round($minHonor, 0),
                    'std_deviasi' => round($stdDev, 0),
                    'koefisien_variasi' => round($coefficientVariation, 2),
                    'honor_0_500rb' => $honor0_500rb,
                    'honor_501rb_1500rb' => $honor501rb_1500rb,
                    'honor_1501rb_2500rb' => $honor1501rb_2500rb,
                    'honor_2501rb_3500rb' => $honor2501rb_3500rb,
                    'honor_lebih_3501rb' => $honorLebih3501rb,
                    'total_petugas' => count($honors),
                ];
            } else {
                $honorInequalityData[] = [
                    'month' => $monthName,
                    'rata_rata_honor' => 0,
                    'honor_tertinggi' => 0,
                    'honor_terendah' => 0,
                    'std_deviasi' => 0,
                    'koefisien_variasi' => 0,
                    'honor_0_500rb' => 0,
                    'honor_501rb_1500rb' => 0,
                    'honor_1501rb_2500rb' => 0,
                    'honor_2501rb_3500rb' => 0,
                    'honor_lebih_3501rb' => 0,
                    'total_petugas' => 0,
                ];
            }
        }

        // Calculate summary statistics - use last month data with allocations
        // Petugas Monitoring Summary - Get from most recent month with data
        $petugasMonitoringSummary = [
            'tidak_dialokasikan' => 0,
            'kegiatan_1_2' => 0,
            'kegiatan_3_5' => 0,
            'kegiatan_lebih_5' => 0,
        ];

        // Use the last available month data
        if (count($petugasMonitoringData) > 0) {
            $lastMonthData = end($petugasMonitoringData);
            $petugasMonitoringSummary = [
                'tidak_dialokasikan' => round(collect($petugasMonitoringData)->avg('tidak_dialokasikan'), 0),
                'kegiatan_1_2' => round(collect($petugasMonitoringData)->avg('kegiatan_1_2'), 0),
                'kegiatan_3_5' => round(collect($petugasMonitoringData)->avg('kegiatan_3_5'), 0),
                'kegiatan_lebih_5' => round(collect($petugasMonitoringData)->avg('kegiatan_lebih_5'), 0),
            ];
        }

        // Honor Inequality Summary - Average per month
        $honorInequalitySummary = ['has_data' => false];

        $honorMonthsWithData = collect($honorInequalityData)->filter(fn ($data) => $data['total_petugas'] > 0);

        if ($honorMonthsWithData->count() > 0) {
            $avgRataRataHonor = $honorMonthsWithData->avg('rata_rata_honor');
            $avgHonorTertinggi = $honorMonthsWithData->avg('honor_tertinggi');
            $avgHonorTerendah = $honorMonthsWithData->avg('honor_terendah');
            $avgStdDeviasi = $honorMonthsWithData->avg('std_deviasi');
            $avgKoefisienVariasi = $honorMonthsWithData->avg('koefisien_variasi');
            $avgGapHonor = $avgHonorTertinggi - $avgHonorTerendah;
            $avgTotalPetugas = $honorMonthsWithData->avg('total_petugas');

            $honorInequalitySummary = [
                'has_data' => true,
                'rata_rata_honor' => round($avgRataRataHonor, 0),
                'honor_tertinggi' => round($avgHonorTertinggi, 0),
                'honor_terendah' => round($avgHonorTerendah, 0),
                'std_deviasi' => round($avgStdDeviasi, 0),
                'koefisien_variasi' => round($avgKoefisienVariasi, 2),
                'gap_honor' => round($avgGapHonor, 0),
                'total_petugas' => round($avgTotalPetugas, 0),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'additionalStats' => $additionalStats,
            'recentAlokasi' => $recentAlokasi,
            'kegiatanBulanIni' => $kegiatanBulanIni,
            'chartData' => $chartData,
            'petugasMonitoringData' => $petugasMonitoringData,
            'honorInequalityData' => $honorInequalityData,
            'petugasMonitoringSummary' => $petugasMonitoringSummary,
            'honorInequalitySummary' => $honorInequalitySummary,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'userRole' => $user->role,
        ]);
    }
}
