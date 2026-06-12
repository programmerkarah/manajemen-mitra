<?php

namespace Tests\Unit;

use App\Http\Controllers\SbmlReportController;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use ReflectionMethod;
use Tests\TestCase;

class SbmlReportControllerSensusMonthSplitTest extends TestCase
{
    private function callShouldIncludeInMonthlyReport(AlokasiPetugas $alokasi, int $reportMonth, int $tahun): bool
    {
        $controller = new SbmlReportController;
        $method = new ReflectionMethod(SbmlReportController::class, 'shouldIncludeInMonthlyReport');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller, $alokasi, $reportMonth, $tahun);
    }

    private function callCalculateMonthlyHonorForAllocation(float $baseHonor, int $bulan, ?Kegiatan $kegiatan): float
    {
        $controller = new SbmlReportController;
        $method = new ReflectionMethod(SbmlReportController::class, 'calculateMonthlyHonorForAllocation');
        $method->setAccessible(true);

        return (float) $method->invoke($controller, $baseHonor, $bulan, $kegiatan);
    }

    public function test_sensus_ekonomi_muncul_hanya_di_juni_juli_agustus(): void
    {
        $kegiatan = new Kegiatan([
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $periode = new PeriodeAlokasi([
            'bulan' => '06',
            'tahun' => 2026,
        ]);
        $periode->setRelation('kegiatan', $kegiatan);

        $alokasi = new AlokasiPetugas;
        $alokasi->setRelation('periodeAlokasi', $periode);

        $this->assertTrue($this->callShouldIncludeInMonthlyReport($alokasi, 6, 2026));
        $this->assertTrue($this->callShouldIncludeInMonthlyReport($alokasi, 7, 2026));
        $this->assertTrue($this->callShouldIncludeInMonthlyReport($alokasi, 8, 2026));
        $this->assertFalse($this->callShouldIncludeInMonthlyReport($alokasi, 9, 2026));
    }

    public function test_sensus_ekonomi_riva_dalpit_split_nilai_bulanannya(): void
    {
        $kegiatan = new Kegiatan([
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $this->assertSame(2518000.0, $this->callCalculateMonthlyHonorForAllocation(12590000, 6, $kegiatan));
        $this->assertSame(5036000.0, $this->callCalculateMonthlyHonorForAllocation(12590000, 7, $kegiatan));
        $this->assertSame(5036000.0, $this->callCalculateMonthlyHonorForAllocation(12590000, 8, $kegiatan));
    }
}