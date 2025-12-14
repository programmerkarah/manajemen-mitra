<?php

namespace App\Console\Commands;

use App\Models\Kegiatan;
use Illuminate\Console\Command;

class UpdateSisaPaguForExistingPeriodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periode:update-sisa-pagu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update sisa_pagu for existing periode records sequentially by month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating sisa_pagu for existing periodes...');

        $kegiatanList = Kegiatan::with(['periodeAlokasi' => function ($q) {
            $q->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->orderBy('tahun')
                ->orderBy('bulan');
        }])->get();

        $totalUpdated = 0;

        foreach ($kegiatanList as $kegiatan) {
            if ($kegiatan->periodeAlokasi->isEmpty()) {
                continue;
            }

            $paguAnggaran = $kegiatan->anggaran ?? 0;
            $sisaPaguRunning = $paguAnggaran;

            foreach ($kegiatan->periodeAlokasi as $periode) {
                // Calculate total honor for this periode
                $totalHonor = $periode->alokasiPetugas->sum('total_honor');

                // Update running sisa pagu after this periode
                $sisaPaguRunning -= $totalHonor;

                // Update the periode
                $periode->update(['sisa_pagu' => $sisaPaguRunning]);

                $totalUpdated++;

                $this->line("Updated periode {$periode->bulan}/{$periode->tahun} for kegiatan: {$kegiatan->nama_kegiatan} - Sisa Pagu: ".number_format($sisaPaguRunning, 0, ',', '.'));
            }
        }

        $this->info("Successfully updated sisa_pagu for {$totalUpdated} periodes.");

        return Command::SUCCESS;
    }
}
