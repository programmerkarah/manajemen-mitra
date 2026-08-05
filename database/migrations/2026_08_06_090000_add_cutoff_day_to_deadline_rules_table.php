<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deadline_rules')) {
            return;
        }

        Schema::table('deadline_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('deadline_rules', 'cutoff_day')) {
                $table->unsignedTinyInteger('cutoff_day')->nullable()->after('deadline_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('deadline_rules')) {
            return;
        }

        Schema::table('deadline_rules', function (Blueprint $table) {
            if (Schema::hasColumn('deadline_rules', 'cutoff_day')) {
                $table->dropColumn('cutoff_day');
            }
        });
    }
};
