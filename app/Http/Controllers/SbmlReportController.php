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
                $totalHonor = $alokasis->sum('total_honor');

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
                $sbmlRecords = Sbml::where('tahun_anggaran', $tahun)
                    ->where('status_kepegawaian', $statusKepegawaian)
                    ->whereIn('jenis_penugasan', $jenisPenugasanSbml)
                    ->where('status', 'aktif')
                    ->orderByDesc('honor_max')
                    ->get();

                // Take the highest honor_max from matching SBML records
                $maxAllowed = $sbmlRecords->isNotEmpty() ? $sbmlRecords->max('honor_max') : 0;
                $exceeds = $maxAllowed > 0 && $totalHonor > $maxAllowed;

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

                    $kegiatanDetails[$kegiatanId]['total_honor'] += $alokasi->total_honor;
                    $kegiatanDetails[$kegiatanId]['alokasi'][] = [
                        'peran' => $this->formatPeran($alokasi->peran),
                        'jumlah_satuan' => $alokasi->jumlah_satuan,
                        'total_honor' => $alokasi->total_honor,
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
                    'max_allowed' => $maxAllowed,
                    'exceeds' => $exceeds,
                    'difference' => $totalHonor - $maxAllowed,
                    'percentage' => $maxAllowed > 0 ? ($totalHonor / $maxAllowed) * 100 : 0,
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

        return Inertia::render('Sbml/Report', [
            'petugas' => $petugasData,
            'filters' => [
                'tahun' => (int) $tahun,
                'bulan' => $bulan,
            ],
            'bulan_options' => $bulanOptions,
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
