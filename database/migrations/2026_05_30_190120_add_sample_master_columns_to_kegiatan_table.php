<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->foreignId('frame_sampel_listing_id')->nullable()->after('has_listing_updating')->constrained('master_frame_sampel')->nullOnDelete();
            $table->foreignId('frame_sampel_pencacahan_id')->nullable()->after('frame_sampel_listing_id')->constrained('master_frame_sampel')->nullOnDelete();
            $table->foreignId('unit_sampel_listing_id')->nullable()->after('frame_sampel_pencacahan_id')->constrained('master_unit_sampel')->nullOnDelete();
            $table->foreignId('unit_sampel_pencacahan_id')->nullable()->after('unit_sampel_listing_id')->constrained('master_unit_sampel')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('frame_sampel_listing_id');
            $table->dropConstrainedForeignId('frame_sampel_pencacahan_id');
            $table->dropConstrainedForeignId('unit_sampel_listing_id');
            $table->dropConstrainedForeignId('unit_sampel_pencacahan_id');
        });
    }
};
