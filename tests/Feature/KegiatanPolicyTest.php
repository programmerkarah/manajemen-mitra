<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ketua_tim_lainnya_can_update_kegiatan_draft()
    {
        $ketuaTim = User::factory()->create(['active_role' => 'ketua_tim']);
        $ketuaTimLain = User::factory()->create(['active_role' => 'ketua_tim']);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'draft',
        ]);

        $this->actingAs($ketuaTimLain);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertTrue($canUpdate, 'Ketua tim lainnya harus bisa update kegiatan draft sebagai pj_lainnya_id');
    }

    public function test_ketua_tim_lainnya_cannot_update_kegiatan_non_draft()
    {
        $ketuaTim = User::factory()->create(['active_role' => 'ketua_tim']);
        $ketuaTimLain = User::factory()->create(['active_role' => 'ketua_tim']);
        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $ketuaTim->id,
            'pj_lainnya_id' => $ketuaTimLain->id,
            'status' => 'divalidasi',
        ]);

        $this->actingAs($ketuaTimLain);
        $canUpdate = $ketuaTimLain->can('update', $kegiatan);
        $this->assertFalse($canUpdate, 'Ketua tim lainnya tidak boleh update kegiatan non-draft');
    }
}
