<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityLogFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'description' => '']
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole->id);
    }

    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)
            ->withSession(['active_role_id' => $this->adminRole->id]);
    }

    public function test_admin_can_filter_activity_log_by_user_and_date_range(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 10, 0, 0));

        $selectedUser = User::factory()->create(['name' => 'Petugas Filter']);
        $otherUser = User::factory()->create(['name' => 'Petugas Lain']);

        ActivityLog::query()->insert([
            [
                'user_id' => $selectedUser->id,
                'user_name' => $selectedUser->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
            ],
            [
                'user_id' => $selectedUser->id,
                'user_name' => $selectedUser->name,
                'action' => 'Ubah Kegiatan',
                'type' => 'kegiatan',
                'description' => 'Mengubah kegiatan',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 3, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 3, 8, 0, 0),
            ],
            [
                'user_id' => $otherUser->id,
                'user_name' => $otherUser->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 2, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 2, 8, 0, 0),
            ],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.activity-log'), [
            'encrypted_filters' => encryptFilters([
                'user' => (string) $selectedUser->id,
                'date_from' => '2026-06-03',
                'date_to' => '2026-06-01',
            ]),
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ActivityLog')
            ->where('pagination.total', 2)
            ->where('filters.decrypted.user', (string) $selectedUser->id)
            ->where('filters.decrypted.date_from', '2026-06-03')
            ->where('filters.decrypted.date_to', '2026-06-01')
        );

        $logs = collect(decryptData($response->inertiaProps('logs')));

        $this->assertCount(2, $logs);
        $this->assertSame($selectedUser->name, $logs->first()['user']);
        $this->assertSame($selectedUser->name, $logs->last()['user']);
    }

    public function test_admin_can_clear_user_and_date_filters_by_omitting_them(): void
    {
        ActivityLog::query()->insert([
            [
                'user_id' => $this->admin->id,
                'user_name' => $this->admin->name,
                'action' => 'Login',
                'type' => 'auth',
                'description' => 'Masuk ke aplikasi',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 1, 8, 0, 0),
            ],
            [
                'user_id' => $this->admin->id,
                'user_name' => $this->admin->name,
                'action' => 'Ubah Data',
                'type' => 'system',
                'description' => 'Mengubah data',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Testing',
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => Carbon::create(2026, 6, 4, 8, 0, 0),
                'updated_at' => Carbon::create(2026, 6, 4, 8, 0, 0),
            ],
        ]);

        $response = $this->actingAsAdmin()->post(route('admin.activity-log'), [
            'encrypted_filters' => encryptFilters([]),
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ActivityLog')
            ->where('pagination.total', 2)
            ->where('filters.decrypted', [])
        );

        $logs = collect(decryptData($response->inertiaProps('logs')));

        $this->assertCount(2, $logs);
    }

    public function test_admin_can_export_activity_log_with_the_same_filter_inputs(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.activity-log.export', [
            'user' => '123',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-07',
        ]));

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get('content-disposition');

        $this->assertStringContainsString('activity-log-', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }
}
