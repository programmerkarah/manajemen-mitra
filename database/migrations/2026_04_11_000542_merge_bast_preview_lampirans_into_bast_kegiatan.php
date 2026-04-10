<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah bast_id menjadi nullable dan tambahkan spk_id di bast_kegiatan
        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->unsignedBigInteger('bast_id')->nullable()->change();
            $table->string('nama_kegiatan')->nullable()->change();
            $table->string('bulan')->nullable()->change();
            $table->year('tahun')->nullable()->change();
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])->nullable()->change();

            // Tambah spk_id untuk preview records (belum ada BAST)
            $table->foreignId('spk_id')
                ->nullable()
                ->after('bast_id')
                ->constrained('spk')
                ->cascadeOnDelete();

            // Index untuk query preview records berdasarkan spk
            $table->index(['spk_id', 'kegiatan_id', 'periode_alokasi_id'], 'bast_kegiatan_preview_idx');
        });

        // 2. Migrasi data dari bast_preview_lampirans ke bast_kegiatan
        if (Schema::hasTable('bast_preview_lampirans')) {
            DB::statement('
                INSERT INTO bast_kegiatan
                    (bast_id, spk_id, kegiatan_id, periode_alokasi_id, kode_kegiatan,
                     file_path, signed_file_path, generated_at, signed_uploaded_at,
                     created_at, updated_at)
                SELECT
                    NULL, spk_id, kegiatan_id, periode_alokasi_id, kode_kegiatan,
                    file_path, signed_file_path, generated_at, signed_uploaded_at,
                    created_at, updated_at
                FROM bast_preview_lampirans
            ');

            // 3. Drop tabel lama
            Schema::dropIfExists('bast_preview_lampirans');
        }
    }

    public function down(): void
    {
        // Buat kembali tabel bast_preview_lampirans
        Schema::create('bast_preview_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete();
            $table->unsignedBigInteger('kegiatan_id');
            $table->unsignedBigInteger('periode_alokasi_id');
            $table->string('kode_kegiatan', 50);
            $table->text('file_path')->nullable();
            $table->text('signed_file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('signed_uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['spk_id', 'kegiatan_id', 'periode_alokasi_id'], 'bast_preview_lampirans_spk_kgt_periode_unique');
            $table->index('spk_id');
        });

        // Migrasi data preview kembali (bast_id IS NULL di bast_kegiatan)
        DB::statement('
            INSERT INTO bast_preview_lampirans
                (spk_id, kegiatan_id, periode_alokasi_id, kode_kegiatan,
                 file_path, signed_file_path, generated_at, signed_uploaded_at,
                 created_at, updated_at)
            SELECT
                spk_id, kegiatan_id, periode_alokasi_id, kode_kegiatan,
                file_path, signed_file_path, generated_at, signed_uploaded_at,
                created_at, updated_at
            FROM bast_kegiatan
            WHERE bast_id IS NULL
        ');

        // Hapus preview records dari bast_kegiatan
        DB::table('bast_kegiatan')->whereNull('bast_id')->delete();

        // Kembalikan kolom ke non-nullable
        Schema::table('bast_kegiatan', function (Blueprint $table) {
            $table->dropIndex('bast_kegiatan_preview_idx');
            $table->dropForeign(['spk_id']);
            $table->dropColumn('spk_id');

            $table->unsignedBigInteger('bast_id')->nullable(false)->change();
            $table->string('nama_kegiatan')->nullable(false)->change();
            $table->string('bulan')->nullable(false)->change();
            $table->year('tahun')->nullable(false)->change();
            $table->enum('jenis_kegiatan', ['sensus', 'survei'])->nullable(false)->change();
        });
    }
};
