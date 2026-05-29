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
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->decimal('jumlah_satuan', 10, 2)->default(0)->change();
            $table->decimal('partial_jumlah_satuan', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->integer('jumlah_satuan')->default(0)->change();
            $table->integer('partial_jumlah_satuan')->nullable()->change();
        });
    }
};
