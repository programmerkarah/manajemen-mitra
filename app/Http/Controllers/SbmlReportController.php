<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\Petugas;
use App\Models\Sbml;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SbmlReportController extends Controller
{
    /**
     * Display honor summary report per petugas per month
     */
    public function index(Request $request): Response
    {
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan', str_pad(date('m'), 2, '0', STR_PAD_LEFT));

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
            'periodeAlokasi.kegiatan:id,hashed_id,nama_kegiatan',
        ])
            ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan']);
            })
            ->where(function ($query) {
                $query->where('jumlah_satuan', '>', 0)
                    ->orWhere('jumlah_satuan_listing', '>', 0);
            })
            ->get()
            ->groupBy('petugas_id')
            ->map(function ($alokasis, $petugasId) use ($sbmlCache) {
                $petugas = $alokasis->first()->petugas;

                if (! $petugas) {
                    return null;
                }

                // Calculate total honor for this petugas in this month
                $totalHonor = $alokasis->sum(function ($alokasi) {
                    // Include both pencacahan and listing honor
                    return $alokasi->total_honor + ($alokasi->total_honor_listing ?? 0);
                });

                // Get max SBML based on jenis penugasan from allocations
                $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';

                // Collect unique jenis penugasan (peran) from all allocations
                $jenisPenugasanList = $alokasis->pluck('peran')->unique();

                // Get SBML records from cache instead of querying database
                $honorMaxList = $jenisPenugasanList->map(function ($peran) use ($sbmlCache, $statusKepegawaian, $alokasis) {
                    $jenisKegiatan = $alokasis->firstWhere('peran', $peran)?->periodeAlokasi?->jenis_kegiatan ?? null;
                    $cacheKey = $jenisKegiatan.'_'.$statusKepegawaian.'_'.$peran;

                    return $sbmlCache->get($cacheKey)?->honor_max;
                })->filter();

                $minAllowed = $honorMaxList->isNotEmpty() ? $honorMaxList->min() : 0;
                $exceeds = $minAllowed > 0 && $totalHonor > $minAllowed;

                // Group by kegiatan for details
                $kegiatanDetails = [];
                foreach ($alokasis as $alokasi) {
                    $kegiatanId = $alokasi->periodeAlokasi->kegiatan_id;

                    if (! isset($kegiatanDetails[$kegiatanId])) {
                        $kegiatanDetails[$kegiatanId] = [
                            'kegiatan_id' => $kegiatanId,
                            'kegiatan_hashed_id' => $alokasi->periodeAlokasi->kegiatan->hashed_id,
                            'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                            'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                            'total_honor' => 0,
                            'alokasi' => [],
                        ];
                    }

                    // Already filtered by jumlah > 0 in the query
                    $kegiatanDetails[$kegiatanId]['total_honor'] += $alokasi->total_honor + ($alokasi->total_honor_listing ?? 0);
                    $kegiatanDetails[$kegiatanId]['alokasi'][] = [
                        'peran' => $this->formatPeran($alokasi->peran),
                        'jumlah_satuan' => $alokasi->jumlah_satuan,
                        'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                        'total_honor' => $alokasi->total_honor,
                        'total_honor_listing' => $alokasi->total_honor_listing ?? 0,
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

            return [
                'value' => $monthStr,
                'label' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
            ];
        });

        $currentYear = (int) date('Y');

        // Get unique years from alokasi petugas
        $tahunOptions = \App\Models\PeriodeAlokasi::select('tahun')
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
            'petugas' => $petugasData,
            'filters' => [
                'tahun' => (int) $tahun,
                'bulan' => $bulan,
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
}
