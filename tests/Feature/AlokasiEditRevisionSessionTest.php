<?php

namespace Tests\Feature;

use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Role;
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
}
