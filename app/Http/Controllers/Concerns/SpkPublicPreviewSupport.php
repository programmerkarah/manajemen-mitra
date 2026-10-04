<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use App\Services\SpkActionDecisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Vinkla\Hashids\Facades\Hashids;

trait SpkPublicPreviewSupport
{
    public function publicPreviewForm(Request $request): Response
    {
        $activeYear = ActiveYearService::get();

        return Inertia::render('Spk/PublicPreview', [
            'survei_periods' => [],
            'sensus_kegiatans' => [],
            'penugasan_list' => [],
            'active_year' => $activeYear,
            'recaptcha_site_key' => (string) config('services.recaptcha.site_key', ''),
        ]);
    }

    public function publicPreviewOptions(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:64'],
            'telepon_4_digit' => ['required', 'string', 'regex:/^\d{4}$/'],
            'recaptcha_token' => ['required', 'string', 'max:8192'],
        ]);

        if (! $this->isValidPublicPreviewRecaptcha((string) $validated['recaptcha_token'], $request->ip())) {
            return response()->json([
                'message' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
            ], 422);
        }

        $petugas = $this->resolvePublicPreviewPetugas(
            (string) $validated['nama'],
            (string) $validated['nik']
        );

        if (! $petugas) {
            return response()->json([
                'message' => 'Petugas dengan Nama dan NIK tersebut tidak ditemukan.',
            ], 404);
        }

        if (! $this->matchesPublicPreviewPhoneVerification($petugas, (string) $validated['telepon_4_digit'])) {
            return response()->json([
                'message' => 'Verifikasi 4 digit nomor HP tidak sesuai.',
            ], 422);
        }

        $request->session()->put('mitra_preview_verified', [
            'signature' => $this->buildPublicPreviewSessionSignature(
                (string) $validated['nama'],
                (string) $validated['nik'],
                (string) $validated['telepon_4_digit'],
            ),
            'verified_at' => now()->timestamp,
        ]);

        $options = $this->resolvePublicPreviewOptionsForPetugas($petugas, ActiveYearService::get());

        return response()->json([
            'petugas_nama' => $petugas->nama,
            'survei_periods' => $options['survei_periods'],
            'sensus_kegiatans' => $options['sensus_kegiatans'],
            'penugasan_list' => $options['penugasan_list'],
        ]);
    }

    public function publicPreviewDownload(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:64'],
            'telepon_4_digit' => ['required', 'string', 'regex:/^\d{4}$/'],
            'jenis_kegiatan' => ['required', 'in:survei,sensus'],
            'survei_periode' => ['nullable', 'string'],
            'sensus_kegiatan' => ['nullable', 'string'],
            'recaptcha_token' => ['nullable', 'string', 'max:8192'],
            'aksi' => ['nullable', 'in:preview,download'],
            'response_mode' => ['nullable', 'in:binary,url'],
            'download_token' => ['nullable', 'string', 'max:120'],
            'dokumen_tipe' => ['nullable', 'in:pk,bast,bapp'],
            'bapp_termin' => ['nullable', 'in:1,2'],
        ]);

        $hasRecentSessionVerification = $this->hasRecentPublicPreviewSessionVerification(
            $request,
            (string) $validated['nama'],
            (string) $validated['nik'],
            (string) $validated['telepon_4_digit'],
        );

        if (! $hasRecentSessionVerification) {
            if (! $this->isValidPublicPreviewRecaptcha((string) ($validated['recaptcha_token'] ?? ''), $request->ip())) {
                return response()->json([
                    'message' => 'Sesi verifikasi berakhir. Klik "Muat Data" kembali.',
                ], 422);
            }
        }

        $petugas = $this->resolvePublicPreviewPetugas(
            (string) $validated['nama'],
            (string) $validated['nik']
        );

        if (! $petugas) {
            return response()->json([
                'message' => 'Petugas dengan Nama dan NIK tersebut tidak ditemukan.',
            ], 404);
        }

        if (! $this->matchesPublicPreviewPhoneVerification($petugas, (string) $validated['telepon_4_digit'])) {
            return response()->json([
                'message' => 'Verifikasi 4 digit nomor HP tidak sesuai.',
            ], 422);
        }

        $jenisKegiatan = (string) $validated['jenis_kegiatan'];
        $periode = null;
        $selectedKegiatanId = null;

        if ($jenisKegiatan === 'survei') {
            $surveiPeriode = (string) ($validated['survei_periode'] ?? '');
            if (! preg_match('/^\d{4}-\d{2}$/', $surveiPeriode)) {
                return response()->json([
                    'message' => 'Periode survei tidak valid.',
                ], 422);
            }

            [$tahun, $bulan] = explode('-', $surveiPeriode);
            $bulanFormatted = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

            $hasDraftSurvei = PeriodeAlokasi::query()
                ->where('tahun', (int) $tahun)
                ->where('bulan', $bulanFormatted)
                ->where('status', 'draft')
                ->whereHas('kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'survei');
                })
                ->exists();

            if ($hasDraftSurvei) {
                return response()->json([
                    'message' => 'Preview SPK survei belum dapat dilakukan karena masih ada kegiatan draft pada bulan tersebut.',
                ], 422);
            }

            $periode = PeriodeAlokasi::query()
                ->where('tahun', (int) $tahun)
                ->where('bulan', $bulanFormatted)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->whereHas('kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'survei');
                })
                ->first();

            if (! $periode) {
                return response()->json([
                    'message' => 'Periode survei yang dipilih belum siap untuk preview SPK.',
                ], 422);
            }
        }

        if ($jenisKegiatan === 'sensus') {
            $kegiatanHashedId = (string) ($validated['sensus_kegiatan'] ?? '');
            $selectedKegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

            if (! $selectedKegiatanId) {
                return response()->json([
                    'message' => 'Jenis kegiatan sensus tidak valid.',
                ], 422);
            }

            $periode = PeriodeAlokasi::query()
                ->where('kegiatan_id', (int) $selectedKegiatanId)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->orderByDesc('revision_number')
                ->orderByDesc('id')
                ->first();

            if (! $periode) {
                return response()->json([
                    'message' => 'Kegiatan sensus belum dikirim sehingga preview belum tersedia.',
                ], 422);
            }
        }

        if (! $periode) {
            return response()->json([
                'message' => 'Data periode tidak ditemukan.',
            ], 422);
        }

        $hasMatchingAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($query) use ($periode, $jenisKegiatan, $selectedKegiatanId): void {
                $query->where('tahun', $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan, $selectedKegiatanId): void {
                        $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);

                        if ($selectedKegiatanId !== null) {
                            $kegiatanQuery->where('id', $selectedKegiatanId);
                        }
                    });
            })
            ->where(function ($query): void {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->exists();


        if (! $hasMatchingAlokasi) {
            return response()->json([
                'message' => 'Petugas tidak memiliki alokasi Perjanjian Kerja yang sesuai kriteria.',
            ], 422);
        }

        $dokumenTipe = (string) ($validated['dokumen_tipe'] ?? 'pk');
        $bappTermin = max(1, min(2, (int) ($validated['bapp_termin'] ?? 1)));

        if ($dokumenTipe === 'bast') {
            return $this->servePublicPreviewBast($petugas, $periode, $selectedKegiatanId, $jenisKegiatan, $validated);
        }

        if ($dokumenTipe === 'bapp') {
            if ($jenisKegiatan !== 'sensus') {
                return response()->json(['message' => 'BAPP hanya tersedia untuk kegiatan sensus.'], 422);
            }

            return $this->servePublicPreviewBapp($petugas, ActiveYearService::get(), $bappTermin, $validated);
        }

        $finalSignedPdf = $this->resolveFinalSignedSpkPdfBinaryForPublicPreview(
            $periode,
            (int) $petugas->id,
            $selectedKegiatanId,
            $jenisKegiatan,
        );

        $responseFilename = null;
        $sourcePdfContent = null;
        $protectedPdfContent = null;
        $protectedPdfPath = null;

        if ($finalSignedPdf !== null) {
            $responseFilename = $finalSignedPdf['filename'];

            if (($finalSignedPdf['is_protected'] ?? false) === true) {
                $protectedPdfPath = $finalSignedPdf['protected_path'] ?? null;
            } else {
                $sourcePdfContent = $finalSignedPdf['content'];
            }
        } else {
            $nomorSpkPreview = $this->formatPreviewNomorSpkForPeriode(
                $periode,
                $this->getNextNomorUrutForPeriode($periode)
            );

            $pdfPreview = $this->buildMergedSpkPreviewBinary(
                $periode,
                (int) $petugas->id,
                $nomorSpkPreview,
                now()->toDateString(),
                $selectedKegiatanId,
                $jenisKegiatan
            );

            if ($pdfPreview === null) {
                return response()->json([
                    'message' => 'Preview SPK tidak dapat dibuat untuk data ini.',
                ], 422);
            }

            $sourcePdfContent = $pdfPreview['content'];
            $responseFilename = $pdfPreview['filename'];
        }

        if ($protectedPdfContent === null && $protectedPdfPath === null) {
            if (! is_string($sourcePdfContent) || $sourcePdfContent === '') {
                return response()->json([
                    'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            $protectedPdfContent = $this->applyDraftWatermarkAndProtection($sourcePdfContent);

            if ($finalSignedPdf !== null && isset($finalSignedPdf['cache_key'])) {
                $this->storeCachedProtectedPublicPreviewPdf((string) $finalSignedPdf['cache_key'], $protectedPdfContent);
                $protectedPdfPath = $this->getCachedProtectedPublicPreviewPdfPath((string) $finalSignedPdf['cache_key']);
            }
        }

        $disposition = ($validated['aksi'] ?? 'preview') === 'download' ? 'attachment' : 'inline';
        $responseMode = (string) ($validated['response_mode'] ?? 'binary');
        $downloadToken = (string) ($validated['download_token'] ?? '');

        if ($responseMode === 'url' && $disposition === 'inline') {
            if (! is_string($protectedPdfPath) || ! is_file($protectedPdfPath)) {
                if (! is_string($protectedPdfContent) || $protectedPdfContent === '') {
                    return response()->json([
                        'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                    ], 422);
                }

                $protectedPdfPath = $this->storePublicPreviewTemporaryPdf($protectedPdfContent);
            }

            if (! is_string($protectedPdfPath) || ! is_file($protectedPdfPath)) {
                return response()->json([
                    'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            $previewUrl = $this->buildPublicPreviewSignedFileUrl(
                $protectedPdfPath,
                (string) $responseFilename,
                'inline',
            );

            if ($previewUrl === null) {
                return response()->json([
                    'message' => 'URL preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => (string) $responseFilename,
            ]);
        }

        if (is_string($protectedPdfPath) && is_file($protectedPdfPath)) {
            return $this->buildPublicPreviewFileResponse($protectedPdfPath, (string) $responseFilename, $disposition, $downloadToken);
        }

        $response = response($protectedPdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$responseFilename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function buildPublicPreviewSessionSignature(string $nama, string $nik, string $telepon4Digit): string
    {
        return hash('sha256', mb_strtolower(trim($nama)).'|'.trim($nik).'|'.trim($telepon4Digit));
    }

    private function hasRecentPublicPreviewSessionVerification(
        Request $request,
        string $nama,
        string $nik,
        string $telepon4Digit,
    ): bool {
        $payload = $request->session()->get('mitra_preview_verified');

        if (! is_array($payload)) {
            return false;
        }

        $verifiedAt = (int) ($payload['verified_at'] ?? 0);
        if ($verifiedAt <= 0 || (now()->timestamp - $verifiedAt) > 900) {
            return false;
        }

        $signature = (string) ($payload['signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        return hash_equals($signature, $this->buildPublicPreviewSessionSignature($nama, $nik, $telepon4Digit));
    }

    private function resolveFinalSignedSpkPdfBinaryForPublicPreview(
        PeriodeAlokasi $periode,
        int $petugasId,
        ?int $kegiatanId = null,
        ?string $jenisKegiatan = null,
    ): ?array {
        $finalSpk = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereNotNull('signed_file_path')
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($periode, $kegiatanId, $jenisKegiatan): void {
                $query->where('tahun', $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->orderByDesc('addendum_number')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $finalSpk || ! $finalSpk->signed_file_path) {
            return null;
        }

        $rootSpkId = $finalSpk->parent_spk_id ?: $finalSpk->id;

        $signedDocuments = Spk::query()
            ->where('petugas_id', $petugasId)
            ->where(function ($query) use ($rootSpkId): void {
                $query->where('id', $rootSpkId)
                    ->orWhere('parent_spk_id', $rootSpkId);
            })
            ->whereNotNull('signed_file_path')
            ->orderBy('addendum_number')
            ->orderBy('id')
            ->get(['id', 'signed_file_path', 'addendum_number']);

        $signedPaths = $signedDocuments
            ->map(fn (Spk $spk): string => public_path((string) $spk->signed_file_path))
            ->filter(fn (string $path): bool => is_file($path))
            ->values()
            ->all();

        if (empty($signedPaths)) {
            return null;
        }

        $cacheKey = $this->buildPublicPreviewProtectedCacheKey($signedPaths);

        $baseName = pathinfo((string) $finalSpk->signed_file_path, PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $baseName) ?: 'spk_final';

        $downloadFilename = count($signedPaths) === 1
            ? 'Preview_'.$safeBaseName.'.pdf'
            : 'Preview_'.$safeBaseName.'_with_addendum.pdf';

        $cachedProtectedPath = $this->getCachedProtectedPublicPreviewPdfPath($cacheKey);
        if ($cachedProtectedPath !== null) {
            return [
                'filename' => $downloadFilename,
                'cache_key' => $cacheKey,
                'is_protected' => true,
                'protected_path' => $cachedProtectedPath,
            ];
        }

        if (count($signedPaths) === 1) {
            $binaryContent = file_get_contents($signedPaths[0]);
            if (! is_string($binaryContent) || $binaryContent === '') {
                return null;
            }

            return [
                'filename' => $downloadFilename,
                'content' => $binaryContent,
                'cache_key' => $cacheKey,
                'is_protected' => false,
            ];
        }

        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return null;
        }

        $token = time().'_'.uniqid();
        $mergedPath = $tempPath.'/spk_public_preview_signed_merge_'.$token.'.pdf';

        try {
            $merged = PdfMergerService::mergePdfFiles($signedPaths, $mergedPath);
            if (! $merged || ! is_file($mergedPath)) {
                return null;
            }

            $mergedContent = file_get_contents($mergedPath);
            if (! is_string($mergedContent) || $mergedContent === '') {
                return null;
            }

            return [
                'filename' => $downloadFilename,
                'content' => $mergedContent,
                'cache_key' => $cacheKey,
                'is_protected' => false,
            ];
        } finally {
            @unlink($mergedPath);
        }
    }

    private function buildPublicPreviewProtectedCacheKey(array $absolutePdfPaths): string
    {
        $fingerprint = collect($absolutePdfPaths)
            ->map(function (string $path): string {
                $realPath = realpath($path) ?: $path;
                $modifiedTime = (string) (@filemtime($path) ?: 0);
                $fileSize = (string) (@filesize($path) ?: 0);

                return $realPath.'|'.$modifiedTime.'|'.$fileSize;
            })
            ->implode('||');

        return hash('sha256', 'public-preview-v2|'.$fingerprint);
    }

    private function getCachedProtectedPublicPreviewPdfPath(string $cacheKey): ?string
    {
        $cachePath = storage_path('app/temp/public_preview_protected_'.$cacheKey.'.pdf');

        if (! is_file($cachePath)) {
            return null;
        }

        return $cachePath;
    }

    private function storeCachedProtectedPublicPreviewPdf(string $cacheKey, string $protectedPdfContent): void
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return;
        }

        @file_put_contents($tempPath.'/public_preview_protected_'.$cacheKey.'.pdf', $protectedPdfContent);
    }

    private function storePublicPreviewTemporaryPdf(string $pdfContent): ?string
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return null;
        }

        try {
            $filename = 'public_preview_runtime_'.bin2hex(random_bytes(16)).'.pdf';
        } catch (\Throwable) {
            $filename = 'public_preview_runtime_'.uniqid('', true).'.pdf';
        }

        $filePath = $tempPath.'/'.$filename;
        if (@file_put_contents($filePath, $pdfContent) === false) {
            return null;
        }

        return $filePath;
    }

    private function buildPublicPreviewSignedFileUrl(string $filePath, string $responseFilename, string $disposition = 'inline'): ?string
    {
        if (! is_file($filePath)) {
            return null;
        }

        $file = basename($filePath);
        if ($file === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            return null;
        }

        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $responseFilename) ?: 'Preview_SPK.pdf';
        $safeDisposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return URL::temporarySignedRoute(
            'spk.public-preview.file',
            now()->addMinutes(10),
            [
                'file' => $file,
                'filename' => $safeFilename,
                'disposition' => $safeDisposition,
            ],
        );
    }

    public function publicPreviewFile(Request $request, string $file)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            abort(404);
        }

        $filePath = storage_path('app/temp/'.$file);
        if (! is_file($filePath)) {
            abort(404);
        }

        $filename = (string) $request->query('filename', 'Preview_SPK.pdf');
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename) ?: 'Preview_SPK.pdf';
        $disposition = (string) $request->query('disposition', 'inline');
        $safeDisposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $this->buildPublicPreviewFileResponse($filePath, $safeFilename, $safeDisposition, '', 600);
    }

    private function ensureDirectoryExists(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, 0777, true) || is_dir($path);
    }

    private function buildPublicPreviewFileResponse(string $filePath, string $responseFilename, string $disposition, string $downloadToken = '', int $cacheSeconds = 0)
    {
        $cacheControl = $cacheSeconds > 0
            ? 'public, max-age='.$cacheSeconds.', immutable'
            : 'no-cache, must-revalidate';

        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => $cacheControl,
            'Expires' => $cacheSeconds > 0 ? gmdate('D, d M Y H:i:s', time() + $cacheSeconds).' GMT' : '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($disposition === 'attachment') {
            $response = response()->download($filePath, $responseFilename, $headers);

            return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
        }

        $response = response()->file($filePath, $headers + [
            'Content-Disposition' => 'inline; filename="'.$responseFilename.'"',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function appendPublicPreviewDownloadCookie(mixed $response, string $disposition, string $downloadToken): mixed
    {
        if ($disposition !== 'attachment') {
            return $response;
        }

        $token = trim($downloadToken);
        if ($token === '') {
            return $response;
        }

        return $response->cookie(cookie('mitra_download_token', $token, 2, '/', null, false, false, false, 'Lax'));
    }

    private function isValidPublicPreviewRecaptcha(string $token, ?string $ipAddress = null): bool
    {
        if (! (bool) config('services.recaptcha.enabled', false)) {
            return true;
        }

        $secretKey = trim((string) config('services.recaptcha.secret_key', ''));

        if ($secretKey === '' || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            if (! $response->ok()) {
                return false;
            }

            $payload = $response->json();

            return (bool) ($payload['success'] ?? false);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function resolvePublicPreviewOptionsForPetugas(Petugas $petugas, int $activeYear): array
    {
        $alokasiCollection = AlokasiPetugas::query()
            ->with([
                'periodeAlokasi.kegiatan.rateHonors.satuan',
                'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                'frameSampelAllocations.kegiatanFrameSampel.frameSampel',
            ])
            ->where('petugas_id', $petugas->id)
            ->where(function ($query): void {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->whereHas('periodeAlokasi', function ($query) use ($activeYear): void {
                $query->where('tahun', $activeYear)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
            ->get();

        $documentStatusMap = $this->resolvePublicPreviewDocumentStatusMap(
            (int) $petugas->id,
            $alokasiCollection,
        );

        // Batch-query BAST status grouped by periodKey|jenisKegiatan.
        // BAST is 1 per petugas per period — look up directly via spk.petugas_id
        // to avoid any dependency on alokasi status (dikirim/perubahan/direvisi).
        $allBasts = Bast::query()
            ->whereHas('spk', function ($q) use ($petugas, $activeYear): void {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($pq) use ($activeYear): void {
                        $pq->where('tahun', $activeYear);
                    });
            })
            ->with([
                'spk.alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->whereNull('deleted_at')
            ->get(['id', 'spk_id', 'file_path', 'signed_file_path', 'main_signed_file_path', 'compiled_file_path']);

        /** @var array<string, Bast|null> $bastStatusByKey */
        $bastStatusByKey = [];

        foreach ($allBasts as $bast) {
            $spkPeriode = $bast->spk?->alokasiPetugas?->periodeAlokasi;
            $spkKegiatan = $spkPeriode?->kegiatan;

            if (! $spkPeriode || ! $spkKegiatan) {
                continue;
            }

            $spkPeriodKey = sprintf('%d-%02d', (int) $spkPeriode->tahun, (int) $spkPeriode->bulan);
            $spkStatusKey = $this->buildPublicPreviewDocumentStatusKey($spkPeriodKey, (string) $spkKegiatan->jenis_kegiatan);

            $existing = $bastStatusByKey[$spkStatusKey] ?? null;

            // Prefer signed BAST over draft
            if (! $existing || (($bast->signed_file_path || $bast->main_signed_file_path) && ! $existing->signed_file_path && ! $existing->main_signed_file_path)) {
                $bastStatusByKey[$spkStatusKey] = $bast;
            }
        }

        $sensusBast = $allBasts->first(function (Bast $bast): bool {
            $kegiatan = $bast->spk?->alokasiPetugas?->periodeAlokasi?->kegiatan;

            return mb_strtolower((string) $kegiatan?->jenis_kegiatan) === 'sensus'
                && filled($bast->signed_file_path);
        });

        // Batch-query BAPP status (only needed for sensus)
        $bapps = BappSeTermin::query()
            ->where('petugas_id', $petugas->id)
            ->where('tahun', $activeYear)
            ->get(['id', 'termin', 'file_path', 'signed_file_path']);

        $bappByTermin = $bapps->keyBy('termin');

        $penugasanList = $alokasiCollection
            ->map(function (AlokasiPetugas $alokasi) use ($documentStatusMap, $bastStatusByKey, $bappByTermin, $sensusBast): ?array {
                $periode = $alokasi->periodeAlokasi;
                $kegiatan = $periode?->kegiatan;

                if (! $periode || ! $kegiatan) {
                    return null;
                }

                $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
                $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                    $periodKey,
                    (string) $kegiatan->jenis_kegiatan,
                );
                $documentStatus = $documentStatusMap[$statusKey] ?? 'Belum ada PK';

                $isSensus = mb_strtolower((string) $kegiatan->jenis_kegiatan) === 'sensus';
                $bast = $isSensus
                    ? $sensusBast
                    : ($bastStatusByKey[$statusKey] ?? null);

                return [
                    'id' => $alokasi->id,
                    'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                    'kegiatan_hashed_id' => $kegiatan->hashed_id,
                    'periode_key' => $periodKey,
                    'periode_label' => $this->getBulanLabel((int) $periode->bulan).' '.(int) $periode->tahun,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'target_pekerjaan' => $this->resolvePublicPreviewTargetPekerjaan($alokasi),
                    'honor' => (float) $alokasi->getEffectiveCombinedHonor(),
                    'honor_label' => 'Rp '.number_format((float) $alokasi->getEffectiveCombinedHonor(), 0, ',', '.'),
                    'document_status' => $documentStatus,
                    'bast_status' => $isSensus
                        ? ($bast?->signed_file_path ? 'BAST tersedia' : 'Tidak tersedia')
                        : $this->getBastStatusLabel($bast),
                    'bast_available' => $isSensus
                        ? (bool) $bast?->signed_file_path
                        : (bool) ($bast?->compiled_file_path || $bast?->signed_file_path || $bast?->main_signed_file_path || $bast?->file_path),
                    'bapp_termin_i_status' => $isSensus
                        ? ($bappByTermin->get(1)?->signed_file_path ? 'BAPP tersedia' : 'Tidak tersedia')
                        : null,
                    'bapp_termin_ii_status' => $isSensus
                        ? ($bappByTermin->get(2)?->signed_file_path ? 'BAPP tersedia' : 'Tidak tersedia')
                        : null,
                    'bapp_termin_i_available' => $isSensus
                        ? (bool) $bappByTermin->get(1)?->signed_file_path
                        : null,
                    'bapp_termin_ii_available' => $isSensus
                        ? (bool) $bappByTermin->get(2)?->signed_file_path
                        : null,
                ];
            })
            ->filter()
            ->values();

        $penugasanList = $penugasanList
            ->unique(fn (array $item) => $item['jenis_kegiatan'].'|'.$item['kegiatan_hashed_id'].'|'.$item['id'])
            ->values()
            ->all();

        $surveiPeriods = $alokasiCollection
            ->filter(function (AlokasiPetugas $alokasi): bool {
                return mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'survei';
            })
            ->map(function (AlokasiPetugas $alokasi): ?string {
                $periode = $alokasi->periodeAlokasi;

                if (! $periode) {
                    return null;
                }

                $hasDraft = PeriodeAlokasi::query()
                    ->where('tahun', (int) $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->where('status', 'draft')
                    ->whereHas('kegiatan', function ($query): void {
                        $query->where('jenis_kegiatan', 'survei');
                    })
                    ->exists();

                if ($hasDraft) {
                    return null;
                }

                return sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $periodKey): array {
                [$tahun, $bulan] = explode('-', $periodKey);

                return [
                    'value' => $periodKey,
                    'label' => $this->getBulanLabel((int) $bulan).' '.(int) $tahun,
                ];
            })
            ->all();

        $sensusKegiatans = $alokasiCollection
            ->filter(function (AlokasiPetugas $alokasi): bool {
                return mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'sensus';
            })
            ->map(function (AlokasiPetugas $alokasi): ?array {
                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

                if (! $kegiatan) {
                    return null;
                }

                return [
                    'value' => $kegiatan->hashed_id,
                    'label' => $kegiatan->nama_kegiatan,
                ];
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();

        $sensusKegiatans = $sensusKegiatans
            ->unique('value')
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'survei_periods' => $surveiPeriods,
            'sensus_kegiatans' => $sensusKegiatans,
            'penugasan_list' => $penugasanList,
        ];
    }

    private function resolvePublicPreviewDocumentStatusMap(int $petugasId, Collection $alokasiCollection): array
    {
        $keys = [];
        $months = [];
        $years = [];
        $kegiatanIds = [];

        foreach ($alokasiCollection as $alokasi) {
            $periode = $alokasi->periodeAlokasi;
            $kegiatan = $periode?->kegiatan;

            if (! $periode || ! $kegiatan) {
                continue;
            }

            $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                $periodKey,
                (string) $kegiatan->jenis_kegiatan,
            );

            $keys[$statusKey] = [
                'period_key' => $periodKey,
                'jenis_kegiatan' => (string) $kegiatan->jenis_kegiatan,
                'kegiatan_id' => (int) $kegiatan->id,
            ];

            $months[(int) $periode->bulan] = (int) $periode->bulan;
            $years[(int) $periode->tahun] = (int) $periode->tahun;
            $kegiatanIds[(int) $kegiatan->id] = (int) $kegiatan->id;
        }

        if (empty($keys)) {
            return [];
        }

        $documents = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($months, $years, $kegiatanIds): void {
                $query->whereIn('bulan', array_values($months))
                    ->whereIn('tahun', array_values($years))
                    ->whereIn('kegiatan_id', array_values($kegiatanIds));
            })
            ->with([
                'alokasiPetugas.periodeAlokasi:id,kegiatan_id,bulan,tahun',
                'alokasiPetugas.periodeAlokasi.kegiatan:id,jenis_kegiatan',
            ])
            ->get(['id', 'alokasi_petugas_id', 'signed_file_path', 'addendum_number']);

        $groups = [];
        foreach ($documents as $document) {
            $periode = $document->alokasiPetugas?->periodeAlokasi;
            $kegiatan = $periode?->kegiatan;

            if (! $periode || ! $kegiatan) {
                continue;
            }

            $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                $periodKey,
                (string) $kegiatan->jenis_kegiatan,
            );

            $groups[$statusKey][] = $document;
        }

        $result = [];
        foreach ($keys as $statusKey => $meta) {
            $groupDocuments = $groups[$statusKey] ?? [];

            if (empty($groupDocuments)) {
                $result[$statusKey] = 'Belum ada PK';

                continue;
            }

            $hasMainSigned = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
            $hasAddendumDraft = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
            $hasAddendumSigned = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

            if ($hasMainSigned && $hasAddendumSigned) {
                $result[$statusKey] = 'PK Final + Addendum';

                continue;
            }

            if ($hasMainSigned && $hasAddendumDraft) {
                $result[$statusKey] = 'PK Final + Addendum(draft)';

                continue;
            }

            if ($hasAddendumSigned) {
                $result[$statusKey] = 'Addendum Final';

                continue;
            }

            if ($hasMainSigned) {
                $result[$statusKey] = 'PK Final';

                continue;
            }

            $result[$statusKey] = 'PK Draft';
        }

        return $result;
    }

    private function buildPublicPreviewDocumentStatusKey(string $periodKey, string $jenisKegiatan): string
    {
        return $periodKey.'|'.mb_strtolower($jenisKegiatan);
    }

    private function servePublicPreviewBast(Petugas $petugas, PeriodeAlokasi $periode, ?int $kegiatanId, string $jenisKegiatan, array $validated): mixed
    {
        // BAST is 1 per petugas per period — look up directly via spk.petugas_id.
        $bast = Bast::query()
            ->whereHas('spk', function ($q) use ($petugas, $periode, $jenisKegiatan, $kegiatanId): void {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($pq) use ($periode, $jenisKegiatan, $kegiatanId): void {
                        $pq->where('tahun', $periode->tahun);

                        if ($jenisKegiatan !== 'sensus') {
                            $pq->where('bulan', $periode->bulan);
                        }

                        $pq->whereHas('kegiatan', function ($kq) use ($jenisKegiatan, $kegiatanId): void {
                            $kq->where('jenis_kegiatan', $jenisKegiatan);

                            if ($kegiatanId !== null) {
                                $kq->where('id', $kegiatanId);
                            }
                        });
                    });
            })
            ->whereNull('deleted_at')
            ->orderByRaw('(signed_file_path IS NOT NULL OR main_signed_file_path IS NOT NULL) DESC')
            ->first();

        if (! $bast) {
            return response()->json(['message' => 'BAST belum tersedia untuk penugasan ini.'], 422);
        }

        $isSensusEkonomi = mb_strtolower($jenisKegiatan) === 'sensus';

        // SE2026 hanya tersedia jika PDF manual sudah diunggah.
        if ($isSensusEkonomi) {
            $filePath = $bast->signed_file_path;
            if (! $filePath) {
                return response()->json(['message' => 'BAST SE2026 belum diunggah.'], 422);
            }

            $absolutePath = $this->resolvePublicBastAbsolutePath($filePath);
            if (! $absolutePath || ! is_file($absolutePath)) {
                return response()->json(['message' => 'File BAST tidak dapat diakses.'], 422);
            }

            $content = (string) file_get_contents($absolutePath);
        } else {
            // Pada workflow reguler, dokumen utama dan lampiran memang disimpan
            // terpisah selama masih draft. /mitra tetap harus memperlihatkan satu
            // dokumen utuh, jadi gabungkan on-demand bila compiled_file_path belum ada.
            $preferredPath = $bast->compiled_file_path
                ?: ($bast->signed_file_path ?: $bast->main_signed_file_path);

            $preferredAbsolutePath = $this->resolvePublicBastAbsolutePath($preferredPath);
            if ($preferredAbsolutePath && is_file($preferredAbsolutePath)) {
                $content = (string) file_get_contents($preferredAbsolutePath);
            } else {
                $mainPath = $this->resolvePublicBastAbsolutePath($bast->file_path);
                if (! $mainPath || ! is_file($mainPath)) {
                    return response()->json(['message' => 'File BAST belum dibuat.'], 422);
                }

                $bast->loadMissing('bastKegiatan');
                $sourcePaths = [$mainPath];

                foreach ($bast->bastKegiatan as $lampiran) {
                    $lampiranPath = $lampiran->signed_file_path ?: $lampiran->file_path;
                    $lampiranAbsolutePath = $this->resolvePublicBastAbsolutePath($lampiranPath);

                    if ($lampiranAbsolutePath && is_file($lampiranAbsolutePath)) {
                        $sourcePaths[] = $lampiranAbsolutePath;
                    }
                }

                if (count($sourcePaths) === 1) {
                    $content = (string) file_get_contents($mainPath);
                } else {
                    $temporaryPath = tempnam(sys_get_temp_dir(), 'simantik-bast-public-');
                    if (! is_string($temporaryPath) || $temporaryPath === '') {
                        return response()->json(['message' => 'File gabungan BAST tidak dapat dibuat.'], 500);
                    }

                    try {
                        $merged = PdfMergerService::mergePdfFiles(
                            $sourcePaths,
                            $temporaryPath,
                            'BAST public preview',
                        );

                        if (! $merged || ! is_file($temporaryPath)) {
                            return response()->json(['message' => 'Lampiran BAST belum dapat digabungkan.'], 500);
                        }

                        $content = (string) file_get_contents($temporaryPath);
                    } finally {
                        if (is_file($temporaryPath)) {
                            @unlink($temporaryPath);
                        }
                    }
                }
            }
        }

        $nomor = preg_replace('/[^A-Za-z0-9_\-]/', '-', (string) ($bast->nomor_bast ?? 'BAST'));

        return $this->serveProtectedPublicPreviewContent($content, 'BAST_'.$nomor.'.pdf', $validated);
    }

    private function resolvePublicBastAbsolutePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $normalized = ltrim(str_replace('\\\\', '/', (string) $path), '/');

        if (str_starts_with($normalized, 'storage/')) {
            $storageRelativePath = substr($normalized, strlen('storage/'));
            $storagePath = Storage::disk('public')->path($storageRelativePath);

            if (is_file($storagePath)) {
                return $storagePath;
            }
        }

        $publicPath = public_path($normalized);
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = Storage::disk('public')->path($normalized);

        return is_file($storagePath) ? $storagePath : null;
    }

    private function servePublicPreviewBapp(Petugas $petugas, int $tahun, int $termin, array $validated): mixed
    {
        $bapp = BappSeTermin::query()
            ->where('petugas_id', $petugas->id)
            ->where('tahun', $tahun)
            ->where('termin', $termin)
            ->first();

        $terminLabel = $termin === 1 ? 'I' : 'II';

        if (! $bapp) {
            return response()->json(['message' => "BAPP Termin {$terminLabel} belum tersedia."], 422);
        }

        // BAPP SE2026 tidak lagi digenerate. Hanya PDF manual/final yang
        // sudah diunggah yang boleh ditampilkan pada halaman publik.
        $filePath = $bapp->signed_file_path;

        if (! $filePath) {
            return response()->json(['message' => "BAPP Termin {$terminLabel} belum diunggah."], 422);
        }

        $absolutePath = Storage::disk('public')->path($filePath);

        if (! file_exists($absolutePath)) {
            return response()->json(['message' => 'File BAPP tidak dapat diakses.'], 422);
        }

        $content = (string) file_get_contents($absolutePath);
        $nomor = preg_replace('/[^A-Za-z0-9_\-]/', '-', (string) ($bapp->nomor_bapp ?? 'BAPP'));

        return $this->serveProtectedPublicPreviewContent($content, 'BAPP_Termin_'.$terminLabel.'_'.$nomor.'.pdf', $validated);
    }

    private function serveProtectedPublicPreviewContent(string $content, string $filename, array $validated): mixed
    {
        $protectedContent = $this->applyDraftWatermarkAndProtection($content);
        $disposition = ($validated['aksi'] ?? 'preview') === 'download' ? 'attachment' : 'inline';
        $responseMode = (string) ($validated['response_mode'] ?? 'binary');
        $downloadToken = (string) ($validated['download_token'] ?? '');

        if ($responseMode === 'url' && $disposition === 'inline') {
            $tempPath = $this->storePublicPreviewTemporaryPdf($protectedContent);

            if (! is_string($tempPath) || ! is_file($tempPath)) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            $previewUrl = $this->buildPublicPreviewSignedFileUrl($tempPath, $filename, 'inline');

            if (! $previewUrl) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        $response = response($protectedContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function getBastStatusLabel(?Bast $bast): string
    {
        if (! $bast) {
            return 'Belum ada BAST';
        }

        if ($bast->signed_file_path || $bast->main_signed_file_path) {
            return 'BAST Final';
        }

        if ($bast->file_path) {
            return 'Draft BAST';
        }

        return 'Belum ada BAST';
    }

    private function getBappStatusLabel(?BappSeTermin $bapp): string
    {
        if (! $bapp) {
            return 'Belum ada BAPP';
        }

        if ($bapp->signed_file_path) {
            return 'BAPP Final';
        }

        if ($bapp->file_path) {
            return 'Draft BAPP';
        }

        return 'Belum ada BAPP';
    }

    private function resolvePublicPreviewTargetPekerjaan(AlokasiPetugas $alokasi): string
    {
        if (mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'sensus') {
            $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($alokasi, $alokasi);
            if (($metrics['narrative'] ?? '-') !== '-') {
                return (string) $metrics['narrative'];
            }
        }

        $rateHonor = $this->resolvePublicPreviewRateHonorForAlokasi($alokasi);

        if (! $rateHonor || ! $rateHonor->satuan) {
            if ($alokasi->getEffectiveCombinedHonor() > 0) {
                return '1 paket';
            }

            return '-';
        }

        $targetValue = $alokasi->getEffectiveJumlahSatuan();
        if ($targetValue <= 0 && $alokasi->getEffectiveCombinedHonor() > 0) {
            $targetValue = 1;
        }

        if ($targetValue <= 0) {
            return '-';
        }

        return number_format($targetValue, 0, ',', '.').' '.$rateHonor->satuan->nama;
    }

    private function resolvePublicPreviewRateHonorForAlokasi(AlokasiPetugas $alokasi): ?RateHonor
    {
        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

        if (! $kegiatan) {
            return null;
        }

        $kegiatan->loadMissing([
            'rateHonors.satuan',
            'rateHonors.satuanListing',
        ]);

        $rateHonorByKey = $kegiatan->rateHonors->keyBy(function (RateHonor $rateHonor): string {
            return $rateHonor->status_kepegawaian.'|'.$rateHonor->jenis_penugasan;
        });

        $statusKepegawaian = $alokasi->status_kepegawaian
            ?? (($alokasi->petugas->jenis_petugas ?? 'non-organik') === 'organik' ? 'organik' : 'non_organik');

        return $rateHonorByKey->get($statusKepegawaian.'|'.$alokasi->peran)
            ?? $kegiatan->rateHonors->firstWhere('status', 'aktif');
    }

    private function resolvePublicPreviewDocumentStatusForPeriod(
        int $petugasId,
        string $periodKey,
        ?string $jenisKegiatan = null,
        ?int $kegiatanId = null,
    ): string {
        [$tahun, $bulan] = explode('-', $periodKey);

        $documents = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($tahun, $bulan, $jenisKegiatan, $kegiatanId): void {
                $query->where('tahun', (int) $tahun)
                    ->where('bulan', (int) $bulan)
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->orderBy('addendum_number')
            ->get(['id', 'signed_file_path', 'addendum_number']);

        if ($documents->isEmpty()) {
            return 'Belum ada PK';
        }

        $hasMainSigned = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
        $hasAddendumDraft = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
        $hasAddendumSigned = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

        if ($hasMainSigned && $hasAddendumSigned) {
            return 'PK Final + Addendum';
        }

        if ($hasMainSigned && $hasAddendumDraft) {
            return 'PK Final + Addendum(draft)';
        }

        if ($hasAddendumSigned) {
            return 'Addendum Final';
        }

        if ($hasMainSigned) {
            return 'PK Final';
        }

        return 'PK Draft';
    }

    private function applyDraftWatermarkAndProtection(string $pdfBinary): string
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return $pdfBinary;
        }

        $token = time().'_'.uniqid();
        $inputPath = $tempPath.'/spk_public_preview_input_'.$token.'.pdf';
        $outputPath = $tempPath.'/spk_public_preview_output_'.$token.'.pdf';

        try {
            file_put_contents($inputPath, $pdfBinary);

            $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->setProtection(['modify', 'annot-forms', 'fill-forms', 'assemble'], '', '@dm1n_SIMANTIK');

            $pageCount = $pdf->setSourceFile($inputPath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = (($size['width'] ?? 210) > ($size['height'] ?? 297)) ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pageWidth = (float) ($size['width'] ?? 210);
                $pageHeight = (float) ($size['height'] ?? 297);
                $centerX = $pageWidth / 2;
                $centerY = $pageHeight / 2;
                $watermarkText = 'BPS KOTA SAWAHLUNTO';
                $rotationAngle = 28.0;
                $fontSize = min(44.0, max(24.0, $pageWidth * 0.18));

                $pdf->SetAlpha(0.12);
                $pdf->SetTextColor(95, 95, 95);
                $pdf->SetFont('helvetica', 'B', (float) $fontSize);

                $textWidth = (float) $pdf->GetStringWidth($watermarkText);

                // Keep reducing the font until rotated watermark fits safely inside the page.
                while ($fontSize > 16.0) {
                    $theta = deg2rad($rotationAngle);
                    $halfRotatedWidth = (abs($textWidth * cos($theta)) + abs($fontSize * sin($theta))) / 2;
                    $halfRotatedHeight = (abs($textWidth * sin($theta)) + abs($fontSize * cos($theta))) / 2;

                    $fitsHorizontally = $halfRotatedWidth <= ($pageWidth / 2) - 8;
                    $fitsVertically = $halfRotatedHeight <= ($pageHeight / 2) - 8;

                    if ($fitsHorizontally && $fitsVertically) {
                        break;
                    }

                    $fontSize -= 1.0;
                    $pdf->SetFont('helvetica', 'B', (float) $fontSize);
                    $textWidth = (float) $pdf->GetStringWidth($watermarkText);
                }

                $textX = $centerX - ($textWidth / 2);
                $textY = $centerY - ($fontSize * 0.35);

                $pdf->StartTransform();
                $pdf->Rotate($rotationAngle, $centerX, $centerY);
                $pdf->Text($textX, $textY, $watermarkText);
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
            }

            $pdf->Output($outputPath, 'F');
            $securedBinary = file_get_contents($outputPath);

            return is_string($securedBinary) && $securedBinary !== '' ? $securedBinary : $pdfBinary;
        } catch (\Throwable $exception) {
            return $pdfBinary;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    private function formatPreviewNomorSpkForPeriode(PeriodeAlokasi $periode, int $nomorUrut): string
    {
        $nomorSpkAsli = $this->formatNomorSpkForPeriode($periode, $nomorUrut);

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return (string) preg_replace('/^B-(\d+)/', 'PREVIEW-$1', $nomorSpkAsli, 1);
        }

        $parts = explode('/', $nomorSpkAsli);
        if (isset($parts[3]) && mb_strtoupper((string) $parts[3]) === 'K') {
            $parts[3] = 'PREVIEW-K';

            return implode('/', $parts);
        }

        return str_replace('/K/', '/PREVIEW-K/', $nomorSpkAsli);
    }

    private function resolvePublicPreviewPetugas(string $nama, string $nik): ?Petugas
    {
        $normalizedNama = mb_strtolower(trim($nama));
        $normalizedNik = trim($nik);

        return Petugas::query()
            ->where('status', 'aktif')
            ->get()
            ->first(function (Petugas $petugas) use ($normalizedNama, $normalizedNik): bool {
                $petugasNik = trim((string) $petugas->getAttribute('nik'));
                $petugasNama = mb_strtolower(trim((string) $petugas->nama));

                return $petugasNik === $normalizedNik && $petugasNama === $normalizedNama;
            });
    }

    private function matchesPublicPreviewPhoneVerification(Petugas $petugas, string $telepon4Digit): bool
    {
        $normalizedPhoneVerification = preg_replace('/\D+/', '', trim($telepon4Digit)) ?? '';

        if (strlen($normalizedPhoneVerification) !== 4) {
            return false;
        }

        $petugasPhoneDigits = preg_replace('/\D+/', '', (string) $petugas->telepon) ?? '';

        if (strlen($petugasPhoneDigits) < 4) {
            return false;
        }

        return substr($petugasPhoneDigits, -4) === $normalizedPhoneVerification;
    }
}
