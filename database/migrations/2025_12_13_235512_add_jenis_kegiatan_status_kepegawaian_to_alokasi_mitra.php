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
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])
                ->after('peran')
                ->default('survei')
                ->comment('Jenis kegiatan: sensus atau survei');

            $table->enum('status_kepegawaian', ['organik', 'non_organik'])
                ->after('jenis_kegiatan')
                ->default('non_organik')
                ->comment('Status kepegawaian: organik (PNS/PPPK) atau non organik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropColumn(['jenis_kegiatan', 'status_kepegawaian']);
        });
    }
};
