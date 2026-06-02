<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bast_sensus_realisasi_imports', function (Blueprint $table) {
            $table->text('fasih_screenshot_path')->nullable()->after('realisasi_unit_sampel');
            $table->timestamp('fasih_screenshot_uploaded_at')->nullable()->after('fasih_screenshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('bast_sensus_realisasi_imports', function (Blueprint $table) {
            $table->dropColumn([
                'fasih_screenshot_path',
                'fasih_screenshot_uploaded_at',
            ]);
        });
    }
};
