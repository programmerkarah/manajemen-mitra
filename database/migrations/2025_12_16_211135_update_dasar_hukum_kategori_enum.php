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
        // Alter column enum first to allow new values
        DB::statement("ALTER TABLE dasar_hukum MODIFY kategori ENUM('undang_undang', 'peraturan_pemerintah', 'peraturan_presiden', 'peraturan_menteri', 'keputusan_menteri', 'peraturan_badan', 'keputusan_kepala_badan', 'peraturan_menteri_badan', 'keputusan_menteri_kepala_badan') NOT NULL");

        // Update existing data
        DB::table('dasar_hukum')
            ->whereIn('kategori', ['peraturan_menteri', 'peraturan_badan'])
            ->update(['kategori' => 'peraturan_menteri_badan']);

        DB::table('dasar_hukum')
            ->whereIn('kategori', ['keputusan_menteri', 'keputusan_kepala_badan'])
            ->update(['kategori' => 'keputusan_menteri_kepala_badan']);

        // Remove old enum values
        DB::statement("ALTER TABLE dasar_hukum MODIFY kategori ENUM('undang_undang', 'peraturan_pemerintah', 'peraturan_presiden', 'peraturan_menteri_badan', 'keputusan_menteri_kepala_badan') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE dasar_hukum MODIFY kategori ENUM('undang_undang', 'peraturan_pemerintah', 'peraturan_presiden', 'peraturan_menteri', 'keputusan_menteri', 'peraturan_badan', 'keputusan_kepala_badan') NOT NULL");
    }
};
