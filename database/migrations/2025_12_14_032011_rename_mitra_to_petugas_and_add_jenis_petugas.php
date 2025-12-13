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
        // Drop the unique constraint (no foreign keys exist for kegiatan_id and mitra_id)
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropUnique('unique_alokasi');
        });

        // Rename mitra table to petugas
        Schema::rename('mitra', 'petugas');

        // Add jenis_petugas column to petugas table
        Schema::table('petugas', function (Blueprint $table) {
            $table->enum('jenis_petugas', ['organik', 'non-organik'])->default('non-organik')->after('tahun_bergabung');
        });

        // Rename alokasi_mitra table to alokasi_petugas
        Schema::rename('alokasi_mitra', 'alokasi_petugas');

        // Rename mitra_id column to petugas_id in alokasi_petugas
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->renameColumn('mitra_id', 'petugas_id');
        });

        // Re-add foreign keys and unique constraint with new names
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->foreign('kegiatan_id')->references('id')->on('kegiatan')->onDelete('cascade');
            $table->foreign('petugas_id')->references('id')->on('petugas')->onDelete('cascade');
            $table->unique(['kegiatan_id', 'petugas_id', 'bulan', 'tahun'], 'unique_alokasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys and unique constraint
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_id']);
            $table->dropForeign(['petugas_id']);
            $table->dropUnique('unique_alokasi');
        });

        // Rename petugas_id back to mitra_id
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->renameColumn('petugas_id', 'mitra_id');
        });

        // Rename alokasi_petugas back to alokasi_mitra
        Schema::rename('alokasi_petugas', 'alokasi_mitra');

        // Remove jenis_petugas column
        Schema::table('petugas', function (Blueprint $table) {
            $table->dropColumn('jenis_petugas');
        });

        // Rename petugas table back to mitra
        Schema::rename('petugas', 'mitra');

        // Re-add unique constraint only (foreign keys were not present in original state)
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->unique(['kegiatan_id', 'mitra_id', 'bulan', 'tahun'], 'unique_alokasi');
        });
    }
};
