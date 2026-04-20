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
        Schema::table('petugas', function (Blueprint $table) {
            $table->string('desa_kelurahan')->nullable()->after('kecamatan');
        });

        $kecamatanMap = [
            'silungkang' => 'Silungkang',
            'lembah segar' => 'Lembah Segar',
            'barangin' => 'Barangin',
            'talawi' => 'Talawi',
        ];

        $petugas = DB::table('petugas')->whereNull('deleted_at')->get(['id', 'alamat', 'kecamatan']);

        foreach ($petugas as $p) {
            if ($p->kecamatan && in_array($p->kecamatan, $kecamatanMap)) {
                continue;
            }

            $alamat = strtolower($p->alamat ?? '');
            $foundKecamatan = null;

            foreach ($kecamatanMap as $keyword => $standardized) {
                if (preg_match('/(?:kecamatan|kec\.?)\s*'.preg_quote($keyword, '/').'/i', $alamat)) {
                    $foundKecamatan = $standardized;
                    break;
                }
            }

            if (! $foundKecamatan) {
                foreach ($kecamatanMap as $keyword => $standardized) {
                    if (str_contains($alamat, $keyword)) {
                        $foundKecamatan = $standardized;
                        break;
                    }
                }
            }

            if ($foundKecamatan) {
                DB::table('petugas')->where('id', $p->id)->update(['kecamatan' => $foundKecamatan]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->dropColumn('desa_kelurahan');
        });
    }
};
