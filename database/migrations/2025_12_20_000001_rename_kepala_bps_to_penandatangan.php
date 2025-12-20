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
        // Rename table
        Schema::rename('kepala_bps', 'penandatangan');

        // Modify columns
        Schema::table('penandatangan', function (Blueprint $table) {
            // Add new column for type of signer
            $table->enum('jenis_penandatangan', ['kepala', 'ppk'])->default('kepala')->after('nip');

            // Make jabatan nullable and remove default
            $table->string('jabatan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penandatangan', function (Blueprint $table) {
            $table->dropColumn('jenis_penandatangan');
            $table->string('jabatan')->default('Kepala BPS Kota Sawahlunto')->change();
        });

        Schema::rename('penandatangan', 'kepala_bps');
    }
};
