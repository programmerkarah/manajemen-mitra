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
            $table->string('signed_file_path')->nullable()->after('file_path');
            $table->boolean('is_signed')->default(false)->after('signed_file_path');
            $table->timestamp('signed_at')->nullable()->after('is_signed');
            $table->unsignedBigInteger('signed_by')->nullable()->after('signed_at');

            $table->foreign('signed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_kpa', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn(['signed_file_path', 'is_signed', 'signed_at', 'signed_by']);
        });
    }
};
