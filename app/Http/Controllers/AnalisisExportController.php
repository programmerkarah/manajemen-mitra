<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Traits\EffectivePeriodeScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalisisExportController extends Controller
{
    use EffectivePeriodeScope;

    /**
     * Export Analisis Umum as PDF.
     */
    public function umum(): HttpResponse
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) now()->month;

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
                    ->selectRaw('COALESCE(SUM('.$this->sensusEkonomiHonorSqlCase().'), 0) as total')
                    ->value('total');

                return [
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'total_pagu' => (float) $totalPagu,
                    'total_terpakai' => (float) $totalHonor,
                    'persentase' => $totalPagu > 0 ? round(($totalHonor / $totalPagu) * 100, 1) : 0,
                ];
            })->filter(fn ($item) => $item['total_pagu'] > 0)->sortBy([
                ['persentase', 'desc'],
                ['total_pagu', 'desc'],
            ])->values()->all();

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
                ->where('petugas.jenis_petugas', 'non-organik')
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

        $umumPieSvg = $this->buildPieChartSvg($distribusiBebanKerja, 'label', 'count', 'Distribusi Beban Kerja Petugas');
        $umumLineSvg = $this->buildLineChartSvg(
            array_map(fn ($item) => [
                'label' => $this->monthName((int) $item['bulan']),
                'total_honor_jt' => round(((float) $item['total_honor']) / 1_000_000, 2),
                'total_kegiatan' => (int) $item['total_kegiatan'],
            ], $trenAlokasi),
            'label',
            [
                ['key' => 'total_honor_jt', 'label' => 'Total Honor (juta)', 'color' => '#22c55e'],
                ['key' => 'total_kegiatan', 'label' => 'Total Kegiatan', 'color' => '#3b82f6'],
            ],
            'Tren Alokasi Bulanan',
            1,
        );

        // Top 10 petugas penyerap honor terbesar (s.d. bulan berjalan, dengan bobot sensus)
        $topPetugasQuery = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->whereRaw('CAST(periode_alokasi.bulan AS UNSIGNED) <= ?', [$currentMonth])
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($topPetugasQuery);
        $topPetugas = $topPetugasQuery
            ->groupBy('alokasi_petugas.petugas_id', 'petugas.nama', 'petugas.jabatan')
            ->selectRaw('alokasi_petugas.petugas_id, petugas.nama, petugas.jabatan, COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
            ->selectRaw('COALESCE(SUM('.$this->sensusEkonomiHonorSqlCase().'), 0) as total_honor')
            ->orderByRaw('total_honor DESC')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'nama' => $item->nama,
                'jabatan' => $item->jabatan,
                'jumlah_kegiatan' => (int) $item->jumlah_kegiatan,
                'total_honor' => (float) $item->total_honor,
            ])
            ->all();

        $pdf = Pdf::loadView('analisis.umum-pdf', [
            'utilisasiAnggaran' => $utilisasiAnggaran,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenAlokasi' => $trenAlokasi,
            'topPetugas' => $topPetugas,
            'pieChartSvg' => $umumPieSvg,
            'lineChartSvg' => $umumLineSvg,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_umum', $currentYear));
    }

    /**
     * Export Analisis Petugas as PDF.
     */
    public function petugas(): HttpResponse
    {
        $currentYear = (int) date('Y');

        $petugasNonOrganik = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->get();

        $distribusiJenisKelamin = $petugasNonOrganik->groupBy('jenis_kelamin')
            ->map(fn ($group, $key) => [
                'label' => match ($key) {
                    'laki-laki' => 'Laki-laki',
                    'perempuan' => 'Perempuan',
                    default => 'Belum Diisi',
                },
                'count' => $group->count(),
            ])->values()->all();

        $distribusiKecamatan = $petugasNonOrganik->groupBy(fn ($p) => $p->kecamatan ?: 'Belum Diisi')
            ->map(fn ($group, $key) => [
                'kecamatan' => $key,
                'count' => $group->count(),
            ])->sortByDesc('count')->values()->all();

        $distribusiTugasDesaKelurahan = $petugasNonOrganik
            ->groupBy(function ($petugas) {
                $kecamatan = trim((string) ($petugas->kecamatan ?? ''));
                $desaKelurahan = trim((string) ($petugas->desa_kelurahan ?? ''));

                return ($kecamatan !== '' ? $kecamatan : 'Belum Diisi').'|'.($desaKelurahan !== '' ? $desaKelurahan : 'Belum Diisi');
            })
            ->map(function ($group, $key) {
                [$kecamatan, $desaKelurahan] = explode('|', (string) $key, 2);

                return [
                    'kecamatan' => $kecamatan,
                    'desa_kelurahan' => $desaKelurahan,
                    'jumlah_petugas' => $group->count(),
                ];
            })
            ->sortByDesc('jumlah_petugas')
            ->values()
            ->all();

        $usiaRanges = [
            ['label' => '< 20', 'min' => 0, 'max' => 19],
            ['label' => '20-29', 'min' => 20, 'max' => 29],
            ['label' => '30-39', 'min' => 30, 'max' => 39],
            ['label' => '40-49', 'min' => 40, 'max' => 49],
            ['label' => '50-59', 'min' => 50, 'max' => 59],
            ['label' => '≥ 60', 'min' => 60, 'max' => 200],
        ];

        $distribusiUsia = [];
        foreach ($usiaRanges as $range) {
            $count = $petugasNonOrganik->filter(function ($p) use ($range) {
                if (! $p->tanggal_lahir) {
                    return false;
                }
                $usia = Carbon::parse($p->tanggal_lahir)->age;

                return $usia >= $range['min'] && $usia <= $range['max'];
            })->count();
            $distribusiUsia[] = ['label' => $range['label'], 'count' => $count];
        }
        $belumDiisiUsia = $petugasNonOrganik->whereNull('tanggal_lahir')->count();
        if ($belumDiisiUsia > 0) {
            $distribusiUsia[] = ['label' => 'Belum Diisi', 'count' => $belumDiisiUsia];
        }

        $distribusiPendidikan = $petugasNonOrganik->groupBy('pendidikan')
            ->map(fn ($group, $key) => [
                'pendidikan' => $key,
                'count' => $group->count(),
            ])->sortByDesc('count')->values()->all();

        $petugasBelumDialokasikan = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('alokasi_petugas')
                    ->whereColumn('alokasi_petugas.petugas_id', 'petugas.id');
            })
            ->select('id', 'nama', 'kecamatan', 'jenis_kelamin', 'telepon')
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'kecamatan' => $p->kecamatan,
                'jenis_kelamin' => $p->jenis_kelamin,
                'telepon' => $p->telepon,
            ])
            ->values()
            ->all();

        $alokasiPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $jumlahPetugas = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($jumlahPetugas, $bulan, 'kegiatan');
            $this->applyEffectivePeriode($jumlahPetugas);
            $jumlahPetugas = $jumlahPetugas
                ->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            $jumlahKegiatan = DB::table('periode_alokasi')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear);
            $this->applySensusEkonomiMonthFilter($jumlahKegiatan, $bulan, 'kegiatan');
            $this->applyEffectivePeriode($jumlahKegiatan);
            $jumlahKegiatan = $jumlahKegiatan
                ->distinct('periode_alokasi.kegiatan_id')
                ->count('periode_alokasi.kegiatan_id');

            $alokasiPerBulan[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => $jumlahPetugas,
                'jumlah_kegiatan' => $jumlahKegiatan,
            ];
        }

        $petugasKegiatan = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($petugasKegiatan);
        $petugasKegiatan = $petugasKegiatan->select(
            'petugas.id as petugas_id',
            'petugas.nama as petugas_nama',
            'kegiatan.id as kegiatan_id',
            'kegiatan.nama_kegiatan',
            'kegiatan.kode_kegiatan',
        )
            ->distinct()
            ->get();

        $petugasKegiatanGrouped = $petugasKegiatan->groupBy('petugas_id')->map(function ($items) {
            $first = $items->first();

            return [
                'petugas_id' => $first->petugas_id,
                'petugas_nama' => $first->petugas_nama,
                'kegiatan' => $items->map(fn ($item) => [
                    'id' => $item->kegiatan_id,
                    'nama' => $item->nama_kegiatan,
                    'kode' => $item->kode_kegiatan,
                ])->unique('id')->values()->all(),
                'jumlah_kegiatan' => $items->unique('kegiatan_id')->count(),
            ];
        })->sortByDesc('jumlah_kegiatan')->values()->all();

        $petugasAlokasiRaw = collect();
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $monthlyQuery = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($monthlyQuery, $bulan, 'kegiatan');
            $this->applyEffectivePeriode($monthlyQuery);

            $monthlyRows = $monthlyQuery->select(
                'petugas.id as petugas_id',
                'petugas.nama as petugas_nama',
                DB::raw($bulan.' as bulan'),
            )
                ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
                ->selectRaw('COALESCE(SUM('.$this->sensusEkonomiHonorSqlCaseForMonth($bulan).'), 0) as total_honor')
                ->groupBy('petugas.id', 'petugas.nama')
                ->get();

            $petugasAlokasiRaw = $petugasAlokasiRaw->merge($monthlyRows);
        }

        $petugasAlokasiDetail = $petugasAlokasiRaw->groupBy('petugas_id')->map(function ($items) {
            $first = $items->first();
            $bulanData = [];
            $honorData = [];
            for ($b = 1; $b <= 12; $b++) {
                $found = $items->firstWhere('bulan', $b);
                $bulanData[$b] = $found ? (int) $found->jumlah_kegiatan : 0;
                $honorData[$b] = $found ? (float) $found->total_honor : 0;
            }

            return [
                'petugas_id' => $first->petugas_id,
                'petugas_nama' => $first->petugas_nama,
                'bulan' => $bulanData,
                'honor' => $honorData,
                'total' => array_sum($bulanData),
                'total_honor' => array_sum($honorData),
            ];
        })->sortByDesc('total')->values()->all();

        $top5ByKegiatan = collect($petugasAlokasiDetail)
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'label' => (string) $item['petugas_nama'],
                'value' => (int) $item['total'],
            ])
            ->all();

        $top5ByHonor = collect($petugasAlokasiDetail)
            ->sortByDesc('total_honor')
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'label' => (string) $item['petugas_nama'],
                'value' => (float) $item['total_honor'],
            ])
            ->all();

        $top5DetailByKegiatan = collect($petugasAlokasiDetail)
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();

        $top5DetailByHonor = collect($petugasAlokasiDetail)
            ->sortByDesc('total_honor')
            ->take(5)
            ->values()
            ->all();

        $top5Colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];

        $top5KegiatanRows = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $row = ['label' => $this->monthName($bulan)];
            foreach ($top5DetailByKegiatan as $index => $item) {
                $row['series_'.$index] = (int) ($item['bulan'][$bulan] ?? 0);
            }
            $top5KegiatanRows[] = $row;
        }

        $top5KegiatanSeries = collect($top5DetailByKegiatan)
            ->values()
            ->map(fn ($item, $index) => [
                'key' => 'series_'.$index,
                'label' => (string) $item['petugas_nama'],
                'color' => $top5Colors[$index % count($top5Colors)],
            ])
            ->all();

        $top5HonorRows = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $row = ['label' => $this->monthName($bulan)];
            foreach ($top5DetailByHonor as $index => $item) {
                $row['series_'.$index] = round(((float) ($item['honor'][$bulan] ?? 0)) / 1_000_000, 2);
            }
            $top5HonorRows[] = $row;
        }

        $top5HonorSeries = collect($top5DetailByHonor)
            ->values()
            ->map(fn ($item, $index) => [
                'key' => 'series_'.$index,
                'label' => (string) $item['petugas_nama'],
                'color' => $top5Colors[$index % count($top5Colors)],
            ])
            ->all();

        $top5KegiatanSvg = $this->buildLineChartSvg(
            $top5KegiatanRows,
            'label',
            $top5KegiatanSeries,
            'Top 5 Petugas Berdasarkan Alokasi Kegiatan (Per Bulan)',
            0,
        );

        $top5HonorSvg = $this->buildLineChartSvg(
            $top5HonorRows,
            'label',
            $top5HonorSeries,
            'Top 5 Petugas Berdasarkan Alokasi Honor (Juta Rupiah per Bulan)',
            2,
        );

        $kecamatanPieSvg = $this->buildPieChartSvg(
            array_map(fn ($item) => [
                'label' => (string) ($item['kecamatan'] ?? 'Belum Diisi'),
                'count' => (int) ($item['count'] ?? 0),
            ], $distribusiKecamatan),
            'label',
            'count',
            'Distribusi Kecamatan Petugas',
        );

        $usiaPieSvg = $this->buildPieChartSvg(
            array_map(fn ($item) => [
                'label' => (string) ($item['label'] ?? '-'),
                'count' => (int) ($item['count'] ?? 0),
            ], array_values(array_filter($distribusiUsia, fn ($item) => (int) ($item['count'] ?? 0) > 0))),
            'label',
            'count',
            'Distribusi Usia Petugas',
        );

        $pendidikanPieSvg = $this->buildPieChartSvg(
            array_map(fn ($item) => [
                'label' => (string) (($item['pendidikan'] ?? null) ?: 'Belum Diisi'),
                'count' => (int) ($item['count'] ?? 0),
            ], $distribusiPendidikan),
            'label',
            'count',
            'Distribusi Pendidikan Petugas',
        );

        $desaTugasPieData = collect($distribusiTugasDesaKelurahan)
            ->take(8)
            ->map(fn ($item) => [
                'label' => (string) $item['desa_kelurahan'],
                'count' => (int) $item['jumlah_petugas'],
            ])
            ->values();

        $sisaTugas = collect($distribusiTugasDesaKelurahan)
            ->slice(8)
            ->sum('jumlah_petugas');

        if ($sisaTugas > 0) {
            $desaTugasPieData->push([
                'label' => 'Lainnya',
                'count' => (int) $sisaTugas,
            ]);
        }

        $desaTugasPieSvg = $this->buildPieChartSvg(
            $desaTugasPieData->all(),
            'label',
            'count',
            'Distribusi Tugas per Desa/Kelurahan',
        );

        $petugasPieSvg = $this->buildPieChartSvg($distribusiJenisKelamin, 'label', 'count', 'Distribusi Jenis Kelamin Petugas');
        $petugasLineSvg = $this->buildLineChartSvg(
            array_map(fn ($item) => [
                'label' => $this->monthName((int) $item['bulan']),
                'jumlah_petugas' => (int) $item['jumlah_petugas'],
                'jumlah_kegiatan' => (int) $item['jumlah_kegiatan'],
            ], $alokasiPerBulan),
            'label',
            [
                ['key' => 'jumlah_petugas', 'label' => 'Jumlah Petugas', 'color' => '#3b82f6'],
                ['key' => 'jumlah_kegiatan', 'label' => 'Jumlah Kegiatan', 'color' => '#22c55e'],
            ],
            'Tren Alokasi Petugas dan Kegiatan',
            0,
        );

        $pdf = Pdf::loadView('analisis.petugas-pdf', [
            'distribusiJenisKelamin' => $distribusiJenisKelamin,
            'distribusiKecamatan' => $distribusiKecamatan,
            'distribusiTugasDesaKelurahan' => $distribusiTugasDesaKelurahan,
            'distribusiUsia' => $distribusiUsia,
            'distribusiPendidikan' => $distribusiPendidikan,
            'kecamatanPieSvg' => $kecamatanPieSvg,
            'usiaPieSvg' => $usiaPieSvg,
            'pendidikanPieSvg' => $pendidikanPieSvg,
            'desaTugasPieSvg' => $desaTugasPieSvg,
            'alokasiPerBulan' => $alokasiPerBulan,
            'petugasKegiatan' => $petugasKegiatanGrouped,
            'petugasAlokasiDetail' => $petugasAlokasiDetail,
            'top5ByKegiatan' => $top5ByKegiatan,
            'top5ByHonor' => $top5ByHonor,
            'top5KegiatanSvg' => $top5KegiatanSvg,
            'top5HonorSvg' => $top5HonorSvg,
            'top5DetailByKegiatan' => $top5DetailByKegiatan,
            'top5DetailByHonor' => $top5DetailByHonor,
            'petugasBelumDialokasikan' => $petugasBelumDialokasikan,
            'pieChartSvg' => $petugasPieSvg,
            'lineChartSvg' => $petugasLineSvg,
            'totalPetugas' => $petugasNonOrganik->count(),
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_petugas', $currentYear));
    }

    /**
     * Export Analisis Petugas Organik as PDF.
     */
    public function petugasOrganik(): HttpResponse
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

            $data = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $bulanFormatted)
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

        $pieChartSvg = $this->buildPieChartSvg($distribusiBebanKerja, 'label', 'count', 'Distribusi Beban Kerja Pegawai Organik');

        $lineChartSvg = $this->buildLineChartSvg(
            array_map(fn ($item) => [
                'label' => $this->monthName((int) $item['bulan']),
                'jumlah_petugas' => (int) $item['jumlah_petugas'],
                'jumlah_kegiatan' => (int) $item['jumlah_kegiatan'],
                'jumlah_alokasi' => (int) $item['jumlah_alokasi'],
            ], $trenBebanKerja),
            'label',
            [
                ['key' => 'jumlah_petugas', 'label' => 'Petugas Teralokasi', 'color' => '#3b82f6'],
                ['key' => 'jumlah_kegiatan', 'label' => 'Jumlah Kegiatan', 'color' => '#22c55e'],
                ['key' => 'jumlah_alokasi', 'label' => 'Jumlah Alokasi', 'color' => '#f59e0b'],
            ],
            'Tren Beban Kerja Organik per Bulan',
            0,
        );

        $pdf = Pdf::loadView('analisis.petugas-organik-pdf', [
            'ringkasan' => $ringkasan,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenBebanKerja' => $trenBebanKerja,
            'bebanKerjaDetail' => $bebanKerjaDetail,
            'pieChartSvg' => $pieChartSvg,
            'lineChartSvg' => $lineChartSvg,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_petugas_organik', $currentYear));
    }

    /**
     * Export Analisis Pulsa as PDF.
     */
    public function pulsa(): HttpResponse
    {
        $currentYear = (int) date('Y');

        // ── Pulsa per bulan ───────────────────────────────────────────────────
        $pulsaPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $data = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->whereIn('status', ['diterima'])
                ->selectRaw('COUNT(*) as total_pengajuan')
                ->selectRaw('COALESCE(SUM(nominal), 0) as total_nominal')
                ->selectRaw('COALESCE(SUM(nominal_disetujui), 0) as total_disetujui')
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
                ->first();

            $pulsaPerBulan[] = [
                'bulan' => $bulan,
                'total_pengajuan' => (int) $data->total_pengajuan,
                'total_nominal' => (float) $data->total_nominal,
                'total_disetujui' => (float) $data->total_disetujui,
                'jumlah_petugas' => (int) $data->jumlah_petugas,
            ];
        }

        // ── Rata-rata per petugas ─────────────────────────────────────────────
        $rataRataPulsa = PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->selectRaw('COALESCE(AVG(nominal_disetujui), 0) as rata_rata')
            ->value('rata_rata');

        // ── Alokasi per bulan (semua status) ──────────────────────────────────
        $alokasiPulsaPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $pulsaStats = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
                ->selectRaw('COUNT(DISTINCT kegiatan_id) as jumlah_kegiatan')
                ->selectRaw('COUNT(*) as diajukan')
                ->selectRaw("SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as disetujui")
                ->selectRaw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
                ->selectRaw("SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as menunggu")
                ->first();

            $alokasiPulsaPerBulan[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $pulsaStats->jumlah_petugas,
                'jumlah_kegiatan' => (int) $pulsaStats->jumlah_kegiatan,
                'diajukan' => (int) $pulsaStats->diajukan,
                'disetujui' => (int) $pulsaStats->disetujui,
                'ditolak' => (int) $pulsaStats->ditolak,
                'menunggu' => (int) $pulsaStats->menunggu,
            ];
        }

        // ── Distribusi jenis pulsa ────────────────────────────────────────────
        $distribusiJenisPulsa = PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->groupBy('jenis_pulsa')
            ->selectRaw('jenis_pulsa, COUNT(*) as count, COALESCE(SUM(nominal_disetujui), 0) as total')
            ->get()
            ->map(fn ($item) => [
                'jenis' => $item->jenis_pulsa === 'pelatihan' ? 'Pelatihan' : 'Pendataan',
                'count' => (int) $item->count,
                'total' => (float) $item->total,
            ])->all();

        // ── KPI summary ───────────────────────────────────────────────────────
        $totalNominal = collect($pulsaPerBulan)->sum('total_nominal');
        $totalDisetujui = collect($pulsaPerBulan)->sum('total_disetujui');
        $totalPengajuan = collect($pulsaPerBulan)->sum('total_pengajuan');
        $approvalRate = $totalNominal > 0 ? round(($totalDisetujui / $totalNominal) * 100) : 0;

        $pulsaPieSvg = $this->buildPieChartSvg($distribusiJenisPulsa, 'jenis', 'total', 'Komposisi Nominal Disetujui per Jenis Pulsa');
        $pulsaLineSvg = $this->buildLineChartSvg(
            array_map(fn ($item) => [
                'label' => $this->monthName((int) $item['bulan']),
                'total_disetujui_jt' => round(((float) $item['total_disetujui']) / 1_000_000, 2),
            ], $pulsaPerBulan),
            'label',
            [
                ['key' => 'total_disetujui_jt', 'label' => 'Nominal Disetujui (juta)', 'color' => '#22c55e'],
            ],
            'Tren Nominal Disetujui per Bulan',
            2,
        );

        $pdf = Pdf::loadView('analisis.pulsa-pdf', [
            'pulsaPerBulan' => $pulsaPerBulan,
            'rataRataPulsa' => round((float) $rataRataPulsa),
            'alokasiPulsaPerBulan' => $alokasiPulsaPerBulan,
            'distribusiJenisPulsa' => $distribusiJenisPulsa,
            'totalNominal' => $totalNominal,
            'totalDisetujui' => $totalDisetujui,
            'totalPengajuan' => $totalPengajuan,
            'approvalRate' => $approvalRate,
            'pieChartSvg' => $pulsaPieSvg,
            'lineChartSvg' => $pulsaLineSvg,
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_pulsa', $currentYear));
    }

    /**
     * Export Analisis Dokumen as PDF.
     */
    public function dokumen(): HttpResponse
    {
        $currentYear = (int) date('Y');

        // ── SK per bulan ──────────────────────────────────────────────────────
        $skPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $data = SkKpa::query()
                ->where('bulan', $bulan)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
                ->selectRaw("SUM(CASE WHEN status = 'diterbitkan' AND (is_signed = 0 OR is_signed IS NULL) THEN 1 ELSE 0 END) as diterbitkan")
                ->selectRaw('SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani')
                ->first();

            $skPerBulan[] = [
                'bulan' => $bulan,
                'total' => (int) $data->total,
                'draft' => (int) $data->draft,
                'diterbitkan' => (int) $data->diterbitkan,
                'ditandatangani' => (int) $data->ditandatangani,
            ];
        }

        // ── SPK per bulan ─────────────────────────────────────────────────────
        $spkPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $data = Spk::query()
                ->whereMonth('tanggal_spk', $bulan)
                ->whereYear('tanggal_spk', $currentYear)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
                ->selectRaw("SUM(CASE WHEN status = 'diterbitkan' THEN 1 ELSE 0 END) as diterbitkan")
                ->first();

            $spkPerBulan[] = [
                'bulan' => $bulan,
                'total' => (int) $data->total,
                'draft' => (int) $data->draft,
                'diterbitkan' => (int) $data->diterbitkan,
            ];
        }

        // ── Summary KPI ───────────────────────────────────────────────────────
        $skTotal = SkKpa::query()->where('tahun', $currentYear)->count();
        $skDiterbitkan = SkKpa::query()->where('tahun', $currentYear)->where('status', 'diterbitkan')->count();
        $skDraft = SkKpa::query()->where('tahun', $currentYear)->where('status', 'draft')->count();
        $spkTotal = Spk::query()->whereYear('tanggal_spk', $currentYear)->count();
        $spkDiterbitkan = Spk::query()->whereYear('tanggal_spk', $currentYear)->where('status', 'diterbitkan')->count();
        $spkDraft = Spk::query()->whereYear('tanggal_spk', $currentYear)->where('status', 'draft')->count();

        // ── SK progress per kegiatan ──────────────────────────────────────────
        $kegiatanAktif = Kegiatan::query()
            ->where('tahun_anggaran', $currentYear)
            ->whereNotIn('status', ['dibatalkan'])
            ->select('id', 'nama_kegiatan', 'kode_kegiatan', 'jenis_kegiatan')
            ->orderBy('nama_kegiatan')
            ->get();

        $skStatsByKegiatan = DB::table('sk_kpa')
            ->where('tahun', $currentYear)
            ->whereNull('deleted_at')
            ->selectRaw("kegiatan_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'diterbitkan' THEN 1 ELSE 0 END) as diterbitkan,
                SUM(CASE WHEN is_signed = 1 THEN 1 ELSE 0 END) as ditandatangani")
            ->groupBy('kegiatan_id')
            ->get()
            ->keyBy('kegiatan_id');

        $kelengkapanSKPerKegiatan = $kegiatanAktif->map(function ($kegiatan) use ($skStatsByKegiatan) {
            $sk = $skStatsByKegiatan->get($kegiatan->id);
            $total = $sk ? (int) $sk->total : 0;
            $draft = $sk ? (int) $sk->draft : 0;
            $diterbitkan = $sk ? (int) $sk->diterbitkan : 0;
            $ditandatangani = $sk ? (int) $sk->ditandatangani : 0;

            $statusDokumen = 'Belum Ada SK';
            if ($total > 0) {
                $statusDokumen = ($draft === 0) ? 'Diterbitkan' : 'Ada Draft';
            }

            return [
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'total_sk' => $total,
                'sk_draft' => $draft,
                'sk_diterbitkan' => $diterbitkan,
                'sk_ditandatangani' => $ditandatangani,
                'status_dokumen' => $statusDokumen,
            ];
        })->sortBy(function ($item) {
            return match ($item['status_dokumen']) {
                'Belum Ada SK' => 0,
                'Ada Draft' => 1,
                default => 2,
            };
        })->values()->all();

        // ── SK draft lama (> 14 hari) ─────────────────────────────────────────
        $skDraftLama = SkKpa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'draft')
            ->where('created_at', '<', now()->subDays(14))
            ->with('kegiatan:id,nama_kegiatan,kode_kegiatan')
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(function ($sk) {
                return [
                    'kegiatan_nama' => $sk->kegiatan?->nama_kegiatan ?? '-',
                    'kegiatan_kode' => $sk->kegiatan?->kode_kegiatan ?? '-',
                    'bulan' => (int) $sk->bulan,
                    'tahun' => (int) $sk->tahun,
                    'umur_hari' => (int) now()->diffInDays($sk->created_at),
                ];
            })
            ->all();

        $dokumenPieSvg = $this->buildPieChartSvg([
            ['label' => 'SK Draft', 'value' => $skDraft],
            ['label' => 'SK Diterbitkan', 'value' => $skDiterbitkan],
            ['label' => 'SPK Draft', 'value' => $spkDraft],
            ['label' => 'SPK Diterbitkan', 'value' => $spkDiterbitkan],
        ], 'label', 'value', 'Status Dokumen SK & SPK');

        $dokumenLineSvg = $this->buildLineChartSvg(
            array_map(function ($index) use ($skPerBulan, $spkPerBulan) {
                return [
                    'label' => $this->monthName($index + 1),
                    'sk_diterbitkan' => (int) (($skPerBulan[$index]['diterbitkan'] ?? 0) + ($skPerBulan[$index]['ditandatangani'] ?? 0)),
                    'spk_diterbitkan' => (int) ($spkPerBulan[$index]['diterbitkan'] ?? 0),
                ];
            }, array_keys($skPerBulan)),
            'label',
            [
                ['key' => 'sk_diterbitkan', 'label' => 'SK Diterbitkan', 'color' => '#22c55e'],
                ['key' => 'spk_diterbitkan', 'label' => 'SPK Diterbitkan', 'color' => '#3b82f6'],
            ],
            'Tren Dokumen Diterbitkan per Bulan',
            0,
        );

        $pdf = Pdf::loadView('analisis.dokumen-pdf', [
            'skPerBulan' => $skPerBulan,
            'spkPerBulan' => $spkPerBulan,
            'skTotal' => $skTotal,
            'skDiterbitkan' => $skDiterbitkan,
            'skDraft' => $skDraft,
            'spkTotal' => $spkTotal,
            'spkDiterbitkan' => $spkDiterbitkan,
            'spkDraft' => $spkDraft,
            'kelengkapanSKPerKegiatan' => $kelengkapanSKPerKegiatan,
            'skDraftLama' => $skDraftLama,
            'pieChartSvg' => $dokumenPieSvg,
            'lineChartSvg' => $dokumenLineSvg,
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_dokumen', $currentYear));
    }

    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildPieChartSvg(array $items, string $labelKey, string $valueKey, ?string $title = null): string
    {
        $fontFamily = 'DejaVu Sans, sans-serif';

        $filtered = array_values(array_filter($items, function ($item) use ($valueKey) {
            return (float) ($item[$valueKey] ?? 0) > 0;
        }));

        $titleElement = $title
            ? sprintf('<text x="380" y="24" font-size="13" fill="#0f172a" text-anchor="middle" font-weight="700">%s</text>', $this->escapeSvg($title))
            : '';

        if (count($filtered) === 0) {
            return sprintf(
                '<svg width="760" height="240" viewBox="0 0 760 240" xmlns="http://www.w3.org/2000/svg" style="font-family:%s"><rect width="100%%" height="100%%" fill="#ffffff" /><rect x="8" y="8" width="744" height="224" rx="10" fill="#f8fafc" stroke="#e2e8f0" />%s<text x="24" y="58" font-size="12" fill="#64748b">Tidak ada data untuk pie chart.</text></svg>',
                $fontFamily,
                $titleElement,
            );
        }

        $colors = ['#2563eb', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];
        $total = array_sum(array_map(fn ($item) => (float) $item[$valueKey], $filtered));

        $cx = 178;
        $cy = 126;
        $r = 78;
        $innerRadius = 40;
        $startAngle = -90.0;

        $paths = [];
        $legend = [];

        foreach ($filtered as $index => $item) {
            $value = (float) $item[$valueKey];
            $slice = ($value / $total) * 360;
            $endAngle = $startAngle + $slice;

            $x1 = $cx + $r * cos(deg2rad($startAngle));
            $y1 = $cy + $r * sin(deg2rad($startAngle));
            $x2 = $cx + $r * cos(deg2rad($endAngle));
            $y2 = $cy + $r * sin(deg2rad($endAngle));

            $largeArc = $slice > 180 ? 1 : 0;
            $color = $colors[$index % count($colors)];

            $paths[] = sprintf(
                '<path d="M %.2f %.2f L %.2f %.2f A %d %d 0 %d 1 %.2f %.2f Z" fill="%s" stroke="#ffffff" stroke-width="1" />',
                $cx,
                $cy,
                $x1,
                $y1,
                $r,
                $r,
                $largeArc,
                $x2,
                $y2,
                $color,
            );

            $label = $this->escapeSvg((string) ($item[$labelKey] ?? '-'));
            $percentage = round(($value / $total) * 100, 1);
            $legend[] = sprintf(
                '<rect x="322" y="%d" width="11" height="11" rx="2" fill="%s" /><text x="340" y="%d" font-size="10" fill="#0f172a">%s: %s (%.1f%%)</text>',
                54 + ($index * 17),
                $color,
                63 + ($index * 17),
                $label,
                number_format($value, 0, ',', '.'),
                $percentage,
            );

            $startAngle = $endAngle;
        }

        $centerSummary = sprintf(
            '<circle cx="%.2f" cy="%.2f" r="%d" fill="#ffffff" stroke="#e2e8f0" /><text x="%.2f" y="%.2f" font-size="9" fill="#64748b" text-anchor="middle">Total</text><text x="%.2f" y="%.2f" font-size="13" font-weight="700" fill="#0f172a" text-anchor="middle">%s</text>',
            $cx,
            $cy,
            $innerRadius,
            $cx,
            $cy - 4,
            $cx,
            $cy + 12,
            number_format($total, 0, ',', '.'),
        );

        return sprintf(
            '<svg width="760" height="240" viewBox="0 0 760 240" xmlns="http://www.w3.org/2000/svg" style="font-family:%s"><rect width="100%%" height="100%%" fill="#ffffff" /><rect x="8" y="8" width="744" height="224" rx="10" fill="#f8fafc" stroke="#e2e8f0" />%s%s%s%s</svg>',
            $fontFamily,
            $titleElement,
            implode('', $paths),
            $centerSummary,
            implode('', $legend),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{key: string, label: string, color: string}>  $series
     */
    private function buildLineChartSvg(array $rows, string $xKey, array $series, ?string $title = null, int $valueDecimals = 1): string
    {
        $fontFamily = 'DejaVu Sans, sans-serif';

        $titleElement = $title
            ? sprintf('<text x="380" y="24" font-size="13" fill="#0f172a" text-anchor="middle" font-weight="700">%s</text>', $this->escapeSvg($title))
            : '';

        if (count($rows) === 0 || count($series) === 0) {
            return sprintf(
                '<svg width="760" height="280" viewBox="0 0 760 280" xmlns="http://www.w3.org/2000/svg" style="font-family:%s"><rect width="100%%" height="100%%" fill="#ffffff" /><rect x="8" y="8" width="744" height="264" rx="10" fill="#f8fafc" stroke="#e2e8f0" />%s<text x="24" y="58" font-size="12" fill="#64748b">Tidak ada data untuk line chart.</text></svg>',
                $fontFamily,
                $titleElement,
            );
        }

        $width = 760;
        $height = 300;
        $left = 42;
        $right = 740;
        $top = $title ? 44 : 24;
        $bottom = 210;

        $maxValue = 0.0;
        foreach ($rows as $row) {
            foreach ($series as $s) {
                $maxValue = max($maxValue, (float) ($row[$s['key']] ?? 0));
            }
        }
        $maxValue = $maxValue > 0 ? $maxValue : 1;

        $grid = [];
        for ($i = 0; $i <= 4; $i++) {
            $y = $top + (($bottom - $top) * $i / 4);
            $value = $maxValue - (($maxValue * $i) / 4);
            $grid[] = sprintf('<line x1="%d" y1="%.2f" x2="%d" y2="%.2f" stroke="#dbe1ea" stroke-width="1" />', $left, $y, $right, $y);
            $grid[] = sprintf('<text x="2" y="%.2f" font-size="9" fill="#64748b">%s</text>', $y + 3, number_format($value, $valueDecimals, ',', '.'));
        }

        $axis = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1" />', $left, $bottom, $right, $bottom)
            .sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1" />', $left, $top, $left, $bottom);

        $count = count($rows);
        $stepX = $count > 1 ? ($right - $left) / ($count - 1) : 0;
        $labels = [];
        foreach ($rows as $index => $row) {
            $x = $left + ($index * $stepX);
            $labels[] = sprintf(
                '<text x="%.2f" y="225" font-size="9" fill="#6b7280" text-anchor="middle">%s</text>',
                $x,
                $this->escapeSvg((string) ($row[$xKey] ?? '')),
            );
        }

        $lines = [];
        foreach ($series as $seriesIndex => $s) {
            $points = [];
            $dots = [];
            $pointLabels = [];
            foreach ($rows as $index => $row) {
                $value = (float) ($row[$s['key']] ?? 0);
                $x = $left + ($index * $stepX);
                $y = $bottom - (($value / $maxValue) * ($bottom - $top));
                $points[] = ['x' => $x, 'y' => $y];
                $dots[] = sprintf('<circle cx="%.2f" cy="%.2f" r="2.4" fill="%s" />', $x, $y, $s['color']);

                if ($value <= 0) {
                    continue;
                }

                $valueText = number_format($value, $valueDecimals, ',', '.');
                $valueYOffset = ($seriesIndex % 2 === 0)
                    ? (-8 - ($seriesIndex * 2))
                    : (12 + ($seriesIndex * 2));
                $labelY = $y + $valueYOffset;

                if ($labelY < ($top + 8)) {
                    $labelY = $top + 8;
                }

                if ($labelY > ($bottom - 4)) {
                    $labelY = $bottom - 4;
                }

                $pointLabels[] = sprintf(
                    '<text x="%.2f" y="%.2f" font-size="8" fill="%s" text-anchor="middle">%s</text>',
                    $x,
                    $labelY,
                    $s['color'],
                    $valueText,
                );
            }

            $lineShape = count($points) > 1
                ? sprintf(
                    '<path d="%s" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />',
                    $this->buildSmoothSvgPath($points),
                    $s['color'],
                )
                : sprintf(
                    '<polyline fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="%s" />',
                    $s['color'],
                    implode(' ', array_map(fn ($point) => sprintf('%.2f,%.2f', $point['x'], $point['y']), $points)),
                );

            $lines[] = sprintf(
                '%s%s%s',
                $lineShape,
                implode('', $dots),
                implode('', $pointLabels),
            );
        }

        $legend = [];
        $legendColumns = 3;
        $legendStartY = 248;
        $legendRowSpacing = 16;
        foreach ($series as $index => $s) {
            $legendRow = intdiv($index, $legendColumns);
            $legendColumn = $index % $legendColumns;
            $legendX = 52 + ($legendColumn * 230);
            $legendY = $legendStartY + ($legendRow * $legendRowSpacing);
            $legend[] = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2" />', $legendX, $legendY, $legendX + 16, $legendY, $s['color']);
            $legend[] = sprintf('<text x="%d" y="%d" font-size="10" fill="#0f172a">%s</text>', $legendX + 22, $legendY + 3, $this->escapeSvg($s['label']));
        }

        return sprintf(
            '<svg width="760" height="280" viewBox="0 0 760 280" xmlns="http://www.w3.org/2000/svg" style="font-family:%s"><rect width="100%%" height="100%%" fill="#ffffff" /><rect x="8" y="8" width="744" height="264" rx="10" fill="#f8fafc" stroke="#e2e8f0" />%s%s%s%s%s%s</svg>',
            $fontFamily,
            $titleElement,
            implode('', $grid),
            $axis,
            implode('', $labels),
            implode('', $lines),
            implode('', $legend),
        );
    }

    /**
     * @param  array<int, array{x: float, y: float}>  $points
     */
    private function buildSmoothSvgPath(array $points): string
    {
        if (count($points) === 0) {
            return '';
        }

        if (count($points) === 1) {
            return sprintf('M %.2f %.2f', $points[0]['x'], $points[0]['y']);
        }

        $path = sprintf('M %.2f %.2f', $points[0]['x'], $points[0]['y']);
        $tension = 0.18;
        $lastIndex = count($points) - 1;

        for ($index = 0; $index < $lastIndex; $index++) {
            $previous = $points[max(0, $index - 1)];
            $current = $points[$index];
            $next = $points[$index + 1];
            $afterNext = $points[min($lastIndex, $index + 2)];

            $controlPointOneX = $current['x'] + (($next['x'] - $previous['x']) * $tension);
            $controlPointOneY = $current['y'] + (($next['y'] - $previous['y']) * $tension);
            $controlPointTwoX = $next['x'] - (($afterNext['x'] - $current['x']) * $tension);
            $controlPointTwoY = $next['y'] - (($afterNext['y'] - $current['y']) * $tension);

            $path .= sprintf(
                ' C %.2f %.2f, %.2f %.2f, %.2f %.2f',
                $controlPointOneX,
                $controlPointOneY,
                $controlPointTwoX,
                $controlPointTwoY,
                $next['x'],
                $next['y'],
            );
        }

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildHorizontalBarChartSvg(array $items, string $labelKey, string $valueKey, string $barColor, bool $showInMillions = false): string
    {
        $filtered = array_values(array_filter($items, function ($item) use ($valueKey) {
            return (float) ($item[$valueKey] ?? 0) > 0;
        }));

        if (count($filtered) === 0) {
            return '<svg width="760" height="240" viewBox="0 0 760 240" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#ffffff" /><text x="24" y="40" font-size="12" fill="#6b7280">Tidak ada data untuk grafik.</text></svg>';
        }

        $width = 760;
        $height = 280;
        $left = 220;
        $right = 730;
        $top = 20;
        $rowHeight = 44;
        $barHeight = 18;
        $maxValue = max(array_map(fn ($item) => (float) $item[$valueKey], $filtered));
        $maxValue = $maxValue > 0 ? $maxValue : 1;

        $elements = [];

        foreach ($filtered as $index => $item) {
            $y = $top + ($index * $rowHeight);
            $value = (float) ($item[$valueKey] ?? 0);
            $barWidth = (($right - $left) * $value) / $maxValue;
            $label = $this->escapeSvg((string) ($item[$labelKey] ?? '-'));
            $displayValue = $showInMillions
                ? number_format($value / 1_000_000, 2, ',', '.').' jt'
                : number_format($value, 0, ',', '.');

            $elements[] = sprintf('<text x="12" y="%d" font-size="10" fill="#111827">%s</text>', $y + 14, $label);
            $elements[] = sprintf(
                '<rect x="%d" y="%d" width="%.2f" height="%d" rx="4" fill="%s" />',
                $left,
                $y,
                $barWidth,
                $barHeight,
                $barColor,
            );
            $elements[] = sprintf(
                '<text x="%.2f" y="%d" font-size="10" fill="#111827">%s</text>',
                $left + $barWidth + 8,
                $y + 13,
                $displayValue,
            );
        }

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg"><rect width="100%%" height="100%%" fill="#ffffff" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            implode('', $elements),
        );
    }

    private function monthName(int $month): string
    {
        $names = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $names[$month] ?? '-';
    }

    private function nonZeroHonorClause(): string
    {
        return '(COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0))';
    }

    private function escapeSvg(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function filename(string $prefix, int $year): string
    {
        return sprintf(
            '%s_%s_%s_%s.pdf',
            $prefix,
            $year,
            now()->format('Ymd_His'),
            Str::lower(Str::random(6)),
        );
    }
}
