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
            $table->decimal('rate_listing', 12, 2)->nullable()->after('rate');
            $table->foreignId('satuan_listing_id')->nullable()->after('satuan_id')->constrained('satuan')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rate_honor', function (Blueprint $table) {
            $table->dropForeign(['satuan_listing_id']);
            $table->dropColumn(['rate_listing', 'satuan_listing_id']);
        });
    }
};
