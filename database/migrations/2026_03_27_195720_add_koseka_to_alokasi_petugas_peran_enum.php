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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `alokasi_petugas` MODIFY COLUMN `peran` ENUM('pcl_ppl','pml','koseka','pengolahan','pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::table('alokasi_petugas')->where('peran', 'koseka')->update(['peran' => 'pcl_ppl']);
            DB::statement("ALTER TABLE `alokasi_petugas` MODIFY COLUMN `peran` ENUM('pcl_ppl','pml','pengolahan','pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
        }
    }
};
