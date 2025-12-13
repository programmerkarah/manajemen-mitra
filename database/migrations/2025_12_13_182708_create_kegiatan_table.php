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
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kegiatan')->unique();
            $table->string('nama_kegiatan');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('tahun_anggaran');
            $table->decimal('anggaran', 15, 2)->nullable();
            $table->foreignId('pj_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'diajukan', 'divalidasi', 'aktif', 'selesai', 'dibatalkan'])->default('draft');
            $table->date('tanggal_validasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
