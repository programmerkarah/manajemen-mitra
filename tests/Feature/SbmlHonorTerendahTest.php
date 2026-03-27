<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Sbml;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SbmlHonorTerendahTest extends TestCase
{
    use RefreshDatabase;

    public function test_honor_petugas_tidak_boleh_melebihi_sbml_terendah()
    {
        // Setup: Buat petugas, dua jenis penugasan, dua SBML berbeda (satu lebih rendah)
        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);
        $tahun = 2025;
        $bulan = '12';

        // SBML: satu 3.5jt, satu 1.5jt
        Sbml::create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
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

        // Buat dua alokasi pada bulan yang sama di kegiatan berbeda
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
        ]);

        $kegiatanKedua = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);
        DB::table('alokasi_petugas')->insert([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 2000000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $periodeKedua = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanKedua->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        DB::table('alokasi_petugas')->insert([
            'periode_alokasi_id' => $periodeKedua->id,
            'kegiatan_id' => $kegiatanKedua->id,
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1000000,
            'peran' => 'pengolahan',
            'status_kepegawaian' => 'non_organik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Jalankan pengecekan honor (akses endpoint rekap-honor)
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );
        $user = User::factory()->create();
        $user->roles()->attach($adminRole->id);
        $this->actingAs($user)->withSession(['active_role_id' => $adminRole->id]);
        $response = $this->get(route('sbml.report', ['tahun' => $tahun, 'bulan' => $bulan]));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sbml/Report')
            ->has('petugas.encrypted'));
        $data = decryptData($response->inertiaProps('petugas.encrypted'));
        $petugasData = collect($data)->first();
        $this->assertEquals(3000000, $petugasData['total_honor']);
        $this->assertEquals(1500000, $petugasData['max_allowed']);
        $this->assertTrue($petugasData['exceeds']);
    }

    public function test_rekap_honor_mengembalikan_jumlah_satuan_dibayarkan_dengan_fallback_ke_nilai_original(): void
    {
        $petugas = Petugas::factory()->create(['jenis_petugas' => 'non-organik']);
        $tahun = 2025;
        $bulan = '11';

        Sbml::create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 9999999,
            'status' => 'aktif',
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        DB::table('alokasi_petugas')->insert([
            'periode_alokasi_id' => $periode->id,
            'kegiatan_id' => $kegiatan->id,
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'status' => 'draft',
            'jenis_kegiatan' => 'survei',
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 5,
            'partial_jumlah_satuan' => 2,
            'is_partial_payment' => 1,
            'total_honor' => 500000,
            'estimasi_honor_partial' => 200000,
            'jumlah_satuan_listing' => 4,
            'partial_jumlah_satuan_listing' => null,
            'is_partial_payment_listing' => 1,
            'total_honor_listing' => 400000,
            'estimasi_honor_partial_listing' => null,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );
        $user = User::factory()->create();
        $user->roles()->attach($adminRole->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get(route('sbml.report', ['tahun' => $tahun, 'bulan' => $bulan]));

        $response->assertOk();
        $data = decryptData($response->inertiaProps('petugas.encrypted'));

        $petugasData = collect($data)->first();
        $alokasi = collect($petugasData['kegiatan_details'])->first()['alokasi'][0];

        $this->assertEquals(600000, $petugasData['total_honor']);
        $this->assertEquals(2, $alokasi['jumlah_satuan_dibayarkan']);
        $this->assertEquals(4, $alokasi['jumlah_satuan_listing_dibayarkan']);
    }

    public function test_rekap_honor_default_filter_menggunakan_bulan_dan_tahun_sekarang(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Role admin']
        );
        $user = User::factory()->create();
        $user->roles()->attach($adminRole->id);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get(route('sbml.report'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Sbml/Report')
            ->where('filters.decrypted.tahun', (int) date('Y'))
            ->where('filters.decrypted.bulan', str_pad(date('m'), 2, '0', STR_PAD_LEFT))
        );
    }
}
