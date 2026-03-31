<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPulsaMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_pulsa_normalizes_english_month_filter_to_numeric_month(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        PengajuanPulsa::query()->create([
            'petugas_id' => $petugas->id,
            'kegiatan_id' => $kegiatan->id,
            'periode_alokasi_id' => $periode->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_pulsa' => 'pendataan',
            'nominal' => 100000,
            'status' => 'dikirim',
        ]);

        $response = $this->get('/monitoring-pulsa?bulan=March');

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $filters = $page['props']['filters'] ?? [];

        $this->assertSame('03', $filters['bulan'] ?? null);
    }
}
