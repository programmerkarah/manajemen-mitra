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

trait SpkDownloadSupport
{
    public function downloadAll(Request $request)
    {
        $this->mergeEncryptedDownloadState($request);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index')->with('error', 'Bulan dan tahun harus diisi');
        }

        $downloadScope = $this->resolveDownloadScopeContext($request);

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        $scopeQuery = PeriodeAlokasi::query()
            ->where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan', 'draft', 'diajukan', 'selesai']);

        if ($downloadScope === 'sensus') {
            $scopeQuery->whereHas('kegiatan', function ($query) {
                $query->where('jenis_kegiatan', 'sensus');
            });
        } else {
            $scopeQuery->whereHas('kegiatan', function ($query) {
                $query->where('jenis_kegiatan', 'survei');
            });
        }

        $selectedPeriode = null;
        $periodeHashedId = $request->input('periode_hashed_id');
        if (filled($periodeHashedId)) {
            $periodeId = Hashids::decode((string) $periodeHashedId)[0] ?? null;
            if ($periodeId) {
                $selectedPeriode = PeriodeAlokasi::with('kegiatan')->find($periodeId);
                if ($selectedPeriode && $this->usesPeriodBasedSpkFlow($selectedPeriode)) {
                    $scopeQuery->whereKey($selectedPeriode->id);
                }
            }
        }

        // Get all periodes in this month
        $allPeriodeInMonth = $scopeQuery->pluck('id');

        $matchingAlokasiIds = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $downloadableFileScope = function ($query): void {
            $query->whereNotNull('file_path')
                ->orWhereNotNull('signed_file_path')
                ->orWhereNotNull('previous_file_path');
        };

        if ($downloadScope === 'sensus' && $selectedPeriode) {
            // Sensus Ekonomi uses a period-based PK flow. Scope the main PKs
            // through the selected period exactly as generation/regeneration does.
            $mainSpks = $this->baseSpkScopeQuery($selectedPeriode)
                ->with(['petugas', 'alokasiPetugas.petugas'])
                ->where($downloadableFileScope)
                ->orderBy('nomor_spk')
                ->get();

            $mainSpkIds = $mainSpks->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            // Include every addendum belonging to those main PKs. The allocation
            // fallback keeps historical addenda without parent_spk_id downloadable.
            $addendumSpks = Spk::with(['petugas', 'alokasiPetugas.petugas'])
                ->where('addendum_number', '>', 0)
                ->where($downloadableFileScope)
                ->where(function ($query) use ($mainSpkIds, $matchingAlokasiIds): void {
                    if ($mainSpkIds->isNotEmpty()) {
                        $query->whereIn('parent_spk_id', $mainSpkIds->all());
                    }

                    if ($matchingAlokasiIds->isNotEmpty()) {
                        $method = $mainSpkIds->isNotEmpty() ? 'orWhere' : 'where';
                        $query->{$method}(function ($scopeQuery) use ($matchingAlokasiIds): void {
                            $this->applyAlokasiScopeToSpkQuery($scopeQuery, $matchingAlokasiIds);
                        });
                    }

                    if ($mainSpkIds->isEmpty() && $matchingAlokasiIds->isEmpty()) {
                        $query->whereRaw('0 = 1');
                    }
                })
                ->orderBy('nomor_spk')
                ->orderBy('addendum_number')
                ->get();
        } else {
            $mainSpks = Spk::with(['petugas', 'alokasiPetugas.petugas'])
                ->where(function ($query): void {
                    $query->where('addendum_number', 0)
                        ->orWhereNull('addendum_number');
                })
                ->where($downloadableFileScope)
                ->where(function ($query) use ($matchingAlokasiIds): void {
                    $this->applyAlokasiScopeToSpkQuery($query, $matchingAlokasiIds);
                })
                ->orderBy('nomor_spk')
                ->get();

            $addendumSpks = Spk::with(['petugas', 'alokasiPetugas.petugas'])
                ->where('addendum_number', '>', 0)
                ->where($downloadableFileScope)
                ->where(function ($query) use ($matchingAlokasiIds): void {
                    $this->applyAlokasiScopeToSpkQuery($query, $matchingAlokasiIds);
                })
                ->orderBy('nomor_spk')
                ->orderBy('addendum_number')
                ->get();
        }

        if ($mainSpks->isEmpty() && $addendumSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada file PK utama maupun addendum untuk diunduh');
        }

        // Create ZIP file with deterministic name (no timestamp)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $zipScope = $downloadScope === 'sensus' && $selectedPeriode
            ? 'Sensus_Ekonomi_'.$selectedPeriode->id
            : 'Reguler';
        $zipFileName = "SPK_{$zipScope}_{$bulanLabel}_{$tahun}.zip";

