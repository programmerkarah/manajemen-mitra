<?php

namespace App\Services;

use InvalidArgumentException;

class SensusEkonomiBappNumberService
{
    public function formatStoppedPetugasNumber(int $sequence, int $year): string
    {
        return sprintf('B-%03d/BAPPP-SE2026/1373.PL200/%d', $sequence, $year);
    }

    public function formatReplacementNumber(
        int $sequence,
        int $year,
        int $terminCount,
        ?string $roman = null,
    ): string {
        if ($terminCount === 2) {
            if ($roman === null || trim($roman) === '') {
                throw new InvalidArgumentException('Roman termin wajib diisi untuk skema 2 termin.');
            }

            return sprintf(
                'B-%03d/BAPP-%s-SE2026/1373/PL.200/%d',
                $sequence,
                strtoupper(trim($roman)),
                $year,
            );
        }

        if ($terminCount === 1) {
            return sprintf('B-%03d/BAPP-SE2026/1373/PL.200/%d', $sequence, $year);
        }

        throw new InvalidArgumentException('Jumlah termin tidak valid untuk format nomor BAPP sensus ekonomi.');
    }
}
