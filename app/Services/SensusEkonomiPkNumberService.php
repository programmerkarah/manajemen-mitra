<?php

namespace App\Services;

use App\Models\SensusEkonomiPkppContract;
use App\Models\Spk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SensusEkonomiPkNumberService
{
    public function previewNextNumber(int $year): string
    {
        $maxSequence = max(
            $this->getMaxSpkSequence($year),
            $this->getMaxPkppSequence($year),
        );

        return $this->formatNumber($maxSequence + 1, $year);
    }

    public function allocateNextNumber(int $year): string
    {
        return DB::transaction(function () use ($year): string {
            $maxSequence = max(
                $this->getMaxSpkSequence($year, true),
                $this->getMaxPkppSequence($year, true),
            );

            return $this->formatNumber($maxSequence + 1, $year);
        }, attempts: 5);
    }

    private function formatNumber(int $sequence, int $year): string
    {
        return sprintf('B-%03d/SPK-SE2026/1373/PL.200/%d', $sequence, $year);
    }

    private function getMaxSpkSequence(int $year, bool $lockForUpdate = false): int
    {
        if (! Schema::hasTable('spk')) {
            return 0;
        }

        $query = Spk::query()
            ->where('nomor_spk', 'like', sprintf('B-%%/SPK-SE2026/1373/PL.200/%d', $year));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->pluck('nomor_spk')
            ->map(fn (string $nomorSpk) => $this->extractSequence($nomorSpk, $year))
            ->max() ?? 0;
    }

    private function getMaxPkppSequence(int $year, bool $lockForUpdate = false): int
    {
        if (! Schema::hasTable('sensus_ekonomi_pkpp_contracts')) {
            return 0;
        }

        $query = SensusEkonomiPkppContract::query()
            ->whereNotNull('nomor_pkpp')
            ->where('nomor_pkpp', 'like', sprintf('B-%%/SPK-SE2026/1373/PL.200/%d', $year));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->pluck('nomor_pkpp')
            ->map(fn (?string $nomorPkpp) => $this->extractSequence((string) $nomorPkpp, $year))
            ->max() ?? 0;
    }

    private function extractSequence(string $number, int $year): int
    {
        $pattern = sprintf('/^B-(\d+)\/SPK-SE2026\/1373\/PL\.200\/%d$/', $year);
        if (preg_match($pattern, $number, $matches) !== 1) {
            return 0;
        }

        return (int) ($matches[1] ?? 0);
    }
}
