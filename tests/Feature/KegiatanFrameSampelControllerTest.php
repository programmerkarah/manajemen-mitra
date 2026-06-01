<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanFrameSampelControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeKetuaTim(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim', 'description' => 'Role ketua tim']
        );
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return [$user, $role];
    }

    public function test_store_rejects_listing_when_listing_toggle_is_disabled(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $frameListing = MasterFrameSampel::create([
            'nama' => 'Frame Listing',
            'kode' => 'FL',
            'is_active' => true,
        ]);
        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan',
            'kode' => 'FP',
            'is_active' => true,
        ]);
        $unit = MasterUnitSampel::create([
            'nama' => 'Rumah Tangga',
            'kode' => 'RT',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'has_listing_updating' => false,
            'frame_sampel_listing_id' => $frameListing->id,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'unit_sampel_listing_ids' => [$unit->id],
            'unit_sampel_pencacahan_ids' => [$unit->id],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->from('/kegiatan/'.$kegiatan->hashed_id.'/frame-sampel')
            ->post('/kegiatan/'.$kegiatan->hashed_id.'/frame-sampel', [
                'tahapan' => 'listing',
                'nama_frame' => 'Frame A',
                'target_unit_sampel' => [$unit->id => 10],
            ]);

        $response->assertSessionHasErrors('tahapan');
        $this->assertDatabaseMissing('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'tahapan' => 'listing',
        ]);
    }

    public function test_store_allows_pencacahan_when_listing_toggle_is_disabled(): void
    {
        [$user, $role] = $this->makeKetuaTim();

        $framePencacahan = MasterFrameSampel::create([
            'nama' => 'Frame Pencacahan',
            'kode' => 'FP2',
            'is_active' => true,
        ]);
        $unit = MasterUnitSampel::create([
            'nama' => 'Usaha',
            'kode' => 'USH',
            'is_active' => true,
        ]);

        $kegiatan = Kegiatan::factory()->create([
            'ketua_tim_user_id' => $user->id,
            'pj_lainnya_id' => $user->id,
            'has_listing_updating' => false,
            'frame_sampel_pencacahan_id' => $framePencacahan->id,
            'unit_sampel_pencacahan_ids' => [$unit->id],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_id' => $role->id])
            ->post('/kegiatan/'.$kegiatan->hashed_id.'/frame-sampel', [
                'tahapan' => 'pencacahan',
                'nama_frame' => 'Frame B',
                'target_unit_sampel' => [$unit->id => 7],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kegiatan_frame_sampel', [
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $framePencacahan->id,
            'tahapan' => 'pencacahan',
            'target_unit_sampel->'.$unit->id => 7,
        ]);
    }
}
