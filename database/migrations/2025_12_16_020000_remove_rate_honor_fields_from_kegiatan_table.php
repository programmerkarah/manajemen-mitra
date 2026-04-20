<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop foreign key constraints with raw SQL (ignore error if not exists)
        try {
            DB::statement('ALTER TABLE kegiatan DROP FOREIGN KEY kegiatan_rate_honor_id_foreign');
        } catch (Throwable $e) {
        }
        try {
            DB::statement('ALTER TABLE kegiatan DROP FOREIGN KEY kegiatan_rate_honor_approved_by_foreign');
        } catch (Throwable $e) {
        }
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn([
                'rate_honor_id',
                'rate_honor_status',
                'rate_honor_approved_by',
                'rate_honor_approved_at',
                'rate_honor_notes',
            ]);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->string('rate_honor_id')->nullable();
            $table->enum('rate_honor_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->unsignedBigInteger('rate_honor_approved_by')->nullable();
            $table->timestamp('rate_honor_approved_at')->nullable();
            $table->text('rate_honor_notes')->nullable();
        });
    }
};
