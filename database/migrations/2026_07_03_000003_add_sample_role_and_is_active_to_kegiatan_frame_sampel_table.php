<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->string('sample_role', 50)
                ->default('utama')
                ->after('nama_target')
                ->comment('Peran sampel untuk purpossive: utama, cadangan, atau lainnya');
            $table->boolean('is_active')
                ->default(true)
                ->after('sample_role')
                ->comment('Menandai apakah baris sampel dipilih sebagai sampel aktif');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->dropColumn(['sample_role', 'is_active']);
        });
    }
};
