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

    private function actingAsApprover(): User
    {
        $approverRole = Role::firstOrCreate(
            ['name' => 'approver'],
            ['display_name' => 'Approver', 'description' => 'Role approver']
        );

        $user = User::factory()->create();
        $user->roles()->attach($approverRole->id);

        $this->actingAs($user)
            ->withSession(['active_role_id' => $approverRole->id, 'active_role_user_id' => $user->id]);

        return $user;
    }

    public function test_generate_page_excludes_only_fully_generated_petugas_in_same_month(): void
    {
        $user = $this->actingAsApprover();

        $tahun = ActiveYearService::get();
        Carbon::setTestNow("{$tahun}-05-10 09:00:00");

        $kegiatanAwyujonExisting = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Survei Awyujon Existing',
        ]);

        $kegiatanAwyujonNew = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Survei Awyujon Baru',
        ]);

        $kegiatanTriana = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Survei Triana Putri',
        ]);

        $kegiatanSakernas = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Sakernas Mei',
        ]);

        $periodeAwyujonExisting = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonExisting->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAwyujonNew = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonNew->id,
            'bulan' => '5',
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeTriana = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanTriana->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'disetujui',
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

        $petugasTriana = Petugas::factory()->create([
            'nama' => 'Triana Putri',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasSakernas = Petugas::factory()->create([
            'nama' => 'Sakernas Mei',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $alokasiAwyujonExisting = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonExisting->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        $alokasiAwyujonNew = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonNew->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        $alokasiTriana = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeTriana->id,
            'petugas_id' => $petugasTriana->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
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
            'alokasi_petugas_id' => $alokasiAwyujonExisting->id,
            'alokasi_petugas_ids' => [$alokasiAwyujonExisting->id],
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

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/002/K/'.$tahun,
            'petugas_id' => $petugasTriana->id,
            'alokasi_petugas_id' => $alokasiTriana->id,
            'alokasi_petugas_ids' => [$alokasiTriana->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 2,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja awal',
            'nilai_kontrak' => 300000,
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

        $this->assertContains('Sakernas Mei', $names);
        $this->assertContains('awyujon', $names);
        $this->assertNotContains('Triana Putri', $names);
        $this->assertCount(2, $names);
    }

    public function test_generate_page_excludes_same_month_snapshot_matches_and_petugas_with_addendum(): void
    {
        $user = $this->actingAsApprover();

        $tahun = ActiveYearService::get();
        Carbon::setTestNow("{$tahun}-03-10 09:00:00");

        $triggerKegiatan = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Trigger Maret Generate',
        ]);

        $triggerPeriode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $triggerKegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugasValid = Petugas::factory()->create([
            'nama' => 'Petugas Valid Regenerate',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasAwyujon = Petugas::factory()->create([
            'nama' => 'Awyujon',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasCici = Petugas::factory()->create([
            'nama' => 'Cici Liani Indrias Putri',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $kegiatanValidAwal = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Kegiatan Valid Awal',
        ]);

        $kegiatanValidBaru = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Kegiatan Valid Baru',
        ]);

        $periodeValidAwal = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanValidAwal->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeValidBaru = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanValidBaru->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiValidAwal = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeValidAwal->id,
            'petugas_id' => $petugasValid->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 3,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 300000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/301/K/'.$tahun,
            'petugas_id' => $petugasValid->id,
            'alokasi_petugas_id' => $alokasiValidAwal->id,
            'alokasi_petugas_ids' => [$alokasiValidAwal->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 301,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja awal valid',
            'nilai_kontrak' => 300000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeValidBaru->id,
            'petugas_id' => $petugasValid->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 200000,
            'total_honor_listing' => 0,
        ]);

        $kegiatanAwyujonA = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Awyujon Kegiatan A',
        ]);

        $kegiatanAwyujonB = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Awyujon Kegiatan B',
        ]);

        $kegiatanAwyujonC = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Awyujon Kegiatan C',
        ]);

        $periodeAwyujonA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonA->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAwyujonB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonB->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAwyujonDirevisi = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonC->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'direvisi',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeAwyujonPerubahan = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanAwyujonC->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiAwyujonA = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonA->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 10,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 1100000,
            'total_honor_listing' => 0,
        ]);

        $alokasiAwyujonB = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonB->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 114000,
            'total_honor_listing' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonDirevisi->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 162000,
            'total_honor_listing' => 0,
        ]);

        $alokasiAwyujonPerubahan = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeAwyujonPerubahan->id,
            'petugas_id' => $petugasAwyujon->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 162000,
            'total_honor_listing' => 0,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/302/K/'.$tahun,
            'petugas_id' => $petugasAwyujon->id,
            'alokasi_petugas_id' => $alokasiAwyujonA->id,
            'alokasi_petugas_ids' => [$alokasiAwyujonA->id, $alokasiAwyujonB->id, $alokasiAwyujonPerubahan->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 302,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja Awyujon',
            'nilai_kontrak' => 1376000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $kegiatanCiciA = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Cici Kegiatan A',
        ]);

        $kegiatanCiciB = Kegiatan::factory()->create([
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'nama_kegiatan' => 'Cici Kegiatan B',
        ]);

        $periodeCiciA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanCiciA->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'perubahan',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeCiciB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanCiciB->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'status' => 'dikirim',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiCiciA = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeCiciA->id,
            'petugas_id' => $petugasCici->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 4,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 476000,
            'total_honor_listing' => 0,
        ]);

        $alokasiCiciB = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeCiciB->id,
            'petugas_id' => $petugasCici->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 132000,
            'total_honor_listing' => 0,
        ]);

        $ciciOriginal = Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/303/K/'.$tahun,
            'petugas_id' => $petugasCici->id,
            'alokasi_petugas_id' => $alokasiCiciA->id,
            'alokasi_petugas_ids' => [$alokasiCiciA->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 303,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja Cici',
            'nilai_kontrak' => 476000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'PPIS/13730/303-ADD/K/'.$tahun,
            'petugas_id' => $petugasCici->id,
            'alokasi_petugas_id' => $alokasiCiciB->id,
            'alokasi_petugas_ids' => [$alokasiCiciA->id, $alokasiCiciB->id],
            'parent_spk_id' => $ciciOriginal->id,
            'addendum_number' => 1,
            'nomor_urut_base' => 303,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai_kerja' => now()->endOfMonth()->toDateString(),
            'uraian_pekerjaan' => 'Addendum Cici',
            'nilai_kontrak' => 608000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $response = $this->get('/spk/periode/'.$triggerPeriode->hashed_id.'/generate');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Spk/Generate'));

        $petugasList = collect($response->inertiaProps('petugas_list'));
        $names = $petugasList->pluck('petugas.nama')->all();

        $this->assertContains('Petugas Valid Regenerate', $names);
        $this->assertNotContains('Awyujon', $names);
        $this->assertNotContains('Cici Liani Indrias Putri', $names);
        $this->assertCount(1, $names);
    }
}
