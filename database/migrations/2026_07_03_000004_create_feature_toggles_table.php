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
        Schema::create('feature_toggles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['key' => 'kegiatan', 'label' => 'Kegiatan', 'description' => 'Menjalankan seluruh fitur pengelolaan kegiatan.', 'enabled' => true, 'sort_order' => 10],
            ['key' => 'alokasi', 'label' => 'Alokasi', 'description' => 'Menjalankan fitur alokasi petugas dan periode alokasi.', 'enabled' => true, 'sort_order' => 20],
            ['key' => 'spk', 'label' => 'SPK', 'description' => 'Menjalankan fitur surat perjanjian kerja.', 'enabled' => true, 'sort_order' => 30],
            ['key' => 'bast', 'label' => 'BAST', 'description' => 'Menjalankan fitur berita acara serah terima.', 'enabled' => true, 'sort_order' => 40],
            ['key' => 'pengajuan_pulsa', 'label' => 'Pengajuan Pulsa', 'description' => 'Menjalankan fitur pengajuan pulsa.', 'enabled' => true, 'sort_order' => 50],
            ['key' => 'petugas', 'label' => 'Petugas', 'description' => 'Menjalankan fitur manajemen petugas.', 'enabled' => true, 'sort_order' => 60],
        ];

        foreach ($defaults as $default) {
            DB::table('feature_toggles')->updateOrInsert(
                ['key' => $default['key']],
                [
                    'label' => $default['label'],
                    'description' => $default['description'],
                    'enabled' => $default['enabled'],
                    'sort_order' => $default['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_toggles');
    }
};
