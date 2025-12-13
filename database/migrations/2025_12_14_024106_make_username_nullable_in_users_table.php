<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set default value untuk existing rows yang tidak punya username
        DB::statement('UPDATE users SET username = LOWER(REPLACE(name, " ", "")) WHERE username IS NULL OR username = ""');
        
        Schema::table('users', function (Blueprint $table) {
            // Hanya change nullable, tanpa menambah unique lagi (sudah ada dari migration sebelumnya)
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
        });
    }
};
