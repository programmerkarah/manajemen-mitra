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
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->enum('tahapan', ['both', 'listing_only', 'pencacahan_only'])->default('both')->after('jenis_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->dropColumn('tahapan');
        });
    }
};
