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
        Schema::table('mitra', function (Blueprint $table) {
            // Ubah kolom yang dienkripsi menjadi text untuk menampung encrypted string
            $table->text('nik')->change();
            $table->text('npwp')->nullable()->change();
            $table->text('no_rekening')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitra', function (Blueprint $table) {
            // Kembalikan ke ukuran semula
            $table->string('nik', 16)->change();
            $table->string('npwp', 20)->nullable()->change();
            $table->string('no_rekening')->nullable()->change();
        });
    }
};
