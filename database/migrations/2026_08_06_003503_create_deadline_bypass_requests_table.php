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
        Schema::create('deadline_bypass_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_rule_id')->constrained('deadline_rules')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->nullOnDelete();
            $table->foreignId('periode_alokasi_id')->nullable()->constrained('periode_alokasi')->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('route_name')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('target_url')->nullable();
            $table->text('reason')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedTinyInteger('max_uses')->default(1);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['requested_by_user_id', 'status']);
            $table->index(['deadline_rule_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deadline_bypass_requests');
    }
};
