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
        Schema::table('review_petugas', function (Blueprint $table) {
            if (! $this->indexExists('review_petugas', 'review_petugas_reviewer_user_id_index')) {
                $table->index('reviewer_user_id', 'review_petugas_reviewer_user_id_index');
            }

            if (! $this->indexExists('review_petugas', 'review_petugas_periode_alokasi_id_index')) {
                $table->index('periode_alokasi_id', 'review_petugas_periode_alokasi_id_index');
            }

            if ($this->indexExists('review_petugas', 'review_petugas_reviewer_periode_unique')) {
                $table->dropUnique('review_petugas_reviewer_periode_unique');
            }

            if (! $this->indexExists('review_petugas', 'review_petugas_reviewer_periode_petugas_unique')) {
                $table->unique(
                    ['reviewer_user_id', 'periode_alokasi_id', 'petugas_id'],
                    'review_petugas_reviewer_periode_petugas_unique'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_petugas', function (Blueprint $table) {
            if ($this->indexExists('review_petugas', 'review_petugas_reviewer_periode_petugas_unique')) {
                $table->dropUnique('review_petugas_reviewer_periode_petugas_unique');
            }

            if (! $this->indexExists('review_petugas', 'review_petugas_reviewer_periode_unique')) {
                $table->unique(
                    ['reviewer_user_id', 'periode_alokasi_id'],
                    'review_petugas_reviewer_periode_unique'
                );
            }

            if ($this->indexExists('review_petugas', 'review_petugas_periode_alokasi_id_index')) {
                $table->dropIndex('review_petugas_periode_alokasi_id_index');
            }

            if ($this->indexExists('review_petugas', 'review_petugas_reviewer_user_id_index')) {
                $table->dropIndex('review_petugas_reviewer_user_id_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select(sprintf("PRAGMA index_list('%s')", $table));

            foreach ($indexes as $item) {
                if (($item->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
