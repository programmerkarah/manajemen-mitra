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
        Schema::table('dasar_hukum', function (Blueprint $table) {
            $table->dropColumn('tahun_tln');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dasar_hukum', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_tln')->nullable()->after('nomor_tln');
        });
    }
};
