<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah role Ketua Tim
        Role::firstOrCreate(
            ['name' => 'ketua_tim'],
            ['display_name' => 'Ketua Tim']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus role Ketua Tim
        Role::where('name', 'ketua_tim')->delete();
    }
};
