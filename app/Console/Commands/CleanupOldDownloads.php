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
    protected $signature = 'download:cleanup {--hours=24 : Delete files older than this many hours}';

    /**
     * Command aliases for backward compatibility
     */
    protected $aliases = ['downloads:cleanup'];

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
            if (! is_file($downloadFile)) {
                continue;
            }

            $basename = basename($downloadFile);
            $fileTime = filemtime($downloadFile);
            $fileSize = filesize($downloadFile);

            // Delete if file is old (beyond threshold)
            $isOld = $fileTime < $cleanupThreshold;

            // Also delete if file has timestamp pattern (old naming convention)
            // Pattern: filename_1234567890.zip (ends with underscore + 10 digits)
            $hasTimestamp = preg_match('/_\d{10}\.zip$/', $basename);

            if ($isOld || $hasTimestamp) {
                if (@unlink($downloadFile)) {
                    $deletedCount++;
                    $totalSize += $fileSize;
                    $reason = $hasTimestamp ? 'timestamp pattern' : 'age';
                    $this->info("Deleted ({$reason}): {$basename}");
                }
            }
        }

        $sizeInMB = round($totalSize / 1024 / 1024, 2);
        $this->info("Cleanup completed: {$deletedCount} file(s) deleted, {$sizeInMB} MB freed.");

        return 0;
    }
}
