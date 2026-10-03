<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        if (! Schema::hasTable('oauth_access_tokens')) {
            Schema::create('oauth_access_tokens', function (Blueprint $table): void {
                $table->string('id', 100)->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('client_id', 100);
                $table->string('name')->nullable();
                $table->text('scopes')->nullable();
                $table->boolean('revoked')->default(false);
                $table->timestamps();
                $table->dateTime('expires_at')->nullable();
            });
        }
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

        // Do not drop oauth_access_tokens here: the table may be owned by
        // Laravel Passport in an existing installation.
    }
};
