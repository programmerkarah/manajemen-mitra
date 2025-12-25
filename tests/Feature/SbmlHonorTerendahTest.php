<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Sbml;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SbmlHonorTerendahTest extends TestCase
{
    use RefreshDatabase;

    public function test_honor_petugas_tidak_boleh_melebihi_sbml_terendah()
    {
        // Setup: Buat petugas, dua jenis penugasan, dua SBML berbeda (satu lebih rendah)
        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non_organik']);
        $tahun = 2025;
        $bulan = '12';

        // SBML: satu 3.5jt, satu 1.5jt
        Sbml::create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'lapangan',
            'honor_max' => 3500000,
            'status' => 'aktif',
        ]);
        Sbml::create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pengolahan',
            'honor_max' => 1500000,
            'status' => 'aktif',
        ]);

        // Buat dua alokasi untuk petugas tsb di bulan sama, honor total 3jt (masih di bawah 3.5jt, tapi di atas 1.5jt)
        $periode = PeriodeAlokasi::factory()->create([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);
        AlokasiPetugas::create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 2000000,
            'peran' => 'lapangan',
            'status_kepegawaian' => 'non_organik',
        ]);
        AlokasiPetugas::create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1000000,
            'peran' => 'pengolahan',
            'status_kepegawaian' => 'non_organik',
        ]);

        // Jalankan pengecekan honor (akses endpoint rekap-honor)
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('sbml.report', ['tahun' => $tahun, 'bulan' => $bulan]));
        $response->assertStatus(200);
        $data = $response->viewData('petugas');
        $petugasData = collect($data)->first();
        $this->assertEquals(3000000, $petugasData['total_honor']);
        $this->assertEquals(1500000, $petugasData['max_allowed']);
        $this->assertTrue($petugasData['exceeds']);
    }
}
