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
        DB::table('bapp_se_termin')
            ->whereNull('replacement_termin_count')
            ->update(['replacement_termin_count' => 0]);

        Schema::table('bapp_se_termin', function (Blueprint $table) {
            $table->index('spk_id', 'bapp_se_termin_spk_id_idx');
            $table->dropUnique('bapp_se_termin_spk_termin_unique');
            $table->unique(
                ['spk_id', 'termin', 'document_type', 'replacement_termin_count'],
                'bapp_se_termin_spk_termin_document_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bapp_se_termin', function (Blueprint $table) {
            $table->dropUnique('bapp_se_termin_spk_termin_document_unique');
            $table->unique(['spk_id', 'termin'], 'bapp_se_termin_spk_termin_unique');
            $table->dropIndex('bapp_se_termin_spk_id_idx');
        });
    }
};
