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
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->date('jadwal_pengolahan_listing_mulai')->nullable()->after('tanggal_selesai_listing');
            $table->date('jadwal_pengolahan_listing_selesai')->nullable()->after('jadwal_pengolahan_listing_mulai');
            $table->date('jadwal_pengolahan_pencacahan_mulai')->nullable()->after('jadwal_pengolahan_listing_selesai');
            $table->date('jadwal_pengolahan_pencacahan_selesai')->nullable()->after('jadwal_pengolahan_pencacahan_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->dropColumn([
                'jadwal_pengolahan_listing_mulai',
                'jadwal_pengolahan_listing_selesai',
                'jadwal_pengolahan_pencacahan_mulai',
                'jadwal_pengolahan_pencacahan_selesai',
            ]);
        });
    }
};
