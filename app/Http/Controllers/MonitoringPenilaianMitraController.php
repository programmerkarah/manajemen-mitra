<?php

namespace App\Http\Controllers;

use App\Models\ReviewPetugas;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringPenilaianMitraController extends Controller
{
    public function index(Request $request): Response
    {
        $activeYear = ActiveYearService::get();
        $selectedMonth = (string) $request->input('bulan', 'all');
        $selectedKegiatanId = (string) $request->input('kegiatan_id', 'all');
        $selectedPetugasId = (string) $request->input('petugas_id', 'all');

        $baseQuery = ReviewPetugas::query()
            ->with([
                'petugas:id,nama',
                'kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id,pj_lainnya_id',
                'periodeAlokasi:id,bulan,tahun',
                'reviewer:id,name',
            ])
            ->whereHas('periodeAlokasi', function ($periodeQuery) use ($activeYear) {
                $periodeQuery->where('tahun', $activeYear);
            });

        if ($selectedMonth !== 'all') {
            $baseQuery->whereHas('periodeAlokasi', function ($periodeQuery) use ($selectedMonth) {
                $periodeQuery->where('bulan', $selectedMonth);
            });
        }

        $kegiatanOptionsQuery = clone $baseQuery;
        if ($selectedPetugasId !== 'all') {
            $kegiatanOptionsQuery->where('petugas_id', (int) $selectedPetugasId);
        }

        $kegiatanOptions = $kegiatanOptionsQuery
            ->get()
            ->groupBy('kegiatan_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();

                return [
                    'value' => (string) $first->kegiatan_id,
                    'label' => $first->kegiatan?->nama_kegiatan ?? '-',
                ];
            })
            ->sortBy('label')
            ->values();

        $query = clone $baseQuery;

        if ($selectedKegiatanId !== 'all') {
            $query->where('kegiatan_id', (int) $selectedKegiatanId);
        }

        $hallOfFameReviews = (clone $query)
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get();

        $petugasOptions = (clone $query)
            ->get()
            ->groupBy('petugas_id')
            ->map(function ($groupReviews) {
                $first = $groupReviews->first();

                return [
                    'value' => (string) $first->petugas_id,
                    'label' => $first->petugas?->nama ?? '-',
                ];
            })
            ->sortBy('label')
            ->values();

        if ($selectedPetugasId !== 'all') {
            $query->where('petugas_id', (int) $selectedPetugasId);
        }

        $reviews = $query
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get();

        $rows = $reviews->map(function (ReviewPetugas $review) {
            $reviewedAt = $review->reviewed_at ?? $review->created_at;

            return [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'ulasan' => $review->ulasan,
                'reviewed_at' => $reviewedAt?->format('Y-m-d H:i:s'),
                'reviewed_month' => $reviewedAt?->format('m') ?? str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT),
                'petugas_id' => $review->petugas_id,
                'petugas_nama' => $review->petugas?->nama ?? '-',
                'kegiatan_id' => $review->kegiatan_id,
                'kegiatan_kode' => $review->kegiatan?->kode_kegiatan ?? '-',
                'kegiatan_nama' => $review->kegiatan?->nama_kegiatan ?? '-',
                'periode_bulan' => str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT),
                'reviewer_name' => $review->reviewer?->name ?? '-',
            ];
        })->values();

        $hallOfFameRows = $hallOfFameReviews->map(function (ReviewPetugas $review) {
            return [
                'rating' => (int) $review->rating,
                'petugas_id' => $review->petugas_id,
                'petugas_nama' => $review->petugas?->nama ?? '-',
                'kegiatan_id' => $review->kegiatan_id,
            ];
        })->values();

        $hallOfFameGlobalAvg = $hallOfFameRows->isNotEmpty()
            ? (float) $hallOfFameRows->avg('rating')
            : 0.0;

        $hallOfFame = $hallOfFameRows
            ->groupBy('petugas_id')
            ->map(function ($groupRows) use ($hallOfFameGlobalAvg) {
                $first = $groupRows->first();
                $reviewCount = $groupRows->count();
                $avgRating = (float) $groupRows->avg('rating');
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($hallOfFameGlobalAvg * 0.3))
                    * (0.6 + (0.4 * $confidence));

                return [
                    'petugas_id' => $first['petugas_id'],
                    'petugas_nama' => $first['petugas_nama'],
                    'avg_rating' => round($avgRating, 2),
                    'review_count' => $reviewCount,
                    'kegiatan_count' => $groupRows->pluck('kegiatan_id')->unique()->count(),
                    'balanced_score' => round($balancedScore, 3),
                ];
            })
            ->sortByDesc(fn ($row) => ($row['balanced_score'] * 1000) + $row['review_count'])
            ->values()
            ->first();

        $hallOfFameTable = $hallOfFameRows
            ->groupBy('petugas_id')
            ->map(function ($groupRows) use ($hallOfFameGlobalAvg) {
                $first = $groupRows->first();
                $reviewCount = $groupRows->count();
                $kegiatanCount = $groupRows->pluck('kegiatan_id')->unique()->count();
                $avgRating = (float) $groupRows->avg('rating');
                $avgReviewPerKegiatan = $kegiatanCount > 0 ? $reviewCount / $kegiatanCount : 0;
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($hallOfFameGlobalAvg * 0.3))
                    * (0.6 + (0.4 * $confidence));

                return [
                    'petugas_id' => $first['petugas_id'],
                    'petugas_nama' => $first['petugas_nama'],
                    'kegiatan_count' => $kegiatanCount,
                    'review_count' => $reviewCount,
                    'avg_review_per_kegiatan' => round($avgReviewPerKegiatan, 2),
                    'avg_rating' => round($avgRating, 2),
                    'balanced_score' => round($balancedScore, 3),
                ];
            })
            ->sortByDesc(fn ($row) => ($row['balanced_score'] * 1000) + $row['review_count'])
            ->values();

        $ratingDistribution = collect([1, 2, 3, 4, 5])->map(function (int $rating) use ($rows) {
            return [
                'rating' => $rating,
                'jumlah' => $rows->where('rating', $rating)->count(),
            ];
        })->values();

        $monthMap = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'Mei',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Agu',
            '09' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Des',
        ];

        $monthlyTrend = collect(range(1, 12))->map(function (int $month) use ($rows, $monthMap) {
            $monthValue = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $monthRows = $rows->where('reviewed_month', $monthValue);
            $reviewCount = $monthRows->count();

            return [
                'month' => $monthMap[$monthValue],
                'jumlah_review' => $reviewCount,
                'rata_rating' => $reviewCount > 0 ? round((float) $monthRows->avg('rating'), 2) : 0,
            ];
        })->values();

        $petugasStats = $rows
            ->groupBy('petugas_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();

                return [
                    'petugas_id' => $first['petugas_id'],
                    'petugas_nama' => $first['petugas_nama'],
                    'review_count' => $groupRows->count(),
                    'avg_rating' => round((float) $groupRows->avg('rating'), 2),
                    'ulasan_count' => $groupRows->filter(fn ($row) => filled($row['ulasan']))->count(),
                    'kegiatan_count' => $groupRows->pluck('kegiatan_id')->unique()->count(),
                ];
            })
            ->values();

        $topBottomLimit = $selectedKegiatanId === 'all' ? 5 : 3;

        $topPetugas = $petugasStats
            ->filter(fn ($row) => $row['avg_rating'] >= 3)
            ->sortByDesc(fn ($row) => ($row['avg_rating'] * 1000) + $row['review_count'])
            ->take($topBottomLimit)
            ->values();

        $bottomPetugas = $petugasStats
            ->filter(fn ($row) => $row['avg_rating'] < 3)
            ->sortBy(fn ($row) => ($row['avg_rating'] * 1000) - $row['review_count'])
            ->take($topBottomLimit)
            ->values();

        $kegiatanStats = $rows
            ->groupBy('kegiatan_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();

                return [
                    'kegiatan_id' => $first['kegiatan_id'],
                    'kegiatan_kode' => $first['kegiatan_kode'],
                    'kegiatan_nama' => $first['kegiatan_nama'],
                    'review_count' => $groupRows->count(),
                    'avg_rating' => round((float) $groupRows->avg('rating'), 2),
                    'petugas_count' => $groupRows->pluck('petugas_id')->unique()->count(),
                ];
            })
            ->sortByDesc('review_count')
            ->take(10)
            ->values();

        $kegiatanTop3ForPetugas = $rows
            ->groupBy('kegiatan_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();

                return [
                    'kegiatan_id' => $first['kegiatan_id'],
                    'kegiatan_nama' => $first['kegiatan_nama'],
                    'avg_rating' => round((float) $groupRows->avg('rating'), 2),
                    'review_count' => $groupRows->count(),
                ];
            })
            ->sortByDesc(fn ($item) => ($item['avg_rating'] * 1000) + $item['review_count'])
            ->take(3)
            ->values();

        $kegiatanBottom3ForPetugas = $rows
            ->groupBy('kegiatan_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();

                return [
                    'kegiatan_id' => $first['kegiatan_id'],
                    'kegiatan_nama' => $first['kegiatan_nama'],
                    'avg_rating' => round((float) $groupRows->avg('rating'), 2),
                    'review_count' => $groupRows->count(),
                ];
            })
            ->sortBy(fn ($item) => ($item['avg_rating'] * 1000) - $item['review_count'])
            ->take(3)
            ->values();

        $selectedPetugasEpisodeCount = $selectedPetugasId === 'all' ? 0 : $rows->count();
        $showKegiatanRankForPetugas = $selectedPetugasId !== 'all' && $selectedPetugasEpisodeCount > 5;

        $summary = [
            'total_reviews' => $rows->count(),
            'avg_rating' => $rows->isNotEmpty() ? round((float) $rows->avg('rating'), 2) : 0,
            'petugas_reviewed' => $rows->pluck('petugas_id')->unique()->count(),
            'kegiatan_reviewed' => $rows->pluck('kegiatan_id')->unique()->count(),
            'reviews_with_ulasan' => $rows->filter(fn ($row) => filled($row['ulasan']))->count(),
        ];

        return Inertia::render('Monitoring/PenilaianMitraStatistik', [
            'active_year' => $activeYear,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'filters' => [
                'bulan' => $selectedMonth,
                'kegiatan_id' => $selectedKegiatanId,
                'petugas_id' => $selectedPetugasId,
            ],
            'show_kegiatan_terbanyak' => $selectedKegiatanId === 'all' && $selectedPetugasId === 'all',
            'show_mitra_top_bottom' => $selectedPetugasId === 'all',
            'show_kegiatan_rank_for_petugas' => $showKegiatanRankForPetugas,
            'summary' => $summary,
            'hall_of_fame' => $hallOfFame,
            'hall_of_fame_table' => $hallOfFameTable,
            'rating_distribution' => $ratingDistribution,
            'monthly_trend' => $monthlyTrend,
            'top_petugas' => $topPetugas,
            'bottom_petugas' => $bottomPetugas,
            'top_kegiatan_for_petugas' => $kegiatanTop3ForPetugas,
            'bottom_kegiatan_for_petugas' => $kegiatanBottom3ForPetugas,
            'kegiatan_stats' => $kegiatanStats,
            'kegiatan_options' => $kegiatanOptions,
            'petugas_options' => $petugasOptions,
            'review_rows' => [
                'encrypted' => encryptData($rows->toArray()),
            ],
        ]);
    }
}
