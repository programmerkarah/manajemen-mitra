<?php

namespace Tests\Unit;

use App\Models\AlokasiPetugas;
use Tests\TestCase;

class AlokasiPetugasEffectiveValuesTest extends TestCase
{
    public function test_returns_original_values_when_partial_is_not_active(): void
    {
        $alokasi = new AlokasiPetugas([
            'jumlah_satuan' => 8,
            'total_honor' => 40000,
            'jumlah_satuan_listing' => 3,
            'total_honor_listing' => 15000,
            'is_partial_payment' => false,
            'is_partial_payment_listing' => false,
        ]);

        $this->assertSame(8.0, $alokasi->getEffectiveJumlahSatuan());
        $this->assertSame(3, $alokasi->getEffectiveJumlahSatuanListing());
        $this->assertSame(40000.0, $alokasi->getEffectiveTotalHonor());
        $this->assertSame(15000.0, $alokasi->getEffectiveTotalHonorListing());
        $this->assertSame(55000.0, $alokasi->getEffectiveCombinedHonor());
    }

    public function test_prefers_partial_values_when_partial_is_active(): void
    {
        $alokasi = new AlokasiPetugas([
            'jumlah_satuan' => 8,
            'total_honor' => 40000,
            'partial_jumlah_satuan' => 2,
            'estimasi_honor_partial' => 10000,
            'jumlah_satuan_listing' => 3,
            'total_honor_listing' => 15000,
            'partial_jumlah_satuan_listing' => 1,
            'estimasi_honor_partial_listing' => 5000,
            'is_partial_payment' => true,
            'is_partial_payment_listing' => true,
        ]);

        $this->assertSame(2.0, $alokasi->getEffectiveJumlahSatuan());
        $this->assertSame(1, $alokasi->getEffectiveJumlahSatuanListing());
        $this->assertSame(10000.0, $alokasi->getEffectiveTotalHonor());
        $this->assertSame(5000.0, $alokasi->getEffectiveTotalHonorListing());
        $this->assertSame(15000.0, $alokasi->getEffectiveCombinedHonor());
    }
}
