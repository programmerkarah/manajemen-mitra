<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add new columns
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('type')->default('general')->after('action'); // auth, mitra, kegiatan, user, system, etc.
            $table->text('description')->nullable()->after('type');
            $table->string('ip_address')->nullable()->after('status');
            $table->text('user_agent')->nullable()->after('ip_address');

            // Rename meta to metadata and change to JSON
            $table->json('metadata')->nullable()->after('user_agent');

            // Add updated_at timestamp
            $table->timestamp('updated_at')->nullable()->after('created_at');

            // Add indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('created_at');
        });

        // Drop the old meta column
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['created_at']);

            $table->dropColumn([
                'user_name',
                'type',
                'description',
                'ip_address',
                'user_agent',
                'metadata',
                'updated_at',
            ]);

            $table->text('meta')->nullable();
        });
    }
};
