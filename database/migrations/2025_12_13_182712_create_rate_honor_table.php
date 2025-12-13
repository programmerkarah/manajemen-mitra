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
        Schema::create('rate_honor', function (Blueprint $table) {
            $table->id();
            $table->string('posisi');
            $table->text('deskripsi')->nullable();
            $table->decimal('rate_per_hari', 12, 2);
            $table->decimal('rate_per_bulan', 12, 2)->nullable();
            $table->integer('tahun_berlaku');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_honor');
    }
};
