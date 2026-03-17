<?php

namespace App\Http\Controllers;

use App\Models\Bast;
use App\Models\DasarHukum;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\ReviewPetugas;
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
        $activeRole = $user->getActiveRole()?->name;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentMonthFormatted = str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT);

        // Basic stats
        $stats = [
            'total_petugas' => Petugas::where('status', 'aktif')->count(),
            'total_kegiatan' => Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
            'draft_kegiatan' => Kegiatan::where('status', 'draft')->where('tahun_anggaran', $currentYear)->count(),
            'bast_pending' => Bast::where('status', 'draft')->whereYear('created_at', $currentYear)->count(),
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
                'total' => SkKpa::where('tahun', $currentYear)->count(),
                'draft' => SkKpa::where('status', 'draft')->where('tahun', $currentYear)->count(),
                'diterbitkan' => SkKpa::where('status', 'diterbitkan')->where('tahun', $currentYear)->count(),
                'dibatalkan' => SkKpa::where('status', 'dibatalkan')->where('tahun', $currentYear)->count(),
            ],
            // SPK Stats
            'spk' => [
                'total' => Spk::whereYear('tanggal_spk', $currentYear)->count(),
            ],
            // Petugas by Type
            'petugas_detail' => [
                'organik' => Petugas::where('jenis_petugas', 'organik')->where('status', 'aktif')->count(),
                'non_organik' => Petugas::where('jenis_petugas', 'non-organik')->where('status', 'aktif')->count(),
            ],
            // Kegiatan by Type
            'kegiatan_detail' => [
                'sensus' => Kegiatan::where('jenis_kegiatan', 'sensus')->whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
                'survei' => Kegiatan::where('jenis_kegiatan', 'survei')->whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
            ],
            // Alokasi by Status
            'alokasi_detail' => [
                'draft' => PeriodeAlokasi::where('status', 'draft')->where('tahun', $currentYear)->count(),
                'dikirim' => PeriodeAlokasi::where('status', 'dikirim')->where('tahun', $currentYear)->count(),
                'direvisi' => PeriodeAlokasi::where('status', 'direvisi')->where('tahun', $currentYear)->count(),
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
            ->where('tahun', $currentYear)
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
                        'nama_kegiatan' => $periode->kegiatan->nama_kegiatan ?? '',
                        'kode_kegiatan' => $periode->kegiatan->kode_kegiatan ?? '',
                    ],
                    'jumlah_organik' => $organikCount,
                    'jumlah_non_organik' => $nonOrganikCount,
                    'total_petugas' => $organikCount + $nonOrganikCount,
                ];
            });

        $spkPetugasIdsCurrentMonth = Spk::query()
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($currentMonthFormatted, $currentYear) {
                $query->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear);
            })
            ->pluck('petugas_id')
            ->unique();

        $bastPetugasIdsCurrentMonth = Spk::query()
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($currentMonthFormatted, $currentYear) {
                $query->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear);
            })
            ->whereHas('bast')
            ->pluck('petugas_id')
            ->unique();

        // Get kegiatan bulan ini with details
        $kegiatanBulanIni = Kegiatan::query()
            ->with(['ketuaTim'])
            ->whereIn('status', ['aktif', 'divalidasi'])
            ->where('tahun_anggaran', $currentYear)
            ->where(function ($query) use ($currentMonth, $currentYear) {
                $query->whereYear('tanggal_mulai', '<=', $currentYear)
                    ->whereMonth('tanggal_mulai', '<=', $currentMonth)
                    ->whereYear('tanggal_selesai', '>=', $currentYear)
                    ->whereMonth('tanggal_selesai', '>=', $currentMonth);
            })
            ->when($activeRole === 'ketua_tim', function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id);
            })
            ->get()
            ->map(function ($kegiatan) use ($bastPetugasIdsCurrentMonth, $currentMonth, $currentYear, $currentMonthFormatted, $spkPetugasIdsCurrentMonth) {
                // Get periode alokasi for current month
                $periodeAlokasi = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear)
                    ->latest('updated_at')
                    ->first();

                $hasCurrentMonthChangeIndicator = false;
                if ($periodeAlokasi) {
                    $hasCurrentMonthChangeIndicator = in_array($periodeAlokasi->status, ['draft', 'direvisi', 'perubahan'], true)
                        || (int) ($periodeAlokasi->revision_number ?? 0) > 0
                        || ! empty($periodeAlokasi->parent_periode_id);
                }

                // Get SK for current month
                $skCurrentMonth = SkKpa::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonth)
                    ->where('tahun', $currentYear)
                    ->first();

                // Get latest SK up to current month (fallback when no change indication this month)
                $skLatest = SkKpa::where('kegiatan_id', $kegiatan->id)
                    ->where('tahun', $currentYear)
                    ->where('bulan', '<=', $currentMonth)
                    ->orderByDesc('bulan')
                    ->latest('updated_at')
                    ->first();

                $skForDisplay = $skCurrentMonth;
                $skSource = 'bulan_berjalan';

                if (! $hasCurrentMonthChangeIndicator && $skLatest) {
                    $skForDisplay = $skLatest;
                    $skSource = $skCurrentMonth && $skLatest->id === $skCurrentMonth->id
                        ? 'bulan_berjalan'
                        : 'periode_terakhir';
                }

                $showMissingSk = ! $skForDisplay
                    || ($hasCurrentMonthChangeIndicator && ! $skCurrentMonth);

                $allocatedNonOrganikPetugasIds = $periodeAlokasi
                    ? $periodeAlokasi->alokasiPetugas()
                        ->whereHas('petugas', function ($query) {
                            $query->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('jumlah_satuan', '>', 0)
                                ->orWhere('jumlah_satuan_listing', '>', 0)
                                ->orWhere('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->distinct()
                        ->pluck('petugas_id')
                    : collect();

                $requiredDocumentCount = $allocatedNonOrganikPetugasIds->count();
                $spkCount = $allocatedNonOrganikPetugasIds->intersect($spkPetugasIdsCurrentMonth)->count();
                $bastCount = $allocatedNonOrganikPetugasIds->intersect($bastPetugasIdsCurrentMonth)->count();
                $totalPetugasAlokasi = $periodeAlokasi ? $periodeAlokasi->alokasiPetugas()->count() : 0;

                $spkRequiresDocument = $requiredDocumentCount > 0;
                $bastRequiresDocument = $requiredDocumentCount > 0;

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
                    'sk' => $skForDisplay ? [
                        'id' => $skForDisplay->id,
                        'hashed_id' => $skForDisplay->hashed_id,
                        'nomor_sk' => $skForDisplay->nomor_sk,
                        'status' => $skForDisplay->status,
                        'is_signed' => $skForDisplay->is_signed,
                    ] : null,
                    'sk_meta' => [
                        'show_missing' => $showMissingSk,
                        'source' => $skSource,
                        'source_bulan' => $skForDisplay?->bulan,
                        'source_tahun' => $skForDisplay?->tahun,
                    ],
                    'spk' => [
                        'count' => $spkCount,
                        'has_spk' => $spkCount > 0,
                        'required_count' => $requiredDocumentCount,
                        'requires_document' => $spkRequiresDocument,
                        'is_complete' => $spkRequiresDocument && $spkCount >= $requiredDocumentCount,
                        'detail_url' => $periodeAlokasi
                            ? route('spk.show-by-month-get', ['bulan' => $periodeAlokasi->bulan, 'tahun' => $periodeAlokasi->tahun])
                            : null,
                    ],
                    'bast' => [
                        'count' => $bastCount,
                        'has_bast' => $bastCount > 0,
                        'required_count' => $requiredDocumentCount,
                        'requires_document' => $bastRequiresDocument,
                        'is_complete' => $bastRequiresDocument && $bastCount >= $requiredDocumentCount,
                        'detail_url' => $periodeAlokasi
                            ? route('bast.list', ['bulan' => $periodeAlokasi->bulan, 'tahun' => $periodeAlokasi->tahun])
                            : null,
                    ],
                ];
            });

        $today = Carbon::today();
        $attentionItems = collect();

        if (in_array($activeRole, ['admin', 'operator', 'ketua_tim'], true)) {
            $draftKegiatanQuery = Kegiatan::query()
                ->where('status', 'draft')
                ->where('tahun_anggaran', $currentYear);

            if ($activeRole === 'ketua_tim') {
                $draftKegiatanQuery->where('ketua_tim_user_id', $user->id);
            }

            $draftKegiatanCount = $draftKegiatanQuery->count();

            if ($draftKegiatanCount > 0) {
                $attentionItems->push([
                    'key' => 'kegiatan_draft',
                    'label' => 'kegiatan draft',
                    'count' => $draftKegiatanCount,
                    'url' => route('kegiatan.index'),
                    'description' => 'Perlu ditindaklanjuti ke alokasi',
                    'severity' => 'warning',
                ]);
            }
        }

        if (in_array($activeRole, ['admin', 'approver'], true)) {
            $spkAttentionCount = $kegiatanBulanIni->filter(function ($kegiatan) {
                return ($kegiatan['periode_alokasi']['has_alokasi'] ?? false)
                    && ($kegiatan['spk']['requires_document'] ?? false)
                    && ! ($kegiatan['spk']['is_complete'] ?? false);
            })->count();

            if ($spkAttentionCount > 0) {
                $attentionItems->push([
                    'key' => 'spk_missing',
                    'label' => 'perjanjian kerja belum dibuat',
                    'count' => $spkAttentionCount,
                    'url' => route('spk.index'),
                    'description' => 'Sudah ada SK dan alokasi, SPK perlu digenerate',
                    'severity' => 'warning',
                ]);
            }
        }

        if (in_array($activeRole, ['admin', 'operator', 'pj', 'approver', 'ketua_tim'], true)) {
            $spkBastQuery = Spk::query()
                ->with(['alokasiPetugas:id,periode_alokasi_id'])
                ->whereYear('tanggal_spk', $currentYear)
                ->whereDoesntHave('bast');

            if ($activeRole === 'ketua_tim') {
                $kegiatanIds = Kegiatan::query()
                    ->where('ketua_tim_user_id', $user->id)
                    ->pluck('id');

                $spkBastQuery->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($kegiatanIds) {
                    $query->whereIn('kegiatan_id', $kegiatanIds);
                });
            }

            $spkWithoutBast = $spkBastQuery->get();

            $bastDueSoonCount = 0;
            $bastOverdueCount = 0;

            foreach ($spkWithoutBast as $spk) {
                $expectedBastDate = $spk->tanggal_selesai_kerja ?? $spk->tanggal_mulai_kerja;
                if (! $expectedBastDate) {
                    continue;
                }

                $targetDate = Carbon::parse($expectedBastDate);
                while (in_array($targetDate->dayOfWeekIso, [6, 7], true)) {
                    $targetDate->subDay();
                }

                if ($targetDate->lt($today)) {
                    $bastOverdueCount++;
                } elseif ($targetDate->betweenIncluded($today, $today->copy()->addDays(3))) {
                    $bastDueSoonCount++;
                }
            }

            $bastAttentionCount = $bastDueSoonCount + $bastOverdueCount;

            if ($bastAttentionCount > 0) {
                $attentionItems->push([
                    'key' => 'bast_due',
                    'label' => 'BAST mendekati / melewati target',
                    'count' => $bastAttentionCount,
                    'url' => route('bast.index'),
                    'description' => sprintf('%d lewat target, %d mendekati target', $bastOverdueCount, $bastDueSoonCount),
                    'severity' => $bastOverdueCount > 0 ? 'danger' : 'warning',
                ]);
            }
        }

        // Chart data from January to current month
        $chartData = [];
        $petugasMonitoringData = [];

        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthName = Carbon::create($currentYear, $month, 1)->format('M');
            $monthFormatted = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

            // Count total non-organik petugas allocated for this month
            $totalPetugasAlokasi = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $monthFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            // Count kegiatan for this month
            $kegiatanCount = PeriodeAlokasi::where('bulan', $monthFormatted)
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
                ->where('periode_alokasi.bulan', $monthFormatted)
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
            $monthFormatted = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

            // Get all honor data for this month, prefer 'perubahan' over 'dikirim' per (petugas, kegiatan)
            $rawAlokasi = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $monthFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan'])
                ->select(
                    'alokasi_petugas.petugas_id',
                    'periode_alokasi.kegiatan_id',
                    'periode_alokasi.status as periode_status',
                    'alokasi_petugas.total_honor',
                    'alokasi_petugas.total_honor_listing'
                )
                ->get();

            // Group by petugas_id + kegiatan_id, prefer perubahan
            $grouped = [];
            foreach ($rawAlokasi as $row) {
                $key = $row->petugas_id.'-'.$row->kegiatan_id;
                if (! isset($grouped[$key])) {
                    $grouped[$key] = $row;
                } else {
                    // Prefer perubahan over dikirim
                    if ($row->periode_status === 'perubahan') {
                        $grouped[$key] = $row;
                    }
                }
            }

            // Sum honor per petugas
            $petugasHonor = [];
            foreach ($grouped as $row) {
                $pid = $row->petugas_id;
                $honor = ($row->total_honor ?? 0) + ($row->total_honor_listing ?? 0);
                if (! isset($petugasHonor[$pid])) {
                    $petugasHonor[$pid] = 0;
                }
                $petugasHonor[$pid] += $honor;
            }

            // Build honorData as collection of objects for compatibility
            $honorData = collect();
            foreach ($petugasHonor as $pid => $total) {
                if ($total > 0) {
                    $honorData->push((object) [
                        'petugas_id' => $pid,
                        'total_honor_bulan' => $total,
                    ]);
                }
            }

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

        $reviewRows = ReviewPetugas::query()
            ->with([
                'petugas:id,nama',
                'periodeAlokasi:id,bulan,tahun',
            ])
            ->whereHas('periodeAlokasi', function ($query) use ($currentYear) {
                $query->where('tahun', $currentYear);
            })
            ->get();

        $reviewRowsCurrentMonth = $reviewRows->filter(function ($review) use ($currentMonthFormatted) {
            $reviewMonth = $review->reviewed_at?->format('m')
                ?? str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT);

            return $reviewMonth === $currentMonthFormatted;
        });

        $topMitra = $reviewRows
            ->groupBy('petugas_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'petugas_id' => $first->petugas_id,
                    'petugas_nama' => $first->petugas?->nama ?? '-',
                    'avg_rating' => round((float) $group->avg('rating'), 2),
                    'total_review' => $group->count(),
                ];
            })
            ->sortByDesc(fn ($item) => ($item['avg_rating'] * 1000) + $item['total_review'])
            ->take(3)
            ->values();

        $mitraReviewSummary = [
            'year' => [
                'total_reviews' => $reviewRows->count(),
                'avg_rating' => $reviewRows->count() > 0 ? round((float) $reviewRows->avg('rating'), 2) : 0,
                'mitra_reviewed' => $reviewRows->pluck('petugas_id')->unique()->count(),
                'positive_percentage' => $reviewRows->count() > 0
                    ? round(($reviewRows->where('rating', '>=', 4)->count() / $reviewRows->count()) * 100, 1)
                    : 0,
            ],
            'current_month' => [
                'total_reviews' => $reviewRowsCurrentMonth->count(),
                'avg_rating' => $reviewRowsCurrentMonth->count() > 0 ? round((float) $reviewRowsCurrentMonth->avg('rating'), 2) : 0,
                'mitra_reviewed' => $reviewRowsCurrentMonth->pluck('petugas_id')->unique()->count(),
                'positive_percentage' => $reviewRowsCurrentMonth->count() > 0
                    ? round(($reviewRowsCurrentMonth->where('rating', '>=', 4)->count() / $reviewRowsCurrentMonth->count()) * 100, 1)
                    : 0,
            ],
            'top_mitra' => $topMitra,
        ];

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
            'mitraReviewSummary' => $mitraReviewSummary,
            'attentionItems' => $attentionItems->values(),
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'userRole' => $user->role,
        ]);
    }
}
