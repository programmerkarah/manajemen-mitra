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
        Schema::create('bast_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bast_id')->constrained('bast')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete();
            $table->string('nomor_spk');
            $table->string('nama_petugas');
            
            // Hasil Pekerjaan
            $table->decimal('hasil_listing', 10, 2)->nullable();
            $table->string('satuan_listing')->nullable();
            $table->decimal('hasil_pendataan_lapangan', 10, 2)->nullable();
            $table->string('satuan_pendataan_lapangan')->nullable();
            $table->decimal('hasil_pengolahan', 10, 2)->nullable();
            $table->string('satuan_pengolahan')->nullable();
            
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->unique(['bast_id', 'petugas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bast_petugas');
    }
};
