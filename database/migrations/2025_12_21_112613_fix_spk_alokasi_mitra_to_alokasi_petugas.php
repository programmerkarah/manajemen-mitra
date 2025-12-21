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
            // Drop old foreign key if exists
            $table->dropForeign(['alokasi_mitra_id']);

            // Rename column
            $table->renameColumn('alokasi_mitra_id', 'alokasi_petugas_id');
        });

        // Add new foreign key after rename
        Schema::table('spk', function (Blueprint $table) {
            $table->foreign('alokasi_petugas_id')
                ->references('id')
                ->on('alokasi_petugas')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['alokasi_petugas_id']);

            // Rename back
            $table->renameColumn('alokasi_petugas_id', 'alokasi_mitra_id');
        });

        // Add old foreign key back
        Schema::table('spk', function (Blueprint $table) {
            $table->foreign('alokasi_mitra_id')
                ->references('id')
                ->on('alokasi_mitra')
                ->cascadeOnDelete();
        });
    }
};
