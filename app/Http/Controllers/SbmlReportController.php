<?php

namespace App\Http\Controllers;

use App\Models\Mitra;
use App\Models\Sbml;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SbmlReportController extends Controller
{
    /**
     * Display SBML monitoring report
     */
    public function index(Request $request): Response
    {
        $tahun = $request->input('tahun', date('Y'));
        $bulan = $request->input('bulan');
        $jenisKegiatan = $request->input('jenis_kegiatan');
        $statusKepegawaian = $request->input('status_kepegawaian');

        // Get mitra with their allocations
        $mitrasQuery = Mitra::with(['alokasiMitra' => function ($query) use ($tahun, $bulan, $jenisKegiatan, $statusKepegawaian) {
            $query->where('tahun', $tahun);
            if ($bulan) {
                $query->where('bulan', $bulan);
            }
            if ($jenisKegiatan) {
                $query->where('jenis_kegiatan', $jenisKegiatan);
            }
            if ($statusKepegawaian) {
                $query->where('status_kepegawaian', $statusKepegawaian);
            }
            $query->with(['kegiatan']);
        }])->where('status', 'aktif');

        $mitras = $mitrasQuery->get()->map(function ($mitra) use ($tahun) {
            // Group allocations by month, jenis_kegiatan, status_kepegawaian, and peran
            $monthlyData = [];

            foreach ($mitra->alokasiMitra as $alokasi) {
                $key = $alokasi->bulan.'_'.$alokasi->jenis_kegiatan.'_'.$alokasi->status_kepegawaian;

                if (! isset($monthlyData[$key])) {
                    $monthlyData[$key] = [
                        'bulan' => $alokasi->bulan,
                        'jenis_kegiatan' => $alokasi->jenis_kegiatan,
                        'status_kepegawaian' => $alokasi->status_kepegawaian,
                        'total_honor' => 0,
                        'highest_peran' => null,
                        'details' => [],
                    ];
                }

                $monthlyData[$key]['total_honor'] += (float) $alokasi->total_honor;
                $monthlyData[$key]['details'][] = [
                    'kegiatan' => $alokasi->kegiatan->nama_kegiatan,
                    'peran' => $alokasi->peran,
                    'honor' => (float) $alokasi->total_honor,
                ];

                // Determine highest role
                if (! $monthlyData[$key]['highest_peran']) {
                    $monthlyData[$key]['highest_peran'] = $alokasi->peran;
                } else {
                    $monthlyData[$key]['highest_peran'] = $this->getHighestRole(
                        $monthlyData[$key]['highest_peran'],
                        $alokasi->peran
                    );
                }
            }

            // Calculate max allowed and violations for each month
            foreach ($monthlyData as $key => &$data) {
                $maxAllowed = Sbml::getMaxForCriteria(
                    $tahun,
                    $data['jenis_kegiatan'],
                    $data['status_kepegawaian'],
                    $data['highest_peran']
                );

                $data['max_allowed'] = $maxAllowed;
                $data['exceeds'] = $maxAllowed > 0 && $data['total_honor'] > $maxAllowed;
                $data['difference'] = $data['total_honor'] - $maxAllowed;
            }

            return [
                'id' => $mitra->id,
                'hashed_id' => $mitra->hashed_id,
                'nama' => $mitra->nama,
                'nik' => $mitra->nik,
                'monthly_data' => array_values($monthlyData),
                'total_honor_tahun' => array_sum(array_column($monthlyData, 'total_honor')),
                'has_violations' => collect($monthlyData)->contains('exceeds', true),
            ];
        })->filter(function ($mitra) {
            return count($mitra['monthly_data']) > 0;
        })->values();

        return Inertia::render('Sbml/Report', [
            'mitras' => $mitras,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'filters' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'jenis_kegiatan' => $jenisKegiatan,
                'status_kepegawaian' => $statusKepegawaian,
            ],
        ]);
    }

    /**
     * Get the highest role priority
     * Priority: pml > pcl_ppl > pengolahan
     */
    private function getHighestRole(string $role1, string $role2): string
    {
        $priority = [
            'pml' => 3,
            'pcl_ppl' => 2,
            'pengolahan' => 1,
        ];

        return ($priority[$role1] ?? 0) >= ($priority[$role2] ?? 0) ? $role1 : $role2;
    }
}
