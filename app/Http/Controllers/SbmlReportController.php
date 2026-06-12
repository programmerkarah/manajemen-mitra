<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Sbml;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SbmlReportController extends Controller
{
    /**
     * Display honor summary report per petugas per month
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();

        // Default filter must always follow current month/year when filter is empty
        $defaultTahun = (int) date('Y');
        $defaultBulan = str_pad(date('m'), 2, '0', STR_PAD_LEFT);

        $tahun = (int) ($validated['tahun'] ?? $defaultTahun);
        $bulan = $this->normalizeBulan($validated['bulan'] ?? $defaultBulan);
        $bulanInt = (int) $bulan;

        // Pre-fetch all SBML data for the year to avoid N+1 queries
        $sbmlCache = Sbml::where('tahun_anggaran', $tahun)
            ->where('status', 'aktif')
            ->get()
            ->groupBy(function ($sbml) {
                return $sbml->jenis_kegiatan.'_'.$sbml->status_kepegawaian.'_'.$sbml->jenis_penugasan;
            })
            ->map(function ($group) {
                return $group->first();
            });

        // Get all petugas who have allocations in the selected month
        // Only fetch allocations with jumlah > 0 to reduce data
        $petugasData = AlokasiPetugas::with([
            'petugas:id,nama,nik,jenis_petugas',
            'periodeAlokasi:id,kegiatan_id,jenis_kegiatan,tahun,bulan,status',
            'periodeAlokasi.kegiatan:id,nama_kegiatan',
        ])
            ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)
                    ->whereIn('bulan', $this->resolveReportBulanCandidates($bulan))
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan']);
            })
            ->where(function ($query) {
                $query->where('jumlah_satuan', '>', 0)
                    ->orWhere('jumlah_satuan_listing', '>', 0)
                    ->orWhere('partial_jumlah_satuan', '>', 0)
                    ->orWhere('partial_jumlah_satuan_listing', '>', 0);
            })
            ->get()
            ->groupBy('petugas_id')
            ->map(function ($alokasis, $petugasId) use ($sbmlCache, $bulanInt) {
                $petugas = $alokasis->first()->petugas;

                if (! $petugas) {
                    return null;
                }

                $positiveAlokasis = $alokasis->filter(function ($alokasi) use ($bulanInt) {
                    if (! $this->shouldIncludeInMonthlyReport($alokasi, $bulanInt)) {
                        return false;
                    }

                    $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                        ? (float) $alokasi->estimasi_honor_partial
                        : (float) ($alokasi->total_honor ?? 0);
                    $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                        ? (float) $alokasi->estimasi_honor_partial_listing
                        : (float) ($alokasi->total_honor_listing ?? 0);

                    return $this->calculateMonthlyHonorForAllocation(
                        $effectivePencacahanHonor + $effectiveListingHonor,
                        $this->resolveReportMonthForAllocation($alokasi, $bulanInt),
                        $alokasi->periodeAlokasi?->kegiatan
                    ) > 0;
                });

                if ($positiveAlokasis->isEmpty()) {
                    return null;
                }

                // Calculate total honor for this petugas in this month
                $totalHonor = $positiveAlokasis->sum(function ($alokasi) use ($bulanInt) {
                    $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                        ? (float) $alokasi->estimasi_honor_partial
                        : (float) ($alokasi->total_honor ?? 0);
                    $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                        ? (float) $alokasi->estimasi_honor_partial_listing
                        : (float) ($alokasi->total_honor_listing ?? 0);

                    return $this->calculateMonthlyHonorForAllocation(
                        $effectivePencacahanHonor + $effectiveListingHonor,
                        $this->resolveReportMonthForAllocation($alokasi, $bulanInt),
                        $alokasi->periodeAlokasi?->kegiatan
                    );
                });

                // Get max SBML based on jenis penugasan from allocations
                $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';

                $alokasiKombinasi = $positiveAlokasis->map(function ($alokasi) use ($statusKepegawaian) {
                    return [
                        'jenis_kegiatan' => $alokasi->periodeAlokasi?->jenis_kegiatan ?? null,
                        'jenis_penugasan' => $alokasi->peran,
                        'status_kepegawaian' => $alokasi->status_kepegawaian ?? $statusKepegawaian,
                    ];
                })->unique(function (array $kombinasi): string {
                    return $kombinasi['jenis_kegiatan'].'|'.$kombinasi['jenis_penugasan'].'|'.$kombinasi['status_kepegawaian'];
                });

                $sensusAlokasis = $positiveAlokasis->filter(function ($alokasi) {
                    return $this->isSensusEkonomiKegiatan($alokasi->periodeAlokasi?->kegiatan);
                });
                $regularAlokasis = $positiveAlokasis->reject(function ($alokasi) {
                    return $this->isSensusEkonomiKegiatan($alokasi->periodeAlokasi?->kegiatan);
                });

                $useSensusOnlyMaxAllowed = $this->shouldUseSensusOnlyMaxAllowed($sensusAlokasis, $regularAlokasis);

                $honorMaxList = $alokasiKombinasi->map(function (array $kombinasi) use ($sbmlCache) {
                    $cacheKey = $kombinasi['jenis_kegiatan'].'_'.$kombinasi['status_kepegawaian'].'_'.$kombinasi['jenis_penugasan'];

                    return $sbmlCache->get($cacheKey)?->honor_max;
                })->filter();

                if ($useSensusOnlyMaxAllowed) {
                    $sensusHonorMaxList = $alokasiKombinasi
                        ->filter(function (array $kombinasi) {
                            return $kombinasi['jenis_kegiatan'] === 'sensus';
                        })
                        ->map(function (array $kombinasi) use ($sbmlCache) {
                            $cacheKey = $kombinasi['jenis_kegiatan'].'_'.$kombinasi['status_kepegawaian'].'_'.$kombinasi['jenis_penugasan'];

                            return $sbmlCache->get($cacheKey)?->honor_max;
                        })
                        ->filter();

                    $minAllowed = $sensusHonorMaxList->isNotEmpty() ? $sensusHonorMaxList->max() : 0;
                } else {
                    $minAllowed = $honorMaxList->isNotEmpty() ? $honorMaxList->min() : 0;
                }
                $exceeds = $minAllowed > 0 && $totalHonor > $minAllowed;

                // Group by kegiatan for details
                $kegiatanDetails = [];
                foreach ($positiveAlokasis as $alokasi) {
                    $kegiatanId = $alokasi->periodeAlokasi->kegiatan_id;

                    if (! isset($kegiatanDetails[$kegiatanId])) {
                        $kegiatanDetails[$kegiatanId] = [
                            'kegiatan_id' => $kegiatanId,
                            'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                            'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                            'total_honor' => 0,
                            'alokasi' => [],
                        ];
                    }

                    $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                        ? (float) $alokasi->estimasi_honor_partial
                        : (float) ($alokasi->total_honor ?? 0);
                    $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                        ? (float) $alokasi->estimasi_honor_partial_listing
                        : (float) ($alokasi->total_honor_listing ?? 0);
                    $jumlahSatuanDibayarkan = $alokasi->partial_jumlah_satuan !== null
                        ? (int) $alokasi->partial_jumlah_satuan
                        : (int) ($alokasi->jumlah_satuan ?? 0);
                    $jumlahSatuanListingDibayarkan = $alokasi->partial_jumlah_satuan_listing !== null
                        ? (int) $alokasi->partial_jumlah_satuan_listing
                        : (int) ($alokasi->jumlah_satuan_listing ?? 0);
                    $effectiveMonthlyHonor = $this->calculateMonthlyHonorForAllocation(
                        $effectivePencacahanHonor + $effectiveListingHonor,
                        $this->resolveReportMonthForAllocation($alokasi, $bulanInt),
                        $alokasi->periodeAlokasi?->kegiatan
                    );

                    $kegiatanDetails[$kegiatanId]['total_honor'] += $effectiveMonthlyHonor;
                    $kegiatanDetails[$kegiatanId]['alokasi'][] = [
                        'peran' => $this->formatPeran($alokasi->peran),
                        'jumlah_satuan' => $alokasi->jumlah_satuan,
                        'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                        'jumlah_satuan_dibayarkan' => $jumlahSatuanDibayarkan,
                        'jumlah_satuan_listing_dibayarkan' => $jumlahSatuanListingDibayarkan,
                        'total_honor' => $this->calculateMonthlyHonorForAllocation(
                            $effectivePencacahanHonor,
                            $this->resolveReportMonthForAllocation($alokasi, $bulanInt),
                            $alokasi->periodeAlokasi?->kegiatan
                        ),
                        'total_honor_listing' => $this->calculateMonthlyHonorForAllocation(
                            $effectiveListingHonor,
                            $this->resolveReportMonthForAllocation($alokasi, $bulanInt),
                            $alokasi->periodeAlokasi?->kegiatan
                        ),
                        'status_kepegawaian' => $alokasi->status_kepegawaian,
                        'catatan' => $alokasi->catatan,
                    ];
                }

                return [
                    'petugas_id' => $petugas->id,
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'jenis_petugas' => $petugas->jenis_petugas,
                    'total_honor' => $totalHonor,
                    'max_allowed' => $minAllowed,
                    'exceeds' => $exceeds,
                    'difference' => $totalHonor - $minAllowed,
                    'percentage' => $minAllowed > 0 ? ($totalHonor / $minAllowed) * 100 : 0,
                    'kegiatan_count' => count($kegiatanDetails),
                    'kegiatan_details' => array_values($kegiatanDetails),
                ];
            })
            ->filter()
            ->sortByDesc('total_honor')
            ->values();

        // Generate month options for dropdown
        $bulanOptions = collect(range(1, 12))->map(function ($m) {
            $monthStr = str_pad($m, 2, '0', STR_PAD_LEFT);
            $date = Carbon::create()->month($m);
            $date->setLocale('id');

            return [
                'value' => $monthStr,
                'label' => $date->translatedFormat('F'),
            ];
        });

        $currentYear = (int) date('Y');

        // Get unique years from alokasi petugas
        $tahunOptions = PeriodeAlokasi::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->toArray();

        // Always include current year and next year (for preparation)
        if (! in_array($currentYear, $tahunOptions)) {
            $tahunOptions[] = $currentYear;
        }
        if (! in_array($currentYear + 1, $tahunOptions)) {
            $tahunOptions[] = $currentYear + 1;
        }

        // Sort descending
        rsort($tahunOptions);

        return Inertia::render('Sbml/Report', [
            'petugas' => [
                'encrypted' => encryptData($petugasData->toArray()),
            ],
            'filters' => [
                'encrypted' => encryptFilters($request->only(['tahun', 'bulan'])),
                'decrypted' => $request->only(['tahun', 'bulan']) ?: [
                    'tahun' => (int) $tahun,
                    'bulan' => $bulan,
                ],
            ],
            'bulan_options' => $bulanOptions,
            'tahun_options' => $tahunOptions,
        ]);
    }

    /**
     * Format peran untuk display
     */
    private function formatPeran(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            default => ucfirst($peran),
        };
    }

    private function isSensusEkonomi2026(\App\Models\Kegiatan $kegiatan): bool
    {
        return $kegiatan->jenis_kegiatan === 'sensus'
            && mb_strtolower(trim((string) $kegiatan->nama_kegiatan)) === 'sensus ekonomi';
    }

    private function calculateMonthlyHonorForAllocation(
        float $baseHonor,
        int $bulan,
        ?\App\Models\Kegiatan $kegiatan
    ): float {
        if (! $kegiatan || ! $this->isSensusEkonomi2026($kegiatan)) {
            return $baseHonor;
        }

        $monthlyObWeight = $this->getSensusEkonomiMonthlyObWeight($bulan);

        if ($monthlyObWeight <= 0) {
            return 0.0;
        }

        return $baseHonor * ($monthlyObWeight / 2.5);
    }

    private function getSensusEkonomiMonthlyObWeight(int $bulan): float
    {
        return match ($bulan) {
            6 => 0.5,
            7 => 1.0,
            8 => 1.0,
            default => 0.0,
        };
    }

    private function isSensusEkonomiKegiatan(?\App\Models\Kegiatan $kegiatan): bool
    {
        return $kegiatan !== null
            && $kegiatan->jenis_kegiatan === 'sensus'
            && mb_strtolower(trim((string) $kegiatan->nama_kegiatan)) === 'sensus ekonomi';
    }

    private function shouldUseSensusOnlyMaxAllowed(Collection $sensusAlokasis, Collection $regularAlokasis): bool
    {
        if ($sensusAlokasis->isEmpty() || $regularAlokasis->isEmpty()) {
            return false;
        }

        foreach ($sensusAlokasis as $sensusAlokasi) {
            $sensusRange = $this->resolvePeriodeDateRange($sensusAlokasi->periodeAlokasi);

            if ($sensusRange === null) {
                return false;
            }

            foreach ($regularAlokasis as $regularAlokasi) {
                $regularRange = $this->resolvePeriodeDateRange($regularAlokasi->periodeAlokasi);

                if ($regularRange === null) {
                    return false;
                }

                if ($this->dateRangesOverlap($sensusRange, $regularRange)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array{0:Carbon,1:Carbon}|null
     */
    private function resolvePeriodeDateRange(?PeriodeAlokasi $periode): ?array
    {
        if (! $periode) {
            return null;
        }

        $startCandidates = array_filter([
            $periode->tanggal_mulai ? Carbon::parse($periode->tanggal_mulai) : null,
            $periode->tanggal_mulai_listing ? Carbon::parse($periode->tanggal_mulai_listing) : null,
        ]);
        $endCandidates = array_filter([
            $periode->tanggal_selesai ? Carbon::parse($periode->tanggal_selesai) : null,
            $periode->tanggal_selesai_listing ? Carbon::parse($periode->tanggal_selesai_listing) : null,
        ]);

        if ($startCandidates === [] || $endCandidates === []) {
            $monthStart = Carbon::create($periode->tahun, (int) $periode->bulan, 1)->startOfMonth();
            $monthEnd = Carbon::create($periode->tahun, (int) $periode->bulan, 1)->endOfMonth();

            return [$monthStart, $monthEnd];
        }

        /** @var Carbon $start */
        $start = collect($startCandidates)->sortBy(fn (Carbon $date) => $date->timestamp)->first();
        /** @var Carbon $end */
        $end = collect($endCandidates)->sortByDesc(fn (Carbon $date) => $date->timestamp)->first();

        return [$start, $end];
    }

    /**
     * @param  array{0:Carbon,1:Carbon}  $firstRange
     * @param  array{0:Carbon,1:Carbon}  $secondRange
     */
    private function dateRangesOverlap(array $firstRange, array $secondRange): bool
    {
        [$firstStart, $firstEnd] = $firstRange;
        [$secondStart, $secondEnd] = $secondRange;

        return $firstStart->lte($secondEnd) && $secondStart->lte($firstEnd);
    }

    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }

    private function resolveReportBulanCandidates(string $bulan): array
    {
        $candidates = $this->resolveBulanCandidates($bulan);

        if (in_array((int) $bulan, [6, 7, 8], true)) {
            $candidates = array_merge($candidates, ['06', '07', '08']);
        }

        return array_values(array_unique($candidates));
    }

    private function shouldIncludeInMonthlyReport(\App\Models\AlokasiPetugas $alokasi, int $reportMonth): bool
    {
        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

        if ($this->isSensusEkonomiKegiatan($kegiatan)) {
            return in_array($reportMonth, [6, 7, 8], true);
        }

        return (int) ($alokasi->periodeAlokasi?->bulan ?? 0) === $reportMonth;
    }

    private function resolveReportMonthForAllocation(\App\Models\AlokasiPetugas $alokasi, int $reportMonth): int
    {
        if ($this->isSensusEkonomiKegiatan($alokasi->periodeAlokasi?->kegiatan)) {
            return $reportMonth;
        }

        return (int) ($alokasi->periodeAlokasi?->bulan ?? $reportMonth);
    }

    private function normalizeBulan(string|int $bulan): string
    {
        return str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);
    }
}
