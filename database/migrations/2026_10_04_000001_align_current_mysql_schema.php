<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE kegiatan
            MODIFY metode_pendataan_pencacahan
            ENUM('PAPI','CAPI_FASIH','CAPI_KSA_PRO','CAPI')
            NULL
            COMMENT 'Metode pendataan tahap pencacahan: PAPI, CAPI FASIH, CAPI KSA Pro, atau CAPI umum'
        ");

        DB::statement("
            ALTER TABLE kegiatan
            MODIFY metode_pendataan_listing
            ENUM('PAPI','CAPI_FASIH','CAPI_KSA_PRO','CAPI')
            NULL
            COMMENT 'Metode pendataan tahap listing: PAPI, CAPI FASIH, CAPI KSA Pro, atau CAPI umum'
        ");

        DB::statement('ALTER TABLE kegiatan_frame_sampel MODIFY frame_sampel_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE satuan MODIFY kode VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('kegiatan')
            ->where('metode_pendataan_pencacahan', 'CAPI')
            ->update(['metode_pendataan_pencacahan' => 'CAPI_FASIH']);
        DB::table('kegiatan')
            ->where('metode_pendataan_listing', 'CAPI')
            ->update(['metode_pendataan_listing' => 'CAPI_FASIH']);

        DB::statement("
            ALTER TABLE kegiatan
            MODIFY metode_pendataan_pencacahan
            ENUM('PAPI','CAPI_FASIH','CAPI_KSA_PRO')
            NULL
        ");
        DB::statement("
            ALTER TABLE kegiatan
            MODIFY metode_pendataan_listing
            ENUM('PAPI','CAPI_FASIH','CAPI_KSA_PRO')
            NULL
        ");

        DB::statement('ALTER TABLE satuan MODIFY kode VARCHAR(10) NOT NULL');
    }
};
