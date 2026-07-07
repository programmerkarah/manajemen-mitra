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
        Schema::create('sensus_ekonomi_petugas_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_alokasi_id')->constrained('periode_alokasi')->cascadeOnDelete();
            $table->foreignId('petugas_berhenti_id')->constrained('petugas');
            $table->foreignId('petugas_pengganti_id')->nullable()->constrained('petugas');
            $table->foreignId('pml_cover_petugas_id')->nullable()->constrained('petugas');
            $table->foreignId('spk_lama_id')->nullable()->constrained('spk');
            $table->date('tanggal_berhenti');
            $table->date('tanggal_mulai_cover')->nullable();
            $table->date('tanggal_mulai_pkpp')->nullable();
            $table->decimal('target_awal', 12, 2)->default(0);
            $table->decimal('realisasi_petugas_berhenti', 12, 2)->default(0);
            $table->decimal('realisasi_pml_cover', 12, 2)->default(0);
            $table->decimal('target_sisa', 12, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pml_cover',
                'pengganti_ditetapkan',
                'selesai',
                'dibatalkan',
            ])->default('draft');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['periode_alokasi_id', 'status'], 'se_repl_periode_status_idx');
            $table->index(['petugas_berhenti_id', 'tanggal_berhenti'], 'se_repl_petugas_tgl_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensus_ekonomi_petugas_replacements');
    }
};
