<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkPreviewAllDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_all_download_returns_zip_without_generating_spk(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'divalidasi',
            'nama_kegiatan' => 'Survei Konsumsi Rumah Tangga',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Rina Preview',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'total_honor' => 250000,
            'jumlah_satuan_listing' => 0,
            'total_honor_listing' => 0,
        ]);

        Penandatangan::query()->create([
            'nama' => 'PPK Uji',
            'nip' => '198001012010011001',
            'jenis_penandatangan' => 'ppk',
            'jabatan' => 'PPK',
            'periode_mulai' => now()->subYear()->toDateString(),
            'periode_selesai' => now()->addYear()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->post('/spk/periode/'.$periode->hashed_id.'/preview-all', [
            'tanggal_spk' => $tahun.'-03-10',
            'preview_items_json' => json_encode([
                [
                    'petugas_hashed_id' => $petugas->hashed_id,
                    'nomor_spk' => 'PPIS/13730/1/K/'.$tahun,
                ],
            ]),
        ]);

        $response->assertOk();
        $response->assertDownload('Preview_SPK_Maret_'.$tahun.'.zip');
        $response->assertHeader('content-type', 'application/zip');
    }
}
