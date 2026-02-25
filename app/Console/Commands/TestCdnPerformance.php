<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestCdnPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdn:test {--file= : Test specific file} {--url= : Test specific URL directly} {--size=1024 : Download size in KB to test speed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test CDN performance and cache status for download files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = config('app.url');
        $downloadsDir = public_path('downloads');
        $testFile = $this->option('file');
        $testSize = (int) $this->option('size') * 1024; // Convert KB to bytes

        if (! is_dir($downloadsDir)) {
            $this->error('Downloads directory does not exist.');

            return 1;
        }

        // Get files to test
        $files = $testFile
            ? [public_path('downloads/'.$testFile)]
            : (glob($downloadsDir.'/*.zip') ?: []);

        if (empty($files)) {
            $this->warn('No ZIP files found in downloads directory.');

            return 0;
        }

        $this->info('🔍 Testing CDN Performance...');
        $this->newLine();

        foreach ($files as $filePath) {
            if (! file_exists($filePath)) {
                $this->warn("File not found: {$filePath}");

                continue;
            }

            $fileName = basename($filePath);
            $fileSize = filesize($filePath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            $fileUrl = $baseUrl.'/downloads/'.rawurlencode($fileName);

            $this->line("📦 File: <fg=cyan>{$fileName}</>");
            $this->line("   Size: {$fileSizeMB} MB");
            $this->line("   URL: {$fileUrl}");
            $this->newLine();

            // Test 1: HEAD request to check headers
            $this->line('   🔹 Testing Headers (HEAD request)...');
            $startTime = microtime(true);

            try {
                $response = Http::timeout(30)->head($fileUrl);
                $headTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $this->line("      ✅ Status: <fg=green>{$response->status()}</>");
                    $this->line("      ⏱️  Response Time: {$headTime}ms");

                    // Check CDN headers
                    $cacheStatus = $response->header('x-hcdn-cache-status') ?? 'NOT FOUND';
                    $cacheColor = match ($cacheStatus) {
                        'HIT' => 'green',
                        'MISS' => 'yellow',
                        'BYPASS' => 'red',
                        default => 'gray',
                    };
                    $this->line("      🌐 CDN Cache: <fg={$cacheColor}>{$cacheStatus}</>");

                    if ($upstreamTime = $response->header('x-hcdn-upstream-rt')) {
                        $this->line("      ⬆️  Upstream Time: {$upstreamTime}s");
                    }

                    if ($requestId = $response->header('x-hcdn-request-id')) {
                        $this->line("      🆔 Request ID: {$requestId}");
                    }

                    // Check cache-control
                    if ($cacheControl = $response->header('cache-control')) {
                        $this->line("      💾 Cache-Control: {$cacheControl}");
                    }

                    // Check content-type
                    if ($contentType = $response->header('content-type')) {
                        $this->line("      📄 Content-Type: {$contentType}");
                    }

                    // Check accept-ranges
                    if ($acceptRanges = $response->header('accept-ranges')) {
                        $resumable = $acceptRanges === 'bytes' ? '✅ Yes' : '❌ No';
                        $this->line("      🔄 Resumable: {$resumable}");
                    }
                } else {
                    $this->error("      ❌ Failed: HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error("      ❌ Error: {$e->getMessage()}");
            }

            $this->newLine();

            // Test 2: Partial download to test speed
            $this->line('   🔹 Testing Download Speed (first '.($testSize / 1024).' KB)...');
            $startTime = microtime(true);

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['Range' => "bytes=0-{$testSize}"])
                    ->get($fileUrl);

                $downloadTime = microtime(true) - $startTime;
                $downloadedBytes = strlen($response->body());
                $downloadedKB = round($downloadedBytes / 1024, 2);

                if ($downloadTime > 0) {
                    $speedKBps = round($downloadedKB / $downloadTime, 2);
                    $speedMBps = round($speedKBps / 1024, 2);
                    $estimatedFullTime = round(($fileSizeMB / $speedMBps) / 60, 1);

                    $speedColor = match (true) {
                        $speedMBps >= 5 => 'green',
                        $speedMBps >= 1 => 'yellow',
                        default => 'red',
                    };

                    $this->line("      ⏱️  Downloaded: {$downloadedKB} KB in ".round($downloadTime, 2).'s');
                    $this->line("      🚀 Speed: <fg={$speedColor}>{$speedKBps} KB/s ({$speedMBps} MB/s)</>");
                    $this->line("      ⏳ Est. Full Download: ~{$estimatedFullTime} minutes");
                }
            } catch (\Exception $e) {
                $this->error("      ❌ Error: {$e->getMessage()}");
            }

            $this->newLine();
            $this->line('   '.str_repeat('─', 70));
            $this->newLine();
        }

        $this->info('✅ CDN Performance Test Completed!');
        $this->newLine();
        $this->line('💡 Tips:');
        $this->line('   - HIT = File served from CDN cache (fast)');
        $this->line('   - MISS = File fetched from origin (slower, will be cached)');
        $this->line('   - BYPASS = File skipped CDN (check .htaccess)');
        $this->line('   - Speed < 1 MB/s = Bandwidth throttling likely');
        $this->newLine();

        return 0;
    }
}
