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
            $table->enum('metode_pendataan_pencacahan', ['PAPI', 'CAPI_FASIH', 'CAPI_KSA_PRO'])
                ->nullable()
                ->after('has_listing_updating')
                ->comment('Metode pendataan tahap pencacahan: PAPI, CAPI FASIH, atau CAPI KSA Pro');

            $table->enum('metode_pendataan_listing', ['PAPI', 'CAPI_FASIH', 'CAPI_KSA_PRO'])
                ->nullable()
                ->after('metode_pendataan_pencacahan')
                ->comment('Metode pendataan tahap listing: PAPI, CAPI FASIH, atau CAPI KSA Pro');
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
