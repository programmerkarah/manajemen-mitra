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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkShowByMonthPeriodContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_by_month_with_survei_keeps_month_wide_petugas_list_even_when_periode_hash_is_present(): void
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

        $kegiatanA = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei A',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $kegiatanB = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Survei B',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
        ]);

        $petugasA = Petugas::factory()->create([
            'nama' => 'Petugas Survei A',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $petugasB = Petugas::factory()->create([
            'nama' => 'Petugas Survei B',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeA = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanA->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        $periodeB = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanB->id,
            'bulan' => '05',
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'survei',
        ]);

        $alokasiA = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeA->id,
            'petugas_id' => $petugasA->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 120000,
            'total_honor_listing' => 0,
        ]);

        $alokasiB = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periodeB->id,
            'petugas_id' => $petugasB->id,
            'peran' => 'pml',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 1,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 130000,
            'total_honor_listing' => 0,
        ]);

        $spkA = Spk::query()->create([
            'nomor_spk' => 'PK-SURVEI-001',
            'petugas_id' => $petugasA->id,
            'alokasi_petugas_id' => $alokasiA->id,
            'alokasi_petugas_ids' => [$alokasiA->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => "{$tahun}-05-01",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja survei A',
            'nilai_kontrak' => 120000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        Spk::query()->create([
            'nomor_spk' => 'PK-SURVEI-002',
            'petugas_id' => $petugasB->id,
            'alokasi_petugas_id' => $alokasiB->id,
            'alokasi_petugas_ids' => [$alokasiB->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 2,
            'tanggal_spk' => "{$tahun}-05-01",
            'tanggal_mulai_kerja' => "{$tahun}-05-01",
            'tanggal_selesai_kerja' => "{$tahun}-05-31",
            'uraian_pekerjaan' => 'Perjanjian kerja survei B',
            'nilai_kontrak' => 130000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $state = encryptFilters([
            'bulan' => 5,
            'tahun' => $tahun,
            'periode_hashed_id' => $periodeA->hashed_id,
            'spk' => $spkA->hashed_id,
        ]);

        $response = $this->get('/spk/month?state='.urlencode($state));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Spk/ShowByMonth')
            ->where('petugas_list.encrypted', function ($encrypted): bool {
                $petugasList = collect(decryptData($encrypted));

                return $petugasList->pluck('petugas_nama')->contains('Petugas Survei A')
                    && $petugasList->pluck('petugas_nama')->contains('Petugas Survei B');
            })
            ->where('bulan', 5)
            ->where('tahun', $tahun)
        );
    }

    public function test_show_by_month_preserves_period_hash_for_sensus_detail_navigation(): void
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

        $kegiatan = Kegiatan::factory()->create([
            'nama_kegiatan' => 'Sensus Ekonomi 2026',
            'tahun_anggaran' => $tahun,
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Petugas Sensus Ekonomi',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '06',
            'tahun' => $tahun,
            'status' => 'disetujui',
            'jenis_kegiatan' => 'sensus',
        ]);

        $alokasi = AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'jumlah_satuan' => 2,
            'jumlah_satuan_listing' => 0,
            'total_honor' => 400000,
            'total_honor_listing' => 0,
        ]);

        $spk = Spk::query()->create([
            'nomor_spk' => 'PK-SE-001',
            'petugas_id' => $petugas->id,
            'alokasi_petugas_id' => $alokasi->id,
            'alokasi_petugas_ids' => [$alokasi->id],
            'addendum_number' => 0,
            'nomor_urut_base' => 1,
            'tanggal_spk' => "{$tahun}-06-01",
            'tanggal_mulai_kerja' => "{$tahun}-06-01",
            'tanggal_selesai_kerja' => "{$tahun}-06-30",
            'uraian_pekerjaan' => 'Perjanjian kerja sensus ekonomi',
            'nilai_kontrak' => 400000,
            'nama_ppk' => 'PPK Test',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);

        $state = encryptFilters([
            'bulan' => 6,
            'tahun' => $tahun,
            'periode_hashed_id' => $periode->hashed_id,
            'spk' => $spk->hashed_id,
        ]);

        $response = $this->get('/spk/month?state='.urlencode($state));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Spk/ShowByMonth')
            ->where('periode.hashed_id', $periode->hashed_id)
            ->where('bulan', 6)
            ->where('tahun', $tahun)
        );
    }
}
