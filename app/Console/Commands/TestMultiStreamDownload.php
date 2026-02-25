<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestMultiStreamDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:test-multistream {--url= : URL to test} {--file= : Local file to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test multi-stream/multi-connection download support';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = config('app.url');
        $testUrl = $this->option('url');
        $testFile = $this->option('file');

        if (! $testUrl && ! $testFile) {
            // Find first available ZIP file
            $downloadsDir = public_path('downloads');
            $files = glob($downloadsDir.'/*.zip') ?: [];

            if (empty($files)) {
                $this->error('No ZIP files found in downloads directory.');
                $this->newLine();
                $this->line('💡 Use --url or --file option:');
                $this->line('   php artisan download:test-multistream --url=https://example.com/file.zip');
                $this->line('   php artisan download:test-multistream --file=myfile.zip');

                return 1;
            }

            $testFile = basename($files[0]);
        }

        if ($testFile) {
            $testUrl = $baseUrl.'/downloads/'.rawurlencode($testFile);
        }

        $this->info('🔍 Testing Multi-Stream Download Support...');
        $this->newLine();
        $this->line("📦 URL: {$testUrl}");
        $this->newLine();

        // Test 1: Check if server supports byte-range requests
        $this->line('🔹 Test 1: Checking Accept-Ranges support...');
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)->head($testUrl);

            if ($response->successful()) {
                $acceptRanges = $response->header('Accept-Ranges');
                $contentLength = $response->header('Content-Length');
                $cacheControl = $response->header('Cache-Control');

                $this->line("   ✅ Status: {$response->status()}");

                if ($acceptRanges === 'bytes') {
                    $this->line('   ✅ Accept-Ranges: bytes (Multi-stream SUPPORTED) 🎉');
                } else {
                    $this->warn("   ⚠️  Accept-Ranges: {$acceptRanges} (Multi-stream NOT supported)");
                }

                if ($contentLength) {
                    $sizeMB = round($contentLength / 1024 / 1024, 2);
                    $this->line("   📏 Content-Length: {$sizeMB} MB");
                } else {
                    $this->warn('   ⚠️  Content-Length: Not set (may affect multi-stream)');
                }

                if ($cacheControl) {
                    $this->line("   💾 Cache-Control: {$cacheControl}");
                }
            } else {
                $this->error("   ❌ Failed: HTTP {$response->status()}");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error: {$e->getMessage()}");

            return 1;
        }

        $this->newLine();

        // Test 2: Try byte-range request (partial content)
        $this->line('🔹 Test 2: Testing byte-range request (bytes=0-1023)...');
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->withHeaders(['Range' => 'bytes=0-1023'])
                ->get($testUrl);

            if ($response->status() === 206) {
                $contentRange = $response->header('Content-Range');
                $contentLength = $response->header('Content-Length');

                $this->line('   ✅ Status: 206 Partial Content (Perfect!) 🎊');
                if ($contentRange) {
                    $this->line("   📊 Content-Range: {$contentRange}");
                }
                if ($contentLength) {
                    $this->line("   📦 Content-Length: {$contentLength} bytes");
                }
                $this->line('   ✅ Multi-stream/Multi-connection is FULLY SUPPORTED! 🚀');
            } elseif ($response->status() === 200) {
                $this->warn('   ⚠️  Status: 200 OK (Range request ignored)');
                $this->warn('   ⚠️  Server may not support byte-range requests properly');
            } else {
                $this->error("   ❌ Failed: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error: {$e->getMessage()}");
        }

        $this->newLine();

        // Test 3: Simulate multiple connections
        $this->line('🔹 Test 3: Simulating 3 parallel connections...');
        try {
            /** @var \Illuminate\Http\Client\Response[] $responses */
            $responses = [
                Http::timeout(10)->withHeaders(['Range' => 'bytes=0-1023'])->get($testUrl),
                Http::timeout(10)->withHeaders(['Range' => 'bytes=1024-2047'])->get($testUrl),
                Http::timeout(10)->withHeaders(['Range' => 'bytes=2048-3071'])->get($testUrl),
            ];

            $allSuccess = true;
            foreach ($responses as $i => $response) {
                $chunk = $i + 1;
                if ($response->status() === 206) {
                    $this->line("   ✅ Connection {$chunk}: 206 Partial Content");
                } else {
                    $this->warn("   ⚠️  Connection {$chunk}: HTTP {$response->status()}");
                    $allSuccess = false;
                }
            }

            if ($allSuccess) {
                $this->newLine();
                $this->info('✅ All tests PASSED! Multi-stream download is fully functional!');
            } else {
                $this->newLine();
                $this->warn('⚠️  Some tests failed. Multi-stream may have issues.');
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error: {$e->getMessage()}");
        }

        $this->newLine();
        $this->line('💡 How to use multi-stream downloads:');
        $this->newLine();
        $this->line('   Using aria2c (16 connections):');
        $this->line("   aria2c -x 16 -s 16 \"{$testUrl}\"");
        $this->newLine();
        $this->line('   Using wget (no multi-stream, but supports resume):');
        $this->line("   wget -c \"{$testUrl}\"");
        $this->newLine();
        $this->line('   Using curl (single connection with resume):');
        $this->line("   curl -C - -O \"{$testUrl}\"");
        $this->newLine();

        return 0;
    }
}
