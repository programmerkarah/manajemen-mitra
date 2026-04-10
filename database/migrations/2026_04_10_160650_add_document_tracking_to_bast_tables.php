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
        Schema::table('bast', function (Blueprint $table) {
            $table->string('compiled_file_path')->nullable()->after('file_path');
            $table->string('main_signed_file_path')->nullable()->after('compiled_file_path');
            $table->string('lokasi_kegiatan')->nullable()->after('main_signed_file_path');
        });

        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('jenis_kegiatan');
            $table->string('signed_file_path')->nullable()->after('file_path');
            $table->timestamp('generated_at')->nullable()->after('signed_file_path');
            $table->timestamp('signed_uploaded_at')->nullable()->after('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bast', function (Blueprint $table) {
            $table->dropColumn([
                'compiled_file_path',
                'main_signed_file_path',
                'lokasi_kegiatan',
            ]);
        });

        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->dropColumn([
                'file_path',
                'signed_file_path',
                'generated_at',
                'signed_uploaded_at',
            ]);
        });
    }
};
