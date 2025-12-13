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
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->enum('rate_honor_status', ['pending', 'approved', 'rejected'])->default('pending')->after('rate_honor_id');
            $table->foreignId('rate_honor_approved_by')->nullable()->after('rate_honor_status')->constrained('users')->nullOnDelete();
            $table->timestamp('rate_honor_approved_at')->nullable()->after('rate_honor_approved_by');
            $table->text('rate_honor_notes')->nullable()->after('rate_honor_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropForeign(['rate_honor_approved_by']);
            $table->dropColumn([
                'rate_honor_status',
                'rate_honor_approved_by',
                'rate_honor_approved_at',
                'rate_honor_notes',
            ]);
        });
    }
};
