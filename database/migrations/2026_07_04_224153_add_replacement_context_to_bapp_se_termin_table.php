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
        Schema::table('bapp_se_termin', function (Blueprint $table) {
            $table->foreignId('replacement_id')
                ->nullable()
                ->after('spk_id')
                ->constrained('sensus_ekonomi_petugas_replacements')
                ->nullOnDelete();
            $table->enum('document_type', [
                'regular',
                'stopped_petugas',
                'replacement_pkpp',
            ])->default('regular')->after('termin');
            $table->unsignedTinyInteger('replacement_termin_count')
                ->nullable()
                ->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bapp_se_termin', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_id');
            $table->dropColumn([
                'document_type',
                'replacement_termin_count',
            ]);
        });
    }
};
