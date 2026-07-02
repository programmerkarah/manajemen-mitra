<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\PeriodeAlokasi;
use Illuminate\Contracts\Console\Kernel;

$tahun = (int) date('Y');

$periodes = PeriodeAlokasi::where('tahun', $tahun)
    ->whereHas('kegiatan', function ($q) {
        $q->where('jenis_kegiatan', 'sensus');
    })
    ->with('kegiatan')
    ->get();

foreach ($periodes as $p) {
    echo "PeriodeID={$p->id} KegiatanID={$p->kegiatan_id} Jenis={$p->jenis_kegiatan} Status={$p->status}\n";
}

if ($periodes->isEmpty()) {
    echo "No sensus periodes for {$tahun}\n";
}
