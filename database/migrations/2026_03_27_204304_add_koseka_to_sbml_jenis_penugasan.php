<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run enum modification if not using SQLite (for testing compatibility)
        if (DB::getDriverName() !== 'sqlite') {
            // Modify the enum to add 'koseka'
            DB::statement("ALTER TABLE sbml MODIFY COLUMN jenis_penugasan ENUM('pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan', 'koseka') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Remove entries with koseka first
            DB::table('sbml')->where('jenis_penugasan', 'koseka')->delete();
            // Revert enum to original values
            DB::statement("ALTER TABLE sbml MODIFY COLUMN jenis_penugasan ENUM('pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan') NOT NULL");
        }
    }
};
