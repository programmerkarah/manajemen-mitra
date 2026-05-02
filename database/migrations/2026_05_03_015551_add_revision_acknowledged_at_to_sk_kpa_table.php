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
        Schema::table('sk_kpa', function (Blueprint $table) {
            $table->timestamp('revision_acknowledged_at')->nullable()->after('signed_at');
            $table->foreignId('revision_acknowledged_by')->nullable()->after('revision_acknowledged_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_kpa', function (Blueprint $table) {
            $table->dropForeign(['revision_acknowledged_by']);
            $table->dropColumn(['revision_acknowledged_at', 'revision_acknowledged_by']);
        });
    }
};
