<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use App\Services\ActiveYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlokasiFrameSampelReplacementTest extends TestCase
{
    use RefreshDatabase;

    private function makeOperator(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'operator'],
            ['display_name' => 'Operator', 'description' => '']
        );

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_replace_frame_sample_updates_selected_pivot(): void
    {
        [$user, $role] = $this->makeOperator();
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'Survei - PML',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pml',
            'status_kepegawaian' => 'organik',
            'deskripsi' => 'Rate honor PML',
            'satuan_id' => $satuan->id,
            'rate' => 12000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $alokasi = AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 12000,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'jumlah_frame_sampel' => 1,
            'jumlah_unit_sampel' => 1,
        ]);

        $currentFrame = KegiatanFrameSampel::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => null,
            'tahapan' => 'pencacahan',
            'nama_target' => 'ZAINAL ARIFIN',
            'sample_role' => 'utama',
            'is_active' => true,
            'nama_frame' => 'Usaha A',
            'kode_kecamatan' => '030',
            'kode_desa' => '007',
            'kode_sls' => '2210013',
            'kode_sub_sls' => null,
            'kode_segmen' => null,
            'identitas_tambahan' => ['kdkec' => '030', 'kddes' => '007'],
            'target_unit_sampel' => ['usaha' => 1],
        ]);

        $replacementFrame = KegiatanFrameSampel::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => null,
            'tahapan' => 'pencacahan',
            'nama_target' => 'BUKIT GADANG',
            'sample_role' => 'cadangan',
            'is_active' => true,
            'nama_frame' => 'Usaha B',
            'kode_kecamatan' => '030',
            'kode_desa' => '008',
            'kode_sls' => '2210025',
            'kode_sub_sls' => null,
            'kode_segmen' => null,
            'identitas_tambahan' => ['kdkec' => '030', 'kddes' => '008'],
            'target_unit_sampel' => ['usaha' => 1],
        ]);

        $pivot = AlokasiPetugasFrameSampel::query()->create([
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $currentFrame->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->patch('/alokasi/frame-sampel/'.$pivot->id.'/replace', [
                'kegiatan_frame_sampel_id' => $replacementFrame->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('alokasi_petugas_frame_sampel', [
            'id' => $pivot->id,
            'kegiatan_frame_sampel_id' => $replacementFrame->id,
        ]);
    }

    public function test_replace_frame_sample_rejects_duplicate_frame_for_same_role(): void
    {
        [$user, $role] = $this->makeOperator();
        $tahun = ActiveYearService::get();

        $kegiatan = Kegiatan::factory()->create([
            'status' => 'divalidasi',
            'jenis_kegiatan' => 'survei',
            'tahun_anggaran' => $tahun,
            'has_listing_updating' => false,
        ]);

        $satuan = Satuan::query()->create([
            'kode' => 'STN-'.$tahun,
            'nama' => 'Dokumen',
            'status' => 'aktif',
        ]);

        RateHonor::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'posisi' => 'Survei - PML',
            'jenis_kegiatan' => 'survei',
            'jenis_penugasan' => 'pml',
            'status_kepegawaian' => 'organik',
            'deskripsi' => 'Rate honor PML',
            'satuan_id' => $satuan->id,
            'rate' => 12000,
            'rate_listing' => null,
            'satuan_listing_id' => null,
            'tahun_berlaku' => $tahun,
            'status' => 'aktif',
        ]);

        $periode = PeriodeAlokasi::factory()->create([
            'kegiatan_id' => $kegiatan->id,
            'bulan' => '07',
            'tahun' => $tahun,
            'jenis_kegiatan' => 'survei',
            'status' => 'draft',
        ]);

        $petugas = Petugas::factory()->create([
            'jenis_petugas' => 'organik',
            'status' => 'aktif',
        ]);

        $alokasi = AlokasiPetugas::query()->create([
            'periode_alokasi_id' => $periode->id,
            'petugas_id' => $petugas->id,
            'jumlah_satuan' => 1,
            'total_honor' => 12000,
            'peran' => 'pml',
            'status_kepegawaian' => 'organik',
            'catatan' => null,
            'non_response' => 0,
            'non_response_listing' => 0,
            'jumlah_frame_sampel' => 1,
            'jumlah_unit_sampel' => 1,
        ]);

        $currentFrame = KegiatanFrameSampel::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => null,
            'tahapan' => 'pencacahan',
            'nama_target' => 'ZAINAL ARIFIN',
            'sample_role' => 'utama',
            'is_active' => true,
            'nama_frame' => 'Usaha A',
            'kode_kecamatan' => '030',
            'kode_desa' => '007',
            'kode_sls' => '2210013',
            'kode_sub_sls' => null,
            'kode_segmen' => null,
            'identitas_tambahan' => ['kdkec' => '030', 'kddes' => '007'],
            'target_unit_sampel' => ['usaha' => 1],
        ]);

        $replacementFrame = KegiatanFrameSampel::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => null,
            'tahapan' => 'pencacahan',
            'nama_target' => 'BUKIT GADANG',
            'sample_role' => 'utama',
            'is_active' => true,
            'nama_frame' => 'Usaha B',
            'kode_kecamatan' => '030',
            'kode_desa' => '008',
            'kode_sls' => '2210025',
            'kode_sub_sls' => null,
            'kode_segmen' => null,
            'identitas_tambahan' => ['kdkec' => '030', 'kddes' => '008'],
            'target_unit_sampel' => ['usaha' => 1],
        ]);

        $pivot = AlokasiPetugasFrameSampel::query()->create([
            'alokasi_petugas_id' => $alokasi->id,
            'kegiatan_frame_sampel_id' => $currentFrame->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->patch('/alokasi/frame-sampel/'.$pivot->id.'/replace', [
                'kegiatan_frame_sampel_id' => $replacementFrame->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('alokasi_petugas_frame_sampel', [
            'id' => $pivot->id,
            'kegiatan_frame_sampel_id' => $replacementFrame->id,
        ]);
    }
}
