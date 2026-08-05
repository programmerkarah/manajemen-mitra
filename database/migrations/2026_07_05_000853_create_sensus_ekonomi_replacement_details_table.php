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
        if (Schema::hasTable('sensus_ekonomi_replacement_details')) {
            return;
        }

        Schema::create('sensus_ekonomi_replacement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replacement_id')->constrained('sensus_ekonomi_petugas_replacements')->cascadeOnDelete();
            $table->unsignedBigInteger('alokasi_petugas_frame_sampel_id');
            $table->unsignedBigInteger('kegiatan_frame_sampel_id')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('target_awal', 12, 2)->default(0);
            $table->decimal('realisasi_petugas_berhenti', 12, 2)->default(0);
            $table->decimal('realisasi_pml_cover', 12, 2)->default(0);
            $table->decimal('target_sisa', 12, 2)->default(0);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->foreign('alokasi_petugas_frame_sampel_id', 'se_repl_det_alok_frame_fk')
                ->references('id')
                ->on('alokasi_petugas_frame_sampel')
                ->cascadeOnDelete();
            $table->foreign('kegiatan_frame_sampel_id', 'se_repl_det_keg_frame_fk')
                ->references('id')
                ->on('kegiatan_frame_sampel')
                ->nullOnDelete();

            $table->unique(['replacement_id', 'alokasi_petugas_frame_sampel_id'], 'se_repl_detail_repl_frame_unique');
            $table->index(['replacement_id', 'urutan'], 'se_repl_detail_repl_urutan_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensus_ekonomi_replacement_details');
    }
};
