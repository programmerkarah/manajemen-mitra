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
        Schema::create('sk_kpa', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sk')->unique();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->integer('bulan');
            $table->integer('tahun');
            $table->date('tanggal_sk');
            $table->string('nama_kpa');
            $table->string('nip_kpa');
            $table->text('perihal');
            $table->text('dasar_hukum')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'diterbitkan', 'dibatalkan'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk_kpa');
    }
};
