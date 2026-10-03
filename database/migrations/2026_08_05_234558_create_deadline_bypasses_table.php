<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deadline_bypasses')) {
            Schema::create('deadline_bypasses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deadline_rule_id')->constrained('deadline_rules')->cascadeOnDelete();
                $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->nullOnDelete();
                $table->foreignId('periode_alokasi_id')->nullable()->constrained('periode_alokasi')->nullOnDelete();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedTinyInteger('month')->nullable();
                $table->foreignId('approved_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('granted_for_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->unsignedTinyInteger('max_uses')->default(1);
                $table->unsignedTinyInteger('uses_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('consumed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['deadline_rule_id', 'is_active']);
                $table->index(['year', 'month']);
            });
        }

        Schema::table('deadline_bypasses', function (Blueprint $table) {
            if (! $this->foreignKeyExists('deadline_bypasses', 'deadline_bypasses_deadline_rule_id_foreign')) {
                $table->foreign('deadline_rule_id', 'deadline_bypasses_deadline_rule_id_foreign')
                    ->references('id')
                    ->on('deadline_rules')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('deadline_bypasses', 'deadline_bypasses_kegiatan_id_foreign')) {
                $table->foreign('kegiatan_id', 'deadline_bypasses_kegiatan_id_foreign')
                    ->references('id')
                    ->on('kegiatan')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('deadline_bypasses', 'deadline_bypasses_periode_alokasi_id_foreign')) {
                $table->foreign('periode_alokasi_id', 'deadline_bypasses_periode_alokasi_id_foreign')
                    ->references('id')
                    ->on('periode_alokasi')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('deadline_bypasses', 'deadline_bypasses_approved_by_user_id_foreign')) {
                $table->foreign('approved_by_user_id', 'deadline_bypasses_approved_by_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('deadline_bypasses', 'deadline_bypasses_granted_for_user_id_foreign')) {
                $table->foreign('granted_for_user_id', 'deadline_bypasses_granted_for_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! $this->indexExists('deadline_bypasses', 'deadline_bypasses_deadline_rule_id_is_active_index')) {
                $table->index(['deadline_rule_id', 'is_active']);
            }

            if (! $this->indexExists('deadline_bypasses', 'deadline_bypasses_year_month_index')) {
                $table->index(['year', 'month']);
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $result = DB::selectOne(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
                [DB::getDatabaseName(), $table, $constraintName, 'FOREIGN KEY']
            );

            return $result !== null;
        }

        if ($driver === 'sqlite') {
            $foreignKeys = DB::select(sprintf("PRAGMA foreign_key_list('%s')", $table));

            foreach ($foreignKeys as $foreignKey) {
                if (($foreignKey->table ?? null) === $this->referencedTableForConstraint($constraintName)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $result = DB::selectOne(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [DB::getDatabaseName(), $table, $indexName]
            );

            return $result !== null;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select(sprintf("PRAGMA index_list('%s')", $table));

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private function referencedTableForConstraint(string $constraintName): ?string
    {
        return match ($constraintName) {
            'deadline_bypasses_deadline_rule_id_foreign' => 'deadline_rules',
            'deadline_bypasses_kegiatan_id_foreign' => 'kegiatan',
            'deadline_bypasses_periode_alokasi_id_foreign' => 'periode_alokasi',
            'deadline_bypasses_approved_by_user_id_foreign',
            'deadline_bypasses_granted_for_user_id_foreign' => 'users',
            default => null,
        };
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_bypasses');
    }
};
