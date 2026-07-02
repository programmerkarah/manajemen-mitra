<?php

require __DIR__.'/../vendor/autoload.php';
// Bootstrap Laravel application
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SpkController;
use App\Models\Spk;
use Illuminate\Contracts\Console\Kernel;

$tahun = (int) date('Y');
$bulan = 6; // June

$controller = app(SpkController::class);
$ref = new ReflectionMethod($controller, 'resolveRegenerateCandidatesForMonth');
$ref->setAccessible(true);
$candidates = $ref->invoke($controller, $tahun, $bulan);

if ($candidates->isEmpty()) {
    echo "No regenerate candidates for {$bulan}/{$tahun}\n";
} else {
    foreach ($candidates as $petugasId) {
        $hasOriginal = Spk::where('petugas_id', $petugasId)
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->exists();

        echo "Petugas={$petugasId} candidate_for_regenerate=yes existing_original_in_month=".($hasOriginal ? 'yes' : 'no')."\n";
    }
}
