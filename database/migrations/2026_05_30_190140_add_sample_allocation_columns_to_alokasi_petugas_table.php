<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_frame_sampel')->default(0)->after('jumlah_satuan_listing');
            $table->unsignedInteger('jumlah_unit_sampel')->default(0)->after('jumlah_frame_sampel');
        });
    }

    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn(['jumlah_frame_sampel', 'jumlah_unit_sampel']);
        });
    }
};
