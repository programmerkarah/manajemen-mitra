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
            $table->date('tanggal_mulai')->nullable()->after('bulan');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->date('tanggal_mulai_listing')->nullable()->after('tanggal_selesai');
            $table->date('tanggal_selesai_listing')->nullable()->after('tanggal_mulai_listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai', 'tanggal_mulai_listing', 'tanggal_selesai_listing']);
        });
    }
};
