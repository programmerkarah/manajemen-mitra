<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:cleanup {--minutes=30 : Delete files older than this many minutes}';

    /**
     * Backward-compatible alias.
     *
     * @var array<int, string>
     */
    protected $aliases = ['temps:cleanup'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old files from storage/app/temp directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $tempDirectory = storage_path('app/temp');

        if (! is_dir($tempDirectory)) {
            $this->info('Temp directory does not exist.');

            return self::SUCCESS;
        }

        $cleanupThreshold = time() - ($minutes * 60);
        $deletedCount = 0;
        $totalSize = 0;

        foreach (glob($tempDirectory.'/*') ?: [] as $tempFile) {
            if (! is_file($tempFile)) {
                continue;
            }

            $fileTime = @filemtime($tempFile);
            $fileSize = (int) (@filesize($tempFile) ?: 0);

            if ($fileTime === false || $fileTime >= $cleanupThreshold) {
                continue;
            }

            if (@unlink($tempFile)) {
                $deletedCount++;
                $totalSize += $fileSize;
            }
        }

        $sizeInMb = round($totalSize / 1024 / 1024, 2);
        $this->info("Cleanup completed: {$deletedCount} file(s) deleted, {$sizeInMb} MB freed.");

        return self::SUCCESS;
    }
}
