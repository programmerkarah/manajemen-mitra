<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing user roles to pivot table
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            if ($user->role) {
                $role = DB::table('roles')->where('name', $user->role)->first();
                if ($role) {
                    DB::table('role_user')->insert([
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Assign guest role to users without role
                $guestRole = DB::table('roles')->where('name', 'guest')->first();
                if ($guestRole) {
                    DB::table('role_user')->insert([
                        'user_id' => $user->id,
                        'role_id' => $guestRole->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Drop the old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add role column back
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('guest')->after('email');
        });

        // Migrate roles back from pivot table (keep first role only)
        $userRoles = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('role_user.user_id', 'roles.name')
            ->get()
            ->groupBy('user_id');

        foreach ($userRoles as $userId => $roles) {
            $firstRole = $roles->first();
            DB::table('users')->where('id', $userId)->update(['role' => $firstRole->name]);
        }

        // Delete pivot table data
        DB::table('role_user')->truncate();
    }
};