        // Ensure downloads directory exists
        // ZIP archives are generated by PHP-FPM, so keep them in Laravel's
        // writable storage tree instead of the deployment-owned public directory.
        $downloadsDir = storage_path('app/tmp-downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir.'/'.$zipFileName;

        // Check if ZIP exists and validate cache
        $shouldRegenerate = true;
        if (file_exists($zipPath)) {
            $zipModTime = filemtime($zipPath);

            // Check if any SPK was updated after ZIP creation
            $latestSpkUpdate = max(
                $mainSpks->max('updated_at')?->timestamp ?? 0,
                $addendumSpks->max('updated_at')?->timestamp ?? 0
            );

            // Reuse if ZIP is newer than latest SPK update
            if ($zipModTime > $latestSpkUpdate) {
                $shouldRegenerate = false;
            }
        }

        if (! $shouldRegenerate) {
            // Reuse existing ZIP - serve directly
            clearstatcache(true, $zipPath);

            return response()->download(
                $zipPath,
                $zipFileName,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Length' => filesize($zipPath),
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=604800',
                ]
            );
        }

        // Generate new ZIP
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        $filesAdded = 0;
        $usedZipEntryNames = [];
        // Masukkan SPK utama
        foreach ($mainSpks as $spk) {
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);
            if (! $fileToUse) {
                continue;
            }

