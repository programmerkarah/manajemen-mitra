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
            $table->enum('metode_pelatihan', ['daring', 'luring', 'hybrid', 'tidak_ada_pelatihan'])
                ->nullable()
                ->after('metode_pendataan_listing')
                ->comment('Metode pelatihan petugas: daring (online), luring (tatap muka), hybrid, atau tidak_ada_pelatihan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('metode_pelatihan');
        });
    }
};
