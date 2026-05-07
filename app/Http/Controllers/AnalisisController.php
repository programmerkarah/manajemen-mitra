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
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $jumlahPetugas = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $bulanFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->nonZeroHonorClause().' > 0');
            $this->applyEffectivePeriode($jumlahPetugas);
            $jumlahPetugas = $jumlahPetugas->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            $jumlahKegiatan = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $bulanFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->nonZeroHonorClause().' > 0');
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
            ->whereRaw($this->nonZeroHonorClause().' > 0');
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
            ->whereRaw($this->nonZeroHonorClause().' > 0');
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
        $petugasAlokasiRaw = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->nonZeroHonorClause().' > 0');
        $this->applyEffectivePeriode($petugasAlokasiRaw);
        $petugasAlokasiRaw = $petugasAlokasiRaw->select(
            'petugas.id as petugas_id',
            'petugas.nama as petugas_nama',
            'periode_alokasi.bulan',
        )
            ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as jumlah_kegiatan')
            ->selectRaw('COALESCE(SUM(alokasi_petugas.total_honor), 0) + COALESCE(SUM(alokasi_petugas.total_honor_listing), 0) as total_honor')
            ->groupBy('petugas.id', 'petugas.nama', 'periode_alokasi.bulan')
            ->get();

        $petugasAlokasiDetail = $petugasAlokasiRaw->groupBy('petugas_id')->map(function ($items) {
            $first = $items->first();
            $bulanData = [];
            $honorData = [];
            for ($b = 1; $b <= 12; $b++) {
                $found = $items->firstWhere('bulan', str_pad($b, 2, '0', STR_PAD_LEFT));
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
            ->whereRaw($this->nonZeroHonorClause().' > 0')
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
            ->select('id', 'nama', 'kecamatan', 'jenis_kelamin')
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'kecamatan' => $p->kecamatan,
                'jenis_kelamin' => $p->jenis_kelamin,
            ])
            ->values()
            ->all();

        return Inertia::render('Analisis/Petugas', [
            'distribusiJenisKelamin' => $distribusiJenisKelamin,
            'distribusiKecamatan' => $distribusiKecamatan,
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
     * Analisis Kebutuhan dan Pengadaan Pulsa.
     */
    public function pulsa(): Response
    {
        $currentYear = (int) date('Y');

        // Distribusi Alokasi Pulsa per Bulan
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
                'rata_rata_per_petugas' => $data->jumlah_petugas > 0
                    ? round($data->total_disetujui / $data->jumlah_petugas)
                    : 0,
            ];
        }

        // Rata-rata penggunaan pulsa per petugas per bulan (all statuses)
        $rataRataPulsa = PengajuanPulsa::query()
            ->where('tahun', $currentYear)
            ->where('status', 'diterima')
            ->selectRaw('COALESCE(AVG(nominal_disetujui), 0) as rata_rata')
            ->value('rata_rata');

        // Tabel alokasi petugas per bulan dan jumlah kegiatan (pulsa context)
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

        // Distribusi per jenis pulsa
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

        // SK per bulan
        $skPerBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $data = SkKpa::query()
                ->where('bulan', $bulan)
                ->where('tahun', $currentYear)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'draft' AND (is_signed = 0 OR is_signed IS NULL) THEN 1 ELSE 0 END) as draft")
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

        // SPK per bulan (based on tanggal_spk)
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

        // Summary totals
        $skTotal = SkKpa::query()->where('tahun', $currentYear)->count();
        $skDiterbitkan = SkKpa::query()->where('tahun', $currentYear)->where('status', 'diterbitkan')->count();
        $spkTotal = Spk::query()->whereYear('tanggal_spk', $currentYear)->count();
        $spkDiterbitkan = Spk::query()->whereYear('tanggal_spk', $currentYear)->where('status', 'diterbitkan')->count();

        return Inertia::render('Analisis/Dokumen', [
            'skPerBulan' => $skPerBulan,
            'spkPerBulan' => $spkPerBulan,
            'skTotal' => $skTotal,
            'skDiterbitkan' => $skDiterbitkan,
            'spkTotal' => $spkTotal,
            'spkDiterbitkan' => $spkDiterbitkan,
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Analisis Umum/Lainnya.
     */
    public function umum(): Response
    {
        $currentYear = (int) date('Y');

        // Utilisasi Anggaran per Kegiatan
        $utilisasiAnggaran = Kegiatan::query()
            ->where('tahun_anggaran', $currentYear)
            ->whereNotIn('status', ['dibatalkan'])
            ->get()
            ->map(function ($kegiatan) use ($currentYear) {
                $totalPagu = ($kegiatan->pagu_pencacahan ?? 0) + ($kegiatan->pagu_listing ?? 0);

                $totalHonorQuery = DB::table('alokasi_petugas')
                    ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                    ->where('periode_alokasi.kegiatan_id', $kegiatan->id)
                    ->where('periode_alokasi.tahun', $currentYear)
                    ->whereRaw($this->nonZeroHonorClause().' > 0');

                $this->applyEffectivePeriode($totalHonorQuery);

                $totalHonor = $totalHonorQuery
                    ->selectRaw('COALESCE(SUM(alokasi_petugas.total_honor), 0) + COALESCE(SUM(alokasi_petugas.total_honor_listing), 0) as total')
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
            ->where('periode_alokasi.tahun', $currentYear)
            ->where('petugas.jenis_petugas', 'non-organik')
            ->whereRaw($this->nonZeroHonorClause().' > 0');
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

            $data = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $bulanFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->nonZeroHonorClause().' > 0');
            $this->applyEffectivePeriode($data);
            $data = $data
                ->selectRaw('COUNT(DISTINCT alokasi_petugas.petugas_id) as jumlah_petugas')
                ->selectRaw('COALESCE(SUM(alokasi_petugas.total_honor), 0) + COALESCE(SUM(alokasi_petugas.total_honor_listing), 0) as total_honor')
                ->selectRaw('COUNT(DISTINCT periode_alokasi.kegiatan_id) as total_kegiatan')
                ->first();

            $trenAlokasi[] = [
                'bulan' => $bulan,
                'jumlah_petugas' => (int) $data->jumlah_petugas,
                'total_honor' => (float) $data->total_honor,
                'total_kegiatan' => (int) $data->total_kegiatan,
            ];
        }

        return Inertia::render('Analisis/Umum', [
            'utilisasiAnggaran' => $utilisasiAnggaran,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenAlokasi' => $trenAlokasi,
            'currentYear' => $currentYear,
        ]);
    }

    private function nonZeroHonorClause(): string
    {
        return '(COALESCE(alokasi_petugas.total_honor, 0) + COALESCE(alokasi_petugas.total_honor_listing, 0))';
    }
}
