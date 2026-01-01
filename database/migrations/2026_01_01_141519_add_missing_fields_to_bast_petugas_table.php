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
        Schema::table('bast_petugas', function (Blueprint $table) {
            // Add missing instrumen fields
            $table->string('instrumen_listing')->nullable()->after('satuan_listing');
            $table->string('instrumen_pendataan_lapangan')->nullable()->after('satuan_pendataan_lapangan');

            // Add missing pengolahan listing fields
            $table->decimal('hasil_pengolahan_listing', 10, 2)->nullable()->after('satuan_pengolahan');
            $table->string('satuan_pengolahan_listing')->nullable()->after('hasil_pengolahan_listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bast_petugas', function (Blueprint $table) {
            $table->dropColumn([
                'instrumen_listing',
                'instrumen_pendataan_lapangan',
                'hasil_pengolahan_listing',
                'satuan_pengolahan_listing',
            ]);
        });
    }
};
