<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DashboardInsightService
{
    /**
     * Build natural-language insight bullets for workload inequality.
     *
     * @param  Collection<int, array<string, mixed>>  $workloadMonthsWithData
     * @return array<int, string>
     */
    public function buildWorkloadInsights(
        Collection $workloadMonthsWithData,
        int $currentMonth,
        float $avgCv,
        float $avgAvgKegiatan,
        int $totalNonOrganikAktif,
        int $rekomendasiMin,
        int $rekomendasiMax,
        float $utilizationRate,
    ): array {
        $insights = [];
        $monthsWithData = $workloadMonthsWithData->count();

        if ($monthsWithData < $currentMonth) {
            $insights[] = "Data beban kerja tersedia di {$monthsWithData} dari {$currentMonth} bulan berjalan.";
        } else {
            $insights[] = "Data beban kerja tersedia di semua {$currentMonth} bulan berjalan.";
        }

        $fmtAvg = number_format($avgAvgKegiatan, 1, ',', '.');
        $trendText = '';

        if ($workloadMonthsWithData->count() >= 2) {
            $firstAvg = (float) $workloadMonthsWithData->first()['avg_kegiatan'];
            $lastAvg = (float) $workloadMonthsWithData->last()['avg_kegiatan'];

            if ($firstAvg > 0) {
                $trendPct = (($lastAvg - $firstAvg) / $firstAvg) * 100;

                if (abs($trendPct) >= 10) {
                    $dir = $trendPct > 0 ? 'meningkat' : 'menurun';
                    $trendText = ' (tren '.$dir.' '.abs(round($trendPct, 0)).'% dari bulan pertama ke terakhir)';
                }
            }
        }

        $insights[] = "Rata-rata {$fmtAvg} kegiatan per petugas per bulan{$trendText}.";

        $cvLevel = $avgCv > 40 ? 'tinggi' : ($avgCv > 20 ? 'sedang' : 'rendah');
        $cvVal = round($avgCv, 1);
        $mostUnequalMonth = $workloadMonthsWithData->sortByDesc('gini_kegiatan')->first();
        $cvMonthName = $mostUnequalMonth['month'];
        $cvMonthVal = round((float) ($mostUnequalMonth['gini_kegiatan'] ?? 0), 1);
        $insights[] = "Ketimpangan beban rata-rata {$cvLevel} (Gini {$cvVal}%). Bulan paling timpang: {$cvMonthName} (Gini {$cvMonthVal}%).";

        $totalOverload = $workloadMonthsWithData->sum('kegiatan_lebih_5');
        $totalAllocated = $workloadMonthsWithData->sum('total_dialokasikan');

        if ($totalAllocated > 0) {
            $pctOverload = round(($totalOverload / $totalAllocated) * 100, 1);

            if ($pctOverload > 15) {
                $insights[] = "{$pctOverload}% alokasi petugas overload (>5 kegiatan) — perlu redistribusi segera.";
            } elseif ($pctOverload > 5) {
                $insights[] = "{$pctOverload}% alokasi petugas overload (>5 kegiatan) — pantau keberlangsungannya.";
            } else {
                $insights[] = "Hanya {$pctOverload}% alokasi petugas yang overload (>5 kegiatan) — beban terkendali.";
            }

            $totalUnderutilized = $workloadMonthsWithData->sum('kegiatan_1_2');
            $pctUnderutilized = round(($totalUnderutilized / $totalAllocated) * 100, 1);

            if ($pctUnderutilized > 40) {
                $insights[] = "{$pctUnderutilized}% petugas hanya mendapat 1-2 kegiatan — kapasitas banyak yang belum terpakai.";
            } elseif ($pctUnderutilized > 20) {
                $insights[] = "{$pctUnderutilized}% petugas mendapat 1-2 kegiatan — ada ruang untuk penambahan alokasi.";
            }
        }

        $fmtUtil = number_format($utilizationRate, 1, ',', '.');
        $idlePetugas = $totalNonOrganikAktif - (int) round($workloadMonthsWithData->avg('total_dialokasikan'));
        $idlePetugas = max(0, $idlePetugas);

        if ($utilizationRate < 50) {
            $insights[] = "Utilisasi pool mitra rendah ({$fmtUtil}% dari {$totalNonOrganikAktif} non-organik aktif). Rata-rata {$idlePetugas} petugas idle setiap bulan.";
        } elseif ($utilizationRate < 80) {
            $insights[] = "Utilisasi pool mitra sedang ({$fmtUtil}% dari {$totalNonOrganikAktif} non-organik aktif). Rata-rata {$idlePetugas} petugas masih bisa dialokasikan.";
        } else {
            $insights[] = "Utilisasi pool mitra tinggi ({$fmtUtil}% dari {$totalNonOrganikAktif} non-organik aktif).";
        }

        if ($rekomendasiMin > 0 && $rekomendasiMax > 0) {
            $insights[] = "Rekomendasi: alokasikan {$rekomendasiMin}–{$rekomendasiMax} petugas per bulan agar setiap petugas mendapat 3–5 kegiatan (beban optimal).";
        }

        return $insights;
    }

    public function calculateDashboardHonor(
        int $month,
        float|int $baseHonor,
        ?string $jenisKegiatan,
        ?string $namaKegiatan,
    ): float {
        if (! $this->isSensusEkonomiKegiatan($jenisKegiatan, $namaKegiatan)) {
            return (float) $baseHonor;
        }

        return (float) $baseHonor * $this->getSensusHonorWeight($month);
    }

    /**
     * Build natural-language insight bullets for honor inequality.
     *
     * @param  Collection<int, array<string, mixed>>  $honorMonthsWithData
     * @return array<int, string>
     */
    public function buildHonorInsights(
        Collection $honorMonthsWithData,
        int $currentMonth,
        float $avgRataRataHonor,
        float $avgKoefisienVariasi,
        float $weightedKoefisienVariasi,
    ): array {
        $insights = [];
        $monthsWithData = $honorMonthsWithData->count();

        if ($monthsWithData < $currentMonth) {
            $insights[] = "Data honor tersedia di {$monthsWithData} dari {$currentMonth} bulan berjalan.";
        } else {
            $insights[] = "Data honor tersedia di semua {$currentMonth} bulan berjalan.";
        }

        $fmtAvg = number_format((int) $avgRataRataHonor, 0, ',', '.');
        $trendText = '';

        if ($honorMonthsWithData->count() >= 2) {
            $firstHonor = (float) $honorMonthsWithData->first()['rata_rata_honor'];
            $lastHonor = (float) $honorMonthsWithData->last()['rata_rata_honor'];

            if ($firstHonor > 0) {
                $trendPct = (($lastHonor - $firstHonor) / $firstHonor) * 100;

                if (abs($trendPct) >= 5) {
                    $dir = $trendPct > 0 ? 'naik' : 'turun';
                    $trendText = ' (tren '.$dir.' '.abs(round($trendPct, 0)).'% dari bulan pertama ke terakhir)';
                }
            }
        }

        $insights[] = "Rata-rata honor non-organik Rp {$fmtAvg}/bulan{$trendText}.";

        $cvLevel = $weightedKoefisienVariasi > 50 ? 'tinggi' : ($weightedKoefisienVariasi > 30 ? 'sedang' : 'rendah');
        $cvVal = round($weightedKoefisienVariasi, 1);
        $mostUnequalMonth = $honorMonthsWithData->sortByDesc('koefisien_variasi')->first();
        $cvMonthName = $mostUnequalMonth['month'];
        $cvMonthVal = round((float) $mostUnequalMonth['koefisien_variasi'], 1);
        $insights[] = "Tingkat ketimpangan rata-rata {$cvLevel} (CV {$cvVal}%). Distribusi paling timpang di bulan {$cvMonthName} (CV {$cvMonthVal}%).";

        $bracketTotals = [
            '0–500 rb' => $honorMonthsWithData->sum('honor_0_500rb'),
            '501 rb–1,5 jt' => $honorMonthsWithData->sum('honor_501rb_1500rb'),
            '1,5–2,5 jt' => $honorMonthsWithData->sum('honor_1501rb_2500rb'),
            '2,5–3,5 jt' => $honorMonthsWithData->sum('honor_2501rb_3500rb'),
            '>3,5 jt' => $honorMonthsWithData->sum('honor_lebih_3501rb'),
        ];

        $totalSlots = array_sum($bracketTotals);
        $dominantBracket = 'tidak tersedia';
        $dominantPct = 0;

        if ($totalSlots > 0) {
            arsort($bracketTotals);
            $dominantBracket = array_key_first($bracketTotals);
            $dominantPct = round(reset($bracketTotals) / $totalSlots * 100, 0);
        }

        $insights[] = "Kelompok honor terbanyak di bracket Rp {$dominantBracket} ({$dominantPct}% dari total alokasi).";

        $highestMonth = $honorMonthsWithData->sortByDesc('rata_rata_honor')->first();
        $lowestMonth = $honorMonthsWithData->sortBy('rata_rata_honor')->first();

        if ($highestMonth['month'] !== $lowestMonth['month']) {
            $fmtHighest = number_format((int) $highestMonth['rata_rata_honor'], 0, ',', '.');
            $fmtLowest = number_format((int) $lowestMonth['rata_rata_honor'], 0, ',', '.');
            $insights[] = "Rata-rata honor tertinggi di bulan {$highestMonth['month']} (Rp {$fmtHighest}) dan terendah di bulan {$lowestMonth['month']} (Rp {$fmtLowest}).";
        }

        return $insights;
    }

    private function isSensusEkonomiKegiatan(?string $jenisKegiatan, ?string $namaKegiatan): bool
    {
        return $jenisKegiatan === 'sensus'
            && str_contains(mb_strtolower((string) $namaKegiatan), 'sensus ekonomi');
    }

    private function getSensusHonorWeight(int $month): float
    {
        return match ($month) {
            6 => 0.0,
            7 => 0.4,
            8 => 0.6,
            default => 1.0,
        };
    }
}
