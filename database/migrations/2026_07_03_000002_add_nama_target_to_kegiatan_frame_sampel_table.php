<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->string('nama_target')->nullable()->after('tahapan')->comment('Nama target untuk metode sampling purpossive');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->dropColumn('nama_target');
        });
    }
};
