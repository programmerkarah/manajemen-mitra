<?php

namespace Tests\Unit;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Services\SpkActionDecisionService;
use Tests\TestCase;

class SpkAddendumTotalHonorAggregationTest extends TestCase
{
    public function test_effective_alokasi_prefers_perubahan_and_keeps_non_perubahan_kegiatan(): void
    {
        $service = app(SpkActionDecisionService::class);

        $alokasiGroup = collect([
            $this->makeAlokasi(10, 100, 'direvisi', 500, 0),
            $this->makeAlokasi(10, 101, 'perubahan', 1000, 0),
            $this->makeAlokasi(20, 200, 'dikirim', 2000, 0),
        ]);

        $effective = $service->getEffectiveAlokasiByKegiatan($alokasiGroup);

        $this->assertCount(2, $effective);

        $totalHonor = $effective->sum(
            fn (AlokasiPetugas $alokasi): float =>
                (float) $alokasi->total_honor
                + (float) $alokasi->total_honor_listing,
        );

        $this->assertSame(3000.0, (float) $totalHonor);
        $this->assertSame('perubahan', $effective->get(10)?->periodeAlokasi?->status);
        $this->assertSame('dikirim', $effective->get(20)?->periodeAlokasi?->status);
    }

    private function makeAlokasi(
        int $kegiatanId,
        int $periodeId,
        string $status,
        float $totalHonor,
        float $totalHonorListing,
        int $jumlahSatuan = 1,
        int $jumlahSatuanListing = 0,
    ): AlokasiPetugas {
        $periode = new PeriodeAlokasi([
            'id' => $periodeId,
            'kegiatan_id' => $kegiatanId,
            'status' => $status,
        ]);
        $periode->id = $periodeId;

        $alokasi = new AlokasiPetugas([
            'jumlah_satuan' => $jumlahSatuan,
            'jumlah_satuan_listing' => $jumlahSatuanListing,
            'total_honor' => $totalHonor,
            'total_honor_listing' => $totalHonorListing,
        ]);
        $alokasi->setRelation('periodeAlokasi', $periode);

        return $alokasi;
    }
}
