<?php

namespace Tests\Feature;

use App\Http\Controllers\SpkController;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Vinkla\Hashids\Facades\Hashids;

class SpkPreviewRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_route_handles_month_stored_as_integer_and_returns_pdf(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $petugas = Petugas::factory()->create([
            'nama' => 'Preview Petugas',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        Carbon::setTestNow("{$tahun}-04-03 09:00:00");

        $periodeDikirim = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 4,
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiOriginal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeDikirim->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow("{$tahun}-04-10 09:00:00");

        $periodePerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => 4,
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodePerubahan->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        Carbon::setTestNow();

        $scopePeriodeIds = PeriodeAlokasi::query()
            ->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", ['04'])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        $this->assertNotEmpty(Hashids::decode($periodeDikirim->hashed_id));
        $this->assertNotEmpty(Hashids::decode($petugas->hashed_id));
        $this->assertGreaterThan(0, $scopePeriodeIds->count());
        $this->assertGreaterThan(
            0,
            AlokasiPetugas::query()
                ->whereIn('periode_alokasi_id', $scopePeriodeIds)
                ->where('petugas_id', $petugas->id)
                ->count(),
        );

        $controller = app(SpkController::class);
        $reflection = new \ReflectionMethod($controller, 'resolveSpkScopePeriodeIds');
        $reflection->setAccessible(true);
        $controllerScopePeriodeIds = $reflection->invoke($controller, $periodeDikirim, ['dikirim', 'perubahan']);

        $this->assertGreaterThan(0, $controllerScopePeriodeIds->count());

        $response = $this->post('/spk/periode/'.$periodeDikirim->hashed_id.'/petugas/'.$petugas->hashed_id.'/preview', [
            'nomor_spk' => 'SPK/TEST/001',
            'tanggal_spk' => "{$tahun}-04-11",
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
