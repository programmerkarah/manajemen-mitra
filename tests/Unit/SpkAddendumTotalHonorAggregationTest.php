<?php

namespace Tests\Unit;

use App\Http\Controllers\SpkController;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SpkAddendumTotalHonorAggregationTest extends TestCase
{
    public function test_effective_alokasi_prefers_perubahan_and_keeps_other_kegiatan(): void
    {
        $controller = new SpkController;

        $alokasiGroup = collect([
            $this->makeAlokasi(
                kegiatanId: 10,
                periodeId: 100,
                status: 'direvisi',
                totalHonor: 500,
                totalHonorListing: 0,
            ),
            $this->makeAlokasi(
                kegiatanId: 10,
                periodeId: 101,
                status: 'perubahan',
                totalHonor: 1000,
                totalHonorListing: 0,
            ),
            $this->makeAlokasi(
                kegiatanId: 20,
                periodeId: 200,
                status: 'direvisi',
                totalHonor: 2000,
                totalHonorListing: 0,
            ),
        ]);

        $method = new \ReflectionMethod(SpkController::class, 'getEffectiveAlokasiByKegiatan');
        $method->setAccessible(true);

        /** @var Collection<int, object> $effective */
        $effective = $method->invoke($controller, $alokasiGroup);

        $this->assertCount(2, $effective);

        $totalHonor = $effective->sum(function ($alokasi) {
            return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
        });

        $this->assertSame(3000.0, (float) $totalHonor);
        $this->assertSame('perubahan', $effective->firstWhere('periodeAlokasi.kegiatan_id', 10)->periodeAlokasi->status);
        $this->assertSame('direvisi', $effective->firstWhere('periodeAlokasi.kegiatan_id', 20)->periodeAlokasi->status);
    }

    private function makeAlokasi(
        int $kegiatanId,
        int $periodeId,
        string $status,
        float $totalHonor,
        float $totalHonorListing,
    ): object {
        return (object) [
            'total_honor' => $totalHonor,
            'total_honor_listing' => $totalHonorListing,
            'periodeAlokasi' => (object) [
                'id' => $periodeId,
                'kegiatan_id' => $kegiatanId,
                'status' => $status,
            ],
        ];
    }
}
