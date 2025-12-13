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
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->enum('peran', ['pcl_ppl', 'pml', 'pengolahan'])
                ->after('total_honor')
                ->default('pcl_ppl')
                ->comment('Peran mitra: PCL/PPL, PML, atau Petugas Pengolahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropColumn('peran');
        });
    }
};
