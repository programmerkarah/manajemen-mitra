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
            // Partial payment for pencacahan
            $table->boolean('is_partial_payment')->default(false)->after('total_honor');
            $table->decimal('partial_honor', 15, 2)->nullable()->after('is_partial_payment');

            // Partial payment for listing
            $table->boolean('is_partial_payment_listing')->default(false)->after('total_honor_listing');
            $table->decimal('partial_honor_listing', 15, 2)->nullable()->after('is_partial_payment_listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn([
                'is_partial_payment',
                'partial_honor',
                'is_partial_payment_listing',
                'partial_honor_listing',
            ]);
        });
    }
};
