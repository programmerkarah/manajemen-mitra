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
        // Step 1: Add periode_alokasi_id column (nullable first)
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            $table->unsignedBigInteger('periode_alokasi_id')->nullable()->after('id');
        });

        // Step 2: Migrate data - create PeriodeAlokasi records and link them
        // Disable foreign key checks for MySQL, use PRAGMA for SQLite
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $existingAlokasi = DB::table('alokasi_petugas')
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(function ($item) {
                return $item->kegiatan_id.'_'.$item->bulan.'_'.$item->tahun;
            });

        foreach ($existingAlokasi as $group) {
            $first = $group->first();

            // Create PeriodeAlokasi
            $periodeId = DB::table('periode_alokasi')->insertGetId([
                'kegiatan_id' => $first->kegiatan_id,
                'bulan' => str_pad($first->bulan, 2, '0', STR_PAD_LEFT),
                'tahun' => $first->tahun,
                'jenis_kegiatan' => $first->jenis_kegiatan,
                'status' => $first->status,
                'submitted_by' => $first->submitted_by,
                'submitted_at' => $first->submitted_at,
                'approved_by' => $first->approved_by,
                'approved_at' => $first->approved_at,
                'created_at' => $first->created_at,
                'updated_at' => $first->updated_at,
            ]);

            // Update all alokasi_petugas in this group
            $ids = $group->pluck('id')->toArray();
            DB::table('alokasi_petugas')
                ->whereIn('id', $ids)
                ->update(['periode_alokasi_id' => $periodeId]);
        }

        // Re-enable foreign key checks
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Step 3: Now modify the table structure
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            // Drop foreign keys first (skip for SQLite as it doesn't support dropping FKs by name)
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('alokasi_petugas_kegiatan_id_foreign');
                $table->dropForeign('alokasi_mitra_submitted_by_foreign');
                $table->dropForeign('alokasi_mitra_approved_by_foreign');
            }

            // Drop the unique constraint (skip for SQLite)
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropUnique('unique_alokasi');
            }

            // Drop columns that are now in periode_alokasi
            $table->dropColumn([
                'kegiatan_id',
                'bulan',
                'tahun',
                'jenis_kegiatan',
                'status',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'catatan_approval',
            ]);

            // Make periode_alokasi_id not nullable and add foreign key
            $table->unsignedBigInteger('periode_alokasi_id')->nullable(false)->change();
            $table->foreign('periode_alokasi_id')
                ->references('id')
                ->on('periode_alokasi')
                ->onDelete('cascade');

            // Add unique constraint
            $table->unique(['periode_alokasi_id', 'petugas_id'], 'unique_petugas_per_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alokasi_petugas', function (Blueprint $table) {
            // Drop new constraint
            $table->dropUnique('unique_petugas_per_periode');
            $table->dropForeign(['periode_alokasi_id']);
            $table->dropColumn('periode_alokasi_id');

            // Restore old columns
            $table->foreignId('kegiatan_id')->after('id')->constrained('kegiatan')->cascadeOnDelete();
            $table->integer('bulan')->after('petugas_id');
            $table->year('tahun')->after('bulan');
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])->after('peran');
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak', 'selesai'])->default('draft')->after('status_kepegawaian');
            $table->foreignId('submitted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('catatan_approval')->nullable()->after('approved_at');

            // Restore unique constraint
            $table->unique(['kegiatan_id', 'petugas_id', 'bulan', 'tahun'], 'unique_alokasi');
        });
    }
};
