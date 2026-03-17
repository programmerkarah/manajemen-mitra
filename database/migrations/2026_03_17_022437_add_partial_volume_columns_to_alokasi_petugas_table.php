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
            // Add volume-based partial payment columns for pencacahan
            $table->integer('partial_jumlah_satuan')->nullable()->after('is_partial_payment');
            $table->decimal('estimasi_honor_partial', 15, 2)->nullable()->after('partial_jumlah_satuan');

            // Add volume-based partial payment columns for listing
            $table->integer('partial_jumlah_satuan_listing')->nullable()->after('is_partial_payment_listing');
            $table->decimal('estimasi_honor_partial_listing', 15, 2)->nullable()->after('partial_jumlah_satuan_listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn([
                'partial_jumlah_satuan',
                'estimasi_honor_partial',
                'partial_jumlah_satuan_listing',
                'estimasi_honor_partial_listing',
            ]);
        });
    }
};
