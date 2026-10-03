<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasRecommendationSupport
{
    private function buildPetugasUniqueKegiatanCounts(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as unique_kegiatan_count')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('unique_kegiatan_count', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($count, $petugasId) => [(int) $petugasId => (int) $count])
            ->toArray();
    }

    private function buildPetugasAllocationCounts(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as allocation_count')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('allocation_count', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($count, $petugasId) => [(int) $petugasId => (int) $count])
            ->toArray();
    }

    private function buildPetugasTotalHonorByYear(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('total_honor_combined', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($total, $petugasId) => [(int) $petugasId => (float) $total])
            ->toArray();
    }

    private function buildPetugasSuggestions(Collection $kegiatans, int $activeYear): array
    {
        $kegiatanIds = $kegiatans->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        if (empty($kegiatanIds)) {
            return [];
        }

        $activeStatuses = ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'];

        $previousAllocations = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->whereIn('pa.kegiatan_id', $kegiatanIds)
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('pa.kegiatan_id')
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('CAST(pa.bulan AS UNSIGNED) as bulan')
            ->selectRaw('pa.tahun')
            ->groupBy('pa.kegiatan_id', 'alokasi_petugas.petugas_id', 'pa.bulan', 'pa.tahun')
            ->orderByDesc('pa.tahun')
            ->orderByDesc('pa.bulan')
            ->get();

        $smallestAllocationPetugasIds = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as alokasi_count')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id')
            ->orderBy('alokasi_count')
            ->orderBy('total_honor_combined')
            ->orderBy('alokasi_petugas.petugas_id')
            ->pluck('alokasi_petugas.petugas_id')
            ->map(fn ($petugasId) => (int) $petugasId)
            ->values()
            ->all();

        $groupedPreviousAllocations = $previousAllocations
            ->groupBy(fn ($row) => (int) $row->kegiatan_id)
            ->map(function (Collection $rows) {
                return $rows
                    ->map(fn ($row) => [
                        'petugas_id' => (int) $row->petugas_id,
                        'bulan' => (int) $row->bulan,
                        'tahun' => (int) $row->tahun,
                    ])
                    ->values()
                    ->all();
            });

        $result = [];
        foreach ($kegiatanIds as $kegiatanId) {
            $result[$kegiatanId] = [
                'previous_allocations' => $groupedPreviousAllocations->get($kegiatanId, []),
                'smallest_allocation_petugas_ids' => $smallestAllocationPetugasIds,
            ];
        }

        return $result;
    }

    private function buildPetugasReviewRecommendations(int $activeYear): array
    {
        $activeStatuses = ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'];

        $reviewRows = ReviewPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'review_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('review_petugas.petugas_id')
            ->selectRaw('COUNT(*) as review_count')
            ->selectRaw('AVG(review_petugas.rating) as avg_rating')
            ->groupBy('review_petugas.petugas_id')
            ->get();

        if ($reviewRows->isEmpty()) {
            return [
                'has_review_data' => false,
                'global_avg_rating' => 0,
                'by_petugas' => [],
            ];
        }

        $globalAvgRating = (float) (ReviewPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'review_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->avg('review_petugas.rating') ?? 0);

        $byPetugas = $reviewRows
            ->mapWithKeys(function ($row) use ($globalAvgRating) {
                $reviewCount = (int) $row->review_count;
                $avgRating = (float) $row->avg_rating;
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($globalAvgRating * 0.3)) * $confidence
                    + ($globalAvgRating * (1 - $confidence));

                $status = 'neutral';
                if ($reviewCount >= 2 && $avgRating >= 4.0) {
                    $status = 'recommended';
                } elseif ($reviewCount >= 2 && $avgRating < 3.0) {
                    $status = 'not_recommended';
                }

                return [
                    (int) $row->petugas_id => [
                        'review_count' => $reviewCount,
                        'avg_rating' => round($avgRating, 2),
                        'balanced_score' => round($balancedScore, 3),
                        'status' => $status,
                    ],
                ];
            })
            ->toArray();

        return [
            'has_review_data' => true,
            'global_avg_rating' => round($globalAvgRating, 2),
            'by_petugas' => $byPetugas,
        ];
    }
}
