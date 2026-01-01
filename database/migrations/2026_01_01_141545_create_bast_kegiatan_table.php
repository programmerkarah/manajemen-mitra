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
        Schema::create('bast_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bast_id')->constrained('bast')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('periode_alokasi_id')->constrained('periode_alokasi')->cascadeOnDelete();
            $table->string('kode_kegiatan');
            $table->string('nama_kegiatan');
            $table->string('bulan');
            $table->year('tahun');
            $table->enum('jenis_kegiatan', ['sensus', 'survei']);
            $table->timestamps();

            $table->unique(['bast_id', 'kegiatan_id', 'periode_alokasi_id'], 'bast_kegiatan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bast_kegiatan');
    }
};
