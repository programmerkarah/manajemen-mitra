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
        Schema::create('spk', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_spk')->unique();
            $table->foreignId('sk_kpa_id')->constrained('sk_kpa')->cascadeOnDelete();
            $table->foreignId('alokasi_petugas_id')->constrained('alokasi_petugas')->cascadeOnDelete();
            $table->date('tanggal_spk');
            $table->date('tanggal_mulai_kerja');
            $table->date('tanggal_selesai_kerja');
            $table->text('uraian_pekerjaan');
            $table->decimal('nilai_kontrak', 15, 2);
            $table->string('nama_ppk');
            $table->string('nip_ppk');
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'diterbitkan', 'selesai', 'dibatalkan'])->default('draft');
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
        Schema::dropIfExists('spk');
    }
};
