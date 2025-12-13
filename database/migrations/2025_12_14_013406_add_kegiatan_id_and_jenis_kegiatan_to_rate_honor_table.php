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
            $table->foreignId('kegiatan_id')->nullable()->after('id')->constrained('kegiatan')->onDelete('cascade');
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])->nullable()->after('posisi');
            $table->decimal('rate', 15, 0)->change(); // Change to no decimal places
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rate_honor', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_id']);
            $table->dropColumn(['kegiatan_id', 'jenis_kegiatan']);
            $table->decimal('rate', 15, 2)->change(); // Revert to 2 decimal places
        });
    }
};
