<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\Petugas;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Traits\EffectivePeriodeScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalisisController extends Controller
{
    use EffectivePeriodeScope;

    /**
     * Analisis Petugas Non-Organik.
     */
    public function petugas(): Response
    {
        $currentYear = (int) date('Y');

        $petugasNonOrganik = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->get();

        // Distribusi Jenis Kelamin
        $distribusiJenisKelamin = $petugasNonOrganik->groupBy('jenis_kelamin')
            ->map(fn ($group, $key) => [
                'label' => match ($key) {
                    'laki-laki' => 'Laki-laki',
                    'perempuan' => 'Perempuan',
                    default => 'Belum Diisi',
                },
                'value' => $key ?: 'belum_diisi',
                'count' => $group->count(),
            ])->values()->all();

        // Distribusi Kecamatan
        $distribusiKecamatan = $petugasNonOrganik->groupBy(fn ($p) => $p->kecamatan ?: 'Belum Diisi')
            ->map(fn ($group, $key) => [
                'kecamatan' => $key,
                'count' => $group->count(),
            ])->sortByDesc('count')->values()->all();

        // Distribusi Desa/Kelurahan
        $distribusiDesaKelurahan = $petugasNonOrganik->groupBy(fn ($p) => $p->desa_kelurahan ?: 'Belum Diisi')
            ->map(fn ($group, $key) => [
                'desa_kelurahan' => $key,
                'count' => $group->count(),
            ])->sortByDesc('count')->values()->all();

        // Distribusi petugas per Kecamatan & Desa/Kelurahan
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

        // Distribusi Usia
        $distribusiUsia = [];
        $usiaRanges = [
            ['label' => '< 20', 'min' => 0, 'max' => 19],
            ['label' => '20-29', 'min' => 20, 'max' => 29],
            ['label' => '30-39', 'min' => 30, 'max' => 39],
            ['label' => '40-49', 'min' => 40, 'max' => 49],
            ['label' => '50-59', 'min' => 50, 'max' => 59],
            ['label' => '≥ 60', 'min' => 60, 'max' => 200],
        ];

        foreach ($usiaRanges as $range) {
            $count = $petugasNonOrganik->filter(function ($p) use ($range) {
                if (! $p->tanggal_lahir) {
                    return false;
                }
                $usia = Carbon::parse($p->tanggal_lahir)->age;

                return $usia >= $range['min'] && $usia <= $range['max'];
            })->count();

            $distribusiUsia[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        $belumDiisiUsia = $petugasNonOrganik->whereNull('tanggal_lahir')->count();
        if ($belumDiisiUsia > 0) {
            $distribusiUsia[] = ['label' => 'Belum Diisi', 'count' => $belumDiisiUsia];
        }

        // Distribusi Pendidikan
        $distribusiPendidikan = $petugasNonOrganik->groupBy('pendidikan')
            ->map(fn ($group, $key) => [
                'pendidikan' => $key,
                'count' => $group->count(),
            ])->sortByDesc('count')->values()->all();

        // Tabel Alokasi Petugas per Bulan
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
            $jumlahPetugas = $jumlahPetugas->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            $jumlahKegiatan = DB::table('periode_alokasi')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear);
            $this->applySensusEkonomiMonthFilter($jumlahKegiatan, $bulan, 'kegiatan');
            $this->applyEffectivePeriode($jumlahKegiatan);
            $jumlahKegiatan = $jumlahKegiatan->distinct('periode_alokasi.kegiatan_id')
                ->count('periode_alokasi.kegiatan_id');

            $alokasiPerBulan[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => $jumlahPetugas,
                'jumlah_kegiatan' => $jumlahKegiatan,
            ];
        }

        // Petugas-Kegiatan mapping (Venn diagram data)
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

        // Kegiatan list for filter (effective periode + alokasi non-zero)
        $kegiatanList = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereNotIn('kegiatan.status', ['dibatalkan'])
            ->whereRaw($this->allocationOrHonorExistsClause());
        $this->applyEffectivePeriode($kegiatanList);
        $kegiatanList = $kegiatanList
            ->select(
                'kegiatan.id',
                'kegiatan.nama_kegiatan',
                'kegiatan.kode_kegiatan',
            )
            ->distinct()
            ->orderBy('kegiatan.nama_kegiatan')
            ->get();

        // Per-petugas monthly allocation detail
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

        // Petugas list for filter
        $petugasList = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->where('status', 'aktif')
            ->whereNull('deleted_at')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama])
            ->values()
            ->all();

        // Petugas Rutin: kegiatan yang sama muncul di >= 2 bulan berbeda untuk petugas yang sama
        $petugasRutinRaw = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->allocationOrHonorExistsClause())
            ->whereRaw('TIMESTAMPDIFF(MONTH, kegiatan.tanggal_mulai, kegiatan.tanggal_selesai) > 2');
        $this->applyEffectivePeriode($petugasRutinRaw);
        $petugasRutinRaw = $petugasRutinRaw
            ->select(
                'petugas.id as petugas_id',
                'petugas.nama as petugas_nama',
                'kegiatan.id as kegiatan_id',
                'kegiatan.nama_kegiatan',
                'kegiatan.kode_kegiatan',
                'periode_alokasi.bulan',
            )
            ->distinct()
            ->get();

        $petugasRutin = $petugasRutinRaw
            ->groupBy('petugas_id')
            ->map(function ($items) {
                $first = $items->first();
                $kegiatanRutin = $items
                    ->groupBy('kegiatan_id')
                    ->filter(fn ($kegItems) => $kegItems->count() >= 2)
                    ->map(fn ($kegItems) => [
                        'kegiatan_id' => $kegItems->first()->kegiatan_id,
                        'nama_kegiatan' => $kegItems->first()->nama_kegiatan,
                        'kode_kegiatan' => $kegItems->first()->kode_kegiatan,
                        'jumlah_bulan' => $kegItems->count(),
                        'bulan_list' => $kegItems->pluck('bulan')->sort()->values()->all(),
                    ])
                    ->sortByDesc('jumlah_bulan')
                    ->values()
                    ->all();

                if (empty($kegiatanRutin)) {
                    return null;
                }

                return [
                    'petugas_id' => $first->petugas_id,
                    'petugas_nama' => $first->petugas_nama,
                    'jumlah_kegiatan_rutin' => count($kegiatanRutin),
                    'kegiatan_rutin' => $kegiatanRutin,
                ];
            })
            ->filter(fn ($p) => $p !== null)
            ->sortByDesc('jumlah_kegiatan_rutin')
            ->values()
            ->all();

        // Petugas yang belum pernah dialokasikan (tidak ada entri di alokasi_petugas sama sekali)
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

        return Inertia::render('Analisis/Petugas', [
            'distribusiJenisKelamin' => $distribusiJenisKelamin,
            'distribusiKecamatan' => $distribusiKecamatan,
            'distribusiDesaKelurahan' => $distribusiDesaKelurahan,
            'distribusiTugasDesaKelurahan' => $distribusiTugasDesaKelurahan,
            'distribusiUsia' => $distribusiUsia,
            'distribusiPendidikan' => $distribusiPendidikan,
            'alokasiPerBulan' => $alokasiPerBulan,
            'petugasKegiatan' => $petugasKegiatanGrouped,
            'kegiatanList' => $kegiatanList,
            'petugasAlokasiDetail' => $petugasAlokasiDetail,
            'petugasList' => $petugasList,
            'petugasBelumDialokasikan' => $petugasBelumDialokasikan,
            'petugasRutin' => $petugasRutin,
            'totalPetugas' => $petugasNonOrganik->count(),
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Analisis Beban Kerja Petugas Organik.
     */
    public function petugasOrganik(): Response
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

    /**
     * Analisis Kebutuhan dan Pengadaan Pulsa.
     */
    public function pulsa(): Response
    {
        $currentYear = (int) date('Y');

        $pulsaPerBulan = [];
        $alokasiPulsaPerBulan = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $acceptedPulsaQuery = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->where('status', 'diterima');

            $acceptedPulsaStats = (clone $acceptedPulsaQuery)
                ->selectRaw('COUNT(*) as total_pengajuan')
                ->selectRaw('COALESCE(SUM(nominal), 0) as total_nominal')
                ->selectRaw('COALESCE(SUM(nominal_disetujui), 0) as total_disetujui')
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
                ->first();

            $pulsaPerBulan[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $acceptedPulsaStats->jumlah_petugas,
                'total_pengajuan' => (int) ($acceptedPulsaStats->total_pengajuan ?? 0),
                'total_nominal' => (float) ($acceptedPulsaStats->total_nominal ?? 0),
                'total_disetujui' => (float) ($acceptedPulsaStats->total_disetujui ?? 0),
                'rata_rata_per_petugas' => $acceptedPulsaStats->jumlah_petugas > 0
                    ? round(((float) ($acceptedPulsaStats->total_disetujui ?? 0)) / (int) $acceptedPulsaStats->jumlah_petugas)
                    : 0,
            ];

            $pulsaStats = PengajuanPulsa::query()
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(*) as jumlah_kegiatan')
                ->selectRaw("SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as diajukan")
                ->selectRaw("SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as disetujui")
                ->selectRaw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
                ->selectRaw("SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as menunggu")
                ->selectRaw('COUNT(DISTINCT petugas_id) as jumlah_petugas')
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

        $rataRataPulsa = (float) (PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->avg('nominal_disetujui') ?? 0);

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

        return Inertia::render('Analisis/Pulsa', [
            'pulsaPerBulan' => $pulsaPerBulan,
            'rataRataPulsa' => round((float) $rataRataPulsa),
            'alokasiPulsaPerBulan' => $alokasiPulsaPerBulan,
            'distribusiJenisPulsa' => $distribusiJenisPulsa,
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Analisis Dokumen SK dan Perjanjian Kerja.
     */
    public function dokumen(): Response
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

            $statusDokumen = 'belum';
            if ($total > 0) {
                $statusDokumen = ($draft === 0 && $total > 0) ? 'lengkap' : 'sebagian';
            }

            return [
                'kegiatan_id' => $kegiatan->id,
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
                'belum' => 0,
                'sebagian' => 1,
                'lengkap' => 2,
            };
        })->values()->all();

        // ── SK draft yang sudah lama (> 14 hari) ─────────────────────────────
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
                    'id' => $sk->id,
                    'kegiatan_nama' => $sk->kegiatan?->nama_kegiatan ?? '-',
                    'kegiatan_kode' => $sk->kegiatan?->kode_kegiatan ?? '-',
                    'bulan' => (int) $sk->bulan,
                    'tahun' => (int) $sk->tahun,
                    'umur_hari' => (int) now()->diffInDays($sk->created_at),
                ];
            })
            ->all();

        return Inertia::render('Analisis/Dokumen', [
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
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Analisis Umum/Lainnya.
     */
    public function umum(): Response
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

    private function calculateSensusWeightedHonor(int $bulan, float|int $baseHonor, ?string $jenisKegiatan): float
    {
        if ($jenisKegiatan !== 'sensus') {
            return (float) $baseHonor;
        }

        return $this->sensusEkonomiHonorWeight($bulan) * (float) $baseHonor;
    }

    private function nonZeroHonorClause(): string
    {
        return '(
            CASE
                WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL
                    THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0)
                ELSE COALESCE(alokasi_petugas.total_honor, 0)
            END
            + CASE
                WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL
                    THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0)
                ELSE COALESCE(alokasi_petugas.total_honor_listing, 0)
            END
        )';
    }

    private function allocationOrHonorExistsClause(): string
    {
        return '(
            COALESCE(alokasi_petugas.jumlah_satuan, 0) > 0
            OR COALESCE(alokasi_petugas.jumlah_satuan_listing, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor, 0) > 0
            OR COALESCE(alokasi_petugas.total_honor_listing, 0) > 0
            OR COALESCE(alokasi_petugas.estimasi_honor_partial, 0) > 0
            OR COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) > 0
            OR (
                CAST(periode_alokasi.bulan AS UNSIGNED) IN (6, 7, 8)
                AND COALESCE(kegiatan.jenis_kegiatan, \'\') = \'sensus\'
                AND LOWER(COALESCE(kegiatan.nama_kegiatan, \'\')) LIKE \'%sensus ekonomi%\'
            )
        )';
    }

    /**
     * @return array<int, string>
     */
    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }
}
