<?php

namespace Tests\Feature;

use App\Imports\AlokasiPetugasImport;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AlokasiPetugasImportTest extends TestCase
{
    use RefreshDatabase;

    private function createKegiatanWithRate(array $attributes = []): array
    {
        $activeYear = ActiveYearService::get();
        $kegiatan = Kegiatan::factory()->create(array_merge([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi',
            'has_listing_updating' => false,
        ], $attributes));

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$activeYear,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $activeYear,
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'status' => 'draft',
        ]);

        return [$kegiatan, $periode];
    }

    public function test_process_rows_applies_factor_for_sensus_economy_imports(): void
    {
        [, $periode] = $this->createKegiatanWithRate();
        $petugas = Petugas::factory()->create([
            'nik' => '1234567890123456',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $import = new AlokasiPetugasImport($periode->id, true);
        $import->processRows(new Collection([
            [
                'nik' => $petugas->nik,
                'kode_penugasan' => 'PCL/PPL',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan_pencacahan' => '4',
                'honor_pencacahan' => '1000',
                'pembayaran_parsial' => 'Tidak',
                'jumlah_satuan_parsial_pencacahan' => '0',
                'catatan' => '',
            ],
        ]));

        $alokasi = AlokasiPetugas::query()->firstOrFail();

        $this->assertSame(2500.0, (float) $alokasi->total_honor);
        $this->assertSame(4.0, (float) $alokasi->jumlah_satuan);
    }

    public function test_process_rows_keeps_non_sensus_honor_from_imported_value(): void
    {
        $activeYear = ActiveYearService::get();
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Survei Harga',
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$activeYear,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1500,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $activeYear,
            'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
            'status' => 'draft',
        ]);

        $petugas = Petugas::factory()->create([
            'nik' => '2234567890123456',
            'status' => 'aktif',
            'jenis_petugas' => 'non-organik',
        ]);

        $import = new AlokasiPetugasImport($periode->id, true);
        $import->processRows(new Collection([
            [
                'nik' => $petugas->nik,
                'kode_penugasan' => 'PCL/PPL',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan_pencacahan' => '4',
                'honor_pencacahan' => '6000',
                'pembayaran_parsial' => 'Tidak',
                'jumlah_satuan_parsial_pencacahan' => '0',
                'catatan' => '',
            ],
        ]));

        $alokasi = AlokasiPetugas::query()->firstOrFail();

        $this->assertSame(6000.0, (float) $alokasi->total_honor);
        $this->assertSame(4.0, (float) $alokasi->jumlah_satuan);
    }
}
