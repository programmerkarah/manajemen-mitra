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
            $table->enum('metode_pendataan_pencacahan', ['PAPI', 'CAPI'])
                ->nullable()
                ->after('has_listing_updating')
                ->comment('Metode pendataan tahap pencacahan: PAPI atau CAPI (FASIH)');

            $table->enum('metode_pendataan_listing', ['PAPI', 'CAPI'])
                ->nullable()
                ->after('metode_pendataan_pencacahan')
                ->comment('Metode pendataan tahap listing: PAPI atau CAPI (FASIH), hanya untuk kegiatan dengan listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn(['metode_pendataan_pencacahan', 'metode_pendataan_listing']);
        });
    }
};
