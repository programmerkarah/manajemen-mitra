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

        DB::statement("ALTER TABLE `rate_honor` MODIFY COLUMN `jenis_penugasan` ENUM('pcl_ppl','pml','koseka','pengolahan','pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('rate_honor')->where('jenis_penugasan', 'koseka')->delete();
        DB::statement("ALTER TABLE `rate_honor` MODIFY COLUMN `jenis_penugasan` ENUM('pcl_ppl','pml','pengolahan','pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }
};
