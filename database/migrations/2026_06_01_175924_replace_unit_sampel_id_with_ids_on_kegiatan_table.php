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
        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->json('unit_sampel_pencacahan_ids')->nullable()->after('unit_sampel_pencacahan_id');
            $table->json('unit_sampel_listing_ids')->nullable()->after('unit_sampel_listing_id');
        });

        // Migrate existing single IDs into JSON arrays
        DB::table('kegiatan')
            ->whereNotNull('unit_sampel_pencacahan_id')
            ->eachById(function (object $row): void {
                DB::table('kegiatan')
                    ->where('id', $row->id)
                    ->update(['unit_sampel_pencacahan_ids' => json_encode([$row->unit_sampel_pencacahan_id])]);
            });

        DB::table('kegiatan')
            ->whereNotNull('unit_sampel_listing_id')
            ->eachById(function (object $row): void {
                DB::table('kegiatan')
                    ->where('id', $row->id)
                    ->update(['unit_sampel_listing_ids' => json_encode([$row->unit_sampel_listing_id])]);
            });

        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('unit_sampel_pencacahan_id');
            $table->dropConstrainedForeignId('unit_sampel_listing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->foreignId('unit_sampel_pencacahan_id')->nullable()->constrained('master_unit_sampel')->nullOnDelete();
            $table->foreignId('unit_sampel_listing_id')->nullable()->constrained('master_unit_sampel')->nullOnDelete();
        });

        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->dropColumn(['unit_sampel_pencacahan_ids', 'unit_sampel_listing_ids']);
        });
    }
};
