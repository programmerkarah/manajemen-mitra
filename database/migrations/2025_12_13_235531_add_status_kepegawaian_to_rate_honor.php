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
            $table->enum('jenis_penugasan', ['pcl_ppl', 'pml', 'pengolahan'])
                ->after('posisi')
                ->default('pcl_ppl')
                ->comment('Jenis penugasan: PCL/PPL, PML, atau Petugas Pengolahan');

            $table->enum('status_kepegawaian', ['organik', 'non_organik'])
                ->after('jenis_penugasan')
                ->default('non_organik')
                ->comment('Status kepegawaian: organik (PNS/PPPK) atau non organik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rate_honor', function (Blueprint $table) {
            $table->dropColumn(['jenis_penugasan', 'status_kepegawaian']);
        });
    }
};
