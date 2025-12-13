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
        Schema::create('bast', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bast')->unique();
            $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->date('tanggal_bast');
            $table->date('tanggal_serah_terima');
            $table->text('uraian_pekerjaan');
            $table->string('nama_ketua_tim');
            $table->string('nip_ketua_tim')->nullable();
            $table->string('nama_ppk');
            $table->string('nip_ppk');
            $table->text('hasil_pekerjaan')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'diserahkan', 'diterima', 'ditolak'])->default('draft');
            $table->text('catatan')->nullable();
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
        Schema::dropIfExists('bast');
    }
};
