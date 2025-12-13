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
        Schema::table('sbml', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['pcl_ppl_max', 'pml_max', 'pengolahan_max']);

            // Add new structure columns
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])
                ->after('tahun_anggaran')
                ->comment('Jenis kegiatan: sensus atau survei');

            $table->enum('status_kepegawaian', ['organik', 'non_organik'])
                ->after('jenis_kegiatan')
                ->comment('Status kepegawaian: organik (PNS/PPPK) atau non organik');

            $table->enum('jenis_penugasan', ['pcl_ppl', 'pml', 'pengolahan'])
                ->after('status_kepegawaian')
                ->comment('Jenis penugasan: PCL/PPL, PML, atau Petugas Pengolahan');

            $table->decimal('honor_max', 15, 2)
                ->after('jenis_penugasan')
                ->comment('Batas maksimal honor per bulan');

            // Update unique constraint
            $table->dropUnique(['tahun_anggaran']);
            $table->unique(['tahun_anggaran', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan'], 'sbml_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbml', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('sbml_unique');

            // Drop new columns
            $table->dropColumn(['jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'honor_max']);

            // Restore old columns
            $table->decimal('pcl_ppl_max', 15, 2)->comment('Batas maksimal honor per bulan untuk PCL/PPL');
            $table->decimal('pml_max', 15, 2)->comment('Batas maksimal honor per bulan untuk PML');
            $table->decimal('pengolahan_max', 15, 2)->comment('Batas maksimal honor per bulan untuk Petugas Pengolahan');

            // Restore unique constraint
            $table->unique('tahun_anggaran');
        });
    }
};
