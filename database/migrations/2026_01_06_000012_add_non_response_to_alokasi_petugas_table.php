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
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->integer('non_response')->nullable()->after('jumlah_satuan')->comment('Jumlah non response untuk pendataan lapangan');
            $table->integer('non_response_listing')->nullable()->after('jumlah_satuan_listing')->comment('Jumlah non response untuk listing/updating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn(['non_response', 'non_response_listing']);
        });
    }
};
