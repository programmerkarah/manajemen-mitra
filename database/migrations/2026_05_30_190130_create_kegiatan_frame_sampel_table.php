<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_frame_sampel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('frame_sampel_id')->constrained('master_frame_sampel')->cascadeOnDelete();
            $table->enum('tahapan', ['listing', 'pencacahan'])->default('pencacahan');
            $table->string('nama_frame')->nullable();
            $table->string('kode_kecamatan', 20)->nullable();
            $table->string('kode_desa', 20)->nullable();
            $table->string('kode_sls', 20)->nullable();
            $table->string('kode_sub_sls', 20)->nullable();
            $table->string('kode_segmen', 20)->nullable();
            $table->json('identitas_tambahan')->nullable();
            $table->unsignedInteger('target_unit_sampel')->default(0);
            $table->timestamps();

            $table->index(['kegiatan_id', 'tahapan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_frame_sampel');
    }
};
