<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('alokasi_petugas')
            ->whereNull('estimasi_honor_partial')
            ->whereNotNull('partial_honor')
            ->update([
                'estimasi_honor_partial' => DB::raw('partial_honor'),
            ]);

        DB::table('alokasi_petugas')
            ->whereNull('estimasi_honor_partial_listing')
            ->whereNotNull('partial_honor_listing')
            ->update([
                'estimasi_honor_partial_listing' => DB::raw('partial_honor_listing'),
            ]);

        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->dropColumn([
                'partial_honor',
                'partial_honor_listing',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->decimal('partial_honor', 15, 2)->nullable()->after('is_partial_payment');
            $table->decimal('partial_honor_listing', 15, 2)->nullable()->after('is_partial_payment_listing');
        });

        DB::table('alokasi_petugas')
            ->whereNull('partial_honor')
            ->whereNotNull('estimasi_honor_partial')
            ->update([
                'partial_honor' => DB::raw('estimasi_honor_partial'),
            ]);

        DB::table('alokasi_petugas')
            ->whereNull('partial_honor_listing')
            ->whereNotNull('estimasi_honor_partial_listing')
            ->update([
                'partial_honor_listing' => DB::raw('estimasi_honor_partial_listing'),
            ]);
    }
};
