<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkGenerateScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_page_includes_approved_period_petugas_in_same_month(): void
    {
        $approverRole = Role::firstOrCreate(
            ['name' => 'approver'],
            ['display_name' => 'Approver', 'description' => 'Role approver']
        );

        $user = User::factory()->create();
        $user->roles()->attach($approverRole->id);

        $this->actingAs($user)
            ->withSession(['active_role_id' => $approverRole->id, 'active_role_user_id' => $user->id]);

        $tahun = ActiveYearService::get();
        Carbon::setTestNow("{$tahun}-05-10 09:00:00");

        $kegiatanAwyujon = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Survei Awyujon',
        ]);

        $kegiatanSakernas = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Sakernas Mei',
        ]);

        $periodeAwyujon = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujon->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeSakernas = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanSakernas->id,
            'bulan' => '5',
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugasAwyujon = Petugas::factory()->create([
            'nama' => 'awyujon',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasSakernas = Petugas::factory()->create([
            'nama' => 'Sakernas Mei',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $alokasiAwyujon = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujon->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeSakernas->id,
            'petugas_id' => $petugasSakernas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 5,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 500000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/001/K/'.$tahun,
            'petugas_id' => $petugasAwyujon->id,
            'alokasi_petugas_id' => $alokasiAwyujon->id,
            'alokasi_petugas_ids' => [$alokasiAwyujon->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 400000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $response = $this->get('/spk/periode/'.$periodeSakernas->hashed_id.'/generate');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spk/Generate'));

        $petugasList = collect($response->inertiaProps('petugas_list'));

        $names = $petugasList->pluck('petugas.nama')->all();

        $this->assertContains('awyujon', $names);
        $this->assertContains('Sakernas Mei', $names);
        $this->assertCount(2, $names);
    }
}
