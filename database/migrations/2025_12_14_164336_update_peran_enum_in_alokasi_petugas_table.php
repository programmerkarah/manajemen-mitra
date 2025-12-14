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
        // Update peran enum to include pengawas_pengolahan
        DB::statement("ALTER TABLE alokasi_petugas MODIFY COLUMN peran ENUM('pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE alokasi_petugas MODIFY COLUMN peran ENUM('pcl_ppl', 'pml', 'pengolahan') NOT NULL DEFAULT 'pcl_ppl'");
    }
};
