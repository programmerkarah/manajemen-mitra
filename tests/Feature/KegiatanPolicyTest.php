<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ketua_tim_lainnya_can_update_kegiatan_draft()
    {
        $ketuaTimRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $ketuaTim = User::factory()->create();
        $ketuaTim->roles()->attach($ketuaTimRole->id);

        $ketuaTimLain = User::factory()->create();
        $ketuaTimLain->roles()->attach($ketuaTimRole->id);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'draft',
        ]);

        $this->actingAs($ketuaTimLain)
            ->withSession(['active_role_id' => $ketuaTimRole->id]);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertTrue($canUpdate, 'Ketua tim lainnya harus bisa update kegiatan draft sebagai pj_lainnya_id');
    }

    public function test_ketua_tim_lainnya_can_update_kegiatan_divalidasi()
    {
        $ketuaTimRole = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );

        $ketuaTim = User::factory()->create();
        $ketuaTim->roles()->attach($ketuaTimRole->id);

        $ketuaTimLain = User::factory()->create();
        $ketuaTimLain->roles()->attach($ketuaTimRole->id);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'divalidasi',
        ]);

        $this->actingAs($ketuaTimLain)
            ->withSession(['active_role_id' => $ketuaTimRole->id]);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertTrue($canUpdate, 'Ketua tim lainnya boleh update kegiatan divalidasi sesuai policy saat ini');
    }
}
