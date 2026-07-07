<?php

namespace Tests\Unit;

use App\Services\SensusEkonomiBappNumberService;
use InvalidArgumentException;
use Tests\TestCase;

class SensusEkonomiBappNumberServiceTest extends TestCase
{
    public function test_it_formats_stopped_petugas_bapp_number(): void
    {
        $service = new SensusEkonomiBappNumberService;

        $number = $service->formatStoppedPetugasNumber(7, 2026);

        $this->assertSame('B-007/BAPPP-SE2026/1373.PL200/2026', $number);
    }

    public function test_it_formats_two_termin_replacement_bapp_number(): void
    {
        $service = new SensusEkonomiBappNumberService;

        $number = $service->formatReplacementNumber(12, 2026, 2, 'ii');

        $this->assertSame('B-012/BAPP-II-SE2026/1373/PL.200/2026', $number);
    }

    public function test_it_formats_single_termin_replacement_bapp_number(): void
    {
        $service = new SensusEkonomiBappNumberService;

        $number = $service->formatReplacementNumber(3, 2026, 1);

        $this->assertSame('B-003/BAPP-SE2026/1373/PL.200/2026', $number);
    }

    public function test_it_rejects_two_termin_format_without_roman_label(): void
    {
        $service = new SensusEkonomiBappNumberService;

        $this->expectException(InvalidArgumentException::class);

        $service->formatReplacementNumber(3, 2026, 2, null);
    }
}
