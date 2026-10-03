<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensus_ekonomi_petugas_replacements', function (Blueprint $table) {
            if (! Schema::hasColumn('sensus_ekonomi_petugas_replacements', 'termin_i_paid')) {
                $table->boolean('termin_i_paid')->nullable()->after('termination_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensus_ekonomi_petugas_replacements', function (Blueprint $table) {
            if (Schema::hasColumn('sensus_ekonomi_petugas_replacements', 'termin_i_paid')) {
                $table->dropColumn('termin_i_paid');
            }
        });
    }
};
