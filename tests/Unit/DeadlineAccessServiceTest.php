<?php

namespace Tests\Unit;

use App\Models\DeadlineRule;
use App\Services\DeadlineAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeadlineAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_monthly_cutoff_blocks_july_after_june_cutoff_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 26, 10, 0, 0));
        $this->upsertRule('alokasi.manage', 25);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.manage', [
            'year' => 2026,
            'month' => 7,
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertNotNull($result['message']);
    }

    public function test_monthly_cutoff_allows_july_on_cutoff_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 25, 10, 0, 0));
        $this->upsertRule('alokasi.manage', 25);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.manage', [
            'year' => 2026,
            'month' => 7,
        ]);

        $this->assertTrue($result['allowed']);
    }

    public function test_monthly_cutoff_allows_july_before_cutoff_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 24, 10, 0, 0));
        $this->upsertRule('alokasi.manage', 25);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.manage', [
            'year' => 2026,
            'month' => 7,
        ]);

        $this->assertTrue($result['allowed']);
    }

    public function test_bast_cutoff_blocks_september_when_now_is_august_fourth(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 4, 9, 0, 0));
        $this->upsertRule('bast.manage', 3);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('bast.manage', [
            'year' => 2026,
            'month' => 9,
        ]);

        $this->assertFalse($result['allowed']);
    }

    public function test_bast_cutoff_allows_september_on_august_third(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 3, 9, 0, 0));
        $this->upsertRule('bast.manage', 3);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('bast.manage', [
            'year' => 2026,
            'month' => 9,
        ]);

        $this->assertTrue($result['allowed']);
    }

    public function test_revisi_allows_previous_month_on_cutoff_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 9, 0, 0));
        $this->upsertRule('alokasi.revisi', 1);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.revisi', [
            'year' => 2026,
            'month' => 7,
        ]);

        $this->assertTrue($result['allowed']);
    }

    public function test_revisi_allows_current_month_even_after_cutoff_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 9, 0, 0));
        $this->upsertRule('alokasi.revisi', 1);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.revisi', [
            'year' => 2026,
            'month' => 8,
        ]);

        $this->assertTrue($result['allowed']);
    }

    public function test_revisi_blocks_older_than_previous_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 3, 9, 0, 0));
        $this->upsertRule('alokasi.revisi', 1);

        $service = app(DeadlineAccessService::class);
        $result = $service->evaluate('alokasi.revisi', [
            'year' => 2026,
            'month' => 6,
        ]);

        $this->assertFalse($result['allowed']);
    }

    private function upsertRule(string $key, int $cutoffDay): void
    {
        DeadlineRule::query()->updateOrCreate(
            ['key' => $key],
            [
                'feature_key' => explode('.', $key)[0],
                'action_key' => explode('.', $key)[1] ?? 'manage',
                'label' => $key,
                'description' => 'Rule for test',
                'scope_type' => 'monthly',
                'cutoff_day' => $cutoffDay,
                'is_enforced' => true,
                'allow_manual_bypass' => false,
                'deadline_at' => null,
            ],
        );
    }
}
