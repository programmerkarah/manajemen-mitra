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
        // First, update existing data: diajukan -> dikirim
        DB::table('periode_alokasi')->where('status', 'diajukan')->update(['status' => 'dikirim']);
        DB::table('periode_alokasi')->where('status', 'disetujui')->update(['status' => 'dikirim']);
        DB::table('periode_alokasi')->where('status', 'ditolak')->update(['status' => 'dihapus']);
        DB::table('periode_alokasi')->where('status', 'selesai')->update(['status' => 'dikirim']);

        // Then update enum
        DB::statement("ALTER TABLE periode_alokasi MODIFY COLUMN status ENUM('draft', 'dikirim', 'direvisi', 'dihapus') DEFAULT 'draft'");

        // Remove soft deletes from alokasi_petugas
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore soft deletes
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Revert status enum
        DB::statement("ALTER TABLE periode_alokasi MODIFY COLUMN status ENUM('draft', 'diajukan', 'disetujui', 'ditolak', 'selesai') DEFAULT 'draft'");
    }
};
