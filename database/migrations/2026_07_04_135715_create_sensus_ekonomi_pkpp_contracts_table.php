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
        Schema::create('sensus_ekonomi_pkpp_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replacement_id')->constrained('sensus_ekonomi_petugas_replacements')->cascadeOnDelete();
            $table->foreignId('periode_alokasi_id')->constrained('periode_alokasi')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas');
            $table->foreignId('spk_id')->nullable()->constrained('spk')->nullOnDelete();
            $table->string('nomor_pkpp')->nullable();
            $table->date('tanggal_kontrak');
            $table->date('tanggal_mulai_lapangan');
            $table->string('skema_kode', 32);
            $table->unsignedTinyInteger('termin_count');
            $table->decimal('honor_ob', 8, 2);
            $table->unsignedTinyInteger('persentase_termin_1')->nullable();
            $table->unsignedTinyInteger('persentase_termin_2')->nullable();
            $table->json('target_termin_1')->nullable();
            $table->json('target_termin_2')->nullable();
            $table->json('target_total')->nullable();
            $table->string('waktu_penyelesaian_termin_1')->nullable();
            $table->string('waktu_penyelesaian_termin_akhir')->nullable();
            $table->string('periode_pasal_7')->nullable();
            $table->decimal('biaya_ganti_rugi', 12, 2)->default(347440);
            $table->enum('status', ['draft', 'aktif', 'selesai', 'dibatalkan'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['periode_alokasi_id', 'status']);
            $table->index(['skema_kode', 'termin_count']);
            $table->unique(['replacement_id', 'petugas_id'], 'pkpp_replacement_petugas_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensus_ekonomi_pkpp_contracts');
    }
};
