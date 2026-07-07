<?php

namespace Tests\Unit;

use App\Services\SensusEkonomiPkppSchemeService;
use InvalidArgumentException;
use Tests\TestCase;

class SensusEkonomiPkppSchemeServiceTest extends TestCase
{
    public function test_it_resolves_scheme_1_for_contract_until_26_june(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $scheme = $service->resolveScheme('2026-06-26');

        $this->assertSame('scheme_1', $scheme['code']);
        $this->assertSame('2026-07-01', $scheme['lapangan_deadline']);
        $this->assertSame(2.0, $scheme['honor_ob']);
        $this->assertSame(2, $scheme['termin_count']);
        $this->assertSame([50, 50], $scheme['termin_targets']);
        $this->assertSame('Minimal 1 bulan', $scheme['termin_satu_waktu']);
    }

    public function test_it_resolves_scheme_2_for_contract_until_2_july(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $scheme = $service->resolveScheme('2026-07-02');

        $this->assertSame('scheme_2', $scheme['code']);
        $this->assertSame('2026-07-06', $scheme['lapangan_deadline']);
        $this->assertSame(1.75, $scheme['honor_ob']);
        $this->assertSame(2, $scheme['termin_count']);
        $this->assertSame([50, 50], $scheme['termin_shares']);
    }

    public function test_it_resolves_scheme_3_for_contract_until_14_july(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $scheme = $service->resolveScheme('2026-07-14');

        $this->assertSame('scheme_3', $scheme['code']);
        $this->assertSame(1, $scheme['termin_count']);
        $this->assertSame([100], $scheme['termin_targets']);
        $this->assertSame('31 Agustus 2026', $scheme['termin_akhir_waktu']);
    }

    public function test_it_resolves_scheme_4_for_contract_until_21_july(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $scheme = $service->resolveScheme('2026-07-21');

        $this->assertSame('scheme_4', $scheme['code']);
        $this->assertSame(1.25, $scheme['honor_ob']);
        $this->assertSame('Juli - Agustus', $scheme['pasal_7_periode']);
    }

    public function test_it_resolves_scheme_5_for_contract_until_27_july(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $scheme = $service->resolveScheme('2026-07-27');

        $this->assertSame('scheme_5', $scheme['code']);
        $this->assertSame(1.0, $scheme['honor_ob']);
        $this->assertSame('Agustus', $scheme['pasal_7_periode']);
        $this->assertSame('2026-08-01', $scheme['lapangan_deadline']);
    }

    public function test_it_throws_error_for_contract_outside_supported_schedule(): void
    {
        $service = new SensusEkonomiPkppSchemeService;

        $this->expectException(InvalidArgumentException::class);

        $service->resolveScheme('2026-07-28');
    }
}
