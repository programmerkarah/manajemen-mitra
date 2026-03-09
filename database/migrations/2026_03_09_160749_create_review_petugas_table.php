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
        Schema::create('review_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('ulasan', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['kegiatan_id', 'petugas_id', 'reviewer_user_id'], 'review_petugas_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_petugas');
    }
};
