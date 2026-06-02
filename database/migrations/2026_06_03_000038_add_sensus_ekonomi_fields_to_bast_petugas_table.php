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
        Schema::table('bast_petugas', function (Blueprint $table): void {
            $table->unsignedInteger('muatan_input')->nullable()->after('nama_petugas');
            $table->unsignedInteger('muatan_prelist')->nullable()->after('muatan_input');
            $table->json('realisasi_unit_sampel')->nullable()->after('muatan_prelist');
            $table->string('fasih_screenshot_path')->nullable()->after('realisasi_unit_sampel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bast_petugas', function (Blueprint $table): void {
            $table->dropColumn([
                'muatan_input',
                'muatan_prelist',
                'realisasi_unit_sampel',
                'fasih_screenshot_path',
            ]);
        });
    }
};
