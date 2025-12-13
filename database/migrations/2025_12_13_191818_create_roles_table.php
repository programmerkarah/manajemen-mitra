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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // guest, admin, operator, pj, approver
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default roles
        DB::table('roles')->insert([
            ['name' => 'guest', 'display_name' => 'Guest', 'description' => 'Hanya bisa melihat dashboard', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Akses penuh ke semua fitur', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'operator', 'display_name' => 'Operator', 'description' => 'Mengelola alokasi mitra', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pj', 'display_name' => 'Penanggung Jawab', 'description' => 'Mengelola kegiatan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'approver', 'display_name' => 'Approver', 'description' => 'Menyetujui alokasi', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
