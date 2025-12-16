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
            // Modify the enum to add 'pengawas_pengolahan'
            DB::statement("ALTER TABLE sbml MODIFY COLUMN jenis_penugasan ENUM('pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan') NOT NULL");
            // Also change honor_max to decimal(15,0) - no decimal places
            DB::statement('ALTER TABLE sbml MODIFY COLUMN honor_max DECIMAL(15,0) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Remove entries with pengawas_pengolahan first
            DB::table('sbml')->where('jenis_penugasan', 'pengawas_pengolahan')->delete();
            // Revert enum to original values
            DB::statement("ALTER TABLE sbml MODIFY COLUMN jenis_penugasan ENUM('pcl_ppl', 'pml', 'pengolahan') NOT NULL");
            // Revert honor_max back to decimal(15,2)
            DB::statement('ALTER TABLE sbml MODIFY COLUMN honor_max DECIMAL(15,2) NOT NULL');
        }
    }
};
