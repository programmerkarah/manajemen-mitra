<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\SpkController;
use App\Models\PeriodeAlokasi;
use ReflectionMethod;

$controller = app(SpkController::class);

// Get June 2026 periods with survei activities (not sensus)
$periodJune = PeriodeAlokasi::whereIn('bulan', ['06', '6'])
    ->where('tahun', 2026)
    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
    ->whereHas('kegiatan', function ($q) {
        $q->where('jenis_kegiatan', '!=', 'sensus');
    })
    ->get();

echo "=== June 2026 Periods (Regular/Survei) ===\n";
echo "Total periods: " . $periodJune->count() . "\n";
foreach ($periodJune as $periode) {
    echo "- Kegiatan: {$periode->kegiatan->nama_kegiatan} (jenis: {$periode->kegiatan->jenis_kegiatan})\n";
}

// Use reflection to call private method
$reflection = new ReflectionMethod($controller, 'resolveRegenerateCandidatesForMonth');
$reflection->setAccessible(true);

$regenerateCandidates = $reflection->invoke($controller, 2026, 6);

echo "\n=== Regenerate Candidates for June 2026 ===\n";
echo "Count: " . $regenerateCandidates->count() . "\n";
if ($regenerateCandidates->isNotEmpty()) {
    echo "Petugas IDs needing regenerate: " . $regenerateCandidates->implode(', ') . "\n";
} else {
    echo "No regenerate candidates\n";
}

// Also check the action decisions
$decisionReflection = new ReflectionMethod($controller, 'resolveSpkActionDecisionsForMonth');
$decisionReflection->setAccessible(true);

$decisions = $decisionReflection->invoke($controller, 2026, 6);

echo "\n=== SPK Action Decisions for June 2026 ===\n";
echo "Total petugas: " . $decisions->count() . "\n";
$shouldRegenerate = $decisions->filter(fn ($d) => $d['should_regenerate']);
$shouldAddendum = $decisions->filter(fn ($d) => $d['should_addendum']);

echo "Petugas needing regenerate: " . $shouldRegenerate->count() . "\n";
foreach ($shouldRegenerate as $decision) {
    echo "- Petugas {$decision['petugas_id']}: regenerate={$decision['should_regenerate']}, has_addendum={$decision['has_addendum']}\n";
}

echo "\nPetugas needing addendum: " . $shouldAddendum->count() . "\n";
foreach ($shouldAddendum as $decision) {
    echo "- Petugas {$decision['petugas_id']}: addendum={$decision['should_addendum']}, has_addendum={$decision['has_addendum']}\n";
}

// Get detailed info about the 4 regenerate petugas
echo "\n=== Detailed Info for Regenerate Petugas ===\n";
foreach ([30, 63, 25, 42] as $petugasId) {
    $petugas = \App\Models\Petugas::find($petugasId);
    echo "\nPetugas {$petugasId}: {$petugas->nama} (jenis: {$petugas->jenis_petugas})\n";
    
    $alokasi = \App\Models\AlokasiPetugas::where('petugas_id', $petugasId)
        ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
        ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
        ->where('periode_alokasi.bulan', '6')
        ->where('periode_alokasi.tahun', 2026)
        ->where('periode_alokasi.status', 'dikirim')
        ->where('kegiatan.jenis_kegiatan', 'survei')
        ->select('alokasi_petugas.*', 'kegiatan.nama_kegiatan', 'periode_alokasi.status as periode_status')
        ->get();
    
    echo "  Allocations in June for survei: " . $alokasi->count() . "\n";
    foreach ($alokasi as $a) {
        echo "    - {$a->nama_kegiatan}: honor={$a->total_honor}, listing={$a->total_honor_listing}\n";
    }
    
    $spk = \App\Models\Spk::where('petugas_id', $petugasId)
        ->where('addendum_number', 0)
        ->whereYear('tanggal_spk', 2026)
        ->whereMonth('tanggal_spk', 6)
        ->first();
    
    echo "  June SPK: " . ($spk ? "Yes ({$spk->nomor_spk})" : "No") . "\n";
}
