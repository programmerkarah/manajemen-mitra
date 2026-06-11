<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Sbml;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AlokasiPartialValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminUser(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => '']
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    private function setupKegiatanWithRateHonor(int $tahun): array
    {
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        $rateHonor = RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1083000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 3455000,
            'status' => 'aktif',
        ]);

        return [$kegiatan, $rateHonor];
    }

    private function setupSensusEkonomiKegiatanWithRateHonor(int $tahun): array
    {
        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'sensus',
            'nama_kegiatan' => 'Sensus Ekonomi',
            'tahun_anggaran' => $tahun,
            'tanggal_mulai' => "$tahun-01-01",
            'tanggal_selesai' => "$tahun-12-31",
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'SE-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'PCL',
            'jenis_kegiatan' => 'sensus',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate sensus ekonomi',
            'satuan_id' => $satuan->id,
            'rate' => 1000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $tahun,
            'jenis_kegiatan' => 'sensus',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 5000000,
            'status' => 'aktif',
        ]);

        return [$kegiatan];
    }

    private function createSpkForAlokasi(AlokasiPetugas $alokasi, User $user, int $sequence = 1): Spk
    {
        return Spk::query()->create([
            'nomor_spk' => sprintf('SPK/%d/%04d', $sequence, $alokasi->id),
            'petugas_id' => $alokasi->petugas_id,
            'alokasi_petugas_id' => $alokasi->id,
            'tanggal_spk' => now()->toDateString(),
            'tanggal_mulai_kerja' => now()->toDateString(),
            'tanggal_selesai_kerja' => now()->addDays(7)->toDateString(),
            'uraian_pekerjaan' => 'Perjanjian kerja pengumpulan data',
            'nilai_kontrak' => $alokasi->total_honor,
            'nama_ppk' => 'Pejabat Pembuat Komitmen',
            'nip_ppk' => '198001012010011001',
            'status' => 'diterbitkan',
            'created_by' => $user->id,
        ]);
    }

    public function test_store_multiple_rejects_partial_workload_above_original_workload(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $payload = [
            'tanggal_mulai' => "$tahun-03-01",
            'tanggal_selesai' => "$tahun-03-31",
            'alokasi' => [
                [
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 5,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => true,
                    'partial_jumlah_satuan' => 6,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", $payload);

        $response->assertSessionHasErrors(['partial_validation']);
    }

    public function test_store_multiple_uses_partial_honor_for_monthly_sbml_validation(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);
        $kegiatanExisting = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeExisting = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanExisting->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeExisting->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1376000,
            'is_partial_payment' => false,
            'estimasi_honor_partial' => null,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $payload = [
            'tanggal_mulai' => "$tahun-03-01",
            'tanggal_selesai' => "$tahun-03-31",
            'alokasi' => [
                [
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 2,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => true,
                    'partial_jumlah_satuan' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", $payload);

        $response->assertSessionDoesntHaveErrors(['sbml_constraint']);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alokasi_petugas', [
            'petugas_id' => $petugas->id,
            'is_partial_payment' => 1,
            'partial_jumlah_satuan' => 1,
            'estimasi_honor_partial' => 1083000,
            'total_honor' => 2166000,
        ]);
    }

    public function test_store_multiple_uses_fixed_honor_for_sensus_economy(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupSensusEkonomiKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $payload = [
            'tanggal_mulai' => "$tahun-03-01",
            'tanggal_selesai' => "$tahun-03-31",
            'alokasi' => [
                [
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 3044860,
                    'jenis_kegiatan' => 'sensus',
                    'tahapan' => 'pencacahan_only',
                    'is_partial_payment' => false,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/kegiatan/{$kegiatan->hashed_id}/store-multiple", $payload);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alokasi_petugas', [
            'petugas_id' => $petugas->id,
            'total_honor' => 2500,
            'jumlah_satuan' => 3044860,
        ]);
    }

    public function test_update_periode_rejects_combined_partial_honor_above_monthly_sbml_limit(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $kegiatanExisting = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'pagu_pencacahan' => 100000000,
            'has_listing_updating' => false,
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeExisting = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatanExisting->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeExisting->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1500000,
            'is_partial_payment' => false,
            'estimasi_honor_partial' => null,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $existingCurrentAlokasi = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1000000,
            'is_partial_payment' => false,
            'estimasi_honor_partial' => null,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $payload = [
            'tanggal_mulai' => "$tahun-03-01",
            'tanggal_selesai' => "$tahun-03-31",
            'alokasi' => [
                [
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 2,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => true,
                    'partial_jumlah_satuan' => 1,
                ],
                [
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 3,
                    'tahun' => $tahun,
                    'jumlah_satuan' => 2,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => true,
                    'partial_jumlah_satuan' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->put("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03", $payload);

        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('alokasi_petugas', [
            'id' => $existingCurrentAlokasi->id,
            'periode_alokasi_id' => $periode->id,
            'total_honor' => 1000000,
            'is_partial_payment' => 0,
        ]);
        $this->assertSame(1, AlokasiPetugas::query()->where('periode_alokasi_id', $periode->id)->count());
    }

    public function test_edit_periode_returns_full_honor_and_partial_fields_for_existing_partial_allocation(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
            'tahapan' => 'both',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 2,
            'total_honor' => 2166000,
            'is_partial_payment' => true,
            'partial_jumlah_satuan' => 1,
            'estimasi_honor_partial' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03/edit");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Alokasi/Create')
            ->where('isEditMode', true)
            ->where('selectedKegiatan.rate_honors.0.sbml_limit', fn ($value) => (float) $value === 3455000.0)
            ->has('copiedAlokasi', 1)
            ->where('copiedAlokasi.0.total_honor', fn ($value) => (float) $value === 2166000.0)
            ->where('copiedAlokasi.0.is_partial_payment', true)
            ->where('copiedAlokasi.0.partial_jumlah_satuan', 1)
            ->where('copiedAlokasi.0.estimasi_honor_partial', fn ($value) => (float) $value === 1083000.0)
        );
    }

    public function test_edit_periode_includes_dynamic_unit_sampel_items_for_sensus_ekonomi(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupSensusEkonomiKegiatanWithRateHonor($tahun);

        $keluarga = MasterUnitSampel::query()->create([
            'nama' => 'keluarga',
            'kode' => 'KLG',
            'deskripsi' => 'Unit keluarga',
            'is_active' => true,
        ]);

        $usaha = MasterUnitSampel::query()->create([
            'nama' => 'usaha',
            'kode' => 'USH',
            'deskripsi' => 'Unit usaha',
            'is_active' => true,
        ]);

        $kegiatan->update([
            'unit_sampel_pencacahan_ids' => [$keluarga->id, $usaha->id],
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'sensus',
            'status' => 'draft',
            'tahapan' => 'both',
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'jumlah_unit_sampel' => 588,
            'total_honor' => 2500,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03/edit");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Alokasi/Create')
            ->where('isEditMode', true)
            ->where('selectedKegiatan.unit_sampel_pencacahan_items.0.nama', 'keluarga')
            ->where('selectedKegiatan.unit_sampel_pencacahan_items.1.nama', 'usaha')
        );
    }

    public function test_destroy_periode_allows_admin_to_cancel_draft_periode_when_spk_not_generated(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $alokasi = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->delete("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03");

        $response->assertRedirect(route('alokasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('periode_alokasi', [
            'id' => $periode->id,
        ]);
        $this->assertDatabaseMissing('alokasi_petugas', [
            'id' => $alokasi->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Batalkan Alokasi Periode',
            'type' => 'alokasi',
            'status' => 'success',
        ]);
    }

    public function test_destroy_periode_rejects_when_periode_is_not_draft(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'tahapan' => 'listing_only',
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->delete("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03");

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('periode_alokasi', [
            'id' => $periode->id,
            'status' => 'dikirim',
        ]);
    }

    public function test_destroy_periode_still_allows_draft_cancel_when_spk_exists_in_different_month_for_same_petugas(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeJanuari = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
        ]);

        $alokasiJanuari = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeJanuari->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $this->createSpkForAlokasi($alokasiJanuari, $admin);

        $periodeApril = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '04',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $alokasiApril = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeApril->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->delete("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/04");

        $response->assertRedirect(route('alokasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('periode_alokasi', [
            'id' => $periodeApril->id,
        ]);
        $this->assertDatabaseMissing('alokasi_petugas', [
            'id' => $alokasiApril->id,
        ]);
    }

    public function test_kembalikan_ke_draft_allows_admin_when_no_spk_has_been_generated(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'submitted_at' => now(),
            'submitted_by' => $admin->id,
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // No SPK created yet for any officer

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03/kembalikan-draft");

        $response->assertRedirect(route('alokasi.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('periode_alokasi', [
            'id' => $periode->id,
            'status' => 'draft',
            'submitted_at' => null,
            'submitted_by' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Kembalikan Alokasi ke Draft',
            'type' => 'alokasi',
            'status' => 'success',
        ]);
    }

    public function test_kembalikan_ke_draft_rejects_when_spk_has_been_generated_and_logs_activity(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
        ]);

        $alokasi = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        // SPK exists (regardless of file_path) → cannot revert
        $this->createSpkForAlokasi($alokasi, $admin);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/03/kembalikan-draft");

        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('periode_alokasi', [
            'id' => $periode->id,
            'status' => 'dikirim',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Kembalikan Alokasi ke Draft',
            'type' => 'alokasi',
            'status' => 'warning',
        ]);
    }

    public function test_kembalikan_ke_draft_allows_when_spk_exists_only_in_different_month(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periodeJanuari = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '01',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
        ]);

        $alokasiJanuari = AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeJanuari->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $this->createSpkForAlokasi($alokasiJanuari, $admin);

        $periodeFebruari = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '02',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periodeFebruari->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 1083000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post("/alokasi/periode/{$kegiatan->hashed_id}/{$tahun}/02/kembalikan-draft");

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('periode_alokasi', [
            'id' => $periodeFebruari->id,
            'status' => 'draft',
        ]);
    }

    public function test_show_periode_returns_paid_workload_and_rate_honor_from_master_rate(): void
    {
        [$admin, $adminRole] = $this->makeAdminUser();
        $tahun = ActiveYearService::get();
        [$kegiatan, $rateHonor] = $this->setupKegiatanWithRateHonor($tahun);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '03',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
        ]);

        AlokasiPetugas::factory()->create(['periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 5,
            'total_honor' => 5415000,
            'is_partial_payment' => true,
            'partial_jumlah_satuan' => 2,
            'estimasi_honor_partial' => 2166000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->get(route('alokasi.periode.show', [
                'kegiatan' => $kegiatan->hashed_id,
                'tahun' => $tahun,
                'bulan' => '03',
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Alokasi/ShowPeriode')
            ->where('periode.alokasi_petugas.0.jumlah_satuan', 5)
            ->where('periode.alokasi_petugas.0.jumlah_satuan_dibayarkan', 2)
            ->where('periode.alokasi_petugas.0.rate_pencacahan', fn ($value) => (float) $value === (float) $rateHonor->rate)
            ->where('periode.alokasi_petugas.0.total_honor', fn ($value) => (float) $value === 2166000.0)
        );
    }
}
