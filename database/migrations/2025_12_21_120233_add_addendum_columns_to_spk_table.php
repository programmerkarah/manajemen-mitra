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
        Schema::table('spk', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_spk_id')->nullable()->after('id');
            $table->integer('addendum_number')->default(0)->after('parent_spk_id')->comment('0 for original SPK, 1+ for addendums');

            $table->foreign('parent_spk_id')->references('id')->on('spk')->onDelete('cascade');
            $table->index(['parent_spk_id', 'addendum_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->dropForeign(['parent_spk_id']);
            $table->dropIndex(['parent_spk_id', 'addendum_number']);
            $table->dropColumn(['parent_spk_id', 'addendum_number']);
        });
    }
};
