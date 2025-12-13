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
        Schema::table('rate_honor', function (Blueprint $table) {
            $table->dropColumn(['rate_per_hari', 'rate_per_bulan']);
            $table->foreignId('satuan_id')->after('deskripsi')->constrained('satuan')->cascadeOnDelete();
            $table->decimal('rate', 12, 2)->after('satuan_id');
        });

        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropColumn('jumlah_hari');
            $table->integer('jumlah_satuan')->default(0)->after('tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropColumn('jumlah_satuan');
            $table->integer('jumlah_hari')->default(0)->after('tahun');
        });

        Schema::table('rate_honor', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
            $table->dropColumn(['satuan_id', 'rate']);
            $table->decimal('rate_per_hari', 12, 2)->after('deskripsi');
            $table->decimal('rate_per_bulan', 12, 2)->nullable()->after('rate_per_hari');
        });
    }
};
