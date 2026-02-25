<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MultiStreamDownloadResponse extends BinaryFileResponse
{
    /**
     * Create a new multi-stream download response
     *
     * @param  string  $file
     * @param  string|null  $filename
     * @param  bool  $disposition
     * @return void
     */
    public function __construct($file, $filename = null, array $headers = [], $disposition = 'attachment')
    {
        parent::__construct($file, Response::HTTP_OK, $headers, true, $disposition);

        // Enable byte-range support for multi-stream downloads
        $this->headers->set('Accept-Ranges', 'bytes');

        // Set custom filename if provided
        if ($filename) {
            $this->setContentDisposition($disposition, $filename);
        }

        // Ensure proper cache headers for CDN
        if (! $this->headers->has('Cache-Control')) {
            $this->headers->set('Cache-Control', 'public, max-age=604800'); // 7 days
        }

        // Set expires header
        $this->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 604800).' GMT');

        // Ensure proper content type
        if (! $this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'application/zip');
        }

        // Make the response public for CDN caching
        $this->setPublic();
        $this->setMaxAge(604800);
        $this->setSharedMaxAge(604800);

        // Enable ETag for better caching
        $this->setAutoEtag();
    }

    /**
     * Prepare the response for sending
     *
     * @return $this
     */
    public function prepare(\Symfony\Component\HttpFoundation\Request $request): static
    {
        // Check if client requested byte-range
        if ($request->headers->has('Range')) {
            $this->prepareRangeResponse($request);
        }

        return parent::prepare($request);
    }

    /**
     * Prepare response for byte-range requests
     *
     * @return void
     */
    protected function prepareRangeResponse(Request $request)
    {
        $fileSize = $this->file->getSize();
        $range = $request->headers->get('Range');

        // Parse range header (e.g., "bytes=0-1023")
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int) $matches[1];
            $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

            // Validate range
            if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
                $this->setStatusCode(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
                $this->headers->set('Content-Range', "bytes */{$fileSize}");

                return;
            }

            // Set 206 Partial Content status
            $this->setStatusCode(Response::HTTP_PARTIAL_CONTENT);

            // Set content range header
            $this->headers->set('Content-Range', "bytes {$start}-{$end}/{$fileSize}");

            // Set content length for the range
            $this->headers->set('Content-Length', (string) ($end - $start + 1));

            // Note: Browser will handle the byte range automatically via HTTP protocol
            // No need to manually slice the file here
        }
    }

    /**
     * Create a new multi-stream download response
     *
     * @param  string  $file
     * @param  string|null  $filename
     * @return static
     */
    public static function create($file, $filename = null, array $headers = []): self
    {
        return new static($file, $filename, $headers);
    }
}
