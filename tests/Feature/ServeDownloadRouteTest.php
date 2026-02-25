<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ServeDownloadRouteTest extends TestCase
{
    /**
     * Test that serve-download route (optional, for signed access) works correctly.
     * Note: Files are primarily served directly from /downloads/ folder by web server.
     * This route provides backup access with signature validation.
     */
    public function test_serve_download_requires_valid_signature_and_returns_cacheable_file_response(): void
    {
        $downloadsDirectory = public_path('downloads');

        if (! file_exists($downloadsDirectory)) {
            mkdir($downloadsDirectory, 0755, true);
        }

        $fileName = 'test-download-'.uniqid().'.zip';
        $filePath = $downloadsDirectory.DIRECTORY_SEPARATOR.$fileName;
        file_put_contents($filePath, 'test-content');

        try {
            // Test unsigned URL returns 403
            $unsignedResponse = $this->get(route('serve.download', ['filename' => $fileName], false));
            $unsignedResponse->assertForbidden();

            // Test signed URL returns file with cache headers
            $signedUrl = URL::temporarySignedRoute(
                'serve.download',
                now()->addMinutes(5),
                ['filename' => $fileName]
            );

            $signedResponse = $this->get($signedUrl);

            // Should serve file directly (200), not redirect
            $signedResponse->assertOk();
            // Cache-Control header should contain both "public" and "max-age=604800"
            $cacheControl = $signedResponse->headers->get('Cache-Control');
            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=604800', $cacheControl);
            $signedResponse->assertHeader('Content-Type', 'application/zip');
            $signedResponse->assertHeaderMissing('Set-Cookie');
        } finally {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
}
