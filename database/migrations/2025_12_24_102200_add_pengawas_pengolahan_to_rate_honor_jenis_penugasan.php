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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Add 'pengawas_pengolahan' to enum values for jenis_penugasan in rate_honor
        DB::statement("ALTER TABLE `rate_honor` MODIFY COLUMN `jenis_penugasan` ENUM('pcl_ppl','pml','pengolahan','pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Revert back to previous enum values (remove pengawas_pengolahan)
        // Any rows with 'pengawas_pengolahan' must be changed before rolling back.
        DB::statement("ALTER TABLE `rate_honor` MODIFY COLUMN `jenis_penugasan` ENUM('pcl_ppl','pml','pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }
};
