<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Kegiatan;
use App\Services\AlokasiPetugas\PetugasRecommendationService;
use Illuminate\Support\Collection;

trait AlokasiPetugasRecommendationSupport
{
    private function buildPetugasUniqueKegiatanCounts(
        int $activeYear,
    ): array {
        return app(PetugasRecommendationService::class)
            ->uniqueKegiatanCounts($activeYear);
    }

    private function buildPetugasAllocationCounts(int $activeYear): array
    {
        return app(PetugasRecommendationService::class)
            ->allocationCounts($activeYear);
    }

    private function buildPetugasTotalHonorByYear(int $activeYear): array
    {
        return app(PetugasRecommendationService::class)
            ->totalHonorByYear($activeYear);
    }

    /**
     * @param  Collection<int, Kegiatan>  $kegiatans
     */
    private function buildPetugasSuggestions(
        Collection $kegiatans,
        int $activeYear,
    ): array {
        return app(PetugasRecommendationService::class)
            ->suggestions($kegiatans, $activeYear);
    }

    private function buildPetugasReviewRecommendations(
        int $activeYear,
    ): array {
        return app(PetugasRecommendationService::class)
            ->reviewRecommendations($activeYear);
    }
}
