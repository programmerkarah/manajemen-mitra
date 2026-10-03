<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE kegiatan_frame_sampel
            MODIFY sample_role VARCHAR(50) NULL DEFAULT NULL
            COMMENT 'Peran sampel untuk purpossive: utama, cadangan, atau lainnya'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('kegiatan_frame_sampel')
            ->whereNull('sample_role')
            ->update(['sample_role' => 'utama']);

        DB::statement("
            ALTER TABLE kegiatan_frame_sampel
            MODIFY sample_role VARCHAR(50) NOT NULL DEFAULT 'utama'
            COMMENT 'Peran sampel untuk purpossive: utama, cadangan, atau lainnya'
        ");
    }
};
