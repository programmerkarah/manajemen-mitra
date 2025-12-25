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
        Schema::table('spk', function (Blueprint $table) {
            $table->string('nomor_urut_suffix', 5)->nullable()->after('nomor_spk');
            $table->integer('nomor_urut_base')->nullable()->after('nomor_urut_suffix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->dropColumn(['nomor_urut_suffix', 'nomor_urut_base']);
        });
    }
};
