<?php

namespace Tests\Unit;

use App\Services\SensusEkonomiPkNumberService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SensusEkonomiPkNumberServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_preview_next_number_uses_sensus_ekonomi_format(): void
    {
        $service = new SensusEkonomiPkNumberService;

        $number = $service->previewNextNumber(2026);

        $this->assertMatchesRegularExpression('/^B-\d{3}\/SPK-SE2026\/1373\/PL\.200\/2026$/', $number);
    }

    public function test_allocate_next_number_uses_sensus_ekonomi_format(): void
    {
        $service = new SensusEkonomiPkNumberService;

        $nextNumber = $service->allocateNextNumber(2026);

        $this->assertMatchesRegularExpression('/^B-\d{3}\/SPK-SE2026\/1373\/PL\.200\/2026$/', $nextNumber);
    }
}
