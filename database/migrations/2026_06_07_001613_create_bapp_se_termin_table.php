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
        Schema::create('bapp_se_termin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->unsignedTinyInteger('termin')->comment('1 = Termin I (Juli), 2 = Termin II (Agustus)');
            $table->unsignedTinyInteger('bulan')->comment('7 for Termin I, 8 for Termin II');
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_bapp')->nullable();
            $table->date('tanggal_bapp')->nullable();
            $table->string('nama_ketua_tim')->nullable();
            $table->string('nip_ketua_tim')->nullable();
            $table->string('nama_ppk')->nullable();
            $table->string('nip_ppk')->nullable();
            $table->string('jabatan_ppk')->nullable();
            $table->string('nama_kabkota')->nullable();
            $table->unsignedInteger('target_sls')->nullable()->comment('Target SLS for this termin');
            $table->json('target_unit_sampel')->nullable()->comment('Target per unit type {"keluarga": 100, "usaha": 50}');
            $table->unsignedInteger('realisasi_sls')->nullable()->comment('Realisasi SLS');
            $table->json('realisasi_unit_sampel')->nullable()->comment('Realisasi per unit type');
            $table->unsignedTinyInteger('persentase')->default(40)->comment('40 for Termin I, 60 for Termin II');
            $table->decimal('nilai_perjanjian', 15, 2)->nullable()->comment('nilai_kontrak * persentase / 100');
            $table->text('file_path')->nullable();
            $table->string('fasih_screenshot_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['spk_id', 'termin'], 'bapp_se_termin_spk_termin_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bapp_se_termin');
    }
};
