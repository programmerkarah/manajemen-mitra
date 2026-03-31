<?php

namespace App\Console\Commands;

use App\Models\AlokasiPetugas;
use App\Models\Spk;
use Illuminate\Console\Command;

class UpdateSpkAlokasiPetugasIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spk:update-alokasi-ids {--dry-run : Run without actually updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update alokasi_petugas_ids column for all existing SPK based on nilai_kontrak and periode';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no database changes will be made');
        }

        $this->info('Starting SPK alokasi_petugas_ids update...');

        // Get all SPK records
        $allSpk = Spk::with(['alokasiPetugas.periodeAlokasi', 'petugas'])
            ->orderBy('id')
            ->get();

        $this->info("Found {$allSpk->count()} SPK records to process");

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($allSpk->count());
        $progressBar->start();

        foreach ($allSpk as $spk) {
            $progressBar->advance();

            try {
                // Get periode info from existing alokasi_petugas
                if (! $spk->alokasiPetugas || ! $spk->alokasiPetugas->periodeAlokasi) {
                    $this->newLine();
                    $this->warn("SPK #{$spk->id} ({$spk->nomor_spk}): Missing alokasi or periode data - skipped");
                    $skipped++;

                    continue;
                }

                $periode = $spk->alokasiPetugas->periodeAlokasi;
                $bulan = $periode->bulan;
                $tahun = $periode->tahun;
                $petugasId = $spk->petugas_id;
                $nilaiKontrak = (float) $spk->nilai_kontrak;

                // Find all alokasi_petugas for this petugas in the same month/year
                // We need to consider the status at the time SPK was created
                $allAlokasi = AlokasiPetugas::where('petugas_id', $petugasId)
                    ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                        $q->where('bulan', $bulan)
                            ->where('tahun', $tahun)
                            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
                    })
                    ->with('periodeAlokasi')
                    ->get();

                if ($allAlokasi->isEmpty()) {
                    $this->newLine();
                    $this->warn("SPK #{$spk->id}: No allocations found for petugas {$spk->petugas->nama} in {$bulan}/{$tahun}");
                    $skipped++;

                    continue;
                }

                // Get effective allocations (latest status per kegiatan)
                $byKegiatan = $allAlokasi->groupBy(function ($alokasi) {
                    return $alokasi->periodeAlokasi->kegiatan_id;
                });

                $effectiveAlokasi = $byKegiatan->map(function ($alokasiGroup) {
                    // Priority: perubahan > direvisi > disetujui > dikirim
                    $perubahan = $alokasiGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                    if ($perubahan) {
                        return $perubahan;
                    }

                    $direvisi = $alokasiGroup->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                    if ($direvisi) {
                        return $direvisi;
                    }

                    $disetujui = $alokasiGroup->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                    if ($disetujui) {
                        return $disetujui;
                    }

                    return $alokasiGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
                })->filter();

                // Calculate total honor from effective allocations
                $totalHonor = $effectiveAlokasi->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                // Check if total matches nilai_kontrak (with 0.01 tolerance for float precision)
                $honorMatches = abs($totalHonor - $nilaiKontrak) < 0.01;

                if (! $honorMatches) {
                    $this->newLine();
                    $this->warn("SPK #{$spk->id}: Total honor ({$totalHonor}) doesn't match nilai_kontrak ({$nilaiKontrak}) - using effective allocations anyway");
                }

                // Get IDs of effective allocations
                $newAlokasiIds = $effectiveAlokasi->pluck('id')->sort()->values()->toArray();

                // Get current stored IDs
                $currentIds = $spk->alokasi_petugas_ids ?? [$spk->alokasi_petugas_id];
                sort($currentIds);

                // Check if update is needed
                if ($currentIds === $newAlokasiIds) {
                    $skipped++;

                    continue; // Already correct
                }

                // Update the record
                if (! $isDryRun) {
                    $spk->alokasi_petugas_ids = $newAlokasiIds;
                    $spk->save();
                }

                $this->newLine();
                $this->info("SPK #{$spk->id} ({$spk->nomor_spk}): Updated from [".implode(',', $currentIds).'] to ['.implode(',', $newAlokasiIds).']');
                $this->line("  Petugas: {$spk->petugas->nama}");
                $this->line("  Periode: {$bulan}/{$tahun}");
                $this->line("  Kegiatan count: {$effectiveAlokasi->count()}");
                $this->line('  Total Honor: Rp '.number_format($totalHonor, 0, ',', '.').' (Nilai Kontrak: Rp '.number_format($nilaiKontrak, 0, ',', '.').')');

                $updated++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("SPK #{$spk->id}: Error - {$e->getMessage()}");
                $errors++;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Update Summary ===');
        $this->line("Total SPK: {$allSpk->count()}");
        $this->info("Updated: {$updated}");
        $this->line("Skipped (already correct): {$skipped}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN completed - no changes were made to the database');
            $this->info('Run without --dry-run flag to apply changes');
        }

        return Command::SUCCESS;
    }
}
