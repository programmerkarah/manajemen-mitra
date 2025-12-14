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
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->decimal('sisa_pagu', 15, 2)->nullable()->after('status')
                ->comment('Sisa pagu kegiatan setelah periode ini ditambahkan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_alokasi', function (Blueprint $table) {
            $table->dropColumn('sisa_pagu');
        });
    }
};
