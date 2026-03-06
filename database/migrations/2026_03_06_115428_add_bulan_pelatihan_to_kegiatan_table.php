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
            $table->unsignedTinyInteger('bulan_pelatihan')
                ->nullable()
                ->after('metode_pelatihan')
                ->comment('Bulan penyelenggaraan pelatihan petugas (1-12)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('bulan_pelatihan');
        });
    }
};
