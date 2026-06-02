<?php

namespace Tests\Unit;

use App\Http\Controllers\SpkController;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use Carbon\Carbon;
use Tests\TestCase;

class SpkPeriodScopeTest extends TestCase
{
    public function test_sensus_ekonomi_uses_period_based_spk_flow_and_range_label(): void
    {
        $controller = new SpkController;
        $periode = new PeriodeAlokasi([
            'bulan' => '06',
            'tahun' => 2026,
            'tanggal_mulai' => Carbon::parse('2026-06-15'),
            'tanggal_selesai' => Carbon::parse('2026-08-31'),
        ]);
        $periode->id = 99;
        $periode->exists = true;
        $periode->setRelation('kegiatan', new Kegiatan([
            'nama_kegiatan' => 'Sensus Ekonomi',
            'jenis_kegiatan' => 'sensus',
        ]));

        $usesPeriodBasedFlow = new \ReflectionMethod(SpkController::class, 'usesPeriodBasedSpkFlow');
        $usesPeriodBasedFlow->setAccessible(true);

        $resolveGroupKey = new \ReflectionMethod(SpkController::class, 'resolveSpkIndexGroupKey');
        $resolveGroupKey->setAccessible(true);

        $resolveDisplayLabel = new \ReflectionMethod(SpkController::class, 'resolveSpkIndexDisplayLabel');
        $resolveDisplayLabel->setAccessible(true);

        $this->assertTrue($usesPeriodBasedFlow->invoke($controller, $periode));
        $this->assertSame('periode-99', $resolveGroupKey->invoke($controller, $periode));
        $this->assertSame('Juni - Agustus 2026', $resolveDisplayLabel->invoke($controller, $periode));
    }

    public function test_regular_survey_stays_month_based_in_spk_index(): void
    {
        $controller = new SpkController;
        $periode = new PeriodeAlokasi([
            'bulan' => '6',
            'tahun' => 2026,
        ]);
        $periode->id = 15;
        $periode->exists = true;
        $periode->setRelation('kegiatan', new Kegiatan([
            'nama_kegiatan' => 'Survei Harga',
            'jenis_kegiatan' => 'survei',
        ]));

        $usesPeriodBasedFlow = new \ReflectionMethod(SpkController::class, 'usesPeriodBasedSpkFlow');
        $usesPeriodBasedFlow->setAccessible(true);

        $resolveGroupKey = new \ReflectionMethod(SpkController::class, 'resolveSpkIndexGroupKey');
        $resolveGroupKey->setAccessible(true);

        $resolveDisplayLabel = new \ReflectionMethod(SpkController::class, 'resolveSpkIndexDisplayLabel');
        $resolveDisplayLabel->setAccessible(true);

        $this->assertFalse($usesPeriodBasedFlow->invoke($controller, $periode));
        $this->assertSame('2026-06', $resolveGroupKey->invoke($controller, $periode));
        $this->assertSame('Juni 2026', $resolveDisplayLabel->invoke($controller, $periode));
    }

    public function test_regular_survey_index_group_key_normalizes_unpadded_months(): void
    {
        $controller = new SpkController;
        $periode = new PeriodeAlokasi([
            'bulan' => '5',
            'tahun' => 2026,
        ]);
        $periode->id = 16;
        $periode->exists = true;
        $periode->setRelation('kegiatan', new Kegiatan([
            'nama_kegiatan' => 'Survei Harga',
            'jenis_kegiatan' => 'survei',
        ]));

        $resolveGroupKey = new \ReflectionMethod(SpkController::class, 'resolveSpkIndexGroupKey');
        $resolveGroupKey->setAccessible(true);

        $this->assertSame('2026-05', $resolveGroupKey->invoke($controller, $periode));
    }
}
