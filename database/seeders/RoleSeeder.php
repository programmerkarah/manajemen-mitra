<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'guest',
                'display_name' => 'Guest',
                'description' => 'Hanya bisa melihat dashboard',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Akses penuh ke semua fitur',
            ],
            [
                'name' => 'operator',
                'display_name' => 'Operator',
                'description' => 'Mengelola alokasi mitra',
            ],
            [
                'name' => 'pj',
                'display_name' => 'Penanggung Jawab',
                'description' => 'Mengelola kegiatan',
            ],
            [
                'name' => 'approver',
                'display_name' => 'Approver',
                'description' => 'Menyetujui alokasi',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                ]
            );
        }
    }
}
