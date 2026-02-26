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
        Schema::table('periode_alokasi', function (Blueprint $table) {
            // Add parent_periode_id to track revisions
            $table->unsignedBigInteger('parent_periode_id')->nullable()->after('kegiatan_id');
            $table->foreign('parent_periode_id')
                ->references('id')
                ->on('periode_alokasi')
                ->onDelete('set null');

            // Add revision_number to track how many times revised
            $table->integer('revision_number')->default(0)->after('parent_periode_id');
        });

        // Update status enum to include 'perubahan' (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE periode_alokasi MODIFY COLUMN status ENUM('draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan', 'dihapus') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->dropForeign(['parent_periode_id']);
            $table->dropColumn(['parent_periode_id', 'revision_number']);
        });

        // Revert status enum (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE periode_alokasi MODIFY COLUMN status ENUM('draft', 'dikirim', 'direvisi', 'disetujui', 'dihapus') NOT NULL DEFAULT 'draft'");
        }
    }
};
