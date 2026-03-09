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
        if (! Schema::hasColumn('review_petugas', 'periode_alokasi_id')) {
            Schema::table('review_petugas', function (Blueprint $table) {
                $table->foreignId('periode_alokasi_id')
                    ->nullable()
                    ->after('petugas_id')
                    ->constrained('periode_alokasi')
                    ->cascadeOnDelete();
            });
        }

        Schema::table('review_petugas', function (Blueprint $table) {
            if (! $this->indexExists('review_petugas', 'review_petugas_kegiatan_id_index')) {
                $table->index('kegiatan_id', 'review_petugas_kegiatan_id_index');
            }

            if ($this->indexExists('review_petugas', 'review_petugas_unique')) {
                $table->dropUnique('review_petugas_unique');
            }

            if (! $this->indexExists('review_petugas', 'review_petugas_reviewer_periode_unique')) {
                $table->unique(
                    ['reviewer_user_id', 'periode_alokasi_id'],
                    'review_petugas_reviewer_periode_unique'
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
            if ($this->indexExists('review_petugas', 'review_petugas_reviewer_periode_unique')) {
                $table->dropUnique('review_petugas_reviewer_periode_unique');
            }

            if (! $this->indexExists('review_petugas', 'review_petugas_unique')) {
                $table->unique(
                    ['kegiatan_id', 'petugas_id', 'reviewer_user_id'],
                    'review_petugas_unique'
                );
            }
        });

        if (Schema::hasColumn('review_petugas', 'periode_alokasi_id')) {
            Schema::table('review_petugas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('periode_alokasi_id');
            });
        }
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
