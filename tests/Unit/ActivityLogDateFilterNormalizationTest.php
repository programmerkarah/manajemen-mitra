<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\SystemSettingsController;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ActivityLogDateFilterNormalizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_normalizes_date_from_only_to_use_today_as_end_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 10, 0, 0));

        $controller = new SystemSettingsController(Mockery::mock(DatabaseBackupService::class));
        $method = new \ReflectionMethod(SystemSettingsController::class, 'normalizeActivityLogDateFilters');
        $method->setAccessible(true);

        $filters = $method->invoke($controller, [
            'date_from' => '2026-06-07',
        ]);

        self::assertSame('2026-06-07', $filters['date_from']);
        self::assertSame('2026-06-10', $filters['date_to']);
    }

    public function test_normalizes_date_to_only_to_use_first_day_of_the_same_year_as_start_date(): void
    {
        $controller = new SystemSettingsController(Mockery::mock(DatabaseBackupService::class));
        $method = new \ReflectionMethod(SystemSettingsController::class, 'normalizeActivityLogDateFilters');
        $method->setAccessible(true);

        $filters = $method->invoke($controller, [
            'date_to' => '2026-06-07',
        ]);

        self::assertSame('2026-01-01', $filters['date_from']);
        self::assertSame('2026-06-07', $filters['date_to']);
    }
}
