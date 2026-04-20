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

        $umumPieSvg = $this->buildPieChartSvg($distribusiBebanKerja, 'label', 'count');
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
        );

        $pdf = Pdf::loadView('analisis.umum-pdf', [
            'utilisasiAnggaran' => $utilisasiAnggaran,
            'distribusiBebanKerja' => $distribusiBebanKerja,
            'trenAlokasi' => $trenAlokasi,
            'pieChartSvg' => $umumPieSvg,
            'lineChartSvg' => $umumLineSvg,
            'currentYear' => $currentYear,
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
            $jumlahPetugas = $jumlahPetugas
                ->distinct('alokasi_petugas.petugas_id')
                ->count('alokasi_petugas.petugas_id');

            $jumlahKegiatan = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.bulan', $bulanFormatted)
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->nonZeroHonorClause().' > 0');
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

        $petugasPieSvg = $this->buildPieChartSvg($distribusiJenisKelamin, 'label', 'count');
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
        );

        $pdf = Pdf::loadView('analisis.petugas-pdf', [
            'distribusiJenisKelamin' => $distribusiJenisKelamin,
            'distribusiKecamatan' => $distribusiKecamatan,
            'distribusiUsia' => $distribusiUsia,
            'distribusiPendidikan' => $distribusiPendidikan,
            'alokasiPerBulan' => $alokasiPerBulan,
            'pieChartSvg' => $petugasPieSvg,
            'lineChartSvg' => $petugasLineSvg,
            'totalPetugas' => $petugasNonOrganik->count(),
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_petugas', $currentYear));
    }

    /**
     * Export Analisis Pulsa as PDF.
     */
    public function pulsa(): HttpResponse
    {
        $currentYear = (int) date('Y');

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

        $pulsaPieSvg = $this->buildPieChartSvg($distribusiJenisPulsa, 'jenis', 'total');
        $pulsaLineSvg = $this->buildLineChartSvg(
            array_map(fn ($item) => [
                'label' => $this->monthName((int) $item['bulan']),
                'total_disetujui_jt' => round(((float) $item['total_disetujui']) / 1_000_000, 2),
            ], $pulsaPerBulan),
            'label',
            [
                ['key' => 'total_disetujui_jt', 'label' => 'Nominal Disetujui (juta)', 'color' => '#22c55e'],
            ],
        );

        $pdf = Pdf::loadView('analisis.pulsa-pdf', [
            'pulsaPerBulan' => $pulsaPerBulan,
            'distribusiJenisPulsa' => $distribusiJenisPulsa,
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

        $skTotal = SkKpa::query()->where('tahun', $currentYear)->count();
        $spkTotal = Spk::query()->whereYear('tanggal_spk', $currentYear)->count();

        $dokumenPieSvg = $this->buildPieChartSvg([
            ['label' => 'SK Draft', 'value' => collect($skPerBulan)->sum('draft')],
            ['label' => 'SK Diterbitkan', 'value' => collect($skPerBulan)->sum('diterbitkan')],
            ['label' => 'SK Ditandatangani', 'value' => collect($skPerBulan)->sum('ditandatangani')],
        ], 'label', 'value');

        $dokumenLineSvg = $this->buildLineChartSvg(
            array_map(function ($index) use ($skPerBulan, $spkPerBulan) {
                return [
                    'label' => $this->monthName($index + 1),
                    'sk_total' => (int) ($skPerBulan[$index]['total'] ?? 0),
                    'spk_total' => (int) ($spkPerBulan[$index]['total'] ?? 0),
                ];
            }, array_keys($skPerBulan)),
            'label',
            [
                ['key' => 'sk_total', 'label' => 'Total SK', 'color' => '#3b82f6'],
                ['key' => 'spk_total', 'label' => 'Total SPK', 'color' => '#f59e0b'],
            ],
        );

        $pdf = Pdf::loadView('analisis.dokumen-pdf', [
            'skPerBulan' => $skPerBulan,
            'spkPerBulan' => $spkPerBulan,
            'skTotal' => $skTotal,
            'spkTotal' => $spkTotal,
            'pieChartSvg' => $dokumenPieSvg,
            'lineChartSvg' => $dokumenLineSvg,
            'currentYear' => $currentYear,
            'tanggalCetak' => now()->timezone(config('app.timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename('analisis_dokumen', $currentYear));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildPieChartSvg(array $items, string $labelKey, string $valueKey): string
    {
        $filtered = array_values(array_filter($items, function ($item) use ($valueKey) {
            return (float) ($item[$valueKey] ?? 0) > 0;
        }));

        if (count($filtered) === 0) {
            return '<p>Tidak ada data untuk pie chart.</p>';
        }

        $colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];
        $total = array_sum(array_map(fn ($item) => (float) $item[$valueKey], $filtered));

        $cx = 120;
        $cy = 110;
        $r = 70;
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
                '<path d="M %.2f %.2f L %.2f %.2f A %d %d 0 %d 1 %.2f %.2f Z" fill="%s" />',
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
                '<rect x="260" y="%d" width="10" height="10" fill="%s" /><text x="276" y="%d" font-size="10" fill="#111827">%s: %s (%.1f%%)</text>',
                30 + ($index * 16),
                $color,
                39 + ($index * 16),
                $label,
                number_format($value, 1, ',', '.'),
                $percentage,
            );

            $startAngle = $endAngle;
        }

        return sprintf(
            '<svg width="760" height="230" viewBox="0 0 760 230" xmlns="http://www.w3.org/2000/svg"><rect width="100%%" height="100%%" fill="#ffffff" />%s%s</svg>',
            implode('', $paths),
            implode('', $legend),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{key: string, label: string, color: string}>  $series
     */
    private function buildLineChartSvg(array $rows, string $xKey, array $series): string
    {
        if (count($rows) === 0 || count($series) === 0) {
            return '<p>Tidak ada data untuk line chart.</p>';
        }

        $width = 760;
        $height = 260;
        $left = 42;
        $right = 740;
        $top = 20;
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
            $grid[] = sprintf('<line x1="%d" y1="%.2f" x2="%d" y2="%.2f" stroke="#e5e7eb" stroke-width="1" />', $left, $y, $right, $y);
            $grid[] = sprintf('<text x="2" y="%.2f" font-size="9" fill="#6b7280">%s</text>', $y + 3, number_format($value, 1, ',', '.'));
        }

        $axis = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#9ca3af" stroke-width="1" />', $left, $bottom, $right, $bottom)
            .sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#9ca3af" stroke-width="1" />', $left, $top, $left, $bottom);

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
        foreach ($series as $s) {
            $points = [];
            $dots = [];
            foreach ($rows as $index => $row) {
                $value = (float) ($row[$s['key']] ?? 0);
                $x = $left + ($index * $stepX);
                $y = $bottom - (($value / $maxValue) * ($bottom - $top));
                $points[] = sprintf('%.2f,%.2f', $x, $y);
                $dots[] = sprintf('<circle cx="%.2f" cy="%.2f" r="2.4" fill="%s" />', $x, $y, $s['color']);
            }

            $lines[] = sprintf(
                '<polyline fill="none" stroke="%s" stroke-width="2" points="%s" />%s',
                $s['color'],
                implode(' ', $points),
                implode('', $dots),
            );
        }

        $legend = [];
        foreach ($series as $index => $s) {
            $legendX = 52 + ($index * 190);
            $legend[] = sprintf('<line x1="%d" y1="244" x2="%d" y2="244" stroke="%s" stroke-width="2" />', $legendX, $legendX + 16, $s['color']);
            $legend[] = sprintf('<text x="%d" y="247" font-size="10" fill="#111827">%s</text>', $legendX + 22, $this->escapeSvg($s['label']));
        }

        return sprintf(
            '<svg width="760" height="260" viewBox="0 0 760 260" xmlns="http://www.w3.org/2000/svg"><rect width="100%%" height="100%%" fill="#ffffff" />%s%s%s%s%s</svg>',
            implode('', $grid),
            $axis,
            implode('', $labels),
            implode('', $lines),
            implode('', $legend),
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
