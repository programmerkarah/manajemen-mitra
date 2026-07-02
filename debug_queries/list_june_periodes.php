<?php

require __DIR__.'/../vendor/autoload.php';
// Bootstrap Laravel application
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
// This script is a quick DB inspector; run via `php debug_queries/list_june_periodes.php` from project root.
use App\Models\AlokasiPetugas;
use App\Models\PeriodeAlokasi;
use App\Models\Spk;
use Illuminate\Contracts\Console\Kernel;

$tahun = (int) date('Y');
$bulan = 6; // June

// Exclude sensus kegiatan to reflect the regular index behavior
$periodes = PeriodeAlokasi::where('tahun', $tahun)
    ->where('bulan', $bulan)
    ->whereHas('kegiatan', function ($q) {
        $q->where('jenis_kegiatan', '!=', 'sensus');
    })
    ->with('kegiatan')
    ->get();
foreach ($periodes as $p) {
    $spkCount = Spk::whereYear('tanggal_spk', $p->tahun)->whereMonth('tanggal_spk', $p->bulan)->count();
    $alokCount = AlokasiPetugas::where('periode_alokasi_id', $p->id)->count();
    echo "PeriodeID={$p->id} KegiatanID={$p->kegiatan_id} Jenis={$p->jenis_kegiatan} Status={$p->status} Alok={$alokCount} SPK_in_month={$spkCount}\n";
}

if ($periodes->isEmpty()) {
    echo "No periodes found for {$bulan}/{$tahun}\n";
}
