<?php

namespace Tests;

use App\Http\Middleware\EnforceFeatureDeadlines;
use App\Http\Middleware\EnsureSingleActiveSession;
use App\Http\Middleware\PreventMaintenanceModeRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->assertSafeTestingDatabaseEnvironment();

        parent::setUp();

        $this->withoutVite();
        Cache::flush();

        // Native-auth tests must not inherit production SSO configuration.
        // SSO-specific tests opt in explicitly with config()->set(...).
        config()->set('services.sso.base_url', '');
        config()->set('services.sso.client_id', null);

        $this->withoutMiddleware([
            EnforceFeatureDeadlines::class,
            EnsureSingleActiveSession::class,
            PreventMaintenanceModeRequests::class,
        ]);

        // Disable foreign key checks for SQLite in tests
        if (DB::getDriverName() === 'sqlite') {
            if (! DB::getSchemaBuilder()->hasTable('users')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            DB::statement('PRAGMA foreign_keys=OFF');
        }
    }

    private function assertSafeTestingDatabaseEnvironment(): void
    {
        $database = (string) (getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? ''));

        // PHPUnit configuration must point to a disposable database before
        // Laravel boots and RefreshDatabase is allowed to run.
        if ($database !== '' && ! str_contains(strtolower($database), 'test')) {
            throw new \RuntimeException(
                'TEST ABORTED: DB_DATABASE must be a dedicated testing database. Current value: '.$database
            );
        }
    }

    protected function tearDown(): void
    {
        // Re-enable foreign key checks
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        parent::tearDown();
    }
}
