<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AnalisisExportControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_petugas_export_pdf_weights_sensus_honor_and_normalizes_months(): void
    {
        $user = User::factory()->admin()->create();
        $previousNow = Carbon::getTestNow();
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0, 0));

        $captured = [];
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('download')->andReturn(response('', 200));
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use (&$captured) {
                $captured = [
                    'view' => $view,
                    'data' => $data,
                ];

                return true;
            })
            ->andReturn($pdfMock);

        try {
            $petugas = Petugas::factory()->create([
                'nama' => 'Petugas Export Sensus',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $petugasBelumDialokasikan = Petugas::factory()->create([
                'nama' => 'Petugas Export Belum Dialokasikan',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $kegiatan = Kegiatan::factory()->create([
                'nama_kegiatan' => 'Sensus Ekonomi Export',
                'jenis_kegiatan' => 'sensus',
                'tahun_anggaran' => 2026,
                'status' => 'aktif',
                'tanggal_mulai' => '2026-06-15',
                'tanggal_selesai' => '2026-08-31',
            ]);

            $periode = PeriodeAlokasi::factory()->create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => '6',
                'tahun' => 2026,
                'status' => 'dikirim',
            ]);

            AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periode->id,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => 'non_organik',
                'total_honor' => 250000,
                'total_honor_listing' => 0,
            ]);

            $response = $this->actingAs($user)
                ->get(route('analisis.petugas.export-pdf'));

            $response->assertOk();

            $this->assertSame('analisis.petugas-pdf', $captured['view']);

            $detail = collect($captured['data']['petugasAlokasiDetail'])->firstWhere('petugas_nama', 'Petugas Export Sensus');
            $alokasiJuni = collect($captured['data']['alokasiPerBulan'])->firstWhere('bulan', 6);
            $alokasiJuli = collect($captured['data']['alokasiPerBulan'])->firstWhere('bulan', 7);
            $alokasiAgustus = collect($captured['data']['alokasiPerBulan'])->firstWhere('bulan', 8);
            $belumDialokasikan = collect($captured['data']['petugasBelumDialokasikan'])->firstWhere('nama', 'Petugas Export Belum Dialokasikan');

            $this->assertNotNull($detail);
            $this->assertEquals(0, $detail['honor'][6]);
            $this->assertEquals(100000, $detail['honor'][7]);
            $this->assertEquals(150000, $detail['honor'][8]);
            $this->assertEquals(250000, $detail['total_honor']);
            $this->assertNotNull($alokasiJuni);
            $this->assertNotNull($alokasiJuli);
            $this->assertNotNull($alokasiAgustus);
            $this->assertSame(1, $alokasiJuni['jumlah_petugas']);
            $this->assertSame(1, $alokasiJuli['jumlah_petugas']);
            $this->assertSame(1, $alokasiAgustus['jumlah_petugas']);
            $this->assertSame(1, $alokasiJuni['jumlah_kegiatan']);
            $this->assertSame(1, $alokasiJuli['jumlah_kegiatan']);
            $this->assertSame(1, $alokasiAgustus['jumlah_kegiatan']);
            $this->assertNotNull($belumDialokasikan);
        } finally {
            Carbon::setTestNow($previousNow);
            \Mockery::close();
        }
    }

    public function test_umum_export_pdf_weights_sensus_honor_in_tren_alokasi(): void
    {
        $user = User::factory()->admin()->create();
        $previousNow = Carbon::getTestNow();
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0, 0));

        $captured = [];
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('download')->andReturn(response('', 200));
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use (&$captured) {
                $captured = [
                    'view' => $view,
                    'data' => $data,
                ];

                return true;
            })
            ->andReturn($pdfMock);

        try {
            $petugas = Petugas::factory()->create([
                'nama' => 'Petugas Export Umum',
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $kegiatan = Kegiatan::factory()->create([
                'nama_kegiatan' => 'Sensus Ekonomi Export Umum',
                'jenis_kegiatan' => 'sensus',
                'tahun_anggaran' => 2026,
                'status' => 'aktif',
                'tanggal_mulai' => '2026-06-15',
                'tanggal_selesai' => '2026-08-31',
                'pagu_pencacahan' => 1000000,
                'pagu_listing' => 0,
            ]);

            foreach ([6, 7, 8] as $bulan) {
                $bulanValue = $bulan === 6 ? '6' : str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

                $periode = PeriodeAlokasi::factory()->create([
                    'kegiatan_id' => $kegiatan->id,
                    'bulan' => $bulanValue,
                    'tahun' => 2026,
                    'status' => 'dikirim',
                ]);

                AlokasiPetugas::factory()->create([
                    'periode_alokasi_id' => $periode->id,
                    'petugas_id' => $petugas->id,
                    'status_kepegawaian' => 'non_organik',
                    'total_honor' => 250000,
                    'total_honor_listing' => 0,
                ]);
            }

            $response = $this->actingAs($user)
                ->get(route('analisis.umum.export-pdf'));

            $response->assertOk();

            $this->assertSame('analisis.umum-pdf', $captured['view']);

            $utilisasi = collect($captured['data']['utilisasiAnggaran'])->firstWhere('nama_kegiatan', 'Sensus Ekonomi Export Umum');
            $trenJuni = collect($captured['data']['trenAlokasi'])->firstWhere('bulan', 6);
            $trenJuli = collect($captured['data']['trenAlokasi'])->firstWhere('bulan', 7);
            $trenAgustus = collect($captured['data']['trenAlokasi'])->firstWhere('bulan', 8);

            $this->assertNotNull($utilisasi);
            $this->assertEquals(250000, $utilisasi['total_terpakai']);
            $this->assertNotNull($trenJuni);
            $this->assertNotNull($trenJuli);
            $this->assertNotNull($trenAgustus);
            $this->assertEquals(50000, $trenJuni['total_honor']);
            $this->assertEquals(100000, $trenJuli['total_honor']);
            $this->assertEquals(100000, $trenAgustus['total_honor']);
        } finally {
            Carbon::setTestNow($previousNow);
            \Mockery::close();
        }
    }
}
