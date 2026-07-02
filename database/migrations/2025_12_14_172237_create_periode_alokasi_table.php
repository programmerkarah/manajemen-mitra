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
        Schema::create('periode_alokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->index();
            $table->string('bulan', 2); // 01-12
            $table->year('tahun');
            $table->enum('jenis_kegiatan', ['sensus', 'survei']);
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak', 'selesai'])->default('draft');

            // Submission tracking
            $table->foreignId('submitted_by')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();

            // Approval tracking
            $table->foreignId('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();

            // Rejection tracking
            $table->foreignId('rejected_by')->nullable()->index();
            $table->timestamp('rejected_at')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kegiatan_id', 'periode_alokasi_kegiatan_fk')
                ->references('id')
                ->on('kegiatan')
                ->cascadeOnDelete();
            $table->foreign('submitted_by', 'periode_alokasi_submitted_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('approved_by', 'periode_alokasi_approved_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('rejected_by', 'periode_alokasi_rejected_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Unique constraint: one periode per kegiatan+bulan+tahun (excluding soft deleted)
            $table->unique(['kegiatan_id', 'bulan', 'tahun', 'deleted_at'], 'unique_periode_alokasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_alokasi');
    }
};
