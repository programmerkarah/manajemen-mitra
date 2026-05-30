<?php

namespace Tests\Unit;

use App\Http\Controllers\AlokasiPetugasController;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AlokasiPeriodeRouteResolverPureTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_route_resolver_accepts_kegiatan_hash(): void
    {
        $resolvedKegiatan = new Kegiatan([
            'id' => 10,
            'nama_kegiatan' => 'Survei Harga',
            'jenis_kegiatan' => 'survei',
        ]);
        $resolvedKegiatan->id = 10;

        $controller = Mockery::mock(AlokasiPetugasController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('resolveKegiatanRouteBinding')
            ->once()
            ->with('hashed-kegiatan')
            ->andReturn($resolvedKegiatan);
        $method = new \ReflectionMethod(AlokasiPetugasController::class, 'resolveKegiatanFromPeriodeRoute');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'hashed-kegiatan', 2026, '06');

        self::assertSame($resolvedKegiatan, $result);
    }

    public function test_route_resolver_accepts_periode_hash_for_matching_month(): void
    {
        $resolvedKegiatan = new Kegiatan([
            'id' => 12,
            'nama_kegiatan' => 'Sensus Ekonomi',
            'jenis_kegiatan' => 'sensus',
        ]);
        $resolvedKegiatan->id = 12;

        $resolvedPeriode = new PeriodeAlokasi([
            'id' => 99,
            'bulan' => '06',
            'tahun' => 2026,
        ]);
        $resolvedPeriode->id = 99;
        $resolvedPeriode->setRelation('kegiatan', $resolvedKegiatan);

        $controller = Mockery::mock(AlokasiPetugasController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('resolveKegiatanRouteBinding')
            ->once()
            ->with('hashed-periode')
            ->andReturn(null);

        $controller->shouldReceive('resolvePeriodeRouteBinding')
            ->once()
            ->with('hashed-periode')
            ->andReturn($resolvedPeriode);
        $method = new \ReflectionMethod(AlokasiPetugasController::class, 'resolveKegiatanFromPeriodeRoute');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'hashed-periode', 2026, '06');

        self::assertSame($resolvedKegiatan, $result);
    }
}
