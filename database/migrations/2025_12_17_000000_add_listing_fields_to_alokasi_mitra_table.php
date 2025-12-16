<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_satuan_listing')->nullable()->after('jumlah_satuan');
            $table->decimal('total_honor_listing', 20, 2)->nullable()->after('total_honor');
        });
    }

    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn(['jumlah_satuan_listing', 'total_honor_listing']);
        });
    }
};
