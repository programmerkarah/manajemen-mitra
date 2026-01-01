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
        Schema::table('sk_kpa', function (Blueprint $table) {
            // Drop old unique constraint on nomor_sk
            $table->dropUnique(['nomor_sk']);

            // Add composite unique constraint on nomor_sk and tahun
            $table->unique(['nomor_sk', 'tahun'], 'sk_kpa_nomor_sk_tahun_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_kpa', function (Blueprint $table) {
            // Drop composite unique constraint
            $table->dropUnique('sk_kpa_nomor_sk_tahun_unique');

            // Restore old unique constraint on nomor_sk
            $table->unique('nomor_sk');
        });
    }
};
