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

        // Get all petugas who have allocations in the selected month
        $petugasData = AlokasiPetugas::with([
            'petugas',
            'periodeAlokasi.kegiatan',
        ])
            ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)
                    ->where('bulan', $bulan)
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan']);
            })
            ->get()
            ->groupBy('petugas_id')
            ->map(function ($alokasis, $petugasId) use ($tahun) {
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

                // Map peran to jenis_penugasan in SBML
                $jenisPenugasanSbml = $jenisPenugasanList->map(function ($peran) {
                    return match ($peran) {
                        'pcl_ppl' => 'pcl_ppl',
                        'pml' => 'pml',
                        'pengolahan', 'pengawas_pengolahan' => 'pengolahan',
                        default => $peran,
                    };
                })->unique();

                // Get SBML records for the jenis penugasan
                // Ambil honor_max SBML hanya untuk penugasan yang sudah diberikan ke petugas
                $honorMaxList = $jenisPenugasanList->map(function ($peran) use ($tahun, $statusKepegawaian) {
                    // Gunakan value peran asli dari alokasi sebagai jenis_penugasan
                    $jenisPenugasan = $peran;
                    $sbml = \App\Models\Sbml::where('tahun_anggaran', $tahun)
                        ->where('status_kepegawaian', $statusKepegawaian)
                        ->where('jenis_penugasan', $jenisPenugasan)
                        ->where('status', 'aktif')
                        ->first();
                    return $sbml ? $sbml->honor_max : null;
                })->filter();

                $minAllowed = $honorMaxList->isNotEmpty() ? $honorMaxList->min() : 0;
                $exceeds = $minAllowed > 0 && $totalHonor > $minAllowed;

                // Group by kegiatan for details
                $kegiatanDetails = [];
                foreach ($alokasis as $alokasi) {
                    $kegiatanId = $alokasi->periodeAlokasi->kegiatan_id;
                    $kegiatan = $alokasi->periodeAlokasi->kegiatan;

                    if (! isset($kegiatanDetails[$kegiatanId])) {
                        $kegiatanDetails[$kegiatanId] = [
                            'kegiatan_id' => $kegiatanId,
                            'kegiatan_hashed_id' => $kegiatan->hashed_id,
                            'nama_kegiatan' => $kegiatan->nama_kegiatan,
                            'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                            'total_honor' => 0,
                            'alokasi' => [],
                        ];
                    }

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
                    'petugas_hashed_id' => $petugas->hashed_id,
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
