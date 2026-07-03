<?php

namespace Tests\Unit;

use App\Http\Controllers\AlokasiPetugasController;
use App\Models\RateHonor;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class AlokasiPetugasControllerPeranTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_resolve_peran_code_uses_jenis_penugasan(): void
    {
        $controller = new AlokasiPetugasController;
        $method = $this->resolvePeranMethod($controller);

        $rateHonor = new RateHonor([
            'jenis_penugasan' => 'pml',
            'posisi' => 'Survei Example - Non-Organik - PCL/PPL',
        ]);

        $this->assertSame('pml', $method->invoke($controller, $rateHonor));
    }

    public function test_resolve_peran_code_falls_back_to_pcl_ppl_for_invalid_values(): void
    {
        $controller = new AlokasiPetugasController;
        $method = $this->resolvePeranMethod($controller);

        $rateHonor = new RateHonor([
            'jenis_penugasan' => 'PML-LAMA',
            'posisi' => 'Survei Example - Non-Organik - PML',
        ]);

        $this->assertSame('pcl_ppl', $method->invoke($controller, $rateHonor));
    }

    private function resolvePeranMethod(AlokasiPetugasController $controller): \ReflectionMethod
    {
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('resolvePeranCodeFromRateHonor');
        $method->setAccessible(true);

        return $method;
    }
}
