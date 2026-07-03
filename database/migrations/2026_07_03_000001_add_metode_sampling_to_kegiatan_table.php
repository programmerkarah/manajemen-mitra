<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->enum('metode_sampling', ['targeted', 'purpossive'])
                ->nullable()
                ->default('targeted')
                ->after('metode_pendataan_listing')
                ->comment('Metode sampling survei: targeted atau purpossive');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table): void {
            $table->dropColumn('metode_sampling');
        });
    }
};
