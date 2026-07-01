<?php

namespace Tests\Unit;

use App\Http\Controllers\AlokasiPetugasController;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AlokasiPeriodeRouteResolverPureTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_merge_alokasi_rows_normalizes_unpadded_months_before_grouping(): void
    {
        $controller = new AlokasiPetugasController;
        $method = new \ReflectionMethod(AlokasiPetugasController::class, 'mergeAlokasiRowsForStorage');
        $method->setAccessible(true);

        $rows = $method->invoke($controller, [
            [
                'petugas_id' => 10,
                'peran' => 'PCL',
                'bulan' => 6,
                'tahun' => 2026,
                'jenis_kegiatan' => 'survei',
                'tahapan' => 'both',
                'frame_sampel_ids' => [1, 2],
            ],
            [
                'petugas_id' => 10,
                'peran' => 'PCL',
                'bulan' => '06',
                'tahun' => 2026,
                'jenis_kegiatan' => 'survei',
                'tahapan' => 'both',
                'frame_sampel_ids' => [2, 3],
            ],
        ]);

        self::assertCount(1, $rows);
        self::assertSame('06', $rows[0]['bulan']);
        self::assertSame([1, 2, 3], $rows[0]['frame_sampel_ids']);
    }

    public function test_sort_alokasi_index_data_orders_by_year_then_month_descending(): void
    {
        $controller = new AlokasiPetugasController;
        $method = new \ReflectionMethod(AlokasiPetugasController::class, 'sortAlokasiIndexData');
        $method->setAccessible(true);

        $sorted = $method->invoke($controller, new Collection([
            [
                'tahun' => 2026,
                'bulan' => '06',
                'latest_created_at' => '2026-06-01 10:00:00',
                'nama' => 'Juni',
            ],
            [
                'tahun' => 2025,
                'bulan' => '12',
                'latest_created_at' => '2025-12-01 10:00:00',
                'nama' => 'Desember',
            ],
            [
                'tahun' => 2026,
                'bulan' => '10',
                'latest_created_at' => '2026-10-01 10:00:00',
                'nama' => 'Oktober',
            ],
            [
                'tahun' => 2026,
                'bulan' => '02',
                'latest_created_at' => '2026-02-01 10:00:00',
                'nama' => 'Februari',
            ],
        ]));

        self::assertSame(['Oktober', 'Juni', 'Februari', 'Desember'], $sorted->pluck('nama')->all());
    }

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
