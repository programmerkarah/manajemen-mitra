<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkIndexAccessTest extends TestCase
{
    public function test_approver_can_see_spk_regular_and_sensus_mode_buttons(): void
    {
        $approverRole = Role::firstOrCreate(
            ['name' => 'approver'],
            ['display_name' => 'Approver', 'description' => 'Role approver']
        );

        $viewer = User::factory()->create();
        $viewer->roles()->attach($approverRole->id);

        $response = $this->actingAs($viewer)
            ->withSession(['active_role_id' => $approverRole->id, 'active_role_user_id' => $viewer->id])
            ->get('/spk');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Spk/Index')
            ->where('can_access_sensus_mode', true)
        );
    }
}
