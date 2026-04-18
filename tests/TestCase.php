<?php

namespace Tests;

use App\Http\Middleware\EnsureSingleActiveSession;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->withoutMiddleware(EnsureSingleActiveSession::class);

        // Disable foreign key checks for SQLite in tests
        if (DB::getDriverName() === 'sqlite') {
            if (! DB::getSchemaBuilder()->hasTable('users')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            DB::statement('PRAGMA foreign_keys=OFF');
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
