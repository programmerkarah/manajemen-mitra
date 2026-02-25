<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ServeDownloadRouteTest extends TestCase
{
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
            $unsignedResponse = $this->get(route('serve.download', ['filename' => $fileName], false));
            $unsignedResponse->assertForbidden();

            $signedUrl = URL::temporarySignedRoute(
                'serve.download',
                now()->addMinutes(5),
                ['filename' => $fileName]
            );

            $signedResponse = $this->get($signedUrl);

            $signedResponse->assertOk();
            $cacheControl = $signedResponse->headers->get('Cache-Control') ?? '';
            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=3600', $cacheControl);
            $this->assertStringContainsString('immutable', $cacheControl);
            $signedResponse->assertHeaderMissing('Set-Cookie');
        } finally {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
}
