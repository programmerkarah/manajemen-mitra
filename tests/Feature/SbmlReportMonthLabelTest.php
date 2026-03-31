<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SbmlReportMonthLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_honor_uses_indonesian_month_labels_for_filter(): void
    {
        $this->withoutMiddleware();

        $response = $this->get('/rekap-honor');

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $bulanOptions = $page['props']['bulan_options'] ?? [];

        $maret = collect($bulanOptions)->first(function (array $item) {
            return ($item['value'] ?? null) === '03';
        });

        $this->assertNotNull($maret);
        $this->assertSame('Maret', $maret['label'] ?? null);
    }
}
