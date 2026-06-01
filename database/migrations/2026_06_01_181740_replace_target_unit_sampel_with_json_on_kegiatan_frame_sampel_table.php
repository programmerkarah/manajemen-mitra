<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes target_unit_sampel from an integer to a JSON object
     * with format {"<unit_sampel_id>": count, ...} to support
     * multiple unit sampel types per frame row.
     */
    public function up(): void
    {
        // Step 1: add temporary JSON column
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->json('target_unit_sampel_json')->nullable()->after('target_unit_sampel');
        });

        // Step 2: migrate existing integer data to JSON
        DB::table('kegiatan_frame_sampel as kfs')
            ->join('kegiatan as k', 'k.id', '=', 'kfs.kegiatan_id')
            ->select(
                'kfs.id as frame_id',
                'kfs.tahapan',
                DB::raw('kfs.target_unit_sampel as old_target'),
                'k.unit_sampel_pencacahan_ids',
                'k.unit_sampel_listing_ids',
            )
            ->orderBy('kfs.id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $idsJson = $row->tahapan === 'listing'
                        ? $row->unit_sampel_listing_ids
                        : $row->unit_sampel_pencacahan_ids;

                    $ids = is_string($idsJson) ? json_decode($idsJson, true) : [];
                    $ids = is_array($ids) ? $ids : [];

                    $firstId = ! empty($ids) ? $ids[0] : null;
                    $oldTarget = (int) $row->old_target;

                    $json = $firstId !== null && $oldTarget > 0
                        ? [$firstId => $oldTarget]
                        : [];

                    DB::table('kegiatan_frame_sampel')
                        ->where('id', $row->frame_id)
                        ->update(['target_unit_sampel_json' => json_encode($json)]);
                }
            });

        // Step 3: drop old integer column
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->dropColumn('target_unit_sampel');
        });

        // Step 4: rename new column
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->renameColumn('target_unit_sampel_json', 'target_unit_sampel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: add integer column back
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->unsignedInteger('target_unit_sampel_int')->default(0)->after('target_unit_sampel');
        });

        // Step 2: compute total from JSON and store as integer
        DB::table('kegiatan_frame_sampel')
            ->select('id', 'target_unit_sampel')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $json = is_string($row->target_unit_sampel)
                        ? json_decode($row->target_unit_sampel, true)
                        : [];
                    $json = is_array($json) ? $json : [];
                    $total = (int) array_sum($json);

                    DB::table('kegiatan_frame_sampel')
                        ->where('id', $row->id)
                        ->update(['target_unit_sampel_int' => $total]);
                }
            });

        // Step 3: drop JSON column
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->dropColumn('target_unit_sampel');
        });

        // Step 4: rename integer column back
        Schema::table('kegiatan_frame_sampel', function (Blueprint $table): void {
            $table->renameColumn('target_unit_sampel_int', 'target_unit_sampel');
        });
    }
};
