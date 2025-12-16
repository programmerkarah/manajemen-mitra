<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            // Rename kolom anggaran ke pagu_pencacahan
            $table->renameColumn('anggaran', 'pagu_pencacahan');
            $table->boolean('has_listing_updating')->default(false)->after('tahun_anggaran');
            $table->decimal('pagu_listing', 15, 2)->nullable()->after('has_listing_updating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->renameColumn('pagu_pencacahan', 'anggaran');
            $table->dropColumn(['has_listing_updating', 'pagu_listing']);
        });
    }
};
