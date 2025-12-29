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
        Schema::table('bast', function (Blueprint $table) {
            // Add periode_alokasi_id foreign key
            $table->foreignId('periode_alokasi_id')->after('spk_id')->nullable()->constrained('periode_alokasi')->cascadeOnDelete();
            
            // Add menggunakan_fasih checkbox
            $table->boolean('menggunakan_fasih')->default(false)->after('tanggal_serah_terima');
            
            // Make spk_id nullable since BAST will be based on periode, not individual SPK
            $table->bigInteger('spk_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bast', function (Blueprint $table) {
            $table->dropForeign(['periode_alokasi_id']);
            $table->dropColumn('periode_alokasi_id');
            $table->dropColumn('menggunakan_fasih');
            $table->bigInteger('spk_id')->unsigned()->nullable(false)->change();
        });
    }
};
