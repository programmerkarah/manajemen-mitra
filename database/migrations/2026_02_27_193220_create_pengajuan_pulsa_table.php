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
        Schema::create('pengajuan_pulsa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('periode_alokasi_id')->nullable()->constrained('periode_alokasi')->nullOnDelete();
            $table->string('bulan', 2)->comment('01-12');
            $table->smallInteger('tahun');
            $table->enum('jenis_pulsa', ['pelatihan', 'pendataan'])->comment('Jenis pengajuan pulsa');
            $table->decimal('nominal', 12, 2)->default(0)->comment('Nominal pulsa dalam rupiah');
            $table->enum('status', ['draft', 'dikirim', 'diterima', 'ditolak'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('catatan')->nullable()->comment('Catatan pengajuan dari ketua tim');
            $table->text('catatan_penolakan')->nullable()->comment('Alasan penolakan dari operator');
            $table->timestamps();

            $table->index(['petugas_id', 'kegiatan_id', 'bulan', 'tahun', 'jenis_pulsa']);
            $table->index(['status', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pulsa');
    }
};
