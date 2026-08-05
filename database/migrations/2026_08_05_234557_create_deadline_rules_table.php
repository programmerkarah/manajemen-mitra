<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('feature_key', 50)->index();
            $table->string('action_key', 50)->index();
            $table->string('label');
            $table->string('description')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->boolean('is_enforced')->default(false);
            $table->boolean('allow_manual_bypass')->default(true);
            $table->string('scope_type', 20)->default('monthly');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_rules');
    }
};
