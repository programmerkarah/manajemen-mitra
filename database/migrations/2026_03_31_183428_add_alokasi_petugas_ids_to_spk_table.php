<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            // Add JSON column to store multiple alokasi_petugas_ids
            $table->json('alokasi_petugas_ids')->nullable()->after('alokasi_petugas_id');
        });

        // Populate alokasi_petugas_ids from existing alokasi_petugas_id
        DB::statement('UPDATE spk SET alokasi_petugas_ids = JSON_ARRAY(alokasi_petugas_id) WHERE alokasi_petugas_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->dropColumn('alokasi_petugas_ids');
        });
    }
};
