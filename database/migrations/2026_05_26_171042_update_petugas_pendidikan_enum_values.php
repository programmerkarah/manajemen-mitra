<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('petugas')) {
            return;
        }

        DB::statement("ALTER TABLE `petugas` MODIFY `pendidikan` ENUM('SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('petugas')) {
            return;
        }

        DB::statement("ALTER TABLE `petugas` MODIFY `pendidikan` ENUM('SD','SMP','SMA','D1','D3','D4','S1','S2','S3') NOT NULL");
    }
};
