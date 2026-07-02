<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SpkController;
use App\Models\Spk;
use Illuminate\Contracts\Console\Kernel;

$tahun = (int) date('Y');
$bulan = 6;

$controller = app(SpkController::class);
$refDecisions = new ReflectionMethod($controller, 'resolveSpkActionDecisionsForMonth');
$refDecisions->setAccessible(true);
$decisions = $refDecisions->invoke($controller, $tahun, $bulan);

$refDelta = new ReflectionMethod($controller, 'analyzeAllocationDeltaForPetugas');
$refDelta->setAccessible(true);

foreach ($decisions as $d) {
    $petugasId = $d['petugas_id'];
    if ($d['should_regenerate']) {
        $delta = $refDelta->invoke($controller, $petugasId, str_pad((string) $bulan, 2, '0', STR_PAD_LEFT), $tahun, 'same_month_original_spk');
        $existingSpk = Spk::query()
            ->where('petugas_id', $petugasId)
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', (int) str_pad((string) $bulan, 2, '0', STR_PAD_LEFT))
            ->first();

        echo "Petugas={$petugasId} SHOULD_REGENERATE => ";
        echo json_encode($delta, JSON_PRETTY_PRINT)."\n";
        echo '  existing_spk_in_month='.($existingSpk ? 'yes(id='.$existingSpk->id.')' : 'no')."\n";
    }
}

if (empty($decisions)) {
    echo "No decisions for {$bulan}/{$tahun}\n";
}
