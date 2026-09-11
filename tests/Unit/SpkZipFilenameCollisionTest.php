<?php

namespace Tests\Unit;

use App\Http\Controllers\SpkController;
use App\Models\AlokasiPetugas;
use App\Models\Petugas;
use App\Models\Spk;
use App\Services\SpkActionDecisionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpkZipFilenameCollisionTest extends TestCase
{
    #[Test]
    public function test_build_zip_filename_avoids_collisions_for_same_petugas_and_nomor(): void
    {
        $controller = new SpkController(app(SpkActionDecisionService::class));

        $petugas = new Petugas(['nama' => 'Budi Santoso']);

        $spkOne = new Spk([
            'id' => 10,
            'nomor_spk' => 'PPIS/13730/1/K/2026',
            'addendum_number' => 0,
        ]);
        $alokasiOne = new AlokasiPetugas(['id' => 101]);
        $alokasiOne->setRelation('petugas', $petugas);
        $spkOne->setRelation('alokasiPetugas', $alokasiOne);

        $spkTwo = new Spk([
            'id' => 11,
            'nomor_spk' => 'PPIS/13730/1/K/2026',
            'addendum_number' => 0,
        ]);
        $alokasiTwo = new AlokasiPetugas(['id' => 102]);
        $alokasiTwo->setRelation('petugas', $petugas);
        $spkTwo->setRelation('alokasiPetugas', $alokasiTwo);

        $buildMethod = new \ReflectionMethod(SpkController::class, 'buildZipFilenameForSpk');
        $buildMethod->setAccessible(true);

        $uniqueMethod = new \ReflectionMethod(SpkController::class, 'makeUniqueZipEntryName');
        $uniqueMethod->setAccessible(true);

        $nameOne = $buildMethod->invoke($controller, $spkOne, 'spk-export/2026/05/SPK_1_Budi_Santoso_Mei.pdf');
        $nameTwo = $buildMethod->invoke($controller, $spkTwo, 'spk-export/2026/05/SPK_1_Budi_Santoso_Mei.pdf');

        $usedNames = [];
        $uniqueNameOne = $uniqueMethod->invokeArgs($controller, [$nameOne, &$usedNames]);
        $uniqueNameTwo = $uniqueMethod->invokeArgs($controller, [$nameTwo, &$usedNames]);

        $this->assertNotSame($uniqueNameOne, $uniqueNameTwo);
        $this->assertStringContainsString('Budi Santoso', $uniqueNameOne);
        $this->assertStringContainsString('Budi Santoso', $uniqueNameTwo);
    }
}
