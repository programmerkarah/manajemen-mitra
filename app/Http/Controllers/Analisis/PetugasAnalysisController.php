<?php

namespace App\Http\Controllers\Analisis;

use App\Http\Controllers\Analisis\Concerns\BuildsAnalisisQueries;
use App\Http\Controllers\Controller;
use App\Models\Petugas;
use App\Traits\EffectivePeriodeScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PetugasAnalysisController extends Controller
{
    use BuildsAnalisisQueries;
    use EffectivePeriodeScope;

    public function __invoke(): Response
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
            $jumlahPetugasIds = $jumlahPetugas
                ->distinct()
                ->pluck('alokasi_petugas.petugas_id')
                ->map(fn ($id) => (int) $id);

            $jumlahPetugas = $jumlahPetugasIds
                ->unique()
                ->count();

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

        $petugasKegiatan = $petugasKegiatan
            ->unique(fn ($item) => $item->petugas_id.'|'.$item->kegiatan_id)
            ->values();

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
                $found = $items->where('bulan', $b);
                $bulanData[$b] = $found->sum(fn ($row) => (int) $row->jumlah_kegiatan);
                $honorData[$b] = $found->sum(fn ($row) => (float) $row->total_honor);
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
}
