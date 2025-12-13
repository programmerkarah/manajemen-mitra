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
        // Add rate_honor_id to kegiatan table
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->foreignId('rate_honor_id')->nullable()->after('pj_user_id')->constrained('rate_honor')->cascadeOnDelete();
        });

        // Migrate existing data: copy rate_honor_id from first alokasi of each kegiatan
        // Using Eloquent for better database compatibility (works with both MySQL and SQLite)
        $alokasiData = DB::table('alokasi_mitra')
            ->select('kegiatan_id', 'rate_honor_id')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('alokasi_mitra')
                    ->groupBy('kegiatan_id');
            })
            ->get();

        foreach ($alokasiData as $alokasi) {
            DB::table('kegiatan')
                ->where('id', $alokasi->kegiatan_id)
                ->update(['rate_honor_id' => $alokasi->rate_honor_id]);
        }

        // Remove rate_honor_id from alokasi_mitra table
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->dropForeign(['rate_honor_id']);
            $table->dropColumn('rate_honor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add rate_honor_id back to alokasi_mitra
        Schema::table('alokasi_mitra', function (Blueprint $table) {
            $table->foreignId('rate_honor_id')->nullable()->after('mitra_id')->constrained('rate_honor')->cascadeOnDelete();
        });

        // Migrate data back: copy rate_honor_id from kegiatan to all its alokasi
        // Using Eloquent for better database compatibility (works with both MySQL and SQLite)
        $kegiatanData = DB::table('kegiatan')
            ->select('id', 'rate_honor_id')
            ->whereNotNull('rate_honor_id')
            ->get();

        foreach ($kegiatanData as $kegiatan) {
            DB::table('alokasi_mitra')
                ->where('kegiatan_id', $kegiatan->id)
                ->update(['rate_honor_id' => $kegiatan->rate_honor_id]);
        }

        // Remove rate_honor_id from kegiatan
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropForeign(['rate_honor_id']);
            $table->dropColumn('rate_honor_id');
        });
    }
};
