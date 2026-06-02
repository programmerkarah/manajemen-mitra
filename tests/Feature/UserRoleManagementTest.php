<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        if (Role::count() === 0) {
            Role::create(['name' => 'guest', 'display_name' => 'Guest', 'description' => 'Guest']);
            Role::create(['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin']);
            Role::create(['name' => 'operator', 'display_name' => 'Operator', 'description' => 'Operator']);
            Role::create(['name' => 'pj', 'display_name' => 'PJ', 'description' => 'PJ']);
            Role::create(['name' => 'approver', 'display_name' => 'Approver', 'description' => 'Approver']);
        }
    }

    public function test_admin_can_view_users_list(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $admin->load('roles'); // Eager load roles

        $this->assertTrue($admin->isAdmin(), 'User should be admin');

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Users/Index'));
    }

    public function test_non_admin_cannot_view_users_list(): void
    {
        $this->seedRoles();
        $user = User::factory()->operator()->create();

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(302);
    }

    public function test_admin_can_view_edit_user_roles_page(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get("/users/{$user->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->has('user')
            ->has('allRoles'));
    }

    public function test_admin_can_update_user_roles(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $operatorRole = Role::where('name', 'operator')->first();
        $pjRole = Role::where('name', 'pj')->first();

        $response = $this->actingAs($admin)->patch("/users/{$user->id}", [
            'roles' => [$operatorRole->id, $pjRole->id],
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasRole('operator'));
        $this->assertTrue($user->fresh()->hasRole('pj'));
    }

    public function test_cannot_remove_last_admin_role(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();

        // This is the only admin
        $guestRole = Role::where('name', 'guest')->first();

        $response = $this->actingAs($admin)->patch("/users/{$admin->id}", [
            'roles' => [$guestRole->id],
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_user_must_have_at_least_one_role(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->patch("/users/{$user->id}", [
            'roles' => [],
        ]);

        $response->assertSessionHasErrors('roles');
    }

    public function test_non_admin_cannot_update_user_roles(): void
    {
        $this->seedRoles();
        $operator = User::factory()->operator()->create();
        $user = User::factory()->create();

        $adminRole = Role::where('name', 'admin')->first();

        $response = $this->actingAs($operator)->patch("/users/{$user->id}", [
            'roles' => [$adminRole->id],
        ]);

        $response->assertStatus(302);
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $this->seedRoles();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $operatorRole = Role::where('name', 'operator')->first();
        $pjRole = Role::where('name', 'pj')->first();
        $approverRole = Role::where('name', 'approver')->first();

        $this->actingAs($admin)->patch("/users/{$user->id}", [
            'roles' => [$operatorRole->id, $pjRole->id, $approverRole->id],
        ]);

        $user = $user->fresh();
        $this->assertTrue($user->hasRole('operator'));
        $this->assertTrue($user->hasRole('pj'));
        $this->assertTrue($user->hasRole('approver'));
        $this->assertTrue($user->hasAnyRole(['operator', 'pj', 'approver']));
    }

    public function test_default_active_role_uses_highest_privilege_non_guest_role(): void
    {
        $this->seedRoles();

        $user = User::factory()->create();

        $operatorRole = Role::where('name', 'operator')->firstOrFail();
        $pjRole = Role::where('name', 'pj')->firstOrFail();
        $user->roles()->attach([$operatorRole->id, $pjRole->id]);

        session()->forget(['active_role_id', 'active_role_user_id']);

        $activeRole = $user->fresh()->getActiveRole();

        $this->assertNotNull($activeRole);
        $this->assertSame('operator', $activeRole->name);
    }

    public function test_active_role_session_context_is_isolated_for_view_as_user(): void
    {
        $this->seedRoles();

        $admin = User::factory()->create(['username' => 'rhmtzikri']);
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->roles()->attach($adminRole->id);

        $viewedUser = User::factory()->create();
        $operatorRole = Role::where('name', 'operator')->firstOrFail();
        $viewedUser->roles()->attach($operatorRole->id);

        $this->actingAs($admin)->withSession([
            'active_role_id' => $adminRole->id,
            'active_role_user_id' => $admin->id,
        ]);

        $this->post(route('view-as-user.set'), ['user_id' => $viewedUser->id])->assertRedirect();

        $activeRole = $viewedUser->fresh()->getActiveRole();

        $this->assertNotNull($activeRole);
        $this->assertSame('operator', $activeRole->name);
    }

    public function test_role_switch_uses_viewed_user_roles_when_viewing_as_user(): void
    {
        $this->seedRoles();

        $viewer = User::factory()->create(['username' => 'rhmtzikri']);
        $viewerApproverRole = Role::where('name', 'approver')->firstOrFail();
        $viewer->roles()->attach($viewerApproverRole->id);

        $viewedUser = User::factory()->create(['username' => 'angga']);
        $viewedApproverRole = Role::where('name', 'approver')->firstOrFail();
        $viewedOperatorRole = Role::where('name', 'operator')->firstOrFail();
        $viewedUser->roles()->attach([$viewedApproverRole->id, $viewedOperatorRole->id]);

        $response = $this->actingAs($viewer)
            ->withSession(['view_as_user_id' => $viewedUser->id])
            ->post(route('role.switch'), ['role_id' => $viewedApproverRole->id]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($viewedUser->id, (int) session('active_role_user_id'));
        $this->assertSame($viewedApproverRole->id, (int) session('active_role_id'));
    }
}
