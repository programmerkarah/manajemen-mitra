<?php

namespace Tests\Unit;

use App\Http\Controllers\SpkController;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\PeriodeAlokasi;
use App\Services\Spk\SensusEkonomiSpkService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpkSensusVolumeCalculationTest extends TestCase
{
    #[DataProvider('milestoneCases')]
    public function test_milestone_volume_uses_frame_load_threshold(
        int $selectedRows,
        array $frameMuatanTotals,
        int $expectedTermOneRows,
        int $expectedTermTwoRows,
    ): void {
        $service = app(SensusEkonomiSpkService::class);

        $termOne = $service->milestoneMetrics($selectedRows, $frameMuatanTotals, 40);
        $termTwo = $service->milestoneMetrics($selectedRows, $frameMuatanTotals, 60);

        $this->assertSame($expectedTermOneRows, $termOne['selected_rows']);
        $this->assertSame($expectedTermTwoRows, $termTwo['selected_rows']);
        $this->assertSame(
            $expectedTermOneRows > 0 ? $expectedTermOneRows.' SLS/sub-SLS' : '-',
            $service->volumeNarrative($termOne['selected_rows']),
        );
    }

    /**
     * @return array<string, array{0:int,1:array<int,int>,2:int,3:int}>
     */
    public static function milestoneCases(): array
    {
        return [
            'dominant first frame reaches 40 percent' => [4, [60, 20, 15, 5], 1, 3],
            'two frames needed to reach threshold' => [4, [30, 25, 25, 20], 2, 2],
            'balanced frames' => [10, array_fill(0, 10, 10), 4, 6],
            'no frame load falls back to forty percent rows' => [3, [], 2, 1],
        ];
    }

    public function test_total_volume_label_uses_only_sls_subsls_count(): void
    {
        $controller = new SpkController;

        $method = new \ReflectionMethod(SpkController::class, 'formatSensusEkonomiTotalSlsVolumeLabel');

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

        $formatted = $method->invoke($controller, $periode, 1);

        $this->assertSame('B-001/SPK-SE2026/1373/PL.200/2026', $formatted);
    }

    public function test_extract_nomor_urut_supports_new_b_prefix_format(): void
    {
        $controller = new SpkController;

        $method = new \ReflectionMethod(SpkController::class, 'extractNomorUrut');

        $this->assertSame(1, $method->invoke($controller, 'B-001/SPK-SE2026/1373/PL.200/2026'));
    }

    public function test_build_wilayah_kerja_list_includes_prelist_usaha_dan_keluarga(): void
    {
        $controller = new SpkController;

        $alokasi = new AlokasiPetugas;
        $alokasiFrame = new AlokasiPetugasFrameSampel;
        $kegiatanFrame = new KegiatanFrameSampel([
            'identitas_tambahan' => [
                'kdkec' => '010',
                'kdkec_label' => 'Talawi',
                'kddes' => '002',
                'kddes_label' => 'Talawi Mudiak',
            ],
            'target_unit_sampel' => [
                'usaha' => 12,
                'keluarga' => 7,
            ],
        ]);

        $alokasiFrame->setRelation('kegiatanFrameSampel', $kegiatanFrame);
        $alokasi->setRelation('frameSampelAllocations', collect([$alokasiFrame]));

        $method = new \ReflectionMethod(SpkController::class, 'buildWilayahKerjaList');

        $rows = $method->invoke($controller, $alokasi);

        $this->assertCount(1, $rows);
        $this->assertSame('[010] Talawi', $rows[0]['kecamatan']);
        $this->assertSame('[002] Talawi Mudiak', $rows[0]['desa']);
        $this->assertSame(1, $rows[0]['jumlah_sls']);
        $this->assertSame('12 usaha dan 7 keluarga', $rows[0]['muatan_prelist']);
    }
}