            $filePath = public_path($fileToUse);
            if (file_exists($filePath)) {
                $zipFileNameInArchive = $this->buildZipFilenameForSpk($spk, $fileToUse);
                $zipFileNameInArchive = $this->makeUniqueZipEntryName($zipFileNameInArchive, $usedZipEntryNames);
                $zip->addFile($filePath, $zipFileNameInArchive);
                $filesAdded++;
            }
        }

        // Masukkan addendum yang valid
        foreach ($addendumSpks as $spk) {
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);
            if (! $fileToUse) {
                continue;
            }

            $filePath = public_path($fileToUse);
            if (file_exists($filePath)) {
                $zipFileNameInArchive = $this->buildZipFilenameForSpk($spk, $fileToUse);
                $zipFileNameInArchive = $this->makeUniqueZipEntryName($zipFileNameInArchive, $usedZipEntryNames);
                $zip->addFile($filePath, $zipFileNameInArchive);
                $filesAdded++;
            }
        }

        // Check if any files were actually added
        if ($filesAdded === 0) {
            $zip->close();
            @unlink($zipPath);

            return redirect()->back()->with('error', 'Tidak ada file SPK yang valid untuk diunduh. File mungkin sudah dihapus atau dipindahkan.');
        }

        $zip->close();

        // Verify ZIP file was created successfully
        if (! file_exists($zipPath)) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP. Silakan coba lagi.');
        }

        // Serve file directly with proper headers
        clearstatcache(true, $zipPath);

        return response()->download(
            $zipPath,
            $zipFileName,
            [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipPath),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=604800',
            ]
        );
    }

    public function downloadAllByKegiatan(Request $request, string $periodeHashedId, string $kegiatanHashedId)
    {
        // Legacy URL compatibility. Move the resource identifier/action into
        // session context and canonicalize the browser URL to /spk/generate.
        if ($request->routeIs('spk.create')) {
            $requestedAction = (string) $request->query('action', '');
            $request->session()->put('spk.generate.context', [
                'periode' => $periodeHashedId,
                'action' => in_array($requestedAction, ['generate_pk', 'regenerate_pk'], true)
                    ? $requestedAction
                    : null,
            ]);

            return redirect()->route('spk.generate-page');
        }

        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

        if (! $periodeId || ! $kegiatanId) {
            abort(404);
        }

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);
        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Get all petugas who are allocated to this kegiatan in this month/year
        $petugasIdsInKegiatan = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->where('periode_alokasi.kegiatan_id', $kegiatanId)
            ->where('periode_alokasi.bulan', $periode->bulan)
            ->where('periode_alokasi.tahun', $periode->tahun)
            ->whereIn('periode_alokasi.status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->distinct()
            ->pluck('alokasi_petugas.petugas_id');

        // Get all periodes in the same month and year (for any kegiatan)
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->pluck('id');

        $matchingAlokasiIds = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        // Ambil semua SPK utama (addendum_number = 0)
        $mainSpks = Spk::with(['petugas', 'alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where('addendum_number', 0)
            ->where(function ($query) {
                $query->whereNotNull('file_path')
                    ->orWhereNotNull('signed_file_path')
                    ->orWhereNotNull('previous_file_path');
            })
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->where(function ($query) use ($matchingAlokasiIds) {
                $this->applyAlokasiScopeToSpkQuery($query, $matchingAlokasiIds);
            })
            ->orderBy('nomor_spk')
            ->get();

        // Ambil semua addendum (addendum_number > 0) yang memiliki file
        $addendumSpks = Spk::with(['petugas', 'alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where('addendum_number', '>', 0)
            ->where(function ($query) {
                $query->whereNotNull('signed_file_path')
                    ->orWhereNotNull('file_path')
                    ->orWhereNotNull('previous_file_path');
            })
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->where(function ($query) use ($matchingAlokasiIds) {
                $this->applyAlokasiScopeToSpkQuery($query, $matchingAlokasiIds);
            })
            ->orderBy('nomor_spk')
            ->orderBy('addendum_number')
            ->get();

        if ($mainSpks->isEmpty() && $addendumSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada SPK/addendum yang sudah ditandatangani untuk diunduh pada kegiatan ini');
        }

        // Combine all SPKs
        $allSpks = $mainSpks->merge($addendumSpks);

        // Check if all petugas have files that exist physically.
        // Resolve file by addendum number first to avoid mismatched signed file paths.
        $missingSignedFiles = $allSpks->filter(function ($spk) {
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);

            return empty($fileToUse) || ! file_exists(public_path($fileToUse));
        });

        if ($missingSignedFiles->isNotEmpty()) {
            return redirect()->back()->with('error', 'Tidak dapat mengunduh. Semua petugas pada kegiatan ini harus memiliki file Perjanjian Kerja yang sudah ditandatangani dan tersimpan.');
        }

        // Create ZIP file with deterministic name (no timestamp)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $periode->bulan);
        $kegiatanName = preg_replace('/[\/\\:*?"<>|]/', '_', $kegiatan->nama_kegiatan);
        $zipFileName = "SPK_{$kegiatanName}_{$bulanLabel}_{$periode->tahun}.zip";

        // Ensure downloads directory exists
        // ZIP archives are generated by PHP-FPM, so keep them in Laravel's
        // writable storage tree instead of the deployment-owned public directory.
        $downloadsDir = storage_path('app/tmp-downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir.'/'.$zipFileName;

        // Check if ZIP exists and validate cache
        $shouldRegenerate = true;
        if (file_exists($zipPath)) {
            $zipModTime = filemtime($zipPath);

            // Get latest SPK update timestamp from this kegiatan
            $latestSpkUpdate = $allSpks->max('updated_at')?->timestamp ?? 0;

            // Reuse if ZIP is newer than latest SPK update
            if ($zipModTime > $latestSpkUpdate) {
                $shouldRegenerate = false;
            }
        }

        if (! $shouldRegenerate) {
            // Reuse existing ZIP - serve directly
            clearstatcache(true, $zipPath);

            return response()->download(
                $zipPath,
                $zipFileName,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Length' => filesize($zipPath),
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=604800',
                ]
            );
        }

        // Generate new ZIP
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        $filesAdded = 0;
        $usedZipEntryNames = [];
        foreach ($allSpks as $spk) {
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);
            if (! $fileToUse) {
                continue;
            }

            $filePath = public_path($fileToUse);
            if (! file_exists($filePath)) {
                continue;
            }

            $zipFileNameInArchive = $this->buildZipFilenameForSpk($spk, $fileToUse);
            $zipFileNameInArchive = $this->makeUniqueZipEntryName($zipFileNameInArchive, $usedZipEntryNames);
            $zip->addFile($filePath, $zipFileNameInArchive);
            $filesAdded++;
        }

        $zip->close();

        // Verify ZIP was created
        if ($filesAdded === 0 || ! file_exists($zipPath)) {
            @unlink($zipPath);

            return redirect()->back()->with('error', 'Gagal membuat file ZIP. Tidak ada file yang valid.');
        }

        // Serve file directly with proper headers
        clearstatcache(true, $zipPath);

        return response()->download(
            $zipPath,
            $zipFileName,
            [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipPath),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=604800',
            ]
        );
    }

    public function downloadByKegiatanMonth(Request $request)
    {
        $this->mergeEncryptedDownloadState($request);

        $kegiatanHashedId = (string) $request->input('kegiatan_hashed_id');
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;
        if (! $kegiatanId) {
            abort(422, 'State download kegiatan tidak valid.');
        }

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all petugas who are allocated to this kegiatan in this month/year
        $petugasIdsInKegiatan = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->where('periode_alokasi.kegiatan_id', $kegiatanId)
            ->where('periode_alokasi.bulan', $bulanFormatted)
            ->where('periode_alokasi.tahun', $tahun)
            ->whereIn('periode_alokasi.status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->distinct()
            ->pluck('alokasi_petugas.petugas_id');

        // Get all periodes in the same month and year (for any kegiatan)
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->pluck('id');

        $matchingAlokasiIds = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        // Get ALL SPKs for these petugas in this month/year, regardless of which kegiatan the SPK was created for
        $allSpks = Spk::with(['petugas', 'alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where(function ($q) {
                $q->whereNotNull('file_path')
                    ->orWhereNotNull('signed_file_path');
            })
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->where(function ($query) use ($matchingAlokasiIds) {
                $this->applyAlokasiScopeToSpkQuery($query, $matchingAlokasiIds);
            })
            ->orderBy('nomor_spk')
            ->orderBy('addendum_number')
            ->get();

        if ($allSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada SPK untuk kegiatan ini di periode tersebut.');
        }

        // Check if SPKs have at least one valid physical file available to download.
        $missingFiles = $allSpks->filter(function ($spk) {
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);

            if (empty($fileToUse)) {
                return true;
            }

            return ! file_exists(public_path($fileToUse));
        });

        if ($missingFiles->isNotEmpty()) {
            return redirect()->back()->with('error', 'Tidak dapat mengunduh. Semua SPK pada kegiatan ini harus memiliki file Perjanjian Kerja yang tersimpan.');
        }

        // Create ZIP file with deterministic name (no timestamp)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $kegiatanName = preg_replace('/[\/\\\:*?"<>|]/', '_', $kegiatan->nama_kegiatan);
        $zipFileName = "SPK_{$kegiatanName}_{$bulanLabel}_{$tahun}.zip";

        // Ensure downloads directory exists
        // ZIP archives are generated by PHP-FPM, so keep them in Laravel's
        // writable storage tree instead of the deployment-owned public directory.
        $downloadsDir = storage_path('app/tmp-downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir.'/'.$zipFileName;

        // Check if ZIP exists and validate cache
        $shouldRegenerate = true;
        if (file_exists($zipPath)) {
            $zipModTime = filemtime($zipPath);

            // Get latest SPK update timestamp
            $latestSpkUpdate = $allSpks->max('updated_at')?->timestamp ?? 0;

            // Reuse if ZIP is newer than latest SPK update
            if ($zipModTime > $latestSpkUpdate) {
                $shouldRegenerate = false;
            }
        }

        if (! $shouldRegenerate) {
            // Reuse existing ZIP - serve directly
            clearstatcache(true, $zipPath);

            return response()->download(
                $zipPath,
                $zipFileName,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Length' => filesize($zipPath),
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=604800',
                ]
            );
        }

        // Generate new ZIP
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP');
        }

        $filesAdded = 0;
        $usedZipEntryNames = [];
        // Add each SPK file to ZIP with organized folder structure
        foreach ($allSpks as $spk) {
            // Resolve file path by addendum number first to avoid cross-document mismatches.
            $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);
            if (! $fileToUse) {
                continue;
            }

            $filePath = public_path($fileToUse);

            if (file_exists($filePath)) {
                $zipFileNameInArchive = $this->buildZipFilenameForSpk($spk, $fileToUse);
                $zipFileNameInArchive = $this->makeUniqueZipEntryName($zipFileNameInArchive, $usedZipEntryNames);

                $zip->addFile($filePath, $zipFileNameInArchive);
                $filesAdded++;
            }
        }

        $zip->close();

        // Verify ZIP was created
        if ($filesAdded === 0 || ! file_exists($zipPath)) {
            @unlink($zipPath);

            return redirect()->back()->with('error', 'Gagal membuat file ZIP. Tidak ada file yang valid.');
        }

        // Serve file directly with proper headers
        clearstatcache(true, $zipPath);

        return response()->download(
            $zipPath,
            $zipFileName,
            [
                'Content-Type' => 'application/zip',
                'Content-Length' => filesize($zipPath),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=604800',
            ]
        );
    }

    public function uploadSigned(Request $request, string $spkHashedId)
    {
        $spkId = Hashids::decode($spkHashedId)[0] ?? null;

        if (! $spkId) {
            abort(404);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $spk = Spk::findOrFail($spkId);

        // Store new signed file
        $file = $request->file('file');
        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;

        // Extract nomor urut
        $nomorUrut = (string) $this->extractNomorUrut((string) $spk->nomor_spk);

        $namaPetugas = preg_replace('/[\/\\\:*?"<>|]/', '', $petugas->nama);
        $namaPetugas = preg_replace('/\s+/', '_', $namaPetugas); // Replace spaces with underscore
        $bulanLabel = $this->getBulanLabel($periode->bulan);
        $bulanFormatted = str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT);
        $tahun = $periode->tahun;

        // Build filename - different format for addendum
        $addendumNumber = (int) ($spk->addendum_number ?? 0);
        if ($addendumNumber > 0) {
            $fileName = "SPK-ADDENDUM-{$addendumNumber}-{$namaPetugas}-{$bulanFormatted}-{$tahun}.pdf";
        } else {
            $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}_signed.pdf";
        }

        $filePath = "spk-export/{$tahun}/{$bulanFormatted}/{$fileName}";

        // Create directory if not exists
        $publicPath = public_path("spk-export/{$tahun}/{$bulanFormatted}");
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Delete old signed file if exists
        if ($spk->signed_file_path && file_exists(public_path($spk->signed_file_path))) {
            @unlink(public_path($spk->signed_file_path));
        }

        $file->move($publicPath, $fileName);

        // Update SPK - save to signed_file_path, keep file_path as generated SPK
        $spk->update([
            'signed_file_path' => $filePath,
            'status' => 'diterbitkan',
        ]);

        // Redirect back to ShowByMonth with proper payload
        return redirect()->route('spk.show-by-month-get', [
            'bulan' => $periode->bulan,
            'tahun' => $periode->tahun,
            'spk' => $spk->hashed_id,
        ])->with('success', 'Dokumen SPK berhasil diunggah');
    }

    public function regenerateDocument(Request $request, string $spkHashedId): RedirectResponse
    {
        $spkId = Hashids::decode($spkHashedId)[0] ?? null;

        if (! $spkId) {
            abort(404);
        }

        $request->validate([
            'mode' => ['nullable', 'string', 'in:full,main,addendum,lampiran'],
        ]);

        $spk = Spk::with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->findOrFail($spkId);

        $requestedMode = $request->input('mode', $spk->addendum_number > 0 ? 'addendum' : 'main');

        if ($requestedMode === 'addendum' && $spk->addendum_number === 0) {
            return redirect()->back()->with('error', 'Mode addendum hanya dapat dipakai untuk dokumen addendum.');
        }

        if (! $spk->alokasiPetugas || ! $spk->alokasiPetugas->periodeAlokasi) {
            return redirect()->back()->with('error', 'Data alokasi periode untuk dokumen ini tidak ditemukan.');
        }

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->petugas;

        if (! $petugas) {
            return redirect()->back()->with('error', 'Data petugas untuk dokumen ini tidak ditemukan.');
        }

        $regenerateStatuses = $requestedMode === 'addendum'
            ? ['dikirim', 'disetujui', 'perubahan']
            : ['dikirim', 'disetujui', 'direvisi'];

        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, $regenerateStatuses);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugas->id)
            ->get();

        $allAlokasi = $this->spkActionDecisionService
            ->getEffectiveAlokasiByKegiatan(
                $allAlokasi,
                $requestedMode === 'addendum'
                    ? ['perubahan', 'disetujui', 'dikirim']
                    : ['perubahan', 'direvisi', 'disetujui', 'dikirim'],
            )
            ->values();

        if ($allAlokasi->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada alokasi aktif untuk petugas ini.');
        }

        $penandatangan = Penandatangan::active()->ppk()->first();
        if (! $penandatangan) {
            return redirect()->back()->with('error', 'Penandatangan PPK tidak ditemukan.');
        }

        $totalHonor = 0;
        $uraianTugas = [];
        $bebanAnggaran = '';
        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $latestEndDate = $spk->tanggal_selesai_kerja ?: Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'nomorSpk' => $spk->nomor_spk,
            'tanggalSpk' => $spk->tanggal_spk,
            'sampaiTanggal' => Carbon::parse($latestEndDate),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'workType' => $this->detectWorkType($allAlokasi),
        ];
        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_main_regen_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_lampiran_regen_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_merged_regen_'.$timestamp.'.pdf';

        $pdfMain = Pdf::loadView('spk-main', $data)->setPaper('a4', 'portrait');
        $mainOutput = $pdfMain->output();
        $mainPageCount = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());
        $data['pageNumberOffset'] = $mainPageCount;

        $pdfLampiran = Pdf::loadView($lampiranView, $data)->setPaper('a4', $lampiranPaper);
        file_put_contents($mainPath, $mainOutput);
        file_put_contents($lampiranPath, $pdfLampiran->output());

        $merged = PdfMergerService::mergePdfFiles([$mainPath, $lampiranPath], $mergedPath);
        $pdfOutput = null;
        if ($merged && file_exists($mergedPath)) {
            $pdfOutput = file_get_contents($mergedPath);
        } else {
            $pdf = Pdf::loadView('spk-petugas', $data)->setPaper('a4', 'portrait');
            $pdfOutput = $pdf->output();
        }

        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($mergedPath);

        $nomorUrut = $this->resolveDisplayNomorUrutSegment((string) $spk->nomor_spk, (int) $this->extractNomorUrut((string) $spk->nomor_spk));
        $namaPetugas = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim((string) $petugas->nama)) ?: 'Petugas';
        $bulanLabel = $this->getBulanLabel($periode->bulan);
        $generatedFileName = 'SPK_'.$nomorUrut.'_'.$namaPetugas.'_'.$bulanLabel.'.pdf';
        $filePath = 'spk-export/'.$periode->tahun.'/'.str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT).'/'.$generatedFileName;

        $publicDir = public_path(dirname($filePath));
        if (! file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $previousSignedPath = $spk->signed_file_path;
        $previousGeneratedPath = $spk->file_path;
        $previousDocumentPath = $previousSignedPath ?: $previousGeneratedPath;

        if ($previousSignedPath && file_exists(public_path($previousSignedPath))) {
            @unlink(public_path($previousSignedPath));
        }

        file_put_contents(public_path($filePath), $pdfOutput);

        $updates = [
            'file_path' => $filePath,
            'nilai_kontrak' => $totalHonor,
            'tanggal_selesai_kerja' => $latestEndDate,
            'lampiran_template' => $data['lampiranTemplate'] ?? null,
            'lampiran_payload' => $data['lampiranPayload'] ?? null,
            'status' => 'draft',
        ];

        if ($previousDocumentPath) {
            $updates['previous_file_path'] = $previousDocumentPath;
        }

        if ($previousSignedPath) {
            $updates['signed_file_path'] = null;
        }

        $spk->update($updates);

        if ($previousSignedPath) {
            return redirect()->back()->with('success', 'Dokumen SPK berhasil dibuat kembali. Silahkan cetak ulang dokumen dan unggah versi bertanda tangan yang baru.');
        }

        return redirect()->back()->with('success', 'Dokumen SPK berhasil diregenerasi tanpa membuat record baru.');
    }

    public function cancelByPeriodeAndPetugas(Request $request, string $periodeHashedId, string $petugasHashedId): RedirectResponse
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            return redirect()->route('spk.index')->with('error', 'Data periode atau petugas tidak valid.');
        }

        $periode = PeriodeAlokasi::with('kegiatan:id,jenis_kegiatan,nama_kegiatan')->find($periodeId);

        if (! $periode) {
            return redirect()->route('spk.index')->with('error', 'Periode tidak ditemukan.');
        }

        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds(
            $periode,
            ['dikirim', 'disetujui', 'direvisi', 'perubahan'],
        );

        $alokasiIds = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($alokasiIds->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada alokasi petugas pada periode ini.');
        }

        $spks = $this->resolveSpksForCancellation($alokasiIds, $petugasId);

        if ($spks->isEmpty()) {
            $documentLabel = $this->usesPeriodBasedSpkFlow($periode) ? 'PK Sensus Ekonomi' : 'Perjanjian Kerja';

            return redirect()->route('spk.show-by-month-get', [
                'bulan' => $periode->bulan,
                'tahun' => $periode->tahun,
                'periode_hashed_id' => $periode->hashed_id,
            ])->with('error', "Tidak ada {$documentLabel} yang dapat dibatalkan untuk petugas ini.");
        }

        $this->purgeSpkDataAndFiles($spks);

        $documentLabel = $this->usesPeriodBasedSpkFlow($periode) ? 'PK Sensus Ekonomi' : 'Perjanjian Kerja Reguler';

        return redirect()->route('spk.index', [
            'mode' => $this->usesPeriodBasedSpkFlow($periode) ? 'sensus-ekonomi' : 'regular',
        ])->with('success', "{$documentLabel} berhasil dibatalkan. Seluruh data dan file terkait telah dihapus.");
    }

    public function cancelAllByPeriode(Request $request, string $periodeHashedId): RedirectResponse
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            return redirect()->route('spk.index')->with('error', 'Data periode tidak valid.');
        }

        $periode = PeriodeAlokasi::with('kegiatan:id,jenis_kegiatan,nama_kegiatan')->find($periodeId);

        if (! $periode) {
            return redirect()->route('spk.index')->with('error', 'Periode tidak ditemukan.');
        }

        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds(
            $periode,
            ['dikirim', 'disetujui', 'direvisi', 'perubahan'],
        );

        $alokasiIds = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($alokasiIds->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada alokasi petugas pada periode ini.');
        }

        $spks = $this->resolveSpksForCancellation($alokasiIds);

        if ($spks->isEmpty()) {
            $documentLabel = $this->usesPeriodBasedSpkFlow($periode) ? 'PK Sensus Ekonomi' : 'Perjanjian Kerja Reguler';

            return redirect()->route('spk.index', [
                'mode' => $this->usesPeriodBasedSpkFlow($periode) ? 'sensus-ekonomi' : 'regular',
            ])->with('error', "Tidak ada {$documentLabel} yang dapat dibatalkan pada periode ini.");
        }

        $this->purgeSpkDataAndFiles($spks);

        $documentLabel = $this->usesPeriodBasedSpkFlow($periode) ? 'PK Sensus Ekonomi' : 'Perjanjian Kerja Reguler';

        return redirect()->route('spk.index', [
            'mode' => $this->usesPeriodBasedSpkFlow($periode) ? 'sensus-ekonomi' : 'regular',
        ])->with('success', "Seluruh {$documentLabel} periode {$this->resolveSpkIndexDisplayLabel($periode)} berhasil dibatalkan.");
    }

    private function purgeSpkDataAndFiles(Collection $spks): void
    {
        if ($spks->isEmpty()) {
            return;
        }

        $spkIds = $spks->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $bastIds = DB::table('bast')
            ->whereIn('spk_id', $spkIds->all())
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $spkFiles = $spks->flatMap(function (Spk $spk): array {
            return [
                $spk->file_path,
                $spk->signed_file_path,
                $spk->previous_file_path,
            ];
        })->filter()->unique()->values();

        $bappFiles = DB::table('bapp_se_termin')
            ->whereIn('spk_id', $spkIds->all())
            ->get(['file_path', 'signed_file_path', 'fasih_screenshot_path'])
            ->flatMap(function ($row): array {
                return [$row->file_path, $row->signed_file_path, $row->fasih_screenshot_path];
            })
            ->filter()
            ->unique()
            ->values();

        $bastFiles = DB::table('bast')
            ->whereIn('spk_id', $spkIds->all())
            ->get(['file_path', 'compiled_file_path', 'main_signed_file_path', 'signed_file_path'])
            ->flatMap(function ($row): array {
                return [$row->file_path, $row->compiled_file_path, $row->main_signed_file_path, $row->signed_file_path];
            })
            ->filter()
            ->unique()
            ->values();

        $bastKegiatanFiles = DB::table('bast_kegiatan')
            ->whereIn('spk_id', $spkIds->all())
            ->get(['file_path', 'signed_file_path', 'fasih_screenshot_path'])
            ->flatMap(function ($row): array {
                return [$row->file_path, $row->signed_file_path, $row->fasih_screenshot_path];
            })
            ->filter()
            ->unique()
            ->values();

        $bastPetugasFiles = DB::table('bast_petugas')
            ->whereIn('spk_id', $spkIds->all())
            ->pluck('fasih_screenshot_path')
            ->filter()
            ->unique()
            ->values();

        $allFiles = $spkFiles
            ->merge($bappFiles)
            ->merge($bastFiles)
            ->merge($bastKegiatanFiles)
            ->merge($bastPetugasFiles)
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($spkIds, $bastIds, $spks): void {
            DB::table('bapp_se_termin')->whereIn('spk_id', $spkIds->all())->delete();
            DB::table('bast_number_allocations')->whereIn('spk_id', $spkIds->all())->delete();
            DB::table('bast_kegiatan')->whereIn('spk_id', $spkIds->all())->delete();
            DB::table('bast_petugas')->whereIn('spk_id', $spkIds->all())->delete();

            if ($bastIds->isNotEmpty()) {
                DB::table('bast_kegiatan')->whereIn('bast_id', $bastIds->all())->delete();
                DB::table('bast_petugas')->whereIn('bast_id', $bastIds->all())->delete();
            }

            DB::table('bast')->whereIn('spk_id', $spkIds->all())->delete();

            foreach ($spks as $spk) {
                $spk->forceDelete();
            }
        });

        foreach ($allFiles as $relativePath) {
            $this->deleteGeneratedPublicFile((string) $relativePath);
        }
    }

    private function resolveSpksForCancellation(Collection $alokasiIds, ?int $petugasId = null): Collection
    {
        if ($alokasiIds->isEmpty()) {
            return collect();
        }

        $matchingSpkQuery = Spk::query()
            ->when($petugasId, function ($query, $resolvedPetugasId) {
                $query->where('petugas_id', $resolvedPetugasId);
            })
            ->where(function ($query) use ($alokasiIds): void {
                $query->whereIn('alokasi_petugas_id', $alokasiIds->all());

                foreach ($alokasiIds as $alokasiId) {
                    $query->orWhereJsonContains('alokasi_petugas_ids', $alokasiId)
                        ->orWhereJsonContains('alokasi_petugas_ids', (string) $alokasiId);
                }
            });

        $matchingSpks = $matchingSpkQuery->get();

        if ($matchingSpks->isEmpty()) {
            return collect();
        }

        $rootCandidates = $matchingSpks
            ->map(fn (Spk $spk): ?int => $spk->parent_spk_id ? null : $spk->id)
            ->filter()
            ->unique()
            ->values();

        if ($rootCandidates->isEmpty()) {
            return $matchingSpks->unique('id')->values();
        }

        // A root regular PK must be cancellable without pulling its addendum chain
        // into the same cancellation batch. This preserves addendums when the main
        // PK is cancelled and avoids the regeneration bug caused by deleting the
        // parent document and its descendants together.
        return Spk::query()
            ->whereIn('id', $rootCandidates->all())
            ->get()
            ->unique('id')
            ->values();
    }

    private function deleteGeneratedPublicFile(string $relativePath): void
    {
        $relativePath = ltrim(trim($relativePath), '/\\');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return;
        }

        $absolutePath = public_path($relativePath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function generateSignedDownloadUrl(string $filename): string
    {
        // Return direct static URL untuk better CDN caching
        // File di-serve langsung oleh web server (Nginx/Apache), bukan PHP
        return '/downloads/'.rawurlencode($filename);
    }

    private function mergeEncryptedDownloadState(Request $request): void
    {
        $request->validate([
            'state' => ['required', 'string'],
        ]);

        $state = decryptFilters((string) $request->input('state'));
        if (
            empty($state)
            || ! isset($state['bulan'], $state['tahun'])
            || ! is_numeric($state['bulan'])
            || ! is_numeric($state['tahun'])
            || (int) $state['bulan'] < 1
            || (int) $state['bulan'] > 12
        ) {
            abort(422, 'State download tidak valid atau tidak lengkap.');
        }

        $request->merge($state);
    }

    private function applyAlokasiScopeToSpkQuery($query, Collection $matchingAlokasiIds): void
    {
        if ($matchingAlokasiIds->isEmpty()) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function ($scopeQuery) use ($matchingAlokasiIds): void {
            $scopeQuery->whereIn('alokasi_petugas_id', $matchingAlokasiIds->all());

            foreach ($matchingAlokasiIds as $alokasiId) {
                $scopeQuery->orWhereJsonContains('alokasi_petugas_ids', (int) $alokasiId)
                    ->orWhereJsonContains('alokasi_petugas_ids', (string) $alokasiId);
            }
        });
    }

    private function resolveDownloadScopeContext(Request $request): string
    {
        $context = strtolower((string) $request->input('context', 'regular'));
        if (in_array($context, ['sensus', 'sensus-ekonomi', 'period-based', 'period_based'], true)) {
            return 'sensus';
        }

        if ($request->filled('mode') && strtolower((string) $request->input('mode')) === 'sensus-ekonomi') {
            return 'sensus';
        }

        if ($request->filled('periode_hashed_id')) {
            $periodeId = Hashids::decode((string) $request->input('periode_hashed_id'))[0] ?? null;
            if ($periodeId) {
                $periode = PeriodeAlokasi::with('kegiatan')->find($periodeId);
                if ($periode && $this->usesPeriodBasedSpkFlow($periode)) {
                    return 'sensus';
                }
            }
        }

        if ($request->filled('jenis_kegiatan')) {
            return strtolower((string) $request->input('jenis_kegiatan')) === 'sensus' ? 'sensus' : 'survei';
        }

        return 'survei';
    }

    private function resolvePreferredSpkFilePathForZip(Spk $spk): ?string
    {
        $candidates = collect([$spk->signed_file_path, $spk->file_path, $spk->previous_file_path])
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $addendumNumber = (int) ($spk->addendum_number ?? 0);

        if ($addendumNumber > 0) {
            $matched = $candidates->first(function (string $path) use ($addendumNumber): bool {
                return $this->pathMatchesAddendumNumber($path, $addendumNumber);
            });

            if ($matched) {
                return $matched;
            }

            return $candidates->first();
        }

        $nonAddendumPath = $candidates->first(function (string $path): bool {
            return ! $this->pathContainsAddendumMarker($path);
        });

        return $nonAddendumPath ?: $candidates->first();
    }

    private function pathContainsAddendumMarker(string $path): bool
    {
        return preg_match('/addendum|add-\d+/i', $path) === 1;
    }

    private function pathMatchesAddendumNumber(string $path, int $addendumNumber): bool
    {
        $escapedNumber = preg_quote((string) $addendumNumber, '/');

        return preg_match('/add(?:endum)?[_\-]?(?:no[_\-]?)?'.$escapedNumber.'(?!\d)/i', $path) === 1
            || preg_match('/add-'.$escapedNumber.'(?!\d)/i', $path) === 1;
    }

    private function buildZipFilenameForSpk(Spk $spk, string $sourcePath): string
    {
        // Some historical SPKs point to an allocation that has since been replaced
        // or deleted. Keep ZIP generation working even when that relation is absent.
        $petugasName = $spk->petugas?->nama
            ?? $spk->alokasiPetugas?->petugas?->nama
            ?? 'Petugas_'.($spk->petugas_id ?: $spk->id);
        $petugasName = preg_replace('/[\/\\:*?"<>|]/', '_', $petugasName);
        $safeNomor = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) ($spk->nomor_spk ?: basename($sourcePath)));

        if ((int) ($spk->addendum_number ?? 0) > 0) {
            $baseFileName = preg_replace('/\.pdf$/i', '', basename($sourcePath));
            $baseFileName = preg_replace('/(?:_ADDENDUM_\d+|_ADD-\d+)$/i', '', (string) $baseFileName);

            return sprintf('%s_%s_ADDENDUM_%s.pdf', $petugasName, $safeNomor, $spk->addendum_number);
        }

        return sprintf('%s_%s.pdf', $petugasName, $safeNomor);
    }

    private function makeUniqueZipEntryName(string $candidate, array &$usedNames): string
    {
        if (! isset($usedNames[$candidate])) {
            $usedNames[$candidate] = true;

            return $candidate;
        }

        $info = pathinfo($candidate);
        $baseName = $info['filename'] ?? basename($candidate, '.'.$info['extension'] ?? '');
        $extension = isset($info['extension']) && $info['extension'] !== '' ? '.'.$info['extension'] : '';
        $suffix = 2;

        do {
            $candidate = $baseName.'_'.$suffix.$extension;
            $suffix++;
        } while (isset($usedNames[$candidate]));

        $usedNames[$candidate] = true;

        return $candidate;
    }
}
