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
        Schema::table('dasar_hukum', function (Blueprint $table) {
            $table->enum('kategori', [
                'undang_undang',
                'peraturan_pemerintah',
                'peraturan_presiden',
                'peraturan_menteri',
                'keputusan_menteri',
                'peraturan_badan',
                'keputusan_kepala_badan',
            ])->after('id');
            $table->renameColumn('nomor_peraturan', 'nomor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dasar_hukum', function (Blueprint $table) {
            $table->dropColumn('kategori');
            $table->renameColumn('nomor', 'nomor_peraturan');
        });
    }
};
