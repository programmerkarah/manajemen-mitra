<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

trait BastDocumentStorageSupport
{
    private function sanitizeDocumentSegment(string $value): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? 'document';

        return trim($sanitized, '_') ?: 'document';
    }

    private function makeBastKegiatanKey(int $kegiatanId, int $periodeAlokasiId): string
    {
        return $kegiatanId.':'.$periodeAlokasiId;
    }

    private function ensureBastKegiatanBelongsToBast(Bast $bast, BastKegiatan $bastKegiatan): void
    {
        abort_if($bastKegiatan->bast_id !== $bast->id, 404);
    }

    private function ensureBastExportDirectory(string $subdirectory = ''): string
    {
        $relativeDirectory = trim('bast-export/'.trim($subdirectory, '/'), '/');
        $absoluteDirectory = public_path($relativeDirectory);

        if (! file_exists($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }

        return $absoluteDirectory;
    }

    private function resolveDocumentAbsolutePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return public_path(ltrim(str_replace('\\', '/', $path), '/'));
    }

    private function redirectToLocalPath(Request $request, string $fallbackPath): RedirectResponse
    {
        $redirectPath = trim((string) $request->input('redirect_url'));

        if ($redirectPath !== '' && str_starts_with($redirectPath, '/') && ! str_starts_with($redirectPath, '//')) {
            return redirect()->to($redirectPath);
        }

        return redirect()->to($fallbackPath);
    }

    private function rememberOpenDetailFiltersFromBast(Request $request, Bast $bast): void
    {
        $bast->loadMissing([
            'periodeAlokasi:id,bulan,tahun',
            'spk.alokasiPetugas:id,petugas_id',
            'bastPetugas:id,bast_id,petugas_id',
        ]);

        $periode = $bast->periodeAlokasi;
        if (! $periode) {
            return;
        }

        $filters = [
            'bulan' => (int) $periode->bulan,
            'tahun' => (int) $periode->tahun,
        ];

        $petugasId = $bast->spk?->alokasiPetugas?->petugas_id
            ?? $bast->bastPetugas->pluck('petugas_id')->filter()->map(fn ($id) => (int) $id)->first();

        if (filled($petugasId)) {
            $filters['petugas_id'] = (int) $petugasId;
        }

        $request->session()->put('bast_open_detail_filters', $filters);
    }

    private function deleteStoredDocument(?string $path): void
    {
        $absolutePath = $this->resolveDocumentAbsolutePath($path);

        if ($absolutePath && file_exists($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function writePdfToPublicDirectory(string $filename, string $contents, string $subdirectory = ''): string
    {
        $absoluteDirectory = $this->ensureBastExportDirectory($subdirectory);
        $absolutePath = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;

        file_put_contents($absolutePath, $contents);

        return trim('bast-export/'.trim($subdirectory, '/').'/'.$filename, '/');
    }

    private function buildPreviewLampiranStorageFilename(
        Spk $spk,
        int $kegiatanId,
        int $periodeAlokasiId,
        string $kodeKegiatan,
        bool $signed = false,
    ): string {
        $prefix = $signed ? 'LAMPIRAN_SIGNED_PREBAST' : 'LAMPIRAN_PREBAST';

        return $prefix
            .'_SPK_'.$spk->id
            .'_KGT_'.$kegiatanId
            .'_PER_'.$periodeAlokasiId
            .'_'.$this->sanitizeDocumentSegment($kodeKegiatan)
            .'.pdf';
    }

    private function buildPreviewLampiranRelativePath(
        Spk $spk,
        int $kegiatanId,
        int $periodeAlokasiId,
        string $kodeKegiatan,
        bool $signed = false,
    ): string {
        $subdirectory = $signed ? 'lampiran-preview-signed' : 'lampiran-preview-draft';

        return trim(
            'bast-export/'
            .$subdirectory
            .'/'.$this->buildPreviewLampiranStorageFilename($spk, $kegiatanId, $periodeAlokasiId, $kodeKegiatan, $signed),
            '/'
        );
    }

    private function extractBastSequence(?string $nomorBast): int
    {
        if (! $nomorBast) {
            return 0;
        }

        if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $nomorBast, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/B-(\d{3})\/BAST-SE2026\/1373\/PL\.200\/2026/', $nomorBast, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractBastSequenceForScheme(?string $nomorBast, bool $isSensusEkonomi): int
    {
        if (! $nomorBast) {
            return 0;
        }

        if ($isSensusEkonomi) {
            if (preg_match('/B-(\d{3})\/BAST-SE2026\/1373\/PL\.200\/2026/', $nomorBast, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        }

        if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $nomorBast, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function isSensusEkonomiName(?string $name): bool
    {
        if (! $name) {
            return false;
        }

        $normalized = mb_strtolower($name);

        return str_contains($normalized, 'sensus ekonomi');
    }

    private function isSensusEkonomiSpk(Spk $spk): bool
    {
        $spk->loadMissing('alokasiPetugas.periodeAlokasi.kegiatan:id,nama_kegiatan');

        $kegiatanName = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->nama_kegiatan;

        return $this->isSensusEkonomiName($kegiatanName);
    }

    private function formatBastNomor(int $sequence, int $year, bool $isSensusEkonomi): string
    {
        if ($isSensusEkonomi) {
            return sprintf('B-%03d/BAST-SE2026/1373/PL.200/2026', $sequence);
        }

        return sprintf('PPIS/13730/%d/BAST/%d', $sequence, $year);
    }

    private function allocateNomorBastForSpk(Spk $spk, Carbon $tanggalBast): string
    {
        $existing = BastNumberAllocation::query()->where('spk_id', $spk->id)->first();

        if ($existing?->nomor_bast) {
            return (string) $existing->nomor_bast;
        }

        $isSensusEkonomi = $this->isSensusEkonomiSpk($spk);
        $tahun = $tanggalBast->year;
        $bulan = $tanggalBast->month;

        $maxFromBast = Bast::query()
            ->whereYear('tanggal_bast', $tahun)
            ->pluck('nomor_bast')
            ->map(fn (?string $nomorBast) => $this->extractBastSequenceForScheme($nomorBast, $isSensusEkonomi))
            ->max() ?? 0;

        $maxFromAllocation = BastNumberAllocation::query()
            ->where('tahun', $tahun)
            ->pluck('nomor_bast')
            ->map(fn (?string $nomorBast) => $this->extractBastSequenceForScheme($nomorBast, $isSensusEkonomi))
            ->max() ?? 0;

        $nextSequence = max($maxFromBast, $maxFromAllocation) + 1;
        $nomorBast = $this->formatBastNomor($nextSequence, $tahun, $isSensusEkonomi);

        BastNumberAllocation::query()->updateOrCreate(
            ['spk_id' => $spk->id],
            [
                'nomor_bast' => $nomorBast,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'status' => 'allocated',
                'allocated_at' => now(),
            ]
        );

        return $nomorBast;
    }

    private function markNomorBastAllocationUsed(Spk $spk, string $nomorBast): void
    {
        $existing = BastNumberAllocation::query()->where('spk_id', $spk->id)->first();

        $year = (int) now()->year;
        if (preg_match('/(\d{4})$/', $nomorBast, $matches)) {
            $year = (int) $matches[1];
        }

        BastNumberAllocation::query()->updateOrCreate(
            ['spk_id' => $spk->id],
            [
                'nomor_bast' => $nomorBast,
                'tahun' => $existing?->tahun ?? $year,
                'bulan' => $existing?->bulan ?? (int) now()->month,
                'status' => 'used',
                'used_at' => now(),
            ]
        );
    }

    private function getPreviewLampiranDocumentState(
        ?Spk $spk,
        int $kegiatanId,
        int $periodeAlokasiId,
        string $kodeKegiatan,
        ?string $sharedFasihScreenshotPath = null,
    ): array {
        if (! $spk || $kegiatanId <= 0 || $periodeAlokasiId <= 0) {
            return [
                'file_path' => null,
                'signed_file_path' => null,
                'fasih_screenshot_path' => null,
                'generated_at' => null,
                'signed_uploaded_at' => null,
                'status' => 'pending',
                'can_upload_signed' => false,
            ];
        }

        $record = BastKegiatan::query()
            ->whereNull('bast_id')
            ->where('spk_id', $spk->id)
            ->where('kegiatan_id', $kegiatanId)
            ->where('periode_alokasi_id', $periodeAlokasiId)
            ->first();

        $draftAbsolutePath = $this->resolveDocumentAbsolutePath($record?->file_path);
        $signedAbsolutePath = $this->resolveDocumentAbsolutePath($record?->signed_file_path);

        $hasDraft = filled($record?->file_path) && $draftAbsolutePath && file_exists($draftAbsolutePath);
        $hasSigned = filled($record?->signed_file_path) && $signedAbsolutePath && file_exists($signedAbsolutePath);
        $timezone = config('app.timezone', 'Asia/Jakarta');

        return [
            'file_path' => $hasDraft ? $record?->file_path : null,
            'signed_file_path' => $hasSigned ? $record?->signed_file_path : null,
            'fasih_screenshot_path' => $sharedFasihScreenshotPath,
            'generated_at' => $hasDraft && $record?->generated_at
                ? $record->generated_at->copy()->timezone($timezone)->format('d M Y H:i')
                : null,
            'signed_uploaded_at' => $hasSigned && $record?->signed_uploaded_at
                ? $record->signed_uploaded_at->copy()->timezone($timezone)->format('d M Y H:i')
                : null,
            'status' => $hasSigned ? 'signed' : ($hasDraft ? 'generated' : 'pending'),
            'can_upload_signed' => $hasDraft,
        ];
    }

    private function adoptPreviewLampiranFiles(Bast $bast): void
    {
        $bast->loadMissing(['bastKegiatan', 'spk']);

        $spk = $bast->spk;

        if (! $spk) {
            return;
        }

        $previewByKey = BastKegiatan::query()
            ->whereNull('bast_id')
            ->where('spk_id', $spk->id)
            ->get()
            ->keyBy(fn (BastKegiatan $row) => $this->makeBastKegiatanKey((int) $row->kegiatan_id, (int) $row->periode_alokasi_id));

        foreach ($bast->bastKegiatan as $bastKegiatan) {
            $record = $previewByKey->get(
                $this->makeBastKegiatanKey((int) $bastKegiatan->kegiatan_id, (int) $bastKegiatan->periode_alokasi_id)
            );

            if (! $record) {
                continue;
            }

            $draftAbsolutePath = $this->resolveDocumentAbsolutePath($record->file_path);
            $signedAbsolutePath = $this->resolveDocumentAbsolutePath($record->signed_file_path);

            $hasDraft = filled($record->file_path) && $draftAbsolutePath && file_exists($draftAbsolutePath);
            $hasSigned = filled($record->signed_file_path) && $signedAbsolutePath && file_exists($signedAbsolutePath);
            $hasScreenshot = $this->supportsLampiranFasihScreenshotColumns() && filled($record->fasih_screenshot_path);

            if (! $hasDraft && ! $hasSigned && ! $hasScreenshot) {
                continue;
            }

            $updates = [];

            if ($hasDraft) {
                $draftFilename = 'LAMPIRAN_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment((string) $bastKegiatan->kode_kegiatan).'.pdf';
                $this->deleteStoredDocument($bastKegiatan->file_path);
                $updates['file_path'] = $this->writePdfToPublicDirectory(
                    $draftFilename,
                    file_get_contents($draftAbsolutePath),
                    'lampiran'
                );
                $updates['generated_at'] = $record->generated_at ?? now();
            }

            if ($hasSigned) {
                $signedFilename = 'LAMPIRAN_SIGNED_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment((string) $bastKegiatan->kode_kegiatan).'.pdf';
                $this->deleteStoredDocument($bastKegiatan->signed_file_path);
                $updates['signed_file_path'] = $this->writePdfToPublicDirectory(
                    $signedFilename,
                    file_get_contents($signedAbsolutePath),
                    'lampiran-signed'
                );
                $updates['signed_uploaded_at'] = $record->signed_uploaded_at ?? now();
            }

            if ($hasScreenshot) {
                $updates['fasih_screenshot_path'] = $record->fasih_screenshot_path;
                $updates['fasih_screenshot_uploaded_at'] = $record->fasih_screenshot_uploaded_at ?? now();
            }

            if (! empty($updates)) {
                $bastKegiatan->update($updates);
            }

            $this->deleteStoredDocument($record->file_path);
            $this->deleteStoredDocument($record->signed_file_path);
            $record->delete();
        }

        $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));
    }

    private function mergePdfFilesToPublic(array $paths, string $filename, string $subdirectory): ?string
    {
        $absolutePaths = collect($paths)
            ->map(fn ($path) => $this->resolveDocumentAbsolutePath($path))
            ->filter(fn (?string $path) => $path && file_exists($path))
            ->values()
            ->all();

        if (count($absolutePaths) !== count($paths)) {
            Log::error('Merge BAST dibatalkan karena file sumber tidak ditemukan.', [
                'requested_paths' => $paths,
                'resolved_count' => count($absolutePaths),
                'requested_count' => count($paths),
                'output_filename' => $filename,
            ]);

            return null;
        }

        $absoluteDirectory = $this->ensureBastExportDirectory($subdirectory);
        $absoluteOutputPath = $absoluteDirectory.DIRECTORY_SEPARATOR.$filename;

        $merged = PdfMergerService::mergePdfFiles(
            $absolutePaths,
            $absoluteOutputPath,
            $filename
        );

        if (! $merged || ! file_exists($absoluteOutputPath)) {
            Log::error('Service PDF gagal membuat file gabungan BAST.', [
                'output_path' => $absoluteOutputPath,
                'source_files' => array_map('basename', $absolutePaths),
                'subdirectory' => $subdirectory,
            ]);

            return null;
        }

        return trim('bast-export/'.trim($subdirectory, '/').'/'.$filename, '/');
    }

    private function countPdfPagesFromString(?string $pdfContent): int
    {
        if (blank($pdfContent)) {
            return 0;
        }

        try {
            $pdf = new Fpdi;
            $reader = StreamReader::createByString($pdfContent);

            return (int) $pdf->setSourceFile($reader);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countPdfPagesFromRelativePath(?string $relativePath): int
    {
        $absolutePath = $this->resolveDocumentAbsolutePath($relativePath);
        if (! $absolutePath || ! file_exists($absolutePath)) {
            return 0;
        }

        try {
            $pdf = new Fpdi;

            return (int) $pdf->setSourceFile($absolutePath);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function resolveLampiranPageNumberOffset(?Bast $bast = null, ?string $mainPdfContent = null): int
    {
        $mainPageCount = $this->countPdfPagesFromString($mainPdfContent);

        if ($mainPageCount <= 0 && $bast) {
            $mainPageCount = $this->countPdfPagesFromRelativePath($bast->file_path);
        }

        return max(1, $mainPageCount);
    }

    private function prepareStoredBastViewData(Bast $bast): array
    {
        $bast->loadMissing([
            'spk.alokasiPetugas.petugas',
            'spk.alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
            'bastPetugas',
            'bastKegiatan',
        ]);

        $spk = $bast->spk;

        if (! $spk || ! $spk->alokasiPetugas?->petugas) {
            abort(404, 'Data SPK untuk BAST tidak ditemukan.');
        }

        $ppk = (object) [
            'nama' => $bast->nama_ppk,
            'nip' => $bast->nip_ppk,
        ];

        $primaryBastPetugas = $bast->bastPetugas->first();
        $isSensusEkonomi = $bast->spk ? $this->isSensusEkonomiSpk($bast->spk) : false;
        $seInput = $primaryBastPetugas ? [
            'muatan_input' => $primaryBastPetugas->muatan_input,
            'muatan_prelist' => $primaryBastPetugas->muatan_prelist,
            'realisasi_unit_sampel' => $primaryBastPetugas->realisasi_unit_sampel,
        ] : null;

        $sensusReference = $isSensusEkonomi
            ? $this->buildSensusReferencePayload(
                $spk,
                (int) $bast->tanggal_bast->month,
                (int) $bast->tanggal_bast->year,
                $primaryBastPetugas,
            )
            : null;

        $viewData = $this->prepareBastDataForExport(
            $spk,
            collect(),
            $bast->nomor_bast,
            $bast->tanggal_bast->format('Y-m-d'),
            $ppk,
            $seInput,
            $isSensusEkonomi
        );

        $viewData['bast']->kegiatan_list = $this->mergeSharedSensusScreenshotIntoKegiatanList(
            $viewData['bast']->kegiatan_list ?? [],
            $sensusReference['fasih_screenshot_path'] ?? null,
        );

        // Propagate bapp_termin_ii_complete into each SE kegiatan payload so lampiran gating works
        if ($isSensusEkonomi && isset($sensusReference['bapp_termin_ii_complete'])) {
            $terminIIComplete = (bool) $sensusReference['bapp_termin_ii_complete'];
            $viewData['bast']->kegiatan_list = collect($viewData['bast']->kegiatan_list ?? [])
                ->map(function (array $item) use ($terminIIComplete): array {
                    $item['bapp_termin_ii_complete'] = $terminIIComplete;

                    return $item;
                })
                ->all();
        }

        return $viewData;
    }

    private function isLampiranGenerationAllowed(array $kegiatanPayload): bool
    {
        $tanggalSelesai = $this->normalizeDateForCompare($kegiatanPayload['tanggal_selesai'] ?? null);

        if (! $tanggalSelesai) {
            return false;
        }

        $isSensusEkonomi = $this->isSensusEkonomiName($kegiatanPayload['nama_kegiatan'] ?? null);

        if (
            $this->shouldUseLampiranFasihScreenshot(
                $kegiatanPayload['nama_kegiatan'] ?? null,
                $kegiatanPayload['peran'] ?? null,
            )
            && blank($kegiatanPayload['fasih_screenshot_path'] ?? null)
        ) {
            return false;
        }

        // For SE kegiatan: BAPP Termin II must be complete before lampiran can be generated
        if ($isSensusEkonomi && array_key_exists('bapp_termin_ii_complete', $kegiatanPayload)) {
            if (! $kegiatanPayload['bapp_termin_ii_complete']) {
                return false;
            }

            // SE lampiran is allowed once BAPP Termin II is complete, regardless of kegiatan end date
            return true;
        }

        return $tanggalSelesai <= now()->format('Y-m-d');
    }

    private function resolveLampiranCumulativeVolume(AlokasiPetugas $alokasi, string $phase): int
    {
        $unitSampelKumulatif = (int) ($alokasi->jumlah_unit_sampel ?? 0);
        if ($unitSampelKumulatif > 0) {
            return $unitSampelKumulatif;
        }

        return $phase === 'listing'
            ? (int) ($alokasi->jumlah_satuan_listing ?? 0)
            : (int) ($alokasi->jumlah_satuan ?? 0);
    }

    private function sortAndNumberKegiatanLampiran(array $kegiatanList): array
    {
        return collect($kegiatanList)
            ->sort(function (array $left, array $right) {
                $leftDate = $this->normalizeDateForCompare($left['tanggal_selesai'] ?? null) ?? '9999-12-31';
                $rightDate = $this->normalizeDateForCompare($right['tanggal_selesai'] ?? null) ?? '9999-12-31';

                if ($leftDate !== $rightDate) {
                    return $leftDate <=> $rightDate;
                }

                $leftCode = mb_strtolower(trim((string) ($left['kode_kegiatan'] ?? '')));
                $rightCode = mb_strtolower(trim((string) ($right['kode_kegiatan'] ?? '')));

                if ($leftCode !== $rightCode) {
                    return $leftCode <=> $rightCode;
                }

                $leftName = mb_strtolower(trim((string) ($left['nama_kegiatan'] ?? '')));
                $rightName = mb_strtolower(trim((string) ($right['nama_kegiatan'] ?? '')));

                return $leftName <=> $rightName;
            })
            ->values()
            ->map(function (array $item, int $index) {
                $item['lampiran_nomor'] = $index + 1;

                return $item;
            })
            ->all();
    }

    private function syncCompiledBastFiles(Bast $bast): void
    {
        $bast->loadMissing(['bastKegiatan', 'periodeAlokasi']);

        $periode = $bast->periodeAlokasi;
        $isLegacyMode = $periode
            && ((int) $periode->tahun < 2026
                || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4));

        if ($isLegacyMode) {
            if (filled($bast->main_signed_file_path)) {
                if ($bast->signed_file_path !== $bast->main_signed_file_path) {
                    $this->deleteStoredDocument($bast->signed_file_path);
                    $bast->forceFill([
                        'signed_file_path' => $bast->main_signed_file_path,
                        'status' => 'diserahkan',
                    ])->save();
                }
            } elseif (filled($bast->signed_file_path)) {
                $this->deleteStoredDocument($bast->signed_file_path);
                $bast->forceFill([
                    'signed_file_path' => null,
                    'status' => 'draft',
                ])->save();
            }

            return;
        }

        if ($bast->bastKegiatan->isEmpty()) {
            return;
        }

        $updates = [];
        $baseFilename = $this->sanitizeDocumentSegment($bast->nomor_bast);

        if ($bast->file_path && $bast->bastKegiatan->every(fn (BastKegiatan $item) => filled($item->file_path))) {
            $compiledPath = $this->mergePdfFilesToPublic(
                array_merge([$bast->file_path], $bast->bastKegiatan->pluck('file_path')->all()),
                'BAST_COMPILED_'.$baseFilename.'.pdf',
                'compiled'
            );

            if ($compiledPath) {
                $updates['compiled_file_path'] = $compiledPath;
            }
        } else {
            $this->deleteStoredDocument($bast->compiled_file_path);
            $updates['compiled_file_path'] = null;
        }

        if ($bast->main_signed_file_path && $bast->bastKegiatan->every(fn (BastKegiatan $item) => filled($item->signed_file_path))) {
            $compiledSignedPath = $this->mergePdfFilesToPublic(
                array_merge([$bast->main_signed_file_path], $bast->bastKegiatan->pluck('signed_file_path')->all()),
                'BAST_SIGNED_'.$baseFilename.'.pdf',
                'compiled-signed'
            );

            if ($compiledSignedPath) {
                $updates['signed_file_path'] = $compiledSignedPath;
                $updates['status'] = 'diserahkan';
            } else {
                $updates['signed_file_path'] = null;
                $updates['status'] = 'draft';
            }
        } else {
            $this->deleteStoredDocument($bast->signed_file_path);
            $updates['signed_file_path'] = null;
            $updates['status'] = 'draft';
        }

        if (! empty($updates)) {
            $bast->forceFill($updates)->save();
        }
    }

    private function syncBastKegiatanFromPayload(Bast $bast, array $kegiatanList): void
    {
        if (empty($kegiatanList)) {
            return;
        }

        $normalizedItems = collect($kegiatanList)
            ->filter(fn (array $item) => filled($item['kegiatan_id'] ?? null) && filled($item['periode_alokasi_id'] ?? null))
            ->unique(fn (array $item) => $this->makeBastKegiatanKey((int) $item['kegiatan_id'], (int) $item['periode_alokasi_id']))
            ->values();

        if ($normalizedItems->isEmpty()) {
            return;
        }

        $existingByKegiatan = $bast->bastKegiatan()
            ->get()
            ->groupBy(fn (BastKegiatan $record) => (int) $record->kegiatan_id);

        $activeKegiatanIds = [];
        $hasAttachmentMutation = false;

        $normalizedItems->each(function (array $item) use ($bast, $existingByKegiatan, &$activeKegiatanIds, &$hasAttachmentMutation) {
            $kegiatanId = (int) $item['kegiatan_id'];
            $periodeAlokasiId = (int) $item['periode_alokasi_id'];
            $activeKegiatanIds[] = $kegiatanId;

            /** @var Collection<int, BastKegiatan> $recordsForKegiatan */
            $recordsForKegiatan = $existingByKegiatan->get($kegiatanId, collect());

            $exactMatch = $recordsForKegiatan->first(fn (BastKegiatan $record) => (int) $record->periode_alokasi_id === $periodeAlokasiId);

            if ($exactMatch) {
                $exactMatch->update([
                    'kode_kegiatan' => (string) ($item['kode_kegiatan'] ?? '-'),
                    'nama_kegiatan' => (string) ($item['nama_kegiatan'] ?? '-'),
                    'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                    'tahun' => $bast->tanggal_bast->year,
                    'jenis_kegiatan' => (string) ($item['jenis_kegiatan'] ?? 'survei'),
                ]);

                return;
            }

            if ($recordsForKegiatan->isNotEmpty()) {
                /** @var BastKegiatan $recordToReuse */
                $recordToReuse = $recordsForKegiatan->sortBy('id')->first();

                $this->deleteStoredDocument($recordToReuse->file_path);
                $this->deleteStoredDocument($recordToReuse->signed_file_path);

                $recordToReuse->update([
                    'periode_alokasi_id' => $periodeAlokasiId,
                    'kode_kegiatan' => (string) ($item['kode_kegiatan'] ?? '-'),
                    'nama_kegiatan' => (string) ($item['nama_kegiatan'] ?? '-'),
                    'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                    'tahun' => $bast->tanggal_bast->year,
                    'jenis_kegiatan' => (string) ($item['jenis_kegiatan'] ?? 'survei'),
                    'file_path' => null,
                    'signed_file_path' => null,
                    'generated_at' => null,
                    'signed_uploaded_at' => null,
                ]);

                $recordsForKegiatan
                    ->filter(fn (BastKegiatan $record) => $record->id !== $recordToReuse->id)
                    ->each(function (BastKegiatan $record): void {
                        $this->deleteStoredDocument($record->file_path);
                        $this->deleteStoredDocument($record->signed_file_path);
                        $record->delete();
                    });

                $hasAttachmentMutation = true;

                return;
            }

            BastKegiatan::create([
                'bast_id' => $bast->id,
                'kegiatan_id' => $kegiatanId,
                'periode_alokasi_id' => $periodeAlokasiId,
                'kode_kegiatan' => (string) ($item['kode_kegiatan'] ?? '-'),
                'nama_kegiatan' => (string) ($item['nama_kegiatan'] ?? '-'),
                'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                'tahun' => $bast->tanggal_bast->year,
                'jenis_kegiatan' => (string) ($item['jenis_kegiatan'] ?? 'survei'),
            ]);

            $hasAttachmentMutation = true;
        });

        $activeKegiatanIds = collect($activeKegiatanIds)->unique()->values()->all();

        $removedRecords = $bast->bastKegiatan()
            ->when(! empty($activeKegiatanIds), function ($query) use ($activeKegiatanIds) {
                $query->whereNotIn('kegiatan_id', $activeKegiatanIds);
            })
            ->get();

        if ($removedRecords->isNotEmpty()) {
            $removedRecords->each(function (BastKegiatan $record): void {
                $this->deleteStoredDocument($record->file_path);
                $this->deleteStoredDocument($record->signed_file_path);
                $record->delete();
            });

            $hasAttachmentMutation = true;
        }

        if ($hasAttachmentMutation) {
            $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));
        }
    }

    private function nextBastNomorForDate(Carbon $targetDate, bool $isSensusEkonomi = false): string
    {
        $allBast = Bast::whereYear('tanggal_bast', $targetDate->year)
            ->pluck('nomor_bast');

        $maxUrut = 0;
        foreach ($allBast as $existingNomor) {
            $urut = $this->extractBastSequenceForScheme($existingNomor, $isSensusEkonomi);
            if ($urut > $maxUrut) {
                $maxUrut = $urut;
            }
        }

        return $this->formatBastNomor($maxUrut + 1, $targetDate->year, $isSensusEkonomi);
    }

    private function buildOrGetBastForPetugasPeriod(Request $request, int $petugasId, string $bulanFormatted, int $tahun): ?Bast
    {
        $existingBast = Bast::whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
            $q->where('bulan', $bulanFormatted)->where('tahun', $tahun);
        })->whereHas('bastPetugas', function ($q) use ($petugasId) {
            $q->where('petugas_id', $petugasId);
        })->latest('created_at')->first();

        if ($existingBast) {
            return $existingBast;
        }

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)->where('tahun', $tahun);
            })
            ->orderByDesc('addendum_number')
            ->orderByDesc('created_at')
            ->first();

        if (! $spk || ! $spk->alokasiPetugas?->petugas) {
            return null;
        }

        $user = $this->getRequestUser($request);
        if ($user?->active_role === 'ketua_tim') {
            $hasManagedKegiatan = AlokasiPetugas::where('petugas_id', $petugasId)
                ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->whereHas('periodeAlokasi.kegiatan', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user->id)
                            ->orWhere('pj_lainnya_id', $user->id);
                    });
                })
                ->exists();

            abort_unless($hasManagedKegiatan, 403);
        }

        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->with([
                'periodeAlokasi.kegiatan.rateHonors.satuan',
                'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                'periodeAlokasi.kegiatan.ketuaTim',
                'frameSampelAllocations.kegiatanFrameSampel',
                'spk',
            ])
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
            return $this->getAlokasiLatestTanggalSelesai($alokasi);
        })->filter()->max();

        if (! $tanggalBerakhirPalingAkhir) {
            $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja ?? $spk->tanggal_mulai_kerja;
        }

        $targetDate = Carbon::parse($tanggalBerakhirPalingAkhir ?: now()->format('Y-m-d'));
        while (in_array($targetDate->dayOfWeekIso, [6, 7])) {
            $targetDate->subDay();
        }

        $nomorBast = $this->allocateNomorBastForSpk($spk, $targetDate);
        $ppk = Penandatangan::where('jenis_penandatangan', 'ppk')
            ->where('is_active', true)
            ->first();
        $ppkObject = $ppk ? (object) ['nama' => $ppk->nama, 'nip' => $ppk->nip] : (object) ['nama' => ($spk->nama_ppk ?? '-'), 'nip' => ($spk->nip_ppk ?? '-')];

        $viewData = $this->prepareBastDataForExport(
            $spk,
            $allAlokasi,
            $nomorBast,
            $targetDate->format('Y-m-d'),
            $ppkObject
        );
        $viewData = $this->prepareBastDataForExport(
            $spk,
            collect([$spk]),
            $nomorBast,
            $targetDate->format('Y-m-d'),
            $ppkObject
        );

        $kegiatanPertama = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;
        $ketuaTim = $kegiatanPertama?->ketuaTim;

        $bast = Bast::create([
            'spk_id' => $spk->id,
            'kegiatan_id' => $kegiatanPertama?->id,
            'periode_alokasi_id' => $spk->alokasiPetugas?->periodeAlokasi?->id,
            'nomor_bast' => $nomorBast,
            'tanggal_bast' => $targetDate->format('Y-m-d'),
            'tanggal_serah_terima' => $targetDate->format('Y-m-d'),
            'uraian_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
            'nama_ketua_tim' => $ketuaTim?->name ?? '-',
            'nip_ketua_tim' => $ketuaTim?->nip ?? '-',
            'nama_ppk' => $ppkObject->nama,
            'nip_ppk' => $ppkObject->nip,
            'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
            'hasil_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
            'file_path' => null,
            'compiled_file_path' => null,
            'main_signed_file_path' => null,
            'signed_file_path' => null,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        BastPetugas::updateOrCreate(
            [
                'bast_id' => $bast->id,
                'petugas_id' => $spk->alokasiPetugas->petugas->id,
            ],
            [
                'spk_id' => $spk->id,
                'nomor_spk' => $spk->nomor_spk,
                'nama_petugas' => $spk->alokasiPetugas->petugas->nama,
                'hasil_listing' => null,
                'hasil_pendataan_lapangan' => null,
                'hasil_pengolahan' => null,
                'hasil_pengolahan_listing' => null,
                'catatan' => $spk->alokasiPetugas?->catatan,
            ]
        );

        $this->syncBastKegiatanFromPayload($bast, $viewData['bast']->kegiatan_list ?? []);

        return $bast;
    }
}
