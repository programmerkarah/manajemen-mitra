<?php

namespace Tests\Unit;

use App\Http\Controllers\SpkController;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpkSensusVolumeCalculationTest extends TestCase
{
    #[DataProvider('terminSatuVolumeCases')]
    public function test_termin_satu_volume_uses_expected_rounding_rules(
        int $selectedRows,
        array $perUnitSampelTotals,
        array $unitSampelNames,
        string $expectedLabel,
    ): void {
        $controller = new SpkController;

        $calculateMethod = new \ReflectionMethod(SpkController::class, 'calculateSensusEkonomiMilestoneMetrics');
        $calculateMethod->setAccessible(true);

        $formatMethod = new \ReflectionMethod(SpkController::class, 'formatSensusEkonomiVolumeNarrative');
        $formatMethod->setAccessible(true);

        $terminSatuMetrics = $calculateMethod->invoke($controller, $selectedRows, $perUnitSampelTotals, 40);
        $actualLabel = $formatMethod->invoke(
            $controller,
            $terminSatuMetrics['selected_rows'],
            $terminSatuMetrics['per_unit_sampel_totals'],
            $unitSampelNames,
        );

        $this->assertSame($expectedLabel, $actualLabel);
    }

    /**
     * @return array<string, array{0:int,1:array<int,int>,2:array<int,string>,3:string}>
     */
    public static function terminSatuVolumeCases(): array
    {
        return [
            '4 sls and 40 prelist single unit' => [4, [1 => 40], [1 => 'usaha/keluarga'], '2 SLS/sub-SLS dan/atau 16 usaha/keluarga'],
            '10 sls and 100 prelist single unit' => [10, [1 => 100], [1 => 'usaha/keluarga'], '4 SLS/sub-SLS dan/atau 40 usaha/keluarga'],
            '2 sls and 891 prelist single unit' => [2, [1 => 891], [1 => 'usaha/keluarga'], '1 SLS/sub-SLS dan/atau 356 usaha/keluarga'],
            '3 sls and 933 prelist single unit' => [3, [1 => 933], [1 => 'usaha/keluarga'], '1 SLS/sub-SLS dan/atau 373 usaha/keluarga'],
            '2 sls with keluarga and usaha' => [2, [1 => 10, 2 => 5], [1 => 'Keluarga', 2 => 'Usaha'], '1 SLS/sub-SLS dan/atau 4 Keluarga dan/atau 2 Usaha'],
        ];
    }

    public function test_total_volume_label_uses_only_sls_subsls_count(): void
    {
        $controller = new SpkController;

        $method = new \ReflectionMethod(SpkController::class, 'formatSensusEkonomiTotalSlsVolumeLabel');
        $method->setAccessible(true);

        $this->assertSame('Seluruh Muatan 4 SLS/sub-SLS', $method->invoke($controller, 4));
        $this->assertSame('-', $method->invoke($controller, 0));
    }

    public function test_sensus_spk_number_uses_new_format(): void
    {
        $controller = new SpkController;

        $periode = new PeriodeAlokasi([
            'tahun' => 2026,
        ]);

        $kegiatan = new Kegiatan([
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi',
        ]);

        $periode->setRelation('kegiatan', $kegiatan);

        $method = new \ReflectionMethod(SpkController::class, 'formatNomorSpkForPeriode');
        $method->setAccessible(true);

        $formatted = $method->invoke($controller, $periode, 1);

        $this->assertSame('B-001/SPK-SE2026/1373/PL.200/2026', $formatted);
    }

    public function test_extract_nomor_urut_supports_new_b_prefix_format(): void
    {
        $controller = new SpkController;

        $method = new \ReflectionMethod(SpkController::class, 'extractNomorUrut');
        $method->setAccessible(true);

        $this->assertSame(1, $method->invoke($controller, 'B-001/SPK-SE2026/1373/PL.200/2026'));
    }
}
