<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupOldDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'downloads:cleanup {--hours=24 : Delete files older than this many hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old ZIP files from public/downloads directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $downloadsDirectory = public_path('downloads');

        if (! is_dir($downloadsDirectory)) {
            $this->info('Downloads directory does not exist.');

            return 0;
        }

        $cleanupThreshold = time() - ($hours * 3600);
        $deletedCount = 0;
        $totalSize = 0;

        foreach (glob($downloadsDirectory.'/*.zip') ?: [] as $downloadFile) {
            if (is_file($downloadFile) && filemtime($downloadFile) < $cleanupThreshold) {
                $fileSize = filesize($downloadFile);
                if (@unlink($downloadFile)) {
                    $deletedCount++;
                    $totalSize += $fileSize;
                    $this->info("Deleted: {$downloadFile}");
                }
            }
        }

        $sizeInMB = round($totalSize / 1024 / 1024, 2);
        $this->info("Cleanup completed: {$deletedCount} file(s) deleted, {$sizeInMB} MB freed.");

        return 0;
    }
}
