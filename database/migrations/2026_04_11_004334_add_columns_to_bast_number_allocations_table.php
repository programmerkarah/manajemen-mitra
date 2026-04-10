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
        Schema::table('bast_number_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('spk_id')->nullable()->after('id');
            $table->string('nomor_bast')->nullable()->after('spk_id');
            $table->unsignedSmallInteger('tahun')->nullable()->after('nomor_bast');
            $table->unsignedTinyInteger('bulan')->nullable()->after('tahun');
            $table->string('status')->default('allocated')->after('bulan');
            $table->timestamp('allocated_at')->nullable()->after('status');
            $table->timestamp('used_at')->nullable()->after('allocated_at');

            $table->foreign('spk_id')->references('id')->on('spk')->cascadeOnDelete();
            $table->unique('spk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bast_number_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spk_id');
            $table->dropColumn(['nomor_bast', 'tahun', 'bulan', 'status', 'allocated_at', 'used_at']);
        });
    }
};
