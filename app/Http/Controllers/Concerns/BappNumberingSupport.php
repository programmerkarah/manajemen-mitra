<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Spk;
use App\Services\Bapp\BappNumberService;
use Illuminate\Database\Eloquent\Collection;

trait BappNumberingSupport
{
    protected function resolveEntryTanggalBapp(
        array $entry,
        ?string $sharedTanggalBapp,
    ): ?string {
        return app(BappNumberService::class)->resolveEntryDate(
            $entry,
            $sharedTanggalBapp,
        );
    }

    /**
     * @param  Collection<int, Spk>  $spks
     * @return array<int, string>
     */
    private function generateNomorBappMap(
        Collection $spks,
        string $roman,
        int $tahun,
        string $documentType = 'regular',
        int $terminCount = 2,
    ): array {
        return app(BappNumberService::class)->generateNumberMap(
            $spks,
            $roman,
            $tahun,
            $documentType,
            $terminCount,
        );
    }

    private function formatManualBappNumber(
        string $sequence,
        int $termin,
        int $tahun,
        string $documentType,
        int $replacementTerminCount,
    ): string {
        $roman = self::TERMIN_CONFIG[$termin]['roman']
            ?? ($termin === 2 ? 'II' : 'I');

        return app(BappNumberService::class)->formatManualNumber(
            $sequence,
            $tahun,
            $documentType,
            $replacementTerminCount,
            $roman,
        );
    }

    private function extractManualDocumentSequence(
        ?string $number,
    ): ?string {
        return app(BappNumberService::class)->extractSequence($number);
    }
}
