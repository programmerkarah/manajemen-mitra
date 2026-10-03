<?php

namespace App\Services\Bapp;

use App\Models\Spk;
use App\Services\SensusEkonomiBappNumberService;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class BappNumberService
{
    public function __construct(
        private readonly SensusEkonomiBappNumberService $sensusNumberService,
    ) {}

    public function resolveEntryDate(array $entry, ?string $sharedDate): ?string
    {
        $entryDate = isset($entry['tanggal_bapp'])
            ? trim((string) $entry['tanggal_bapp'])
            : '';

        if ($entryDate !== '') {
            return $entryDate;
        }

        $sharedDate = $sharedDate !== null ? trim($sharedDate) : '';

        return $sharedDate !== '' ? $sharedDate : null;
    }

    /**
     * @param  Collection<int, Spk>  $spks
     * @return array<int, string>
     */
    public function generateNumberMap(
        Collection $spks,
        string $roman,
        int $year,
        string $documentType = 'regular',
        int $terminCount = 2,
    ): array {
        $sorted = $spks->sortBy(
            fn (Spk $spk): string => mb_strtolower(
                trim($spk->petugas?->nama ?? ''),
            ),
        )->values();

        $map = [];

        foreach ($sorted as $index => $spk) {
            $sequence = $index + 1;

            if ($documentType === 'stopped_petugas') {
                $map[$spk->id] = $this->sensusNumberService
                    ->formatStoppedPetugasNumber($sequence, $year);

                continue;
            }

            if ($documentType === 'replacement_pkpp') {
                $map[$spk->id] = $this->sensusNumberService
                    ->formatReplacementNumber(
                        $sequence,
                        $year,
                        $terminCount,
                        $roman,
                    );

                continue;
            }

            $map[$spk->id] = sprintf(
                'B-%03d/BAPP-%s-SE2026/1373/PL.200/%d',
                $sequence,
                $roman,
                $year,
            );
        }

        return $map;
    }

    public function formatManualNumber(
        string $sequence,
        int $year,
        string $documentType,
        int $replacementTerminCount,
        string $roman,
    ): string {
        $cleanSequence = preg_replace('/\D+/', '', $sequence) ?: '';

        if ($cleanSequence === '') {
            throw new InvalidArgumentException(
                'Nomor BAPP wajib berupa angka.',
            );
        }

        if (
            $documentType === 'replacement_pkpp'
            && $replacementTerminCount === 1
        ) {
            return sprintf(
                'B-%s/BAPP-SE2026/1373/PL.200/%d',
                $cleanSequence,
                $year,
            );
        }

        return sprintf(
            'B-%s/BAPP-%s-SE2026/1373/PL.200/%d',
            $cleanSequence,
            $roman,
            $year,
        );
    }

    public function extractSequence(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        if (
            preg_match('/^B-(\d+)\//', (string) $number, $matches) === 1
        ) {
            return $matches[1];
        }

        $trimmed = trim((string) $number);

        return preg_match('/^\d+$/', $trimmed) === 1 ? $trimmed : null;
    }
}
