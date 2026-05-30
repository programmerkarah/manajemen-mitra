<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alokasi_petugas_frame_sampel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alokasi_petugas_id')->constrained('alokasi_petugas')->cascadeOnDelete();
            $table->foreignId('kegiatan_frame_sampel_id')->constrained('kegiatan_frame_sampel')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alokasi_petugas_id', 'kegiatan_frame_sampel_id'], 'unique_alokasi_frame_sampel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alokasi_petugas_frame_sampel');
    }
};
