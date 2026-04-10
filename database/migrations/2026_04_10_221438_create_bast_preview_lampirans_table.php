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
        Schema::create('bast_preview_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->constrained('spk')->onDelete('cascade');
            $table->unsignedBigInteger('kegiatan_id');
            $table->unsignedBigInteger('periode_alokasi_id');
            $table->string('kode_kegiatan', 50);
            $table->text('file_path')->nullable();
            $table->text('signed_file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('signed_uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['spk_id', 'kegiatan_id', 'periode_alokasi_id'], 'bast_preview_lampirans_spk_kgt_periode_unique');
            $table->index('spk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bast_preview_lampirans');
    }
};
