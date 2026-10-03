<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensus_ekonomi_pkpp_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('sensus_ekonomi_pkpp_contracts', 'signed_file_path')) {
                $table->text('signed_file_path')->nullable()->after('status');
            }
            if (! Schema::hasColumn('sensus_ekonomi_pkpp_contracts', 'signed_uploaded_at')) {
                $table->timestamp('signed_uploaded_at')->nullable()->after('signed_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensus_ekonomi_pkpp_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('sensus_ekonomi_pkpp_contracts', 'signed_uploaded_at')) {
                $table->dropColumn('signed_uploaded_at');
            }
            if (Schema::hasColumn('sensus_ekonomi_pkpp_contracts', 'signed_file_path')) {
                $table->dropColumn('signed_file_path');
            }
        });
    }
};
