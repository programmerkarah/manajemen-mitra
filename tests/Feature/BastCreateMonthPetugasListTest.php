<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BastCreateMonthPetugasListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_bast_month_includes_petugas_from_latest_monthly_spk_addendum(): void
    {
        $this->withoutMiddleware();

        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeJanuari = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeMaret = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugasNames = [
            'Cici Liani Indrias Putri',
            'Miranda Meliana',
            'Nurlena Rustam',
        ];

        $creator = User::factory()->create();

        foreach ($petugasNames as $index => $namaPetugas) {
            $petugas = Petugas::factory()->create([
                'nama' => $namaPetugas,
                'jenis_petugas' => 'non-organik',
                'status' => 'aktif',
            ]);

            $alokasiJanuari = AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeJanuari->id,
                'petugas_id' => $petugas->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 4,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 400000,
                'total_honor_listing' => 0,
            ]);

            $alokasiMaret = AlokasiPetugas::factory()->create([
                'periode_alokasi_id' => $periodeMaret->id,
                'petugas_id' => $petugas->id,
                'peran' => 'pcl_ppl',
                'status_kepegawaian' => 'non_organik',
                'jumlah_satuan' => 6,
                'jumlah_satuan_listing' => 0,
                'total_honor' => 600000,
                'total_honor_listing' => 0,
            ]);

            $originalSpk = Spk::query()->create([
                'nomor_spk' => sprintf('SPK/ORI/%d', $index + 1),
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasiJanuari->id,
                'addendum_number' => 0,
                'nomor_urut_base' => $index + 1,
                'tanggal_spk' => now()->startOfYear()->toDateString(),
                'tanggal_mulai_kerja' => now()->startOfYear()->toDateString(),
                'tanggal_selesai_kerja' => now()->startOfYear()->addDays(10)->toDateString(),
                'uraian_pekerjaan' => 'Perjanjian kerja awal',
                'nilai_kontrak' => 400000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '198001012010011001',
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);

            Spk::query()->create([
                'nomor_spk' => sprintf('SPK/ADD/%d', $index + 1),
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $alokasiMaret->id,
                'parent_spk_id' => $originalSpk->id,
                'addendum_number' => 1,
                'nomor_urut_base' => $index + 1,
                'tanggal_spk' => now()->setMonth(3)->toDateString(),
                'tanggal_mulai_kerja' => now()->setMonth(3)->startOfMonth()->toDateString(),
                'tanggal_selesai_kerja' => now()->setMonth(3)->endOfMonth()->toDateString(),
                'uraian_pekerjaan' => 'Addendum kerja',
                'nilai_kontrak' => 600000,
                'nama_ppk' => 'PPK Test',
                'nip_ppk' => '198001012010011001',
                'status' => 'diterbitkan',
                'created_by' => $creator->id,
            ]);
        }

        $response = $this->get('/bast/create?bulan=3&tahun='.$tahun);

        $response->assertStatus(200);

        $page = $response->viewData('page');
        $spkList = decryptData($page['props']['spk_list']['encrypted'] ?? null);
        $petugasDalamDaftar = collect($spkList)->pluck('petugas.nama')->all();

        foreach ($petugasNames as $expectedName) {
            $this->assertContains($expectedName, $petugasDalamDaftar);
        }
    }
}
