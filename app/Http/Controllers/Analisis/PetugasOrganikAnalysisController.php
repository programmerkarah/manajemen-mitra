<?php

namespace App\Http\Controllers\Analisis;

use App\Http\Controllers\Analisis\Concerns\BuildsAnalisisQueries;
use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Services\SensusEkonomiReplacementReadService;
use App\Traits\EffectivePeriodeScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PetugasOrganikAnalysisController extends Controller
{
    use BuildsAnalisisQueries;
    use EffectivePeriodeScope;

    public function __invoke(): Response
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) now()->month;
        $activeStatuses = ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'];

        $petugasOrganikAktif = Petugas::query()
            ->where('jenis_petugas', 'organik')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->select('id', 'nama', 'jabatan')
            ->orderBy('nama')
            ->get();

        $alokasiPerPetugasQuery = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->whereRaw('CAST(periode_alokasi.bulan AS UNSIGNED) <= ?', [$currentMonth])
            ->whereIn('periode_alokasi.status', $activeStatuses)
            ->where('petugas.jenis_petugas', 'organik');

        $alokasiPerPetugas = $alokasiPerPetugasQuery
            ->select('petugas.id as petugas_id')
            ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
            ->selectRaw("COUNT(DISTINCT CONCAT(periode_alokasi.kegiatan_id, '-', CAST(periode_alokasi.bulan AS UNSIGNED))) as jumlah_alokasi")
            ->selectRaw('COUNT(DISTINCT CAST(periode_alokasi.bulan AS UNSIGNED)) as jumlah_bulan_dialokasikan')
            ->groupBy('petugas.id')
            ->get()
            ->keyBy('petugas_id');

        $bebanKerjaDetail = $petugasOrganikAktif
            ->map(function ($petugas) use ($alokasiPerPetugas) {
                $stat = $alokasiPerPetugas->get($petugas->id);
                $jumlahKegiatan = $stat ? (int) $stat->jumlah_kegiatan : 0;
                $jumlahAlokasi = $stat ? (int) $stat->jumlah_alokasi : 0;
                $jumlahBulanDialokasikan = $stat ? (int) $stat->jumlah_bulan_dialokasikan : 0;
                $rataRataKegiatanPerBulan = $jumlahBulanDialokasikan > 0 ? $jumlahAlokasi / $jumlahBulanDialokasikan : 0;

                $performanceStatus = 'under_performance';
                $performanceLabel = 'Under Performance';
                if ($rataRataKegiatanPerBulan > 3) {
                    $performanceStatus = 'overload';
                    $performanceLabel = 'Overload';
                } elseif (abs($rataRataKegiatanPerBulan - 1) < 0.00001) {
                    $performanceStatus = 'normal';
                    $performanceLabel = 'Normal';
                } elseif ($rataRataKegiatanPerBulan > 1 && $rataRataKegiatanPerBulan <= 3) {
                    $performanceStatus = 'optimal';
                    $performanceLabel = 'Optimal';
                }

                return [
                    'petugas_id' => $petugas->id,
                    'petugas_nama' => $petugas->nama,
                    'jabatan' => $petugas->jabatan,
                    'jumlah_alokasi' => $jumlahAlokasi,
                    'jumlah_kegiatan' => $jumlahKegiatan,
                    'rata_rata_kegiatan_per_bulan' => round($rataRataKegiatanPerBulan, 2),
                    'performance_status' => $performanceStatus,
                    'performance_label' => $performanceLabel,
                ];
            })
            ->sortByDesc('jumlah_kegiatan')
            ->values()
            ->all();

        $distribusiBebanKerja = [
            ['label' => '0 kegiatan', 'count' => collect($bebanKerjaDetail)->where('jumlah_kegiatan', 0)->count()],
            ['label' => '1 kegiatan', 'count' => collect($bebanKerjaDetail)->where('jumlah_kegiatan', 1)->count()],
            ['label' => '2 kegiatan', 'count' => collect($bebanKerjaDetail)->where('jumlah_kegiatan', 2)->count()],
            ['label' => '3 kegiatan', 'count' => collect($bebanKerjaDetail)->where('jumlah_kegiatan', 3)->count()],
            ['label' => '4-5 kegiatan', 'count' => collect($bebanKerjaDetail)->whereBetween('jumlah_kegiatan', [4, 5])->count()],
            ['label' => '> 5 kegiatan', 'count' => collect($bebanKerjaDetail)->where('jumlah_kegiatan', '>', 5)->count()],
        ];

        $trenBebanKerja = [];
        for ($bulan = 1; $bulan <= $currentMonth; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $bulanCandidates = $this->resolveBulanCandidates($bulanFormatted);

            $data = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->whereIn('periode_alokasi.bulan', $bulanCandidates)
                ->where('periode_alokasi.tahun', $currentYear)
                ->whereIn('periode_alokasi.status', $activeStatuses)
                ->where('petugas.jenis_petugas', 'organik');
            $data = $data
                ->selectRaw('COUNT(DISTINCT alokasi_petugas.petugas_id) as jumlah_petugas')
                ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
                ->selectRaw("COUNT(DISTINCT CONCAT(alokasi_petugas.petugas_id, '-', periode_alokasi.kegiatan_id)) as jumlah_alokasi")
                ->first();

            $trenBebanKerja[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $data->jumlah_petugas,
                'jumlah_kegiatan' => (int) $data->jumlah_kegiatan,
                'jumlah_alokasi' => (int) $data->jumlah_alokasi,
            ];
        }

        $ringkasan = [
            'total_petugas_aktif' => $petugasOrganikAktif->count(),
            'total_petugas_teralokasi' => collect($bebanKerjaDetail)->where('jumlah_alokasi', '>', 0)->count(),
            'total_alokasi' => collect($bebanKerjaDetail)->sum('jumlah_alokasi'),
        ];

        return Inertia::render('Analisis/PetugasOrganik', [
            'ringkasan' => $ringkasan,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenBebanKerja' => $trenBebanKerja,
            'bebanKerjaDetail' => $bebanKerjaDetail,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ]);
    }
}
