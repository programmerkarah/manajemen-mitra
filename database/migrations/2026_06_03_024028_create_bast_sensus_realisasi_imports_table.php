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
        Schema::create('bast_sensus_realisasi_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_spk')->nullable();
            $table->string('nik_petugas', 32)->nullable();
            $table->string('nama_petugas')->nullable();
            $table->unsignedInteger('muatan_prelist_keluarga')->nullable();
            $table->unsignedInteger('muatan_prelist_usaha')->nullable();
            $table->unsignedInteger('realisasi_keluarga')->nullable();
            $table->unsignedInteger('realisasi_usaha')->nullable();
            $table->json('realisasi_unit_sampel')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['spk_id', 'bulan', 'tahun'], 'bast_se_realisasi_imports_spk_bulan_tahun_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bast_sensus_realisasi_imports');
    }
};
