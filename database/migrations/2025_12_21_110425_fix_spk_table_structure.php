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
        Schema::table('spk', function (Blueprint $table) {
            // Remove sk_kpa_id foreign key and column - not needed for SPK
            $table->dropForeign(['sk_kpa_id']);
            $table->dropColumn('sk_kpa_id');

            // Make uraian_pekerjaan nullable - not always required
            $table->text('uraian_pekerjaan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            // Re-add sk_kpa_id
            $table->foreignId('sk_kpa_id')->after('nomor_spk')->constrained('sk_kpa')->cascadeOnDelete();

            // Make uraian_pekerjaan required again
            $table->text('uraian_pekerjaan')->nullable(false)->change();
        });
    }
};
