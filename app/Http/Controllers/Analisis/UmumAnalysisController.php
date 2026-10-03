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

class UmumAnalysisController extends Controller
{
    use BuildsAnalisisQueries;
    use EffectivePeriodeScope;

    public function __invoke(): Response
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) now()->month;

        // Utilisasi Anggaran per Kegiatan
        $utilisasiAnggaran = Kegiatan::query()
            ->where('tahun_anggaran', $currentYear)
            ->whereNotIn('status', ['dibatalkan'])
            ->get()
            ->map(function ($kegiatan) use ($currentYear) {
                $totalPagu = ($kegiatan->pagu_pencacahan ?? 0) + ($kegiatan->pagu_listing ?? 0);

                $totalHonorQuery = DB::table('alokasi_petugas')
                    ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                    ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                    ->where('periode_alokasi.kegiatan_id', $kegiatan->id)
                    ->where('periode_alokasi.tahun', $currentYear)
                    ->whereRaw($this->allocationOrHonorExistsClause());

                $this->applyEffectivePeriode($totalHonorQuery);

                $totalHonor = $totalHonorQuery
                    ->selectRaw('COALESCE(SUM('.$this->effectiveCombinedHonorSqlExpression().'), 0) as total')
                    ->value('total');

                return [
                    'kegiatan_id' => $kegiatan->id,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                    'total_pagu' => (float) $totalPagu,
                    'total_terpakai' => (float) $totalHonor,
                    'persentase' => $totalPagu > 0 ? round(($totalHonor / $totalPagu) * 100, 1) : 0,
                ];
            })->filter(fn ($item) => $item['total_pagu'] > 0)->sortBy([
                ['persentase', 'desc'],
                ['total_pagu', 'desc'],
            ])->values()->all();

        // Beban Kerja Petugas (distribusi jumlah kegiatan per petugas)
        $bebanKerja = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($bebanKerja);
        $bebanKerja = $bebanKerja
            ->groupBy('alokasi_petugas.petugas_id')
            ->selectRaw('alokasi_petugas.petugas_id, COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
            ->get();

        $distribusiBebanKerja = [
            ['label' => '1 kegiatan', 'count' => $bebanKerja->where('jumlah_kegiatan', 1)->count()],
            ['label' => '2 kegiatan', 'count' => $bebanKerja->where('jumlah_kegiatan', 2)->count()],
            ['label' => '3 kegiatan', 'count' => $bebanKerja->where('jumlah_kegiatan', 3)->count()],
            ['label' => '4-5 kegiatan', 'count' => $bebanKerja->whereBetween('jumlah_kegiatan', [4, 5])->count()],
            ['label' => '> 5 kegiatan', 'count' => $bebanKerja->where('jumlah_kegiatan', '>', 5)->count()],
        ];

        // Tren Alokasi bulanan (petugas unik dan honor total)
        $trenAlokasi = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $bulanCandidates = $this->resolveBulanCandidates($bulanFormatted);

            $data = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->whereIn('periode_alokasi.bulan', $bulanCandidates)
                ->where('periode_alokasi.tahun', $currentYear)
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applyEffectivePeriode($data);
            $data = $data
                ->selectRaw('COUNT(DISTINCT alokasi_petugas.petugas_id) as jumlah_petugas')
                ->selectRaw('COALESCE(SUM('.$this->sensusEkonomiHonorSqlCase().'), 0) as total_honor')
                ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as total_kegiatan')
                ->first();

            $trenAlokasi[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $data->jumlah_petugas,
                'total_honor' => (float) $data->total_honor,
                'total_kegiatan' => (int) $data->total_kegiatan,
            ];
        }

        // KPI Summary
        $totalPaguAll = (float) collect($utilisasiAnggaran)->sum('total_pagu');
        $totalTerpakaiAll = (float) collect($utilisasiAnggaran)->sum('total_terpakai');

        $totalPetugasAktifQuery = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($totalPetugasAktifQuery);
        $totalPetugasAktif = $totalPetugasAktifQuery
            ->distinct('alokasi_petugas.petugas_id')
            ->count('alokasi_petugas.petugas_id');

        $totalKegiatanAktif = Kegiatan::query()
            ->where('tahun_anggaran', $currentYear)
            ->whereNotIn('status', ['dibatalkan'])
            ->count();

        $ringkasanKPI = [
            'total_pagu' => $totalPaguAll,
            'total_terpakai' => $totalTerpakaiAll,
            'serapan_persen' => $totalPaguAll > 0 ? round(($totalTerpakaiAll / $totalPaguAll) * 100, 1) : 0,
            'total_petugas_aktif' => $totalPetugasAktif,
            'total_kegiatan_aktif' => $totalKegiatanAktif,
        ];

        // Ringkasan per jenis kegiatan
        $ringkasanJenisKegiatan = collect($utilisasiAnggaran)
            ->groupBy('jenis_kegiatan')
            ->map(function ($items, $jenis) {
                $pagu = (float) collect($items)->sum('total_pagu');
                $terpakai = (float) collect($items)->sum('total_terpakai');

                return [
                    'jenis' => $jenis,
                    'label' => match ($jenis) {
                        'sensus' => 'Sensus',
                        'survei' => 'Survei',
                        'kompilasi' => 'Kompilasi',
                        default => ucfirst((string) $jenis),
                    },
                    'jumlah_kegiatan' => count($items),
                    'total_pagu' => $pagu,
                    'total_terpakai' => $terpakai,
                    'serapan_persen' => $pagu > 0 ? round(($terpakai / $pagu) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total_pagu')
            ->values()
            ->all();

        // Top 10 petugas penyerap honor terbesar, mengikuti formula per bulan seperti analisis petugas
        $topPetugasByMonth = collect();
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $monthlyTopPetugasQuery = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->where('petugas.status', 'aktif')
                ->whereNull('petugas.deleted_at')
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($monthlyTopPetugasQuery, $bulan, 'kegiatan');
            $this->applyEffectivePeriode($monthlyTopPetugasQuery);

            $topPetugasByMonth = $topPetugasByMonth->merge(
                $monthlyTopPetugasQuery
                    ->groupBy('alokasi_petugas.petugas_id', 'petugas.nama', 'petugas.jabatan')
                    ->select(
                        'alokasi_petugas.petugas_id',
                        'petugas.nama',
                        'petugas.jabatan',
                    )
                    ->selectRaw('COALESCE(SUM('.$this->sensusEkonomiHonorSqlCaseForMonth($bulan).'), 0) as total_honor')
                    ->get()
            );
        }

        $topPetugasKegiatanCount = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->where('petugas.status', 'aktif')
            ->whereNull('petugas.deleted_at')
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($topPetugasKegiatanCount);
        $topPetugasKegiatanCount = $topPetugasKegiatanCount
            ->groupBy('alokasi_petugas.petugas_id', 'petugas.nama', 'petugas.jabatan')
            ->selectRaw('alokasi_petugas.petugas_id, petugas.nama, petugas.jabatan, COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
            ->get()
            ->keyBy('petugas_id');

        $topPetugas = $topPetugasByMonth
            ->groupBy('petugas_id')
            ->map(function ($items) use ($topPetugasKegiatanCount) {
                $first = $items->first();

                return [
                    'petugas_id' => $first->petugas_id,
                    'nama' => $first->nama,
                    'jabatan' => $first->jabatan,
                    'jumlah_kegiatan' => (int) ($topPetugasKegiatanCount->get($first->petugas_id)->jumlah_kegiatan ?? 0),
                    'total_honor' => (float) $items->sum('total_honor'),
                ];
            })
            ->sortByDesc('total_honor')
            ->values()
            ->slice(0, 10)
            ->all();

        return Inertia::render('Analisis/Umum', [
            'utilisasiAnggaran' => $utilisasiAnggaran,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenAlokasi' => $trenAlokasi,
            'ringkasanKPI' => $ringkasanKPI,
            'ringkasanJenisKegiatan' => $ringkasanJenisKegiatan,
            'topPetugas' => $topPetugas,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
        ]);
    }
}
