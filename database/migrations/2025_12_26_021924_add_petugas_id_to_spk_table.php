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
        Schema::table('spk', function (Blueprint $table) {
            // Add petugas_id column
            $table->bigInteger('petugas_id')->unsigned()->nullable()->after('nomor_spk');
            $table->foreign('petugas_id')->references('id')->on('petugas')->onDelete('cascade');
            
            // Add index for better performance
            $table->index(['petugas_id', 'addendum_number']);
        });

        // Populate petugas_id from existing alokasi_petugas data
        DB::statement('
            UPDATE spk s
            INNER JOIN alokasi_petugas ap ON s.alokasi_petugas_id = ap.id
            SET s.petugas_id = ap.petugas_id
            WHERE s.petugas_id IS NULL
        ');

        // Make petugas_id NOT NULL after populating
        Schema::table('spk', function (Blueprint $table) {
            $table->bigInteger('petugas_id')->unsigned()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->dropForeign(['petugas_id']);
            $table->dropIndex(['petugas_id', 'addendum_number']);
            $table->dropColumn('petugas_id');
        });
    }
};
