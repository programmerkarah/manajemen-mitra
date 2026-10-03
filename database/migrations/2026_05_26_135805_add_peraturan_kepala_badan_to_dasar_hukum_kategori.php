<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE dasar_hukum MODIFY kategori ENUM('undang_undang', 'peraturan_pemerintah', 'peraturan_presiden', 'peraturan_menteri_badan', 'keputusan_menteri_kepala_badan', 'peraturan_kepala_badan') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE dasar_hukum MODIFY kategori ENUM('undang_undang', 'peraturan_pemerintah', 'peraturan_presiden', 'peraturan_menteri_badan', 'keputusan_menteri_kepala_badan') NOT NULL");
    }
};
