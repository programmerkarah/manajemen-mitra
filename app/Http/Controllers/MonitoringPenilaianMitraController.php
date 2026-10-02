<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\ReviewPetugas;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringPenilaianMitraController extends Controller
{
    public function index(Request $request): Response
    {
        $activeYear = ActiveYearService::get();
        $selectedKegiatanId = (string) $request->input('kegiatan_id', 'all');
        $selectedPetugasId = (string) $request->input('petugas_id', 'all');

        $allReviews = ReviewPetugas::query()
            ->with([
                'petugas:id,nama',
                'kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id,pj_lainnya_id',
                'periodeAlokasi:id,bulan,tahun',
                'reviewer:id,name',
            ])
            ->whereHas('periodeAlokasi', function ($periodeQuery) use ($activeYear) {
                $periodeQuery->where('tahun', $activeYear);
            })
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->get();

        $episodeMeta = $this->buildEpisodeMeta($allReviews, $activeYear);

        $withEpisode = $allReviews->map(function (ReviewPetugas $review) use ($episodeMeta) {
            $meta = $episodeMeta->get($review->id, [
                'start_year' => (int) ($review->periodeAlokasi?->tahun ?? 0),
                'start_month' => (int) ($review->periodeAlokasi?->bulan ?? 0),
                'end_year' => (int) ($review->periodeAlokasi?->tahun ?? 0),
                'end_month' => (int) ($review->periodeAlokasi?->bulan ?? 0),
                'months' => [str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT)],
            ]);

            return [
                'review' => $review,
                'episode' => $meta,
            ];
        });

        $kegiatanSource = $withEpisode;
        if ($selectedPetugasId !== 'all') {
            $kegiatanSource = $kegiatanSource
                ->filter(fn (array $item) => (int) $item['review']->petugas_id === (int) $selectedPetugasId)
                ->values();
        }

        $kegiatanOptions = $kegiatanSource
            ->groupBy(fn (array $item) => $item['review']->kegiatan_id)
            ->map(function (Collection $groupRows) {
                $first = $groupRows->first()['review'];

                return [
                    'value' => (string) $first->kegiatan_id,
                    'label' => $first->kegiatan?->nama_kegiatan ?? '-',
                ];
            })
            ->sortBy('label')
            ->values();

        $querySource = $withEpisode;

        if ($selectedKegiatanId !== 'all') {
            $querySource = $querySource
                ->filter(fn (array $item) => (int) $item['review']->kegiatan_id === (int) $selectedKegiatanId)
                ->values();
        }

        $hallOfFameReviews = $querySource;

        $petugasOptions = $querySource
            ->groupBy(fn (array $item) => $item['review']->petugas_id)
            ->map(function (Collection $groupReviews) {
                $first = $groupReviews->first()['review'];

                return [
                    'value' => (string) $first->petugas_id,
                    'label' => $first->petugas?->nama ?? '-',
                ];
            })
            ->sortBy('label')
            ->values();

        if ($selectedPetugasId !== 'all') {
            $querySource = $querySource
                ->filter(fn (array $item) => (int) $item['review']->petugas_id === (int) $selectedPetugasId)
                ->values();
        }

        $rows = $querySource->map(function (array $item) {
            /** @var ReviewPetugas $review */
            $review = $item['review'];
            $episode = $item['episode'];
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
                'periode_bulan' => str_pad((string) $episode['end_month'], 2, '0', STR_PAD_LEFT),
                'periode_mulai_tahun' => $episode['start_year'],
                'periode_mulai_bulan' => str_pad((string) $episode['start_month'], 2, '0', STR_PAD_LEFT),
                'periode_selesai_tahun' => $episode['end_year'],
                'periode_selesai_bulan' => str_pad((string) $episode['end_month'], 2, '0', STR_PAD_LEFT),
                'periode_bulan_terlibat' => $episode['months'],
                'reviewer_name' => $review->reviewer?->name ?? '-',
            ];
        })->values();

        $hallOfFameRows = $hallOfFameReviews->map(function (array $item) {
            /** @var ReviewPetugas $review */
            $review = $item['review'];

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

        $hallOfFame = $hallOfFameTable->first();

        $topBottomLimit = $selectedKegiatanId === 'all' ? 5 : 3;

        $topPetugas = $hallOfFameTable
            ->take($topBottomLimit)
            ->values();

        $bottomPetugas = $hallOfFameTable
            ->sortBy('balanced_score')
            ->take($topBottomLimit)
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

    private function buildEpisodeMeta(Collection $reviews, int $activeYear): Collection
    {
        if ($reviews->isEmpty()) {
            return collect();
        }

        $kegiatanIds = $reviews->pluck('kegiatan_id')->unique()->values();
        $petugasIds = $reviews->pluck('petugas_id')->unique()->values();

        $assignments = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['dikirim', 'direvisi', 'perubahan'])
            ->whereIn('pa.kegiatan_id', $kegiatanIds)
            ->whereIn('alokasi_petugas.petugas_id', $petugasIds)
            ->where('alokasi_petugas.status_kepegawaian', 'non_organik')
            ->where(function ($query) {
                $query->where('alokasi_petugas.total_honor', '>', 0)
                    ->orWhere('alokasi_petugas.total_honor_listing', '>', 0);
            })
            ->select([
                'pa.id as periode_alokasi_id',
                'pa.kegiatan_id',
                'pa.bulan',
                'pa.tahun',
                'pa.status',
                'alokasi_petugas.petugas_id',
                'alokasi_petugas.peran',
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->kegiatan_id.'-'.(int) $row->petugas_id.'-'.$row->peran)
            ->map(function (Collection $rows) {
                return $rows
                    ->groupBy(fn ($row) => ((int) $row->tahun * 12) + (int) $row->bulan)
                    ->map(function (Collection $monthRows) {
                        return $monthRows
                            ->sortByDesc(fn ($row) => ($this->statusRank((string) $row->status) * 1000000) + (int) $row->periode_alokasi_id)
                            ->first();
                    })
                    ->sortBy(fn ($row) => [ (int) $row->tahun, (int) $row->bulan ])
                    ->values();
            });

        return $reviews->mapWithKeys(function (ReviewPetugas $review) use ($assignments) {
            $targetPeriodId = (int) $review->periode_alokasi_id;

            foreach ($assignments as $groupRows) {
                $targetIndex = $groupRows->search(
                    fn ($row) => (int) $row->periode_alokasi_id === $targetPeriodId
                );

                if ($targetIndex === false) {
                    continue;
                }

                $episode = collect([$groupRows[$targetIndex]]);
                $cursor = $targetIndex - 1;
                $expectedMonthIndex =
                    ((int) $groupRows[$targetIndex]->tahun * 12) +
                    (int) $groupRows[$targetIndex]->bulan - 1;

                while ($cursor >= 0) {
                    $candidate = $groupRows[$cursor];
                    $candidateIndex =
                        ((int) $candidate->tahun * 12) + (int) $candidate->bulan;

                    if ($candidateIndex !== $expectedMonthIndex) {
                        break;
                    }

                    $episode->prepend($candidate);
                    $expectedMonthIndex--;
                    $cursor--;
                }

                $first = $episode->first();
                $last = $episode->last();

                return [
                    $review->id => [
                        'start_year' => (int) $first->tahun,
                        'start_month' => (int) $first->bulan,
                        'end_year' => (int) $last->tahun,
                        'end_month' => (int) $last->bulan,
                        'months' => $episode
                            ->map(fn ($row) => str_pad((string) $row->bulan, 2, '0', STR_PAD_LEFT))
                            ->unique()
                            ->values()
                            ->all(),
                    ],
                ];
            }

            return [
                $review->id => [
                    'start_year' => (int) ($review->periodeAlokasi?->tahun ?? 0),
                    'start_month' => (int) ($review->periodeAlokasi?->bulan ?? 0),
                    'end_year' => (int) ($review->periodeAlokasi?->tahun ?? 0),
                    'end_month' => (int) ($review->periodeAlokasi?->bulan ?? 0),
                    'months' => [str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT)],
                ],
            ];
        });
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'perubahan' => 3,
            'direvisi' => 2,
            'dikirim' => 1,
            default => 0,
        };
    }
}
