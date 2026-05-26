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
            $table->string('nomor_ln', 50)->nullable()->after('tahun');
            $table->unsignedSmallInteger('tahun_ln')->nullable()->after('nomor_ln');
            $table->string('nomor_tln', 50)->nullable()->after('tahun_ln');
            $table->unsignedSmallInteger('tahun_tln')->nullable()->after('nomor_tln');
            $table->string('nomor_bn', 50)->nullable()->after('tahun_tln');
            $table->unsignedSmallInteger('tahun_bn')->nullable()->after('nomor_bn');
            $table->enum('jenis', ['pertama', 'perubahan'])->default('pertama')->after('tahun_bn');
            $table->unsignedBigInteger('induk_id')->nullable()->after('jenis');
            $table->foreign('induk_id')->references('id')->on('dasar_hukum')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dasar_hukum', function (Blueprint $table) {
            $table->dropForeign(['induk_id']);
            $table->dropColumn(['nomor_ln', 'tahun_ln', 'nomor_tln', 'tahun_tln', 'nomor_bn', 'tahun_bn', 'jenis', 'induk_id']);
        });
    }
};
