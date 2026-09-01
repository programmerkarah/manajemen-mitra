<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Sbml;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiEditRevisionSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): array
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => ucfirst(str_replace('_', ' ', $roleName)),
                'description' => '',
            ]
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_revision_post_rejects_draft_periode_hash(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'has_listing_updating' => false,
        ]);

        $draftPeriode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
            'revision_number' => 0,
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Fitri Yati',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $draftPeriode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 150000,
            'peran' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_id' => $adminRole->id])
            ->post('/alokasi/periode/'.$draftPeriode->hashed_id.'/'.$activeYear.'/07/revisi');

        $response->assertRedirect('/alokasi');
        $response->assertSessionHas('error', 'Revisi hanya dapat dilakukan untuk alokasi berstatus dikirim.');
        $response->assertSessionMissing('is_revisi_mode');
        $response->assertSessionMissing('revisi_parent_periode_id');
        $response->assertSessionMissing('revisi_kegiatan_id');
        $response->assertSessionMissing('revisi_tahun');
        $response->assertSessionMissing('revisi_bulan');
    }

    public function test_revision_does_not_persist_when_validation_error_occurs(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
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
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1083000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 3455000,
            'status' => 'aktif',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Bambang Validation',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'revision_number' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'jumlah_satuan' => 10,
            'total_honor' => 10830000,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'estimasi_honor_partial' => null,
            'status_kepegawaian' => 'non_organik',
        ]);

        $response = $this->actingAs($admin)
            ->withSession([
                'active_role_id' => $adminRole->id,
                'is_revisi_mode' => true,
                'revisi_parent_periode_id' => $periode->id,
                'revisi_kegiatan_id' => $kegiatan->id,
                'revisi_tahun' => $activeYear,
                'revisi_bulan' => '07',
            ])
            ->put('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$activeYear.'/07', [
                'tanggal_mulai' => "$activeYear-07-01",
                'tanggal_selesai' => "$activeYear-07-31",
                'alokasi' => [[
                    'petugas_id' => 999999,
                    'peran' => 'PCL',
                    'bulan' => 7,
                    'tahun' => $activeYear,
                    'jumlah_satuan' => 10,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => false,
                    'partial_jumlah_satuan' => null,
                    'is_partial_payment_listing' => false,
                    'partial_jumlah_satuan_listing' => null,
                ]],
            ]);

        $response->assertSessionHasErrors(['validation']);
        $this->assertDatabaseMissing('periode_alokasi', [
            'kegiatan_id' => $kegiatan->id,
            'status' => 'perubahan',
            'parent_periode_id' => $periode->id,
        ]);
        $this->assertSame('dikirim', $periode->fresh()->status);
    }

    public function test_revision_detects_partial_payment_change_as_real_change(): void
    {
        [$admin, $adminRole] = $this->makeUserWithRole('admin');
        $activeYear = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
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
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pcl_ppl',
            'status_kepegawaian' => 'non_organik',
            'deskripsi' => 'Rate honor PCL',
            'satuan_id' => $satuan->id,
            'rate' => 1083000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $activeYear,
            'status' => 'aktif',
        ]);

        Sbml::factory()->create([
            'tahun_anggaran' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'status_kepegawaian' => 'non_organik',
            'jenis_penugasan' => 'pcl_ppl',
            'honor_max' => 3455000,
            'status' => 'aktif',
        ]);

        $petugas = Petugas::factory()->create([
            'nama' => 'Budi Santoso',
            'jenis_petugas' => 'non-organik',
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => $activeYear,
            'jenis_kegiatan' => 'survei',
            'status' => 'dikirim',
            'revision_number' => 0,
        ]);

        AlokasiPetugas::factory()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'peran' => 'pcl_ppl',
            'jumlah_satuan' => 10,
            'total_honor' => 10830000,
            'is_partial_payment' => false,
            'partial_jumlah_satuan' => null,
            'estimasi_honor_partial' => null,
            'status_kepegawaian' => 'non_organik',
        ]);

        $this->actingAs($admin)
            ->withSession([
                'active_role_id' => $adminRole->id,
                'is_revisi_mode' => true,
                'revisi_parent_periode_id' => $periode->id,
                'revisi_kegiatan_id' => $kegiatan->id,
                'revisi_tahun' => $activeYear,
                'revisi_bulan' => '07',
            ])
            ->put('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$activeYear.'/07', [
                'tanggal_mulai' => "$activeYear-07-01",
                'tanggal_selesai' => "$activeYear-07-31",
                'alokasi' => [[
                    'petugas_id' => $petugas->id,
                    'peran' => 'PCL',
                    'bulan' => 7,
                    'tahun' => $activeYear,
                    'jumlah_satuan' => 10,
                    'jenis_kegiatan' => 'survei',
                    'tahapan' => 'both',
                    'is_partial_payment' => true,
                    'partial_jumlah_satuan' => 4,
                    'is_partial_payment_listing' => false,
                    'partial_jumlah_satuan_listing' => null,
                ]],
            ]);

        $this->assertDatabaseHas('periode_alokasi', [
            'kegiatan_id' => $kegiatan->id,
            'status' => 'perubahan',
            'parent_periode_id' => $periode->id,
        ]);

        $this->assertDatabaseHas('alokasi_petugas', [
            'petugas_id' => $petugas->id,
            'is_partial_payment' => 1,
            'partial_jumlah_satuan' => 4,
            'periode_alokasi_id' => $periode->fresh()->load('children')->children->first()->id ?? $periode->id,
        ]);
    }
}
