<?php

namespace Tests\Unit;

use App\Http\Controllers\BastController;
use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use ReflectionMethod;
use Tests\TestCase;

class BastControllerDateSelectionTest extends TestCase
{
    private function callGetAlokasiLatestTanggalSelesai(AlokasiPetugas $alokasi): ?string
    {
        $controller = new BastController;
        $method = new ReflectionMethod(BastController::class, 'getAlokasiLatestTanggalSelesai');
        $method->setAccessible(true);

        return $method->invoke($controller, $alokasi);
    }

    public function test_pendataan_uses_latest_date_across_listing_and_pencacahan(): void
    {
        $periode = new PeriodeAlokasi([
            'tanggal_selesai' => '2026-02-28',
            'tanggal_selesai_listing' => '2026-02-07',
        ]);

        $alokasi = new AlokasiPetugas([
            'peran' => 'pcl_ppl',
            'jumlah_satuan' => 30,
            'jumlah_satuan_listing' => 1,
        ]);
        $alokasi->setRelation('periodeAlokasi', $periode);

        $latestDate = $this->callGetAlokasiLatestTanggalSelesai($alokasi);

        $this->assertSame('2026-02-28', $latestDate);
    }

    public function test_pengolahan_uses_latest_date_across_listing_and_pencacahan(): void
    {
        $periode = new PeriodeAlokasi([
            'jadwal_pengolahan_pencacahan_selesai' => '2026-03-20',
            'jadwal_pengolahan_listing_selesai' => '2026-03-25',
        ]);

        $alokasi = new AlokasiPetugas([
            'peran' => 'pengolahan',
            'jumlah_satuan' => 20,
            'jumlah_satuan_listing' => 15,
        ]);
        $alokasi->setRelation('periodeAlokasi', $periode);

        $latestDate = $this->callGetAlokasiLatestTanggalSelesai($alokasi);

        $this->assertSame('2026-03-25', $latestDate);
    }
}
