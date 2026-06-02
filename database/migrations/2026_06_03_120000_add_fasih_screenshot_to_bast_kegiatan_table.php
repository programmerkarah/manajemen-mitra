<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->text('fasih_screenshot_path')->nullable()->after('signed_file_path');
            $table->timestamp('fasih_screenshot_uploaded_at')->nullable()->after('fasih_screenshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->dropColumn([
                'fasih_screenshot_path',
                'fasih_screenshot_uploaded_at',
            ]);
        });
    }
};
