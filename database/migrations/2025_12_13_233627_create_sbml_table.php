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
        Schema::create('sbml', function (Blueprint $table) {
            $table->id();
            $table->year('tahun_anggaran');
            $table->decimal('pcl_ppl_max', 15, 2)->comment('Batas maksimal honor per bulan untuk PCL/PPL');
            $table->decimal('pml_max', 15, 2)->comment('Batas maksimal honor per bulan untuk PML');
            $table->decimal('pengolahan_max', 15, 2)->comment('Batas maksimal honor per bulan untuk Petugas Pengolahan');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            // Unique constraint untuk tahun anggaran
            $table->unique('tahun_anggaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbml');
    }
};
