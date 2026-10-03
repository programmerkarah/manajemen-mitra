<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('periode_alokasi')
            ->whereRaw("bulan REGEXP '^[0-9]+$'")
            ->update([
                'bulan' => DB::raw('LPAD(bulan, 2, "0")'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('periode_alokasi')
            ->whereRaw("bulan REGEXP '^[0-9]+$'")
            ->update([
                'bulan' => DB::raw('CAST(bulan AS UNSIGNED)'),
            ]);
    }
};
