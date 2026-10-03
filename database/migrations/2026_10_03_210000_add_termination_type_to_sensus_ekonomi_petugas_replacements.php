<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensus_ekonomi_petugas_replacements', function (Blueprint $table) {
            if (! Schema::hasColumn('sensus_ekonomi_petugas_replacements', 'termination_type')) {
                $table->enum('termination_type', ['diberhentikan', 'mengundurkan_diri'])
                    ->nullable()
                    ->after('spk_lama_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensus_ekonomi_petugas_replacements', function (Blueprint $table) {
            if (Schema::hasColumn('sensus_ekonomi_petugas_replacements', 'termination_type')) {
                $table->dropColumn('termination_type');
            }
        });
    }
};
