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
        Schema::table('pengajuan_pulsa', function (Blueprint $table) {
            $table->decimal('nominal_disetujui', 12, 2)
                ->nullable()
                ->after('nominal')
                ->comment('Nominal yang disetujui operator/admin, default = nominal pengajuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_pulsa', function (Blueprint $table) {
            $table->dropColumn('nominal_disetujui');
        });
    }
};
