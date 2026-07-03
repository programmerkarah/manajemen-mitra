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
        Schema::table('alokasi_petugas_frame_sampel', function (Blueprint $table) {
            $table->boolean('is_non_response')->default(false)->after('kegiatan_frame_sampel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas_frame_sampel', function (Blueprint $table) {
            $table->dropColumn('is_non_response');
        });
    }
};
