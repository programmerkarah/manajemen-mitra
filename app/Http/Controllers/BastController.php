<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

class BastController extends Controller
{
    // Role constants
    private const PENDATAAN_ROLES = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];

    private const PENGOLAHAN_ROLES = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];

    private function getRequestUser(Request $request)
    {
        return effectiveUser($request) ?? $request->user();
    }

    private function resolveBastFromHashedId(string $hashedId): ?Bast
    {
        return (new Bast)->resolveRouteBinding($hashedId);
    }

    private function userCanManageBastMain(Request $request): bool
    {
        $user = $this->getRequestUser($request);

        return $user && $user->hasAnyRole(['admin', 'operator']);
    }

    private function userCanManageLampiran(Request $request, BastKegiatan $bastKegiatan): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        return (int) $bastKegiatan->kegiatan?->ketua_tim_user_id === (int) $user->id
            || (int) $bastKegiatan->kegiatan?->pj_lainnya_id === (int) $user->id;
    }

    private function userCanAccessBast(Request $request, Bast $bast): bool
    {
        $user = $this->getRequestUser($request);

        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator'], true)) {
            return true;
        }

        // Ketua tim can open BAST detail across the period.
        // Lampiran actions are still restricted by userCanManageLampiran().
        return $user->active_role === 'ketua_tim';
    }

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

        return 0;
    }

    private function allocateNomorBastForSpk(Spk $spk, Carbon $tanggalBast): string
    {
        $existing = BastNumberAllocation::query()->where('spk_id', $spk->id)->first();

        if ($existing?->nomor_bast) {
            return (string) $existing->nomor_bast;
        }

        $tahun = $tanggalBast->year;
        $bulan = $tanggalBast->month;

        $maxFromBast = Bast::query()
            ->whereYear('tanggal_bast', $tahun)
            ->pluck('nomor_bast')
            ->map(fn (?string $nomorBast) => $this->extractBastSequence($nomorBast))
            ->max() ?? 0;

        $maxFromAllocation = BastNumberAllocation::query()
            ->where('tahun', $tahun)
            ->pluck('nomor_bast')
            ->map(fn (?string $nomorBast) => $this->extractBastSequence($nomorBast))
            ->max() ?? 0;

        $nextSequence = max($maxFromBast, $maxFromAllocation) + 1;
        $nomorBast = sprintf('PPIS/13730/%d/BAST/%d', $nextSequence, $tahun);

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
        if (preg_match('/\/BAST\/(\d{4})$/', $nomorBast, $matches)) {
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

    /**
     * @return array{file_path:?string,signed_file_path:?string,generated_at:?string,signed_uploaded_at:?string,status:string,can_upload_signed:bool}
     */
    private function getPreviewLampiranDocumentState(
        ?Spk $spk,
        int $kegiatanId,
        int $periodeAlokasiId,
        string $kodeKegiatan,
    ): array {
        if (! $spk || $kegiatanId <= 0 || $periodeAlokasiId <= 0) {
            return [
                'file_path' => null,
                'signed_file_path' => null,
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

            if (! $hasDraft && ! $hasSigned) {
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
            return null;
        }

        return trim('bast-export/'.trim($subdirectory, '/').'/'.$filename, '/');
    }

    private function prepareStoredBastViewData(Bast $bast): array
    {
        $bast->loadMissing([
            'spk.alokasiPetugas.petugas',
            'spk.alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ]);

        $spk = $bast->spk;

        if (! $spk || ! $spk->alokasiPetugas?->petugas) {
            abort(404, 'Data SPK untuk BAST tidak ditemukan.');
        }

        $ppk = (object) [
            'nama' => $bast->nama_ppk,
            'nip' => $bast->nip_ppk,
        ];

        return $this->prepareBastDataForExport(
            $spk,
            collect(),
            $bast->nomor_bast,
            $bast->tanggal_bast->format('Y-m-d'),
            $ppk
        );
    }

    private function isLampiranGenerationAllowed(array $kegiatanPayload): bool
    {
        $tanggalSelesai = $this->normalizeDateForCompare($kegiatanPayload['tanggal_selesai'] ?? null);

        if (! $tanggalSelesai) {
            return false;
        }

        return $tanggalSelesai <= now()->format('Y-m-d');
    }

    /**
     * Sort lampiran by earliest tanggal_selesai and assign sequential lampiran number.
     * When tanggal_selesai is the same, use kode_kegiatan then nama_kegiatan for stable order.
     *
     * @param  array<int, array<string, mixed>>  $kegiatanList
     * @return array<int, array<string, mixed>>
     */
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
                    ])->save();
                }
            } elseif (filled($bast->signed_file_path)) {
                $this->deleteStoredDocument($bast->signed_file_path);
                $bast->forceFill([
                    'signed_file_path' => null,
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
            }
        } else {
            $this->deleteStoredDocument($bast->signed_file_path);
            $updates['signed_file_path'] = null;
        }

        if (! empty($updates)) {
            $bast->forceFill($updates)->save();
        }
    }

    /**
     * Ensure bast_kegiatan records exist for all kegiatan payload rows.
     * This lets Detail BAST show per-kegiatan lampiran actions even before generation.
     *
     * @param  array<int, array<string, mixed>>  $kegiatanList
     */
    private function syncBastKegiatanFromPayload(Bast $bast, array $kegiatanList): void
    {
        if (empty($kegiatanList)) {
            return;
        }

        collect($kegiatanList)
            ->filter(fn (array $item) => filled($item['kegiatan_id'] ?? null) && filled($item['periode_alokasi_id'] ?? null))
            ->unique(fn (array $item) => $this->makeBastKegiatanKey((int) $item['kegiatan_id'], (int) $item['periode_alokasi_id']))
            ->each(function (array $item) use ($bast) {
                BastKegiatan::updateOrCreate(
                    [
                        'bast_id' => $bast->id,
                        'kegiatan_id' => (int) $item['kegiatan_id'],
                        'periode_alokasi_id' => (int) $item['periode_alokasi_id'],
                    ],
                    [
                        'kode_kegiatan' => (string) ($item['kode_kegiatan'] ?? '-'),
                        'nama_kegiatan' => (string) ($item['nama_kegiatan'] ?? '-'),
                        'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                        'tahun' => $bast->tanggal_bast->year,
                        'jenis_kegiatan' => (string) ($item['jenis_kegiatan'] ?? 'survei'),
                    ]
                );
            });
    }

    private function nextBastNomorForDate(Carbon $targetDate): string
    {
        $allBast = Bast::whereYear('tanggal_bast', $targetDate->year)
            ->pluck('nomor_bast');

        $maxUrut = 0;
        foreach ($allBast as $existingNomor) {
            if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $existingNomor, $matches)) {
                $urut = (int) $matches[1];
                if ($urut > $maxUrut) {
                    $maxUrut = $urut;
                }
            }
        }

        return sprintf('PPIS/13730/%d/BAST/%d', $maxUrut + 1, $targetDate->year);
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

    public function openDetailByPetugas(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $decrypted = [];
            if ($request->has('encrypted_filters')) {
                $decrypted = decryptFilters($request->input('encrypted_filters'));
            }

            $request->merge($decrypted);

            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            $request->session()->put('bast_open_detail_filters', $filters);

            $routeParams = [];

            if (filled($validated['petugas_id'] ?? null)) {
                $routeParams['petugas_id'] = (int) $validated['petugas_id'];
            }

            return redirect()->route('bast.open-detail-by-petugas', $routeParams);
        }

        if ($request->filled('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $filters = null;

        if ($request->hasAny(['bulan', 'tahun'])) {
            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            $request->session()->put('bast_open_detail_filters', $filters);
        }

        if (! is_array($filters)) {
            $filters = $request->session()->get('bast_open_detail_filters');
            if (! is_array($filters) || ! isset($filters['bulan'], $filters['tahun'])) {
                return redirect()->route('bast.index')
                    ->with('error', 'Pilih periode terlebih dahulu untuk membuka detail BAST.');
            }
        }

        $selectedPetugasId = $request->input('petugas_id');
        if (filled($selectedPetugasId) && is_numeric((string) $selectedPetugasId)) {
            $filters['petugas_id'] = (int) $selectedPetugasId;
        }

        $request->merge($filters);

        return $this->listByMonth($request);
    }

    /**
     * Check if any petugas has pendataan allocation with hasil_pendataan_lapangan > 0
     */
    private function hasPendataan($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_pendataan_lapangan'] ?? 0) > 0;
        });
    }

    /**
     * Check if any petugas has listing allocation with hasil_listing > 0
     */
    private function hasListing($petugas): bool
    {
        return collect($petugas)->contains(function ($p) {
            return in_array($p['peran'] ?? null, self::PENDATAAN_ROLES, true)
                && (int) ($p['hasil_listing'] ?? 0) > 0;
        });
    }

    /**
     * Determine if the BAST should use the FASIH clause.
     *
     * Returns true if any non-pengolahan alokasi belongs to a kegiatan
     * that uses CAPI as its metode_pendataan. If all non-pengolahan alokasi
     * use PAPI (or metode is null / not yet set) the clause is omitted.
     *
     * @param  iterable<AlokasiPetugas>  $allAlokasi
     */
    private function isMenggunakanFasih(iterable $allAlokasi): bool
    {
        foreach ($allAlokasi as $alokasi) {
            if (in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true)) {
                continue;
            }

            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

            if (! $kegiatan) {
                continue;
            }

            if ($kegiatan->metode_pendataan_pencacahan === 'CAPI') {
                return true;
            }

            if ($kegiatan->has_listing_updating && $kegiatan->metode_pendataan_listing === 'CAPI') {
                return true;
            }
        }

        return false;
    }

    private function normalizeDateForCompare($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function getAlokasiLatestTanggalSelesai(AlokasiPetugas $alokasi): ?string
    {
        $periode = $alokasi->periodeAlokasi;
        $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
        $hasListing = (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
        $hasPencacahan = (int) ($alokasi->jumlah_satuan ?? 0) > 0;

        $candidates = [];

        if ($isPengolahanRole) {
            if ($hasListing) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_listing_selesai);
            }

            if ($hasPencacahan) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_pencacahan_selesai);
            }

            if (empty(array_filter($candidates))) {
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_listing_selesai);
                $candidates[] = $this->normalizeDateForCompare($periode?->jadwal_pengolahan_pencacahan_selesai);
            }
        } else {
            if ($hasListing) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai_listing);
            }

            if ($hasPencacahan) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai);
            }

            if (empty(array_filter($candidates))) {
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai_listing);
                $candidates[] = $this->normalizeDateForCompare($periode?->tanggal_selesai);
            }
        }

        return collect($candidates)->filter()->max();
    }

    /**
     * Get effective allocation by kegiatan using priority:
     * perubahan > direvisi > disetujui > dikirim
     */
    private function getEffectiveAlokasiByKegiatan(Collection $alokasiGroup): Collection
    {
        return $alokasiGroup
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Priority: perubahan > direvisi > disetujui > dikirim
                $perubahan = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                if ($perubahan) {
                    return $perubahan;
                }

                $direvisi = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                if ($direvisi) {
                    return $direvisi;
                }

                $disetujui = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                if ($disetujui) {
                    return $disetujui;
                }

                return $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
            })
            ->filter();
    }

    private function getEffectiveAlokasiForPetugasInMonth(int $petugasId, string $bulanFormatted, int $tahun): Collection
    {
        $allAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        return $this->getEffectiveAlokasiByKegiatan($allAlokasi)->values();
    }

    private function hasPositiveBastAttachmentPayloadForPetugas(int $petugasId, string $bulanFormatted, int $tahun): bool
    {
        // Get latest document (SPK or addendum) for this petugas in this month
        $latestDocument = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun);
            })
            ->orderBy('addendum_number', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no SPK found for this month, petugas shouldn't be in BAST
        if (! $latestDocument) {
            return false;
        }

        // Get alokasi_petugas_ids from latest document
        $latestAlokasIds = $latestDocument->alokasi_petugas_ids ?? [$latestDocument->alokasi_petugas_id];

        // Get periode_alokasi_ids from those alokasi
        $latestPeriodeIds = AlokasiPetugas::whereIn('id', $latestAlokasIds)
            ->pluck('periode_alokasi_id')
            ->sort()
            ->values()
            ->toArray();

        // Get all allocations for this petugas in this month
        $allAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi')
            ->get();

        if ($allAlokasi->isEmpty()) {
            return false;
        }

        // Get all kegiatan IDs that this petugas is involved in (from any status)
        $kegiatanIds = $allAlokasi->pluck('periodeAlokasi.kegiatan_id')->unique();

        // For each kegiatan, check if there's a 'perubahan' periode that removes this petugas
        foreach ($kegiatanIds as $kegiatanId) {
            $perubahanPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
                ->where('bulan', $bulanFormatted)
                ->where('tahun', $tahun)
                ->where('status', 'perubahan')
                ->first();

            // If there's a perubahan periode for this kegiatan, check if petugas is removed
            if ($perubahanPeriode) {
                $hasAlokasiInPerubahan = AlokasiPetugas::where('petugas_id', $petugasId)
                    ->where('periode_alokasi_id', $perubahanPeriode->id)
                    ->exists();

                // If petugas is NOT in perubahan periode, they are removed - filter out this kegiatan
                if (! $hasAlokasiInPerubahan) {
                    $allAlokasi = $allAlokasi->filter(function ($alokasi) use ($kegiatanId) {
                        return $alokasi->periodeAlokasi->kegiatan_id !== $kegiatanId;
                    });
                }
            }
        }

        // After filtering removed kegiatan, check if there are any allocations left
        if ($allAlokasi->isEmpty()) {
            return false;
        }

        // Get effective allocations using priority: perubahan > direvisi > disetujui > dikirim
        $effectiveAlokasi = $this->getEffectiveAlokasiByKegiatan($allAlokasi);

        // Get current periode_alokasi_ids
        $currentPeriodeIds = $effectiveAlokasi
            ->pluck('periode_alokasi_id')
            ->sort()
            ->values()
            ->toArray();

        // Calculate current total honor
        $currentTotalHonor = $effectiveAlokasi->sum(function ($alokasi) {
            return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
        });

        // Calculate nilai_kontrak from latest document
        $latestNilaiKontrak = (float) $latestDocument->nilai_kontrak;

        // BAST can only be created if:
        // 1. NO change in daftar kegiatan (periode_alokasi_ids unchanged), AND
        // 2. NO change in total honor (nilai_kontrak unchanged), AND
        // 3. Current total honor is POSITIVE (has actual work/payment)
        $periodeIdsChanged = $latestPeriodeIds !== $currentPeriodeIds;
        $nilaiKontrakChanged = abs($latestNilaiKontrak - $currentTotalHonor) > 0.01;

        // If there are changes, addendum is needed first - cannot create BAST
        if ($periodeIdsChanged || $nilaiKontrakChanged) {
            return false;
        }

        // If current total honor is 0 or negative, no BAST attachment - cannot create BAST
        if ($currentTotalHonor <= 0) {
            return false;
        }

        // Verify that there's at least one allocation with positive values
        return $effectiveAlokasi->contains(function ($alokasi) {
            return
                (int) ($alokasi->jumlah_satuan ?? 0) > 0 ||
                (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0 ||
                (float) ($alokasi->total_honor ?? 0) > 0 ||
                (float) ($alokasi->total_honor_listing ?? 0) > 0;
        });
    }

    private function isLegacyBastAttachmentMode(string $bulanFormatted, int $tahun): bool
    {
        $bulan = (int) ltrim($bulanFormatted, '0');

        return $tahun < 2026 || ($tahun === 2026 && $bulan < 4);
    }

    private function hasPositiveEffectiveAlokasiForPetugasInMonth(int $petugasId, string $bulanFormatted, int $tahun): bool
    {
        $effectiveAlokasi = $this->getEffectiveAlokasiForPetugasInMonth($petugasId, $bulanFormatted, $tahun);

        if ($effectiveAlokasi->isEmpty()) {
            return false;
        }

        return $effectiveAlokasi->contains(function ($alokasi) {
            return
                (int) ($alokasi->jumlah_satuan ?? 0) > 0 ||
                (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0 ||
                (float) ($alokasi->total_honor ?? 0) > 0 ||
                (float) ($alokasi->total_honor_listing ?? 0) > 0;
        });
    }

    /**
     * Display a listing of the resource.
     * Menampilkan periode bulan (Januari-Desember) dengan informasi BAST yang sudah/belum dibuat
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $activeYear = ActiveYearService::get();

        // Ambil semua SPK yang punya alokasi > 0 pada periode status 'perubahan' (final allocation state) di tahun berjalan
        // Konsisten dengan filtering di create() method
        $eligibleSpks = DB::table('spk')
            ->join('alokasi_petugas as ap', 'ap.petugas_id', '=', 'spk.petugas_id')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->where('spk.addendum_number', 0)
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->distinct('spk.petugas_id')
            ->select('spk.*')
            ->get();

        // Untuk setiap SPK, tentukan bulan periode alokasi pertamanya di tahun berjalan (alokasi > 0, status perubahan)
        // Ambil seluruh alokasi_petugas yang join ke periode_alokasi (tahun aktif, status perubahan, jumlah > 0)
        $alokasiRows = DB::table('alokasi_petugas as ap')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->select('ap.petugas_id', 'pa.bulan')
            ->get();

        // Untuk setiap bulan, kumpulkan petugas unik
        $spkByBulan = [];
        foreach (range(1, 12) as $bulan) {
            $petugasIds = $alokasiRows->filter(function ($row) use ($bulan) {
                return (int) ltrim($row->bulan, '0') === $bulan;
            })->pluck('petugas_id')->unique()->values();
            $spkByBulan[$bulan] = $petugasIds->all();
        }

        $data = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $activeYear);

            // Get all unique petugas who have SPK (original or addendum) in this month
            $allPetugasIds = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($activeYear, $bulanFormatted) {
                $q->where('tahun', $activeYear)
                    ->where('bulan', $bulanFormatted);
            })
                ->distinct()
                ->pluck('petugas_id')
                ->filter()
                ->unique()
                ->values();

            // Filter petugas using the same logic as create() method
            $eligiblePetugasIds = $allPetugasIds->filter(function ($petugasId) use ($bulanFormatted, $activeYear, $isLegacyBastMode) {
                if ($isLegacyBastMode) {
                    return $this->hasPositiveBastAttachmentPayloadForPetugas(
                        (int) $petugasId,
                        $bulanFormatted,
                        (int) $activeYear
                    );
                }

                return $this->hasPositiveEffectiveAlokasiForPetugasInMonth(
                    (int) $petugasId,
                    $bulanFormatted,
                    (int) $activeYear
                );
            });

            $eligiblePetugasCount = $eligiblePetugasIds->count();

            // Samakan metrik "BAST dibuat" dengan detail bulan (jumlah petugas pada dokumen BAST bulan tersebut)
            $petugasWithBast = DB::table('bast_petugas as bp')
                ->join('bast as b', 'bp.bast_id', '=', 'b.id')
                ->whereYear('b.tanggal_bast', $activeYear)
                ->whereMonth('b.tanggal_bast', $bulan)
                ->distinct('bp.petugas_id')
                ->count('bp.petugas_id');

            $totalPetugas = max($eligiblePetugasCount, $petugasWithBast);
            $petugasWithoutBast = max(0, $totalPetugas - $petugasWithBast);

            $data[] = [
                'bulan' => $bulan,
                'bulan_label' => $this->getBulanLabel($bulan),
                'tahun' => $activeYear,
                'total_spk' => $totalPetugas,
                'spk_with_bast' => $petugasWithBast,
                'spk_without_bast' => $petugasWithoutBast,
                'has_spk' => $totalPetugas > 0,
                'all_completed' => $totalPetugas > 0 && $petugasWithoutBast === 0,
            ];
        }

        // Encrypt sensitive data
        $encryptedData = encryptData($data);

        return Inertia::render('Bast/Index', [
            'data' => [
                'encrypted' => $encryptedData,
            ],
            'filters' => [
                'search' => $search,
            ],
            'active_year' => $activeYear,
        ]);
    }

    /**
     * List all BAST for a specific month with filter
     */
    public function listByMonth(Request $request): Response|RedirectResponse
    {
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $selectedPetugasId = (int) $request->input('petugas_id', 0);
        $activeYear = ActiveYearService::get();

        // Default to current year if no filter
        if (! $tahun) {
            $tahun = $activeYear;
        }

        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $user = $this->getRequestUser($request);
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        // Get first BAST for this month, filtered by kegiatan managed by the current ketua tim so
        // they land on a BAST that actually contains their lampiran (not an unrelated BAST).
        $firstBast = Bast::whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan) {
            $query->where('tahun', $tahun);
            if ($bulan) {
                $query->where('bulan', $bulan);
            }
        })
            ->when($isKetuaTim, function ($query) use ($user) {
                $query->whereHas('bastKegiatan.kegiatan', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });
                });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($selectedPetugasId > 0) {
            $selectedPetugasBast = Bast::query()
                ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulanFormatted) {
                    $query->where('tahun', $tahun)
                        ->where('bulan', $bulanFormatted);
                })
                ->where(function ($query) use ($selectedPetugasId) {
                    $query->whereHas('spk.alokasiPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    })->orWhereHas('bastPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    });
                })
                ->latest('created_at')
                ->first();

            if ($selectedPetugasBast) {
                return $this->show($request, $selectedPetugasBast);
            }
        }

        // For April 2026+, when selecting a petugas without BAST (or no BAST exists yet),
        // show same detail layout without generating BAST document.
        if ($selectedPetugasId > 0 || ! $firstBast) {
            $canManageMain = $this->userCanManageBastMain($request);

            $periodeReference = PeriodeAlokasi::query()
                ->where('tahun', $tahun)
                ->when($bulan, function ($query) use ($bulanFormatted) {
                    $query->where('bulan', $bulanFormatted);
                })
                ->latest('id')
                ->first();

            $bastList = $periodeReference
                ? $this->buildBastListForPeriod($periodeReference, $canManageMain, $isKetuaTim, $request)
                : collect();

            $existingBastSpkIds = Bast::whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)->where('tahun', $tahun);
            })->pluck('spk_id')->filter()->unique();

            $eligibleWithoutBast = Spk::with('alokasiPetugas.petugas')
                ->whereNotIn('id', $existingBastSpkIds)
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->when($isKetuaTim, function ($query) use ($user, $bulanFormatted, $tahun) {
                    $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $bulanFormatted, $tahun) {
                        $q->where('bulan', $bulanFormatted)
                            ->where('tahun', $tahun)
                            ->whereHas('kegiatan', function ($qk) use ($user) {
                                $qk->where(function ($sub) use ($user) {
                                    $sub->where('ketua_tim_user_id', $user?->id)
                                        ->orWhere('pj_lainnya_id', $user?->id);
                                });
                            });
                    })
                        ->pluck('id')
                        ->toArray();

                    if (empty($alokasiIds)) {
                        $query->whereRaw('0 = 1');

                        return;
                    }

                    $query->where(function ($inner) use ($alokasiIds) {
                        foreach ($alokasiIds as $id) {
                            $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                        }
                    });
                })
                ->get()
                ->map(function ($spk) {
                    $petugas = $spk->alokasiPetugas?->petugas;

                    return [
                        'petugas_nama' => $petugas?->nama ?? 'Petugas tidak diketahui',
                        'petugas_id' => $petugas?->id,
                    ];
                })
                ->sortBy('petugas_nama')
                ->values();

            if ($selectedPetugasId === 0 && $eligibleWithoutBast->isNotEmpty()) {
                $selectedPetugasId = (int) ($eligibleWithoutBast->first()['petugas_id'] ?? 0);
            }

            $selectedPetugas = null;
            $lampiranPreview = collect();
            $selectedSpk = null;

            if ($selectedPetugasId > 0) {
                $selectedSpk = Spk::where('petugas_id', $selectedPetugasId)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                        $q->where('bulan', $bulanFormatted)
                            ->where('tahun', $tahun);
                    })
                    ->orderByDesc('addendum_number')
                    ->orderByDesc('created_at')
                    ->first();

                $alokasiIdsFromLatestSpk = collect($selectedSpk?->alokasi_petugas_ids ?? [])
                    ->filter()
                    ->values();

                if ($selectedSpk?->alokasi_petugas_id) {
                    $alokasiIdsFromLatestSpk->push($selectedSpk->alokasi_petugas_id);
                }

                $alokasiIdsFromLatestSpk = $alokasiIdsFromLatestSpk->unique()->values();

                $alokasiPreviewQuery = AlokasiPetugas::query();

                if ($alokasiIdsFromLatestSpk->isNotEmpty()) {
                    $alokasiPreviewQuery->whereIn('id', $alokasiIdsFromLatestSpk->all());
                } else {
                    $alokasiPreviewQuery->where('petugas_id', $selectedPetugasId)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->where('bulan', $bulanFormatted)
                                ->where('tahun', $tahun)
                                ->whereIn('status', ['dikirim', 'perubahan']);
                        });
                }

                $alokasiPreview = $alokasiPreviewQuery
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
                    ->when($isKetuaTim, function ($query) use ($user) {
                        $query->whereHas('periodeAlokasi.kegiatan', function ($q) use ($user) {
                            $q->where(function ($sub) use ($user) {
                                $sub->where('ketua_tim_user_id', $user?->id)
                                    ->orWhere('pj_lainnya_id', $user?->id);
                            });
                        });
                    })
                    ->with([
                        'petugas',
                        'periodeAlokasi.kegiatan.ketuaTim',
                    ])
                    ->get();

                $selectedPetugas = $alokasiPreview->first()?->petugas;

                $lampiranPreview = $this->getEffectiveAlokasiByKegiatan($alokasiPreview)
                    ->values()
                    ->map(function (AlokasiPetugas $alokasi, int $index) use ($selectedSpk) {
                        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                        $tanggalSelesai = $this->getAlokasiLatestTanggalSelesai($alokasi);
                        $formatted = '-';

                        if ($tanggalSelesai) {
                            try {
                                $formatted = Carbon::parse($tanggalSelesai)->locale('id')->isoFormat('D MMMM YYYY');
                            } catch (\Exception $e) {
                                $formatted = '-';
                            }
                        }

                        $readyToGenerate = false;
                        $normalizedTanggalSelesai = $this->normalizeDateForCompare($tanggalSelesai);
                        if ($normalizedTanggalSelesai) {
                            $readyToGenerate = $normalizedTanggalSelesai <= now()->format('Y-m-d');
                        }

                        $previewDocumentState = $this->getPreviewLampiranDocumentState(
                            $selectedSpk,
                            (int) ($kegiatan?->id ?? 0),
                            (int) ($alokasi->periode_alokasi_id ?? 0),
                            (string) ($kegiatan?->kode_kegiatan ?? '-'),
                        );

                        return [
                            'id' => $index + 1,
                            'kegiatan_id' => (int) ($kegiatan?->id ?? 0),
                            'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                            'kode_kegiatan' => $kegiatan?->kode_kegiatan ?? '-',
                            'nama_kegiatan' => $kegiatan?->nama_kegiatan ?? '-',
                            'jenis_kegiatan' => $kegiatan?->jenis_kegiatan ?? 'survei',
                            'peran' => $alokasi->peran,
                            'tanggal_selesai' => $tanggalSelesai,
                            'tanggal_selesai_formatted' => $formatted,
                            'ketua_tim_nama' => $kegiatan?->ketuaTim?->name,
                            'file_path' => $previewDocumentState['file_path'],
                            'signed_file_path' => $previewDocumentState['signed_file_path'],
                            'generated_at' => $previewDocumentState['generated_at'],
                            'signed_uploaded_at' => $previewDocumentState['signed_uploaded_at'],
                            'status' => $previewDocumentState['status'],
                            'can_download' => $readyToGenerate || filled($previewDocumentState['file_path']) || filled($previewDocumentState['signed_file_path']),
                            'can_generate' => $readyToGenerate,
                            'can_upload_signed' => $previewDocumentState['can_upload_signed'],
                            'can_preview' => $readyToGenerate || filled($previewDocumentState['file_path']) || filled($previewDocumentState['signed_file_path']),
                            'ready_to_generate' => $readyToGenerate,
                            'preview_spk_id' => $selectedSpk?->id,
                        ];
                    });
            }

            $generatedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['file_path']))->count();
            $signedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['signed_file_path']))->count();
            $allLampiranPreviewGenerated = $lampiranPreview->isNotEmpty() && $generatedLampiranPreviewCount === $lampiranPreview->count();
            $allLampiranPreviewSigned = $lampiranPreview->isNotEmpty() && $signedLampiranPreviewCount === $lampiranPreview->count();

            return Inertia::render('Bast/Show', [
                'bast' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'nomor_bast' => '-',
                    'tanggal_bast' => '-',
                    'tanggal_serah_terima' => '-',
                    'menggunakan_fasih' => false,
                    'uraian_pekerjaan' => '-',
                    'nama_ketua_tim' => '-',
                    'nip_ketua_tim' => null,
                    'nama_ppk' => '-',
                    'nip_ppk' => null,
                    'hasil_pekerjaan' => null,
                    'file_path' => null,
                    'compiled_file_path' => null,
                    'main_signed_file_path' => null,
                    'signed_file_path' => null,
                    'lokasi_kegiatan' => null,
                    'status' => 'draft',
                    'catatan' => null,
                    'created_by' => '-',
                    'created_at' => '-',
                    'is_legacy_mode' => false,
                ],
                'spk' => $selectedSpk ? [
                    'id' => $selectedSpk->id,
                    'hashed_id' => $selectedSpk->hashed_id,
                    'nomor_spk' => $selectedSpk->nomor_spk,
                    'tanggal_spk' => $selectedSpk->tanggal_spk?->format('Y-m-d') ?? '-',
                    'nilai_kontrak' => (float) ($selectedSpk->nilai_kontrak ?? 0),
                ] : null,
                'petugas' => $selectedPetugas ? [
                    'id' => $selectedPetugas->id,
                    'hashed_id' => $selectedPetugas->hashed_id ?? '',
                    'nama' => $selectedPetugas->nama,
                    'nik' => $selectedPetugas->nik,
                    'alamat' => $selectedPetugas->alamat,
                    'no_hp' => $selectedPetugas->no_hp,
                ] : null,
                'kegiatan' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'kode_kegiatan' => '-',
                    'nama_kegiatan' => 'Belum ada BAST',
                    'jenis_kegiatan' => 'survei',
                    'tahun_anggaran' => (int) $tahun,
                ],
                'lampiran' => $lampiranPreview->values()->toArray(),
                'bast_list' => $bastList->values()->toArray(),
                'eligible_without_bast' => $eligibleWithoutBast
                    ->filter(function ($item) use ($bastList) {
                        $idsWithBast = $bastList->pluck('petugas_id')->filter()->unique()->toArray();

                        return ! in_array($item['petugas_id'] ?? null, $idsWithBast, true);
                    })
                    ->values()
                    ->toArray(),
                'permissions' => [
                    'can_manage_main' => $canManageMain,
                    'is_ketua_tim' => $isKetuaTim,
                    'can_upload_main' => in_array($user?->active_role, ['admin', 'operator'], true),
                ],
                'summary' => [
                    'total_lampiran' => $lampiranPreview->count(),
                    'generated_lampiran' => $generatedLampiranPreviewCount,
                    'signed_lampiran' => $signedLampiranPreviewCount,
                    'all_lampiran_generated' => $allLampiranPreviewGenerated,
                    'all_lampiran_signed' => $allLampiranPreviewSigned,
                    'main_signed_uploaded' => false,
                    'final_signed_ready' => false,
                ],
                'bulan' => (int) $bulan,
                'tahun' => (int) $tahun,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
            ]);
        }

        if (! $firstBast) {
            return redirect()->route('bast.index')
                ->with('error', 'Tidak ada BAST untuk periode '.$this->getBulanLabel((int) $bulan).' '.$tahun);
        }

        // Redirect to canonical open-detail page (no hash URL)
        $this->rememberOpenDetailFiltersFromBast($request, $firstBast);

        return redirect()->route('bast.open-detail-by-petugas');
    }

    /**
     * Show form to create BAST for a specific month
     * List all SPK in that month that don't have BAST yet
     */
    public function create(Request $request): Response|RedirectResponse
    {

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun', ActiveYearService::get());
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        if (! $bulan) {
            return redirect()->route('bast.index')
                ->with('error', 'Bulan harus diisi');
        }

        // Get all unique petugas who have SPK (original or addendum) in this month
        $allPetugasIds = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($tahun, $bulanFormatted) {
            $q->where('tahun', $tahun)
                ->where('bulan', $bulanFormatted);
        })
            ->distinct()
            ->pluck('petugas_id')
            ->filter()
            ->unique();

        $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $tahun);

        // Filter petugas who are eligible for BAST (have positive honor and no pending addendum)
        $eligiblePetugasIds = $allPetugasIds->filter(function ($petugasId) use ($bulanFormatted, $tahun, $isLegacyBastMode) {
            if ($isLegacyBastMode) {
                return $this->hasPositiveBastAttachmentPayloadForPetugas(
                    (int) $petugasId,
                    $bulanFormatted,
                    (int) $tahun,
                );
            }

            return $this->hasPositiveEffectiveAlokasiForPetugasInMonth(
                (int) $petugasId,
                $bulanFormatted,
                (int) $tahun,
            );
        });

        // For each eligible petugas, get their latest SPK (highest addendum_number)
        $spks = collect();
        foreach ($eligiblePetugasIds as $petugasId) {
            $latestSpk = Spk::where('petugas_id', $petugasId)
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($tahun, $bulanFormatted) {
                    $q->where('tahun', $tahun)
                        ->where('bulan', $bulanFormatted);
                })
                ->with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi', 'bast'])
                ->orderByDesc('addendum_number')
                ->orderByDesc('created_at')
                ->first();

            // Check if this petugas already has BAST for this month
            if ($latestSpk) {
                $hasBastThisMonth = $latestSpk->bast->contains(function ($bast) use ($tahun, $bulan) {
                    if (! $bast->tanggal_bast) {
                        return false;
                    }

                    return (int) $bast->tanggal_bast->format('Y') === (int) $tahun
                        && (int) $bast->tanggal_bast->format('n') === (int) $bulan;
                });

                if (! $hasBastThisMonth) {
                    $spks->push($latestSpk);
                }
            }
        }

        if ($spks->isEmpty()) {
            return redirect()->route('bast.index')
                ->with('info', 'Tidak ada SPK yang belum memiliki BAST di bulan ini');
        }

        // Get starting nomor urut BAST untuk bulan ini
        $lastBast = Bast::whereYear('tanggal_bast', $tahun)
            ->whereMonth('tanggal_bast', $bulan)
            ->orderByDesc('id')
            ->first();

        if ($lastBast && preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $lastBast->nomor_bast, $matches)) {
            $nomorUrutStart = (int) $matches[1] + 1;
        } else {
            $nomorUrutStart = 1;
        }

        // Tentukan tanggal berakhir paling akhir dari semua alokasi di bulan ini
        // Ambil alokasi di bulan ini dari semua periode (tidak peduli status)
        $allAlokasiBulan = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
            $q->where('bulan', $bulanFormatted)
                ->where('tahun', $tahun);
        })->get();
        $tanggalBerakhirPalingAkhir = $allAlokasiBulan->map(function ($alokasi) {
            return $this->getAlokasiLatestTanggalSelesai($alokasi);
        })->filter()->max();

        // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir kemudian nama petugas (A-Z)
        $spks = $spks->sort(function ($a, $b) use ($bulanFormatted, $tahun) {
            // Get tanggal berakhir untuk SPK A
            $petugasA = $a->alokasiPetugas?->petugas;
            $allAlokasiA = $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugasA?->id ?? 0), $bulanFormatted, (int) $tahun)
                ->filter(function ($alokasi) {
                    return (int) ($alokasi->jumlah_satuan ?? 0) > 0 || (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
                });

            $tanggalBerakhirA = $allAlokasiA->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirA) {
                $tanggalBerakhirA = $a->tanggal_selesai_kerja ?? $a->tanggal_mulai_kerja;
            }

            // Get tanggal berakhir untuk SPK B
            $petugasB = $b->alokasiPetugas?->petugas;
            $allAlokasiB = $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugasB?->id ?? 0), $bulanFormatted, (int) $tahun)
                ->filter(function ($alokasi) {
                    return (int) ($alokasi->jumlah_satuan ?? 0) > 0 || (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0;
                });

            $tanggalBerakhirB = $allAlokasiB->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirB) {
                $tanggalBerakhirB = $b->tanggal_selesai_kerja ?? $b->tanggal_mulai_kerja;
            }

            // Compare dates first
            $dateCompare = strcmp($tanggalBerakhirA, $tanggalBerakhirB);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            // If dates are equal, compare by nama petugas (A-Z)
            return strcmp($petugasA?->nama ?? '', $petugasB?->nama ?? '');
        })->values();

        // Format data SPK dengan detail kegiatan yang diikuti petugas
        $spkList = $spks->map(function ($spk, $index) use ($bulanFormatted, $tahun, $nomorUrutStart) {
            $petugas = $spk->alokasiPetugas?->petugas;

            // Ambil SEMUA alokasi petugas untuk bulan ini (semua kegiatan yang diikuti petugas di bulan yang sama)
            $allAlokasi = $this->getEffectiveAlokasiForPetugasInMonth((int) ($petugas?->id ?? 0), $bulanFormatted, (int) $tahun)
                ->filter(function ($alokasi) {
                    return
                        (float) ($alokasi->total_honor ?? 0) > 0 ||
                        (float) ($alokasi->total_honor_listing ?? 0) > 0;
                })
                ->load([
                    'periodeAlokasi.kegiatan',
                    'spk' => function ($query) {
                        $query->orderByDesc('addendum_number');
                    },
                ])
                ->values();

            // Kumpulkan kegiatan unik dengan detail dari semua alokasi petugas
            $kegiatanList = $allAlokasi->map(function ($alokasi) {

                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                $spkTerkait = $alokasi->spk?->first();
                $periodeAlokasi = $alokasi->periodeAlokasi;

                // Kumpulkan semua tanggal selesai yang relevan (listing & pencacahan)
                $tanggalSelesaiArr = [];
                $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES);
                if ($isPengolahanRole) {
                    if (! empty($periodeAlokasi?->jadwal_pengolahan_listing_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->jadwal_pengolahan_listing_selesai;
                    }
                    if (! empty($periodeAlokasi?->jadwal_pengolahan_pencacahan_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->jadwal_pengolahan_pencacahan_selesai;
                    }
                } else {
                    if (! empty($periodeAlokasi?->tanggal_selesai_listing)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->tanggal_selesai_listing;
                    }
                    if (! empty($periodeAlokasi?->tanggal_selesai)) {
                        $tanggalSelesaiArr[] = $periodeAlokasi->tanggal_selesai;
                    }
                }
                $tanggalSelesai = ! empty($tanggalSelesaiArr) ? collect($tanggalSelesaiArr)->max() : null;

                $tanggalSelesaiLabel = '';
                if (! empty($tanggalSelesai)) {
                    try {
                        // Convert to string if it's already a Carbon instance
                        $dateString = $tanggalSelesai instanceof Carbon
                            ? $tanggalSelesai->format('Y-m-d')
                            : $tanggalSelesai;

                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                            $tanggalSelesaiLabel = Carbon::parse($dateString)->locale('id')->isoFormat('D MMMM YYYY');
                        }
                    } catch (\Exception $e) {
                        $tanggalSelesaiLabel = '';
                    }
                }

                return [
                    'kegiatan_id' => $kegiatan?->id,
                    'kode_kegiatan' => $kegiatan?->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan?->nama_kegiatan,
                    'jenis_kegiatan' => $kegiatan?->jenis_kegiatan,
                    'nomor_spk' => $spkTerkait?->nomor_spk ?? 'Belum ada SPK',
                    'tanggal_selesai' => $tanggalSelesai,
                    'tanggal_selesai_label' => $tanggalSelesaiLabel,
                    'peran' => $alokasi->peran,
                    'hasil_listing' => $alokasi->jumlah_satuan_listing,
                    'hasil_pendataan_lapangan' => $alokasi->jumlah_satuan,
                    'hasil_pengolahan' => in_array($alokasi->peran, self::PENGOLAHAN_ROLES) ? $alokasi->jumlah_satuan : null,
                    'hasil_pengolahan_listing' => in_array($alokasi->peran, self::PENGOLAHAN_ROLES) ? $alokasi->jumlah_satuan_listing : null,
                    'spk_id' => $spkTerkait?->id,
                ];
            })->filter()->values();

            // Hitung tanggal berakhir paling akhir dari kegiatan yang diikuti petugas ini
            $tanggalBerakhirPetugasIni = $kegiatanList->map(function ($kegiatan) {
                return $kegiatan['tanggal_selesai'];
            })->filter()->max();

            // Fallback ke tanggal SPK jika tidak ada tanggal dari kegiatan
            if (! $tanggalBerakhirPetugasIni) {
                $tanggalBerakhirPetugasIni = $spk->tanggal_selesai_kerja;
            }

            $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

            // Generate nomor BAST untuk SPK ini dengan nomor urut yang increment
            $nomorUrut = $nomorUrutStart + $index;
            $nomorBastPreview = $tanggalBerakhirPetugasIni
                ? sprintf('PPIS/13730/%d/BAST/%d', $nomorUrut, $tanggalBerakhirPetugasIni instanceof Carbon ? $tanggalBerakhirPetugasIni->year : Carbon::parse($tanggalBerakhirPetugasIni)->year)
                : null;

            return [
                'spk_id' => $spk->id,
                'spk_hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'nomor_bast_preview' => $nomorBastPreview,
                'tanggal_spk' => $spk->tanggal_spk?->format('Y-m-d'),
                'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja?->format('Y-m-d'),
                'tanggal_selesai_kerja_asli' => $spk->tanggal_selesai_kerja?->format('Y-m-d'),
                'tanggal_berakhir_paling_akhir' => $tanggalBerakhirPetugasIni instanceof Carbon ? $tanggalBerakhirPetugasIni->format('Y-m-d') : $tanggalBerakhirPetugasIni,
                'nama_ppk' => $spk->nama_ppk,
                'nip_ppk' => $spk->nip_ppk,
                'petugas' => [
                    'id' => $petugas?->id,
                    'nama' => $petugas?->nama,
                    'nik' => $petugas?->nik,
                    'alamat' => $petugas?->alamat,
                ],
                'ketua_tim' => [
                    'nama' => $ketuaTim?->name,
                    'nip' => $ketuaTim?->nip,
                ],
                'kegiatan_list' => $kegiatanList,
                'jumlah_kegiatan' => is_array($kegiatanList) ? count($kegiatanList) : (method_exists($kegiatanList, 'count') ? $kegiatanList->count() : 0),
            ];
        })->values();

        // Encrypt sensitive data
        $encryptedSpkList = encryptData($spkList);

        return Inertia::render('Bast/CreateForMonth', [
            'bulan' => (int) $bulan,
            'tahun' => $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
            'spk_list' => [
                'encrypted' => $encryptedSpkList,
            ],
        ]);
    }

    /**
     * Generate BAST secara batch untuk multiple SPK
     * Prinsip: 1 SPK = 1 BAST dengan lampiran per kegiatan
     */
    public function generateBatch(Request $request): RedirectResponse
    {
        // Decrypt payload
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $request->validate([
            'spk_ids' => 'required|array|min:1',
            'spk_ids.*' => 'required|integer|exists:spk,id',
        ]);

        // Load semua SPKs yang dipilih dengan relasi yang dibutuhkan
        $spks = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->whereIn('id', $request->spk_ids)->get();

        // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir kemudian nama petugas (A-Z)
        $spksSorted = $spks->sort(function ($a, $b) {
            $bulanA = date('m', strtotime($a->tanggal_mulai_kerja));
            $tahunA = date('Y', strtotime($a->tanggal_mulai_kerja));
            $petugasA = $a->alokasiPetugas?->petugas;

            $allAlokasiA = AlokasiPetugas::where('petugas_id', $petugasA?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($bulanA, $tahunA) {
                    $q->where('bulan', $bulanA)
                        ->where('tahun', $tahunA)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->where(function ($query) {
                    $query->where('jumlah_satuan', '>', 0)
                        ->orWhere('jumlah_satuan_listing', '>', 0);
                })
                ->with('periodeAlokasi')
                ->get();

            $tanggalBerakhirA = $allAlokasiA->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirA) {
                $tanggalBerakhirA = $a->tanggal_selesai_kerja ?? $a->tanggal_mulai_kerja;
            }

            $bulanB = date('m', strtotime($b->tanggal_mulai_kerja));
            $tahunB = date('Y', strtotime($b->tanggal_mulai_kerja));
            $petugasB = $b->alokasiPetugas?->petugas;

            $allAlokasiB = AlokasiPetugas::where('petugas_id', $petugasB?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($bulanB, $tahunB) {
                    $q->where('bulan', $bulanB)
                        ->where('tahun', $tahunB)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->where(function ($query) {
                    $query->where('jumlah_satuan', '>', 0)
                        ->orWhere('jumlah_satuan_listing', '>', 0);
                })
                ->with('periodeAlokasi')
                ->get();

            $tanggalBerakhirB = $allAlokasiB->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $tanggalBerakhirB) {
                $tanggalBerakhirB = $b->tanggal_selesai_kerja ?? $b->tanggal_mulai_kerja;
            }

            // Compare dates first
            $dateCompare = strcmp($tanggalBerakhirA, $tanggalBerakhirB);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            // If dates are equal, compare by nama petugas (A-Z)
            return strcmp($petugasA?->nama ?? '', $petugasB?->nama ?? '');
        })->values();

        // Initialize nomor urut BAST berdasarkan SPK pertama yang sudah diurutkan
        $nomorUrutBast = 0;
        $bulanBast = null;
        $tahunBast = null;

        if ($spksSorted->isNotEmpty()) {
            $firstSpk = $spksSorted->first();
            $firstPetugas = $firstSpk->alokasiPetugas?->petugas;
            $firstBulan = date('m', strtotime($firstSpk->tanggal_mulai_kerja));
            $firstTahun = date('Y', strtotime($firstSpk->tanggal_mulai_kerja));

            // Get tanggal berakhir for first SPK to determine bulan/tahun BAST
            $firstAllAlokasi = AlokasiPetugas::where('petugas_id', $firstPetugas?->id)
                ->whereHas('periodeAlokasi', function ($q) use ($firstBulan, $firstTahun) {
                    $q->where('bulan', $firstBulan)
                        ->where('tahun', $firstTahun)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
                ->with('periodeAlokasi')
                ->get();

            $firstTanggalBerakhir = $firstAllAlokasi->map(function ($alokasi) {
                return $this->getAlokasiLatestTanggalSelesai($alokasi);
            })->filter()->max();

            if (! $firstTanggalBerakhir) {
                $firstTanggalBerakhir = $firstSpk->tanggal_selesai_kerja ?? $firstSpk->tanggal_mulai_kerja;
            }

            // Convert and adjust for weekend
            if ($firstTanggalBerakhir instanceof Carbon) {
                $firstTanggalBerakhir = $firstTanggalBerakhir->format('Y-m-d');
            }

            $carbonTarget = Carbon::parse($firstTanggalBerakhir);
            while (in_array($carbonTarget->dayOfWeekIso, [6, 7])) {
                $carbonTarget->subDay();
            }

            $bulanBast = $carbonTarget->month;
            $tahunBast = $carbonTarget->year;

            // Ambil semua BAST di bulan dan tahun yang sama dan cari nomor tertinggi
            $allBast = Bast::whereYear('tanggal_bast', $tahunBast)
                ->whereMonth('tanggal_bast', $bulanBast)
                ->pluck('nomor_bast');

            $maxUrut = 0;
            foreach ($allBast as $existingNomor) {
                // Pattern: PPIS/13730/{urut}/BAST/{tahun}
                if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $existingNomor, $matches)) {
                    $urut = (int) $matches[1];
                    if ($urut > $maxUrut) {
                        $maxUrut = $urut;
                    }
                }
            }

            $nomorUrutBast = $maxUrut;
        }

        $successCount = 0;
        $failedSpk = [];

        try {

            $usedUruts = [];
            foreach ($spksSorted as $spk) {
                // Gunakan transaction terpisah untuk setiap SPK
                DB::beginTransaction();
                try {
                    // Cek apakah sudah ada BAST untuk petugas dan periode_alokasi yang sama
                    $alokasi = $spk->alokasiPetugas;
                    $petugasId = $alokasi?->petugas_id;
                    $periodeAlokasiId = $alokasi?->periode_alokasi_id;
                    $bastSudahAda = false;
                    if ($petugasId && $periodeAlokasiId) {
                        $bastSudahAda = Bast::where('periode_alokasi_id', $periodeAlokasiId)
                            ->whereHas('spk.alokasiPetugas', function ($q) use ($petugasId) {
                                $q->where('petugas_id', $petugasId);
                            })->exists();
                    }
                    if ($bastSudahAda) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'BAST sudah ada',
                        ];
                        DB::rollBack();

                        continue;
                    }

                    $petugas = $spk->alokasiPetugas?->petugas;
                    $bulan = date('m', strtotime($spk->tanggal_mulai_kerja));
                    $tahun = date('Y', strtotime($spk->tanggal_mulai_kerja));

                    // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
                    // Exclude status 'direvisi' karena tidak perlu masuk ke lampiran
                    $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                            $q->where('bulan', $bulan)
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
                            'spk',
                        ])
                        ->get();

                    // Skip jika tidak ada alokasi dengan pekerjaan
                    if ($allAlokasi->isEmpty()) {
                        $failedSpk[] = [
                            'nomor_spk' => $spk->nomor_spk,
                            'reason' => 'Tidak ada alokasi dengan pekerjaan',
                        ];
                        DB::rollBack();

                        continue;
                    }

                    $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;
                    $petugasUtama = $spk->alokasiPetugas->petugas;
                    $bulanUtama = date('m', strtotime($spk->tanggal_mulai_kerja));
                    $tahunUtama = date('Y', strtotime($spk->tanggal_mulai_kerja));
                    $allAlokasi = AlokasiPetugas::where('petugas_id', $petugasUtama->id)
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanUtama, $tahunUtama) {
                            $q->where('bulan', $bulanUtama)
                                ->where('tahun', $tahunUtama)
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
                            'spk',
                        ])
                        ->get();

                    $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
                        return $this->getAlokasiLatestTanggalSelesai($alokasi);
                    })->filter()->max();

                    if (! $tanggalBerakhirPalingAkhir) {
                        $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja;
                    }
                    if (! $tanggalBerakhirPalingAkhir) {
                        $tanggalBerakhirPalingAkhir = $spk->tanggal_mulai_kerja;
                    }
                    if ($tanggalBerakhirPalingAkhir instanceof Carbon) {
                        $tanggalBerakhirPalingAkhir = $tanggalBerakhirPalingAkhir->format('Y-m-d');
                    }
                    if (empty($tanggalBerakhirPalingAkhir) || $tanggalBerakhirPalingAkhir === '-') {
                        $tanggalBerakhirPalingAkhir = Carbon::now()->format('Y-m-d');
                    }
                    $carbonTarget = Carbon::parse($tanggalBerakhirPalingAkhir);
                    while (in_array($carbonTarget->dayOfWeekIso, [6, 7])) {
                        $carbonTarget->subDay();
                    }
                    $tanggalBerakhirPalingAkhir = $carbonTarget->format('Y-m-d');

                    $nomorBastTemp = null;
                    // Cek apakah SPK ini sudah punya alokasi nomor BAST dari lampiran preview
                    $existingAllocation = BastNumberAllocation::query()->where('spk_id', $spk->id)->first();
                    if ($existingAllocation?->nomor_bast) {
                        $nomorBast = $existingAllocation->nomor_bast;
                    } else {
                        // Ambil nomor urut terakhir dari database untuk tahun ini
                        $allBast = Bast::whereYear('tanggal_bast', $carbonTarget->year)
                            ->pluck('nomor_bast');
                        $maxUrut = 0;
                        foreach ($allBast as $existingNomor) {
                            if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $existingNomor, $matches)) {
                                $urut = (int) $matches[1];
                                if ($urut > $maxUrut) {
                                    $maxUrut = $urut;
                                }
                            }
                        }
                        // Cek nomor yang sudah dialokasikan via bast_number_allocations
                        $maxUrutAllocation = BastNumberAllocation::query()
                            ->where('tahun', $carbonTarget->year)
                            ->pluck('nomor_bast')
                            ->map(fn (?string $n) => $this->extractBastSequence($n))
                            ->max() ?? 0;
                        $maxUrut = max($maxUrut, $maxUrutAllocation);
                        // Cek juga nomor urut yang sudah dipakai di batch ini
                        if (! empty($usedUruts[$carbonTarget->year][$carbonTarget->month])) {
                            $maxUrutBatch = max($maxUrut, max($usedUruts[$carbonTarget->year][$carbonTarget->month]));
                        } else {
                            $maxUrutBatch = $maxUrut;
                        }
                        $nomorBastTemp = $maxUrutBatch + 1;
                        $nomorBast = sprintf('PPIS/13730/%d/BAST/%d', $nomorBastTemp, $carbonTarget->year);
                    }

                    // Ambil PPK aktif
                    $ppk = Penandatangan::where('jenis_penandatangan', 'ppk')
                        ->where('is_active', true)
                        ->first();

                    // Siapkan data BAST main menggunakan logic export yang sama.
                    $viewData = $this->prepareBastDataForExport(
                        $spk,
                        $allAlokasi,
                        $nomorBast,
                        $tanggalBerakhirPalingAkhir,
                        $ppk
                    );

                    // Generate file BAST utama tanpa lampiran.
                    try {
                        $pdfMain = Pdf::loadView('bast', $viewData)
                            ->setPaper('a4', 'portrait');
                        $filename = 'BAST_MAIN_'.$this->sanitizeDocumentSegment($nomorBast).'_'.$this->sanitizeDocumentSegment($spk->alokasiPetugas->petugas->nama).'.pdf';
                        $filePath = $this->writePdfToPublicDirectory($filename, $pdfMain->output(), 'main');
                    } catch (\Exception $pdfException) {
                        throw new \Exception('Gagal generate PDF: '.$pdfException->getMessage());
                    }

                    // Jika PDF berhasil, baru simpan ke database.
                    $bast = Bast::create([
                        'spk_id' => $spk->id,
                        'kegiatan_id' => $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->id,
                        'periode_alokasi_id' => $spk->alokasiPetugas?->periodeAlokasi?->id,
                        'nomor_bast' => $nomorBast,
                        'tanggal_bast' => $tanggalBerakhirPalingAkhir,
                        'tanggal_serah_terima' => $tanggalBerakhirPalingAkhir,
                        'uraian_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
                        'nama_ketua_tim' => $ketuaTim?->name ?? '-',
                        'nip_ketua_tim' => $ketuaTim?->nip ?? '-',
                        'nama_ppk' => $ppk->nama,
                        'nip_ppk' => $ppk->nip ?? '-',
                        'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
                        'hasil_pekerjaan' => $spk->alokasiPetugas?->catatan ?? '-',
                        'file_path' => $filePath,
                        'compiled_file_path' => null,
                        'main_signed_file_path' => null,
                        'signed_file_path' => null,
                        'lokasi_kegiatan' => 'Kota Sawahlunto',
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                    ]);

                    // Create BastPetugas record - hanya 1 per petugas per BAST
                    $alokasi = $spk->alokasiPetugas;
                    $petugas = $alokasi->petugas;
                    if ($petugas) {
                        $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
                        $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
                        // Aggregate hasil dari semua SPK (original + addendum)
                        $totalListing = 0;
                        $totalPendataan = 0;
                        $totalPengolahan = 0;
                        $catatan = [];
                        foreach ($allAlokasi as $alokasiSpk) {
                            $kegiatanSpk = $alokasiSpk?->periodeAlokasi?->kegiatan;
                            if (! $kegiatanSpk) {
                                continue;
                            }
                            $hasListing = ($kegiatanSpk->has_listing_updating ?? false)
                                || $alokasiSpk->getEffectiveJumlahSatuanListing() > 0;
                            if ($hasListing && $isPendataanRole) {
                                $totalListing += $alokasiSpk->getEffectiveJumlahSatuanListing();
                            }
                            if ($isPendataanRole) {
                                $totalPendataan += $alokasiSpk->getEffectiveJumlahSatuan();
                            }
                            if ($isPengolahanRole) {
                                $totalPengolahan += $alokasiSpk->getEffectiveJumlahSatuan();
                            }
                            if ($alokasiSpk->catatan) {
                                $catatan[] = $alokasiSpk->catatan;
                            }
                        }
                        BastPetugas::create([
                            'bast_id' => $bast->id,
                            'petugas_id' => $alokasi->petugas_id,
                            'spk_id' => $spk->id, // SPK utama
                            'nomor_spk' => $spk->nomor_spk,
                            'nama_petugas' => $petugas->nama,
                            'hasil_listing' => $totalListing > 0 ? $totalListing : null,
                            'hasil_pendataan_lapangan' => $totalPendataan > 0 ? $totalPendataan : null,
                            'hasil_pengolahan' => $totalPengolahan > 0 ? $totalPengolahan : null,
                            'hasil_pengolahan_listing' => $totalListing > 0 && $isPengolahanRole ? $totalListing : null,
                            'catatan' => ! empty($catatan) ? implode('; ', $catatan) : null,
                        ]);
                    }

                    collect($viewData['bast']->kegiatan_list)
                        ->unique(fn (array $item) => $this->makeBastKegiatanKey($item['kegiatan_id'], $item['periode_alokasi_id']))
                        ->each(function (array $item) use ($bast) {
                            BastKegiatan::create([
                                'bast_id' => $bast->id,
                                'kegiatan_id' => $item['kegiatan_id'],
                                'periode_alokasi_id' => $item['periode_alokasi_id'],
                                'kode_kegiatan' => $item['kode_kegiatan'],
                                'nama_kegiatan' => $item['nama_kegiatan'],
                                'bulan' => str_pad((string) $bast->tanggal_bast->month, 2, '0', STR_PAD_LEFT),
                                'tahun' => $bast->tanggal_bast->year,
                                'jenis_kegiatan' => $item['jenis_kegiatan'],
                            ]);
                        });

                    $this->adoptPreviewLampiranFiles($bast->fresh('bastKegiatan'));

                    $successCount++;
                    // Simpan nomor urut yang baru saja dipakai ke array batch (hanya jika tidak dari allocation)
                    if (isset($nomorBastTemp)) {
                        $usedUruts[$carbonTarget->year][$carbonTarget->month][] = $nomorBastTemp;
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    if (isset($filePath)) {
                        $this->deleteStoredDocument($filePath);
                    }
                    $failedSpk[] = [
                        'nomor_spk' => $spk->nomor_spk ?? 'Unknown',
                        'reason' => $e->getMessage(),
                    ];
                }
            }
            if (count($failedSpk) === 0) {
                $message = "Berhasil generate {$successCount} BAST.";

                return redirect()->route('bast.index')->with('success', $message);
            } else {
                $failedList = collect($failedSpk)->map(function ($f) {
                    $reason = $f['reason'];
                    // Sederhanakan pesan error duplicate entry
                    if (str_contains($reason, 'Duplicate entry')) {
                        $reason = 'Nomor BAST sudah digunakan';
                    } elseif (str_contains($reason, 'BAST sudah ada')) {
                        $reason = 'BAST sudah pernah dibuat';
                    } elseif (str_contains($reason, 'Integrity constraint violation')) {
                        $reason = 'Data tidak valid atau sudah ada.';
                    } elseif (str_contains($reason, 'Gagal generate PDF')) {
                        $reason = 'Gagal membuat file BAST.';
                    }

                    return "{$f['nomor_spk']} ($reason)";
                })->join(', ');
                $message = "Gagal generate BAST untuk: {$failedList}. Berhasil: {$successCount}.";

                return redirect()->route('bast.index')->with('error', $message);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate BAST: '.$e->getMessage());
        }
    }

    /**
     * Prepare BAST data for export (sama dengan preview)
     */
    private function prepareBastDataForExport(
        $spk,
        $allSpks,
        $nomorBast,
        $tanggalBerakhir,
        $ppk
    ): array {
        $petugas = $spk->alokasiPetugas->petugas;
        $bulan = date('m', strtotime($spk->tanggal_mulai_kerja));
        $tahun = date('Y', strtotime($spk->tanggal_mulai_kerja));

        // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
        // Exclude status 'direvisi' karena tidak perlu masuk ke lampiran
        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                $q->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']); // Exclude 'direvisi'
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
                'spk',
            ])
            ->get();

        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        // Format data untuk BAST
        $bastData = [
            'nomor_bast' => $nomorBast,
            'tanggal_bast' => $tanggalBerakhir,
            'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai' => $tanggalBerakhir,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $ppk->nama,
            'nip_ppk' => $ppk->nip ?? '-',
            'petugas' => [
                'nama' => $petugas?->nama,
                'nik' => $petugas?->nik,
                'alamat' => $petugas?->alamat,
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => [],
        ];

        // Build kegiatan list dengan lampiran
        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
            $periode = $alokasi->periodeAlokasi;
            if (! $kegiatan || ! $periode) {
                continue;
            }

            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
            $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();
            $hasListing = ($kegiatan->has_listing_updating ?? false) || $effectiveListingVolume > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            // Kumpulkan semua tanggal selesai yang relevan (listing & pencacahan)
            $tanggalSelesaiArr = [];
            if ($isPengolahanRole) {
                if (! empty($periode->jadwal_pengolahan_listing_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_listing_selesai;
                }
                if (! empty($periode->jadwal_pengolahan_pencacahan_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_pencacahan_selesai;
                }
            } elseif ($isPendataanRole) {
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
            } else {
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
            }

            // Ambil tanggal paling akhir dari semua tahapan
            $tanggalSelesaiKegiatan = null;
            if (! empty($tanggalSelesaiArr)) {
                $tanggalSelesaiKegiatan = collect($tanggalSelesaiArr)->max();
            }

            // Fallback ke tanggal SPK jika tidak ada tanggal dari periode
            if (empty($tanggalSelesaiKegiatan)) {
                $tanggalSelesaiKegiatan = $spkPetugas?->tanggal_selesai_kerja ?? $alokasi->tanggal_selesai ?? 'Belum ada SPK';
            }

            $ketuaTimKegiatan = $kegiatan->ketuaTim;

            // Validasi tanggal sebelum parsing dan adjust ke hari kerja jika weekend
            $tanggalSelesaiFormatted = '-';
            if (! empty($tanggalSelesaiKegiatan) && $tanggalSelesaiKegiatan !== 'Belum ada SPK') {
                try {
                    // Convert to string if it's already a Carbon instance
                    $dateString = $tanggalSelesaiKegiatan instanceof Carbon
                        ? $tanggalSelesaiKegiatan->format('Y-m-d')
                        : $tanggalSelesaiKegiatan;

                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                        $carbonDate = Carbon::parse($dateString);

                        // Adjust ke hari kerja terakhir sebelum tanggal tersebut jika weekend
                        while (in_array($carbonDate->dayOfWeekIso, [6, 7])) {
                            $carbonDate->subDay();
                        }

                        $tanggalSelesaiFormatted = $carbonDate->locale('id')->isoFormat('D MMMM YYYY');
                    }
                } catch (\Exception $e) {
                    // Try fallback to tanggal BAST utama
                    if (! empty($tanggalBerakhir)) {
                        try {
                            $dateString = $tanggalBerakhir instanceof Carbon
                                ? $tanggalBerakhir->format('Y-m-d')
                                : $tanggalBerakhir;

                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateString)) {
                                $carbonDate = Carbon::parse($dateString);

                                // Adjust ke hari kerja terakhir sebelum tanggal tersebut jika weekend
                                while (in_array($carbonDate->dayOfWeekIso, [6, 7])) {
                                    $carbonDate->subDay();
                                }

                                $tanggalSelesaiFormatted = $carbonDate->locale('id')->isoFormat('D MMMM YYYY');
                            }
                        } catch (\Exception $e2) {
                            $tanggalSelesaiFormatted = '-';
                        }
                    }
                }
            }

            // Generate uraian terpisah untuk listing dan pencacahan
            $uraianListing = null;
            $uraianPencacahan = null;

            if ($hasListing && $isPendataanRole) {
                // Untuk listing: paksa jumlah_satuan = 0 agar generate uraian listing
                $uraianListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0 // Force 0 untuk listing
                );
            }

            if ($isPendataanRole && $effectivePencacahanVolume > 0) {
                // Untuk pencacahan: paksa jumlah_satuan_listing = 0 agar generate uraian pencacahan
                $uraianPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0, // Force 0 untuk pencacahan
                    $effectivePencacahanVolume
                );
            }

            // Generate uraian terpisah untuk pengolahan listing dan pengolahan pencacahan
            $uraianPengolahanListing = null;
            $uraianPengolahanPencacahan = null;

            if ($hasListing && $isPengolahanRole) {
                // Untuk pengolahan listing: paksa jumlah_satuan = 0
                $uraianPengolahanListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0
                );
            }

            if ($isPengolahanRole && $effectivePencacahanVolume > 0) {
                // Untuk pengolahan pencacahan: paksa jumlah_satuan_listing = 0
                $uraianPengolahanPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0,
                    $effectivePencacahanVolume
                );
            }

            // Fallback: use first available uraian
            $uraianPekerjaan = $uraianListing ?? $uraianPencacahan ?? $this->generateUraianPekerjaan(
                $alokasi->peran,
                $kegiatan->nama_kegiatan,
                (int) $periode->bulan,
                $periode->tahun,
                $effectiveListingVolume,
                $effectivePencacahanVolume
            );

            $bastData['kegiatan_list'][] = [
                'kegiatan_id' => $kegiatan->id,
                'periode_alokasi_id' => $periode->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiKegiatan,
                // Tampilkan label tanggal selesai jika valid, jika tidak kosongkan string agar tidak tampil "-" di frontend
                'tanggal_selesai_label' => ($tanggalSelesaiFormatted !== '-' && ! empty($tanggalSelesaiKegiatan)) ? $tanggalSelesaiFormatted : '',
                'tanggal_selesai_formatted' => $tanggalSelesaiFormatted,
                'uraian_pekerjaan' => $uraianPekerjaan,
                'uraian_listing' => $uraianListing,
                'uraian_pencacahan' => $uraianPencacahan,
                'uraian_pengolahan_listing' => $uraianPengolahanListing,
                'uraian_pengolahan_pencacahan' => $uraianPengolahanPencacahan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $effectiveListingVolume : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'non_response_listing' => ($hasListing && $isPendataanRole) ? $alokasi->non_response_listing : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $effectivePencacahanVolume : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'non_response' => $isPendataanRole ? $alokasi->non_response : null,
                'hasil_pengolahan' => $isPengolahanRole ? $effectivePencacahanVolume : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $effectiveListingVolume : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? $rateHonor?->satuanListing?->nama : null,
                'keterangan' => $alokasi->catatan,
                'ketua_tim' => [
                    'nama' => $ketuaTimKegiatan?->name,
                    'nip' => $ketuaTimKegiatan?->nip,
                ],
            ];
        }

        $bastData['kegiatan_list'] = $this->sortAndNumberKegiatanLampiran($bastData['kegiatan_list']);

        $bastObject = (object) $bastData;

        // Get Kepala BPS
        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        return [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'tanggal_akhir_kegiatan' => Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('D MMMM YYYY'),
            'hari' => Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
            'nama_kepala' => $kepala?->nama,
        ];
    }

    /**
     * Prepare BAST data for PDF export
     */
    private function prepareBastData(
        $spk,
        $allSpks,
        $nomorBast,
        $tanggalBerakhir,
        $kegiatan,
        $periodeAlokasi,
        $uraianPekerjaan,
        $ketuaTim,
        $ppk
    ): array {
        $petugas = $spk->alokasiPetugas->petugas;

        // Build kegiatan list
        $kegiatanList = [];
        foreach ($allSpks as $spkKegiatan) {
            $alokasi = $spkKegiatan->alokasiPetugas;
            $keg = $alokasi?->periodeAlokasi?->kegiatan;
            $periode = $alokasi?->periodeAlokasi;

            if (! $keg || ! $periode) {
                continue;
            }

            $rateHonor = $keg->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $hasListing = ($keg->has_listing_updating ?? false) || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $alokasi = $spkKegiatan->alokasiPetugas;
            $keg = $alokasi?->periodeAlokasi?->kegiatan;
            $periode = $alokasi?->periodeAlokasi;

            if (! $keg || ! $periode) {
                continue;
            }

            $rateHonor = $keg->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $hasListing = ($keg->has_listing_updating ?? false) || ($alokasi->jumlah_satuan_listing ?? 0) > 0;

            // Cari SPK dari petugas ini saja, bukan per kegiatan
            $spkPetugas = Spk::where('alokasi_petugas_id', $alokasi->id)->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            $tanggalSelesaiKegiatan = $periode->tanggal_selesai ?? ($spkPetugas?->tanggal_selesai_kerja ?? ($alokasi->tanggal_selesai ?? 'Belum ada SPK'));
            $ketuaTimKegiatan = $keg->ketuaTim;

            // Validasi tanggal sebelum parsing
            $tanggalSelesaiFormatted = '-';
            if (! empty($tanggalSelesaiKegiatan) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesaiKegiatan)) {
                try {
                    $tanggalSelesaiFormatted = Carbon::parse($tanggalSelesaiKegiatan)->locale('id')->isoFormat('D MMMM YYYY');
                } catch (\Exception $e) {
                    $tanggalSelesaiFormatted = '-';
                }
            } elseif (! empty($tanggalBerakhir) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalBerakhir)) {
                // Fallback ke tanggal BAST utama jika tanggal selesai tidak valid
                try {
                    $tanggalSelesaiFormatted = Carbon::parse($tanggalBerakhir)->locale('id')->isoFormat('D MMMM YYYY');
                } catch (\Exception $e) {
                    $tanggalSelesaiFormatted = '-';
                }
            }

            // Generate uraian terpisah untuk listing dan pencacahan
            $uraianListing = null;
            $uraianPencacahan = null;

            if ($hasListing && $isPendataanRole) {
                // Untuk listing: paksa jumlah_satuan = 0 agar generate uraian listing
                $uraianListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $alokasi->jumlah_satuan_listing ?? 0,
                    0 // Force 0 untuk listing
                );
            }

            if ($isPendataanRole && ($alokasi->jumlah_satuan ?? 0) > 0) {
                // Untuk pencacahan: paksa jumlah_satuan_listing = 0 agar generate uraian pencacahan
                $uraianPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0, // Force 0 untuk pencacahan
                    $alokasi->jumlah_satuan ?? 0
                );
            }

            // Generate uraian terpisah untuk pengolahan listing dan pengolahan pencacahan
            $uraianPengolahanListing = null;
            $uraianPengolahanPencacahan = null;

            if ($hasListing && $isPengolahanRole) {
                // Untuk pengolahan listing: paksa jumlah_satuan = 0
                $uraianPengolahanListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $alokasi->jumlah_satuan_listing ?? 0,
                    0
                );
            }

            if ($isPengolahanRole && ($alokasi->jumlah_satuan ?? 0) > 0) {
                // Untuk pengolahan pencacahan: paksa jumlah_satuan_listing = 0
                $uraianPengolahanPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $keg->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0,
                    $alokasi->jumlah_satuan ?? 0
                );
            }

            // Fallback: use first available uraian
            $uraianPekerjaan = $uraianListing ?? $uraianPencacahan ?? $this->generateUraianPekerjaan(
                $alokasi->peran,
                $keg->nama_kegiatan,
                (int) $periode->bulan,
                $periode->tahun,
                $alokasi->jumlah_satuan_listing ?? 0,
                $alokasi->jumlah_satuan ?? 0
            );

            $kegiatanList[] = [
                'kode_kegiatan' => $keg->kode_kegiatan,
                'nama_kegiatan' => $keg->nama_kegiatan,
                'jenis_kegiatan' => $keg->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiKegiatan,
                // Tampilkan label tanggal selesai jika valid, jika tidak kosongkan string agar tidak tampil "-" di frontend
                'tanggal_selesai_label' => ($tanggalSelesaiFormatted !== '-' && ! empty($tanggalSelesaiKegiatan)) ? $tanggalSelesaiFormatted : '',
                'tanggal_selesai_formatted' => $tanggalSelesaiFormatted,
                'uraian_pekerjaan' => $uraianPekerjaan,
                'uraian_listing' => $uraianListing,
                'uraian_pencacahan' => $uraianPencacahan,
                'uraian_pengolahan_listing' => $uraianPengolahanListing,
                'uraian_pengolahan_pencacahan' => $uraianPengolahanPencacahan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $alokasi->jumlah_satuan_listing : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'non_response_listing' => ($hasListing && $isPendataanRole) ? $alokasi->non_response_listing : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $alokasi->jumlah_satuan : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'non_response' => $isPendataanRole ? $alokasi->non_response : null,
                'hasil_pengolahan' => $isPengolahanRole ? $alokasi->jumlah_satuan : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $alokasi->jumlah_satuan_listing : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? $rateHonor?->satuanListing?->nama : null,
                'keterangan' => $alokasi->catatan,
                'ketua_tim' => [
                    'nama' => $ketuaTimKegiatan?->name,
                    'nip' => $ketuaTimKegiatan?->nip,
                ],
            ];
        }

        $kegiatanList = $this->sortAndNumberKegiatanLampiran($kegiatanList);

        // Get Kepala BPS
        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        $bastObject = (object) [
            'nomor_bast' => $nomorBast,
            'tanggal_bast' => $tanggalBerakhir,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $ppk->nama,
            'nip_ppk' => $ppk->nip ?? '-',
            'petugas' => [
                'nama' => $petugas->nama,
                'nik' => $petugas->nik ?? '-',
                'alamat' => $petugas->alamat ?? '-',
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => $kegiatanList,
        ];

        return [
            'bast' => $bastObject,
            'nomor_bast' => $nomorBast,
            'nama_kepala' => $kepala?->nama ?? '-',
        ];
    }

    /**
     * Generate nomor BAST otomatis untuk SPK
     */
    private function generateNomorBastForSpk(Carbon $tanggalBast): string
    {
        $tahun = $tanggalBast->year;
        $bulan = $tanggalBast->month;

        // Get all BAST in this month and extract the highest number
        $allBast = Bast::whereYear('tanggal_bast', $tahun)
            ->whereMonth('tanggal_bast', $bulan)
            ->pluck('nomor_bast');

        $maxUrut = 0;
        foreach ($allBast as $nomorBast) {
            // Pattern: PPIS/13730/{urut}/BAST/{tahun}
            if (preg_match('/PPIS\/13730\/(\d+)\/BAST\/\d{4}/', $nomorBast, $matches)) {
                $urut = (int) $matches[1];
                if ($urut > $maxUrut) {
                    $maxUrut = $urut;
                }
            }
        }

        $urut = $maxUrut + 1;
        $bulanRomawi = $this->getRomanMonth($bulan);

        return sprintf('PPIS/13730/%d/BAST/%d', $urut, $tahun);
    }

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth(int $month): string
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romans[$month - 1] ?? 'I';
    }

    /**
     * Generate uraian pekerjaan berdasarkan jenis penugasan, tahapan, dan periode
     */
    private function generateUraianPekerjaan(
        string $jenisPenugasan,
        string $namaKegiatan,
        int $bulan,
        int $tahun,
        int $jumlahSatuanListing = 0,
        int $jumlahSatuan = 0
    ): string {
        $bulanLabel = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$bulan] ?? 'Januari';

        // Tentukan tahapan berdasarkan jumlah satuan
        $isListing = $jumlahSatuanListing > 0;
        $isLapangan = $jumlahSatuan > 0;

        // Generate uraian berdasarkan jenis penugasan dan tahapan
        return match ($jenisPenugasan) {
            'pcl_ppl' => $isListing
                ? "Melakukan pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",

            'pml' => $isListing && ! $isLapangan
                ? "Melakukan pemeriksaan pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : ($isListing && $isLapangan
                    ? "Melakukan pemeriksaan pemutakhiran dan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                    : "Melakukan pemeriksaan pencacahan {$namaKegiatan} bulan {$bulanLabel} {$tahun}"),

            'pengolahan' => $isListing
                ? "Melakukan pengolahan dokumen pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pengolahan dokumen pencacahan lapangan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",

            'pengawas_pengolahan' => $isListing
                ? "Melakukan pemeriksaan pengolahan dokumen pemutakhiran {$namaKegiatan} bulan {$bulanLabel} {$tahun}"
                : "Melakukan pemeriksaan pengolahan dokumen pencacahan lapangan {$namaKegiatan} bulan {$bulanLabel} {$tahun}",
            default => "Melakukan tugas {$namaKegiatan} bulan {$bulanLabel} {$tahun}",
        };
    }

    /**
     * Preview BAST untuk specific SPK
     */
    public function previewForSpk(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        // Decrypt payload
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $request->validate([
            'spk_id' => 'required|integer|exists:spk,id',
            'nomor_bast' => 'nullable|string',
        ]);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->findOrFail($request->spk_id);

        $petugas = $spk->alokasiPetugas?->petugas;
        $bulan = date('m', strtotime($spk->tanggal_mulai_kerja));
        $tahun = date('Y', strtotime($spk->tanggal_mulai_kerja));

        // Ambil semua alokasi untuk petugas yang sama dalam bulan dan tahun yang sama
        // Exclude status 'direvisi' karena tidak perlu masuk ke lampiran
        $allAlokasi = AlokasiPetugas::where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun) {
                $q->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']); // Exclude 'direvisi'
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
                'spk',
            ])
            ->get();

        // Untuk tanggal BAST utama, gunakan tanggal paling akhir dari semua kegiatan
        $tanggalBerakhirPalingAkhir = $allAlokasi->map(function ($alokasi) {
            // Prioritas 1: tanggal_selesai dari periode alokasi (tanggal kegiatan sebenarnya)
            if ($alokasi->periodeAlokasi?->tanggal_selesai) {
                return $alokasi->periodeAlokasi->tanggal_selesai;
            }
            // Prioritas 2: tanggal_selesai_kerja dari SPK
            if ($alokasi->spk?->first()?->tanggal_selesai_kerja) {
                return $alokasi->spk->first()->tanggal_selesai_kerja;
            }

            // Prioritas 3: tanggal_selesai dari alokasi itu sendiri
            return $alokasi->tanggal_selesai ?? null;
        })->filter()->max(); // Gunakan max() untuk tanggal BAST utama

        // Fallback ke tanggal SPK original jika tidak ada yang lain
        if (! $tanggalBerakhirPalingAkhir) {
            $tanggalBerakhirPalingAkhir = $spk->tanggal_selesai_kerja;
        }

        if (! $tanggalBerakhirPalingAkhir) {
            return back()->with('error', 'Tidak ada tanggal selesai kerja pada SPK ini');
        }

        $ketuaTim = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->ketuaTim;

        // Gunakan alokasi nomor yang sama seperti proses generate final agar preview konsisten.
        $noUrutBAST = $request->input('nomor_bast');
        if (! $noUrutBAST) {
            $tanggalBastCarbon = $tanggalBerakhirPalingAkhir instanceof Carbon
                ? $tanggalBerakhirPalingAkhir
                : Carbon::parse($tanggalBerakhirPalingAkhir);

            $noUrutBAST = $this->allocateNomorBastForSpk($spk, $tanggalBastCarbon);
        }

        // Format data untuk preview
        $bastData = [
            'nomor_bast' => $noUrutBAST,
            'tanggal_bast' => $tanggalBerakhirPalingAkhir,
            'tanggal_pelaksanaan' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai' => $tanggalBerakhirPalingAkhir,
            'lokasi_kegiatan' => 'Kota Sawahlunto',
            'nama_ppk' => $spk->nama_ppk,
            'nip_ppk' => $spk->nip_ppk,
            'petugas' => [
                'nama' => $petugas?->nama,
                'nik' => $petugas?->nik,
                'alamat' => $petugas?->alamat,
            ],
            'ketua_tim' => [
                'nama' => $ketuaTim?->name,
                'nip' => $ketuaTim?->nip,
            ],
            'kegiatan_list' => [],
        ];

        // Build kegiatan list dengan lampiran
        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
            $periode = $alokasi->periodeAlokasi;

            if (! $kegiatan || ! $periode) {
                continue;
            }

            $rateHonor = $kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $isPendataanRole = in_array($alokasi->peran, self::PENDATAAN_ROLES, true);
            $isPengolahanRole = in_array($alokasi->peran, self::PENGOLAHAN_ROLES, true);
            $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
            $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();
            $hasListing = ($kegiatan->has_listing_updating ?? false) || $effectiveListingVolume > 0;

            // Cari SPK dari petugas ini untuk kegiatan yang sama di bulan yang sama
            // Tidak harus dari alokasi_id yang sama karena bisa jadi SPK dibuat dari periode lain yang sudah direvisi
            $spkPetugas = Spk::whereHas('alokasiPetugas', function ($q) use ($petugas, $kegiatan, $periode) {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('periodeAlokasi', function ($qq) use ($kegiatan, $periode) {
                        $qq->where('kegiatan_id', $kegiatan->id)
                            ->where('bulan', $periode->bulan)
                            ->where('tahun', $periode->tahun);
                    });
            })->first();
            $nomorSpk = $spkPetugas?->nomor_spk ?? 'Belum ada SPK';

            // Ambil tanggal selesai berdasarkan jenis peran dan tahapan kegiatan
            $tanggalSelesaiArr = [];
            if ($isPengolahanRole) {
                if (! empty($periode->jadwal_pengolahan_listing_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_listing_selesai;
                }
                if (! empty($periode->jadwal_pengolahan_pencacahan_selesai)) {
                    $tanggalSelesaiArr[] = $periode->jadwal_pengolahan_pencacahan_selesai;
                }
            } elseif ($isPendataanRole) {
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
            } else {
                if (! empty($periode->tanggal_selesai)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai;
                }
                if (! empty($periode->tanggal_selesai_listing)) {
                    $tanggalSelesaiArr[] = $periode->tanggal_selesai_listing;
                }
            }

            // Ambil tanggal paling akhir dari semua tahapan
            $tanggalSelesaiKegiatan = null;
            if (! empty($tanggalSelesaiArr)) {
                $tanggalSelesaiKegiatan = collect($tanggalSelesaiArr)->max();
            }

            // Fallback ke tanggal SPK jika tidak ada tanggal dari periode
            if (empty($tanggalSelesaiKegiatan)) {
                $tanggalSelesaiKegiatan = $spkPetugas?->tanggal_selesai_kerja ?? $alokasi->tanggal_selesai ?? 'Belum ada SPK';
            }
            // Ambil ketua tim dari kegiatan ini
            $ketuaTimKegiatan = $kegiatan->ketuaTim;

            // Generate uraian terpisah untuk listing dan pencacahan
            $uraianListing = null;
            $uraianPencacahan = null;

            if ($hasListing && $isPendataanRole) {
                // Untuk listing: paksa jumlah_satuan = 0 agar generate uraian listing
                $uraianListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0 // Force 0 untuk listing
                );
            }

            if ($isPendataanRole && $effectivePencacahanVolume > 0) {
                // Untuk pencacahan: paksa jumlah_satuan_listing = 0 agar generate uraian pencacahan
                $uraianPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0, // Force 0 untuk pencacahan
                    $effectivePencacahanVolume
                );
            }

            // Generate uraian terpisah untuk pengolahan listing dan pengolahan pencacahan
            $uraianPengolahanListing = null;
            $uraianPengolahanPencacahan = null;

            if ($hasListing && $isPengolahanRole) {
                // Untuk pengolahan listing: paksa jumlah_satuan = 0
                $uraianPengolahanListing = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    $effectiveListingVolume,
                    0
                );
            }

            if ($isPengolahanRole && $effectivePencacahanVolume > 0) {
                // Untuk pengolahan pencacahan: paksa jumlah_satuan_listing = 0
                $uraianPengolahanPencacahan = $this->generateUraianPekerjaan(
                    $alokasi->peran,
                    $kegiatan->nama_kegiatan,
                    (int) $periode->bulan,
                    $periode->tahun,
                    0,
                    $effectivePencacahanVolume
                );
            }

            // Fallback: use first available uraian
            $uraianPekerjaan = $uraianListing ?? $uraianPencacahan ?? $this->generateUraianPekerjaan(
                $alokasi->peran,
                $kegiatan->nama_kegiatan,
                (int) $periode->bulan,
                $periode->tahun,
                $effectiveListingVolume,
                $effectivePencacahanVolume
            );

            // Tanggal hari kerja terdekat dari tanggal selesai
            $tanggalSelesaiFinal = $tanggalSelesaiKegiatan;
            if ($tanggalSelesaiKegiatan && $tanggalSelesaiKegiatan !== 'Belum ada SPK') {
                $carbonTanggal = Carbon::parse($tanggalSelesaiKegiatan);
                // Jika Sabtu (6) atau Minggu (7), fallback ke Jumat atau hari kerja sebelumnya
                while (in_array($carbonTanggal->dayOfWeekIso, [6, 7])) {
                    $carbonTanggal->subDay();
                }
                $tanggalSelesaiFinal = $carbonTanggal->format('Y-m-d');
            }
            $bastData['kegiatan_list'][] = [
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'nomor_spk' => $nomorSpk,
                'tanggal_selesai' => $tanggalSelesaiFinal,
                'tanggal_selesai_formatted' => ($tanggalSelesaiFinal && $tanggalSelesaiFinal !== 'Belum ada SPK')
                    ? Carbon::parse($tanggalSelesaiFinal)->locale('id')->isoFormat('D MMMM YYYY')
                    : '-',
                'uraian_pekerjaan' => $uraianPekerjaan,
                'uraian_listing' => $uraianListing,
                'uraian_pencacahan' => $uraianPencacahan,
                'uraian_pengolahan_listing' => $uraianPengolahanListing,
                'uraian_pengolahan_pencacahan' => $uraianPengolahanPencacahan,
                'peran' => $alokasi->peran,
                'hasil_listing' => ($hasListing && $isPendataanRole) ? $effectiveListingVolume : null,
                'satuan_listing' => ($hasListing && $isPendataanRole) ? $rateHonor?->satuanListing?->nama : null,
                'non_response_listing' => ($hasListing && $isPendataanRole) ? $alokasi->non_response_listing : null,
                'hasil_pendataan_lapangan' => $isPendataanRole ? $effectivePencacahanVolume : null,
                'satuan_pendataan' => $isPendataanRole ? $rateHonor?->satuan?->nama : null,
                'non_response' => $isPendataanRole ? $alokasi->non_response : null,
                'hasil_pengolahan' => $isPengolahanRole ? $effectivePencacahanVolume : null,
                'satuan_pengolahan' => $isPengolahanRole ? $rateHonor?->satuan?->nama : null,
                'hasil_pengolahan_listing' => $isPengolahanRole ? $effectiveListingVolume : null,
                'satuan_pengolahan_listing' => $isPengolahanRole ? $rateHonor?->satuanListing?->nama : null,
                'keterangan' => $alokasi->catatan,
                'ketua_tim' => [
                    'nama' => $ketuaTimKegiatan?->name,
                    'nip' => $ketuaTimKegiatan?->nip,
                ],
            ];
        }

        $bastObject = (object) $bastData;

        $kepala = Penandatangan::where('jenis_penandatangan', 'kepala')
            ->where('is_active', true)
            ->first();

        $viewData = [
            'bast' => $bastObject,
            'nomor_bast' => $bastData['nomor_bast'],
            'tanggal_akhir_kegiatan' => Carbon::parse($tanggalBerakhirPalingAkhir)->locale('id')->isoFormat('D MMMM YYYY'),
            'hari' => Carbon::parse($tanggalBerakhirPalingAkhir)->locale('id')->isoFormat('dddd'),
            'menggunakan_fasih' => $this->isMenggunakanFasih($allAlokasi),
            'jabatan_ppk' => 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik',
            'alamat_unit_kerja' => 'Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto',
            'nama_kepala' => $kepala?->nama,
        ];

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bastData['nomor_bast']);
        $pdfMain = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $previewPath = $tempPath.'/bast_main_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($previewPath, $pdfMain->output());

        return response()->file($previewPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_BAST_MAIN_'.$cleanNomorBast.'-'.$bastData['petugas']['nama'].'.pdf"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview lampiran BAST dari bast-lampiran-spk.blade.php.
     * Admin/operator: semua kegiatan dari SPK.
     * Ketua tim: harus menentukan kegiatan_id dan hanya untuk kegiatan yang dikelola.
     */
    public function previewLampiran(Request $request): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return redirect()->route('bast.index')
                ->with('error', 'Silakan buka preview lampiran dari halaman detail BAST.');
        }

        if ($request->hasFile('file')) {
            return $this->uploadPreviewLampiranSigned($request);
        }

        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $user = $request->user();
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        $rules = ['spk_id' => 'required|integer|exists:spk,id'];
        if ($isKetuaTim) {
            $rules['kegiatan_id'] = 'required|integer|exists:kegiatan,id';
        } else {
            $rules['kegiatan_id'] = 'nullable|integer|exists:kegiatan,id';
        }
        $rules['periode_alokasi_id'] = 'nullable|integer|exists:periode_alokasi,id';

        $request->validate($rules);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->findOrFail($request->spk_id);

        $petugas = $spk->alokasiPetugas?->petugas;
        abort_unless($petugas, 404, 'Petugas pada SPK tidak ditemukan.');

        $ppkPenandatangan = Penandatangan::where('jenis_penandatangan', 'ppk')
            ->where('is_active', true)
            ->first();

        $ppk = (object) [
            'nama' => $ppkPenandatangan?->nama ?? 'PPK',
            'nip' => $ppkPenandatangan?->nip,
        ];

        $tanggalAkhir = $spk->tanggal_selesai_kerja
            ? Carbon::parse($spk->tanggal_selesai_kerja)->format('Y-m-d')
            : now()->format('Y-m-d');
        $nomorBastPreview = $this->allocateNomorBastForSpk($spk, Carbon::parse($tanggalAkhir));

        $viewData = $this->prepareBastDataForExport(
            $spk,
            collect([$spk]),
            $nomorBastPreview,
            $tanggalAkhir,
            $ppk
        );

        $kegiatanId = $request->input('kegiatan_id');
        $periodeAlokasiId = (int) $request->input('periode_alokasi_id', 0);

        if ($kegiatanId) {
            $previewRecordQuery = BastKegiatan::query()
                ->whereNull('bast_id')
                ->where('spk_id', $spk->id)
                ->where('kegiatan_id', (int) $kegiatanId);

            if ($periodeAlokasiId > 0) {
                $previewRecordQuery->where('periode_alokasi_id', $periodeAlokasiId);
            }

            $previewRecord = $previewRecordQuery
                ->orderByDesc('signed_uploaded_at')
                ->orderByDesc('generated_at')
                ->first();

            if ($previewRecord) {
                if ($isKetuaTim) {
                    $managedPreview = Kegiatan::query()
                        ->whereKey((int) $kegiatanId)
                        ->where(function ($q) use ($user) {
                            $q->where('ketua_tim_user_id', $user?->id)
                                ->orWhere('pj_lainnya_id', $user?->id);
                        })
                        ->exists();

                    abort_unless($managedPreview, 403, 'Kegiatan tidak ditemukan atau tidak dapat diakses.');
                }

                $signedPath = $this->resolveDocumentAbsolutePath($previewRecord->signed_file_path);
                if ($signedPath && file_exists($signedPath)) {
                    return response()->file($signedPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_Lampiran_Signed_'.$this->sanitizeDocumentSegment($nomorBastPreview).'_'.$this->sanitizeDocumentSegment((string) $previewRecord->kode_kegiatan).'.pdf"',
                        'Cache-Control' => 'no-cache, must-revalidate',
                        'Expires' => '0',
                    ]);
                }

                $draftPath = $this->resolveDocumentAbsolutePath($previewRecord->file_path);
                if ($draftPath && file_exists($draftPath)) {
                    return response()->file($draftPath, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="preview_Lampiran_'.$this->sanitizeDocumentSegment($nomorBastPreview).'_'.$this->sanitizeDocumentSegment((string) $previewRecord->kode_kegiatan).'.pdf"',
                        'Cache-Control' => 'no-cache, must-revalidate',
                        'Expires' => '0',
                    ]);
                }
            }
        }

        if ($kegiatanId) {
            if ($isKetuaTim) {
                $managedQuery = Kegiatan::query()
                    ->whereKey((int) $kegiatanId)
                    ->where(function ($q) use ($user) {
                        $q->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });

                if ($periodeAlokasiId > 0) {
                    $managedQuery->whereHas('periodeAlokasi', function ($query) use ($periodeAlokasiId) {
                        $query->whereKey($periodeAlokasiId);
                    });
                }

                $managed = $managedQuery->exists();

                abort_unless($managed, 403, 'Kegiatan tidak ditemukan atau tidak dapat diakses.');
            }

            $filteredKegiatan = collect($viewData['bast']->kegiatan_list)
                ->filter(function (array $item) use ($kegiatanId, $periodeAlokasiId) {
                    $sameKegiatan = (int) ($item['kegiatan_id'] ?? 0) === (int) $kegiatanId;

                    if (! $sameKegiatan) {
                        return false;
                    }

                    if ($periodeAlokasiId <= 0) {
                        return true;
                    }

                    return (int) ($item['periode_alokasi_id'] ?? 0) === $periodeAlokasiId;
                })
                ->values()
                ->all();

            if (empty($filteredKegiatan)) {
                abort(403, 'Kegiatan tidak ditemukan atau tidak dapat diakses.');
            }

            $viewData['bast']->kegiatan_list = $filteredKegiatan;
        }

        if (empty($viewData['bast']->kegiatan_list)) {
            abort(404, 'Tidak ada data lampiran untuk SPK ini.');
        }

        $pdfLampiran = Pdf::loadView('bast-lampiran-spk', $viewData)
            ->setPaper('a4', 'landscape');

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $previewPath = $tempPath.'/bast_lampiran_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($previewPath, $pdfLampiran->output());

        return response()->file($previewPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_Lampiran_BAST_'.$petugas->nama.'.pdf"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generic lampiran preview route for both stored BAST and preview-only mode.
     */
    public function previewLampiranByReference(Request $request): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        if ($request->filled('bast_hashed_id') || $request->filled('bast_kegiatan_id')) {
            $request->validate([
                'bast_hashed_id' => 'required|string',
                'bast_kegiatan_id' => 'required|integer|exists:bast_kegiatan,id',
            ]);

            $bast = $this->resolveBastFromHashedId((string) $request->input('bast_hashed_id'));
            abort_unless($bast, 404);
            $bastKegiatan = BastKegiatan::query()->findOrFail((int) $request->input('bast_kegiatan_id'));

            return $this->previewStoredLampiran($request, $bast, $bastKegiatan);
        }

        return $this->previewLampiran($request);
    }

    public function generateDownloadLampiranPreview(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $user = $request->user();
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        $rules = [
            'spk_id' => 'required|integer|exists:spk,id',
            'kegiatan_id' => 'required|integer|exists:kegiatan,id',
            'periode_alokasi_id' => 'nullable|integer|exists:periode_alokasi,id',
        ];

        $request->validate($rules);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan.ketuaTim',
        ])->findOrFail((int) $request->input('spk_id'));

        $petugas = $spk->alokasiPetugas?->petugas;
        abort_unless($petugas, 404, 'Petugas pada SPK tidak ditemukan.');

        $kegiatanId = (int) $request->input('kegiatan_id');
        $periodeAlokasiIdFromRequest = (int) $request->input('periode_alokasi_id', 0);
        if ($isKetuaTim) {
            $managedQuery = Kegiatan::query()
                ->whereKey($kegiatanId)
                ->where(function ($q) use ($user) {
                    $q->where('ketua_tim_user_id', $user?->id)
                        ->orWhere('pj_lainnya_id', $user?->id);
                });

            if ($periodeAlokasiIdFromRequest > 0) {
                $managedQuery->whereHas('periodeAlokasi', function ($query) use ($periodeAlokasiIdFromRequest) {
                    $query->whereKey($periodeAlokasiIdFromRequest);
                });
            }

            $managed = $managedQuery->exists();

            abort_unless($managed, 403, 'Kegiatan tidak ditemukan atau tidak dapat diakses.');
        }

        $ppkPenandatangan = Penandatangan::where('jenis_penandatangan', 'ppk')
            ->where('is_active', true)
            ->first();

        $ppk = (object) [
            'nama' => $ppkPenandatangan?->nama ?? 'PPK',
            'nip' => $ppkPenandatangan?->nip,
        ];

        $tanggalAkhir = $spk->tanggal_selesai_kerja
            ? Carbon::parse($spk->tanggal_selesai_kerja)->format('Y-m-d')
            : now()->format('Y-m-d');
        $nomorBast = $this->allocateNomorBastForSpk($spk, Carbon::parse($tanggalAkhir));

        $viewData = $this->prepareBastDataForExport(
            $spk,
            collect([$spk]),
            $nomorBast,
            $tanggalAkhir,
            $ppk
        );

        $kegiatanPayload = collect($viewData['bast']->kegiatan_list)
            ->first(function (array $item) use ($kegiatanId, $periodeAlokasiIdFromRequest) {
                $sameKegiatan = (int) ($item['kegiatan_id'] ?? 0) === $kegiatanId;

                if (! $sameKegiatan) {
                    return false;
                }

                if ($periodeAlokasiIdFromRequest <= 0) {
                    return true;
                }

                return (int) ($item['periode_alokasi_id'] ?? 0) === $periodeAlokasiIdFromRequest;
            });

        abort_if(! $kegiatanPayload, 404, 'Kegiatan lampiran tidak ditemukan.');
        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Lampiran hanya bisa diunduh setelah kegiatan berakhir.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];

        $pdfLampiran = Pdf::loadView('bast-lampiran-spk', $viewData)
            ->setPaper('a4', 'landscape');

        $periodeAlokasiId = (int) ($kegiatanPayload['periode_alokasi_id'] ?? 0);
        $kodeKegiatan = (string) ($kegiatanPayload['kode_kegiatan'] ?? 'KEGIATAN');
        $filename = 'LAMPIRAN_'.$this->sanitizeDocumentSegment($nomorBast).'_'.$this->sanitizeDocumentSegment($kodeKegiatan).'.pdf';
        $storedPath = $this->buildPreviewLampiranRelativePath($spk, $kegiatanId, $periodeAlokasiId, $kodeKegiatan);
        $signedPath = $this->buildPreviewLampiranRelativePath($spk, $kegiatanId, $periodeAlokasiId, $kodeKegiatan, true);

        $this->deleteStoredDocument($storedPath);
        $this->deleteStoredDocument($signedPath);

        $absoluteDirectory = $this->ensureBastExportDirectory('lampiran-preview-draft');
        file_put_contents($absoluteDirectory.DIRECTORY_SEPARATOR.basename($storedPath), $pdfLampiran->output());

        $absolutePath = $this->resolveDocumentAbsolutePath($storedPath);

        abort_unless($absolutePath && file_exists($absolutePath), 500, 'Gagal menyimpan file lampiran.');

        BastKegiatan::query()->updateOrCreate(
            [
                'bast_id' => null,
                'spk_id' => $spk->id,
                'kegiatan_id' => $kegiatanId,
                'periode_alokasi_id' => $periodeAlokasiId,
            ],
            [
                'kode_kegiatan' => $kodeKegiatan,
                'file_path' => $storedPath,
                'signed_file_path' => null,
                'generated_at' => now(),
                'signed_uploaded_at' => null,
            ]
        );

        return response()->download($absolutePath, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function downloadLampiranPreview(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $user = $request->user();
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        $request->validate([
            'spk_id' => 'required|integer|exists:spk,id',
            'kegiatan_id' => 'required|integer|exists:kegiatan,id',
            'periode_alokasi_id' => 'nullable|integer|exists:periode_alokasi,id',
        ]);

        $spk = Spk::query()->findOrFail((int) $request->input('spk_id'));
        $kegiatanId = (int) $request->input('kegiatan_id');
        $periodeAlokasiId = (int) $request->input('periode_alokasi_id', 0);

        if ($isKetuaTim) {
            $managedQuery = Kegiatan::query()
                ->whereKey($kegiatanId)
                ->where(function ($q) use ($user) {
                    $q->where('ketua_tim_user_id', $user?->id)
                        ->orWhere('pj_lainnya_id', $user?->id);
                });

            if ($periodeAlokasiId > 0) {
                $managedQuery->whereHas('periodeAlokasi', function ($query) use ($periodeAlokasiId) {
                    $query->whereKey($periodeAlokasiId);
                });
            }

            abort_unless($managedQuery->exists(), 403, 'Kegiatan tidak ditemukan atau tidak dapat diakses.');
        }

        $recordQuery = BastKegiatan::query()
            ->whereNull('bast_id')
            ->where('spk_id', $spk->id)
            ->where('kegiatan_id', $kegiatanId);

        if ($periodeAlokasiId > 0) {
            $recordQuery->where('periode_alokasi_id', $periodeAlokasiId);
        }

        $record = $recordQuery
            ->orderByDesc('signed_uploaded_at')
            ->orderByDesc('generated_at')
            ->first();

        if ($record) {
            $nomorBast = $this->allocateNomorBastForSpk($spk, Carbon::parse($spk->tanggal_selesai_kerja ?? now()));

            $signedPath = $this->resolveDocumentAbsolutePath($record->signed_file_path);
            if ($signedPath && file_exists($signedPath)) {
                return response()->download(
                    $signedPath,
                    'LAMPIRAN_SIGNED_'.$this->sanitizeDocumentSegment($nomorBast).'_'.$this->sanitizeDocumentSegment((string) $record->kode_kegiatan).'.pdf',
                    [
                        'Content-Type' => 'application/pdf',
                        'Cache-Control' => 'no-cache, must-revalidate',
                    ]
                );
            }

            $draftPath = $this->resolveDocumentAbsolutePath($record->file_path);
            if ($draftPath && file_exists($draftPath)) {
                return response()->download(
                    $draftPath,
                    'LAMPIRAN_'.$this->sanitizeDocumentSegment($nomorBast).'_'.$this->sanitizeDocumentSegment((string) $record->kode_kegiatan).'.pdf',
                    [
                        'Content-Type' => 'application/pdf',
                        'Cache-Control' => 'no-cache, must-revalidate',
                    ]
                );
            }
        }

        return $this->generateDownloadLampiranPreview($request);
    }

    /**
     * Generic lampiran download route for both stored BAST and preview-only mode.
     */
    public function downloadLampiranByReference(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->filled('bast_hashed_id') || $request->filled('bast_kegiatan_id')) {
            $request->validate([
                'bast_hashed_id' => 'required|string',
                'bast_kegiatan_id' => 'required|integer|exists:bast_kegiatan,id',
            ]);

            $bast = $this->resolveBastFromHashedId((string) $request->input('bast_hashed_id'));
            abort_unless($bast, 404);
            $bastKegiatan = BastKegiatan::query()->findOrFail((int) $request->input('bast_kegiatan_id'));

            return $this->generateDownloadLampiran($request, $bast, $bastKegiatan);
        }

        return $this->downloadLampiranPreview($request);
    }

    public function uploadPreviewLampiranSigned(Request $request): RedirectResponse
    {
        $user = $this->getRequestUser($request);
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        $request->validate([
            'spk_id' => 'required|integer|exists:spk,id',
            'kegiatan_id' => 'required|integer|exists:kegiatan,id',
            'periode_alokasi_id' => 'required|integer|exists:periode_alokasi,id',
            'kode_kegiatan' => 'required|string',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan',
        ])->findOrFail((int) $request->input('spk_id'));

        $kegiatanId = (int) $request->input('kegiatan_id');
        $periodeAlokasiId = (int) $request->integer('periode_alokasi_id');

        $fallbackPath = route('bast.list', [
            'bulan' => (int) ($spk->alokasiPetugas?->periodeAlokasi?->bulan ?? 0),
            'tahun' => (int) ($spk->alokasiPetugas?->periodeAlokasi?->tahun ?? now()->year),
            'petugas_id' => (int) $spk->petugas_id,
        ], false);

        if ($isKetuaTim) {
            $managed = PeriodeAlokasi::query()
                ->whereKey($periodeAlokasiId)
                ->where('kegiatan_id', $kegiatanId)
                ->whereHas('kegiatan', function ($query) use ($user) {
                    $query->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });
                })
                ->exists();

            if (! $managed) {
                return $this->redirectToLocalPath($request, $fallbackPath)
                    ->with('error', 'Kegiatan lampiran tidak ditemukan atau tidak dapat diakses.');
            }
        }

        $draftPath = $this->buildPreviewLampiranRelativePath(
            $spk,
            $kegiatanId,
            $periodeAlokasiId,
            (string) $request->string('kode_kegiatan')
        );

        $draftAbsolutePath = $this->resolveDocumentAbsolutePath($draftPath);

        if (! $draftAbsolutePath || ! file_exists($draftAbsolutePath)) {
            return $this->redirectToLocalPath($request, route('bast.index', absolute: false))
                ->with('error', 'Lampiran belum digenerate.');
        }

        $signedPath = $this->buildPreviewLampiranRelativePath(
            $spk,
            $kegiatanId,
            $periodeAlokasiId,
            (string) $request->string('kode_kegiatan'),
            true,
        );

        $this->deleteStoredDocument($signedPath);

        $targetDirectory = $this->ensureBastExportDirectory('lampiran-preview-signed');
        $request->file('file')->move($targetDirectory, basename($signedPath));

        BastKegiatan::query()->updateOrCreate(
            [
                'bast_id' => null,
                'spk_id' => $spk->id,
                'kegiatan_id' => $kegiatanId,
                'periode_alokasi_id' => $periodeAlokasiId,
            ],
            [
                'kode_kegiatan' => (string) $request->string('kode_kegiatan'),
                'signed_file_path' => $signedPath,
                'signed_uploaded_at' => now(),
            ]
        );

        return $this->redirectToLocalPath($request, $fallbackPath)
            ->with('success', 'Lampiran bertanda tangan berhasil diunggah.');
    }

    /**
     * Generic lampiran signed upload route for both stored BAST and preview-only mode.
     */
    public function uploadLampiranSignedByReference(Request $request): RedirectResponse
    {
        if ($request->filled('bast_hashed_id') || $request->filled('bast_kegiatan_id')) {
            $request->validate([
                'bast_hashed_id' => 'required|string',
                'bast_kegiatan_id' => 'required|integer|exists:bast_kegiatan,id',
                'file' => 'required|file|mimes:pdf|max:10240',
            ]);

            $bast = $this->resolveBastFromHashedId((string) $request->input('bast_hashed_id'));
            if (! $bast) {
                return $this->redirectToLocalPath($request, route('bast.index', absolute: false))
                    ->with('error', 'Data BAST tidak ditemukan atau sudah tidak tersedia.');
            }

            $bastKegiatan = BastKegiatan::query()->find((int) $request->input('bast_kegiatan_id'));

            if (! $bastKegiatan) {
                $this->rememberOpenDetailFiltersFromBast($request, $bast);

                return $this->redirectToLocalPath($request, route('bast.open-detail-by-petugas', absolute: false))
                    ->with('error', 'Data lampiran tidak ditemukan.');
            }

            return $this->uploadLampiranSigned($request, $bast, $bastKegiatan);
        }

        return $this->uploadPreviewLampiranSigned($request);
    }

    public function previewStoredLampiran(Request $request, Bast $bast, BastKegiatan $bastKegiatan): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $bastKegiatan->loadMissing('kegiatan');

        $this->ensureBastKegiatanBelongsToBast($bast, $bastKegiatan);
        abort_unless($this->userCanManageLampiran($request, $bastKegiatan) && $this->userCanAccessBast($request, $bast), 403);

        $signedPath = $this->resolveDocumentAbsolutePath($bastKegiatan->signed_file_path);
        if ($signedPath && file_exists($signedPath)) {
            return response()->file($signedPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview_Lampiran_Signed_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => '0',
            ]);
        }

        $generatedPath = $this->resolveDocumentAbsolutePath($bastKegiatan->file_path);
        if ($generatedPath && file_exists($generatedPath)) {
            return response()->file($generatedPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview_Lampiran_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => '0',
            ]);
        }

        $viewData = $this->prepareStoredBastViewData($bast);
        $kegiatanPayload = collect($viewData['bast']->kegiatan_list)->first(function (array $item) use ($bastKegiatan) {
            return (int) ($item['kegiatan_id'] ?? 0) === (int) $bastKegiatan->kegiatan_id
                && (int) ($item['periode_alokasi_id'] ?? 0) === (int) $bastKegiatan->periode_alokasi_id;
        });

        abort_if(! $kegiatanPayload, 404, 'Lampiran kegiatan tidak ditemukan.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];

        $pdfLampiran = Pdf::loadView('bast-lampiran-spk', $viewData)
            ->setPaper('a4', 'landscape');

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $previewPath = $tempPath.'/bast_lampiran_detail_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($previewPath, $pdfLampiran->output());

        return response()->file($previewPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_Lampiran_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download PDF BAST yang sudah tersimpan
     */
    public function downloadPdf(Request $request, Bast $bast): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan.kegiatan');

        abort_unless($this->userCanAccessBast($request, $bast), 403);

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bast->nomor_bast);
        $filename = 'BAST_MAIN-'.$cleanNomorBast.'.pdf';
        $generatedPath = $this->resolveDocumentAbsolutePath($bast->file_path);

        if ($generatedPath && file_exists($generatedPath)) {
            return response()->download($generatedPath, $filename, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        $viewData = $this->prepareStoredBastViewData($bast);
        $pdfMain = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');

        return $pdfMain->download($filename);
    }

    public function downloadSignedPdf(Request $request, Bast $bast): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan');

        abort_unless($this->userCanAccessBast($request, $bast), 403);

        $path = $bast->bastKegiatan->isEmpty()
            ? ($bast->signed_file_path ?: $bast->main_signed_file_path)
            : $bast->signed_file_path;

        $absolutePath = $this->resolveDocumentAbsolutePath($path);
        abort_unless($absolutePath && file_exists($absolutePath), 404, 'BAST bertanda tangan belum tersedia.');

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bast->nomor_bast);

        return response()->download($absolutePath, 'BAST_SIGNED-'.$cleanNomorBast.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Download the compiled BAST (main + all lampiran) from storage.
     */
    public function downloadCompiledBast(Request $request, Bast $bast): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan.kegiatan');

        abort_unless($this->userCanAccessBast($request, $bast), 403);

        $absolutePath = $this->resolveDocumentAbsolutePath($bast->compiled_file_path);
        abort_unless($absolutePath && file_exists($absolutePath), 404, 'File gabungan belum tersedia. Pastikan BAST dan semua lampiran sudah digenerate.');

        $cleanNomorBast = str_replace(['/', '\\'], '-', $bast->nomor_bast);

        return response()->download($absolutePath, 'BAST_GABUNGAN-'.$cleanNomorBast.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Generate lampiran (if not yet generated) and immediately download it.
     * Combines the generate + download flow into one request.
     */
    public function generateDownloadLampiran(Request $request, Bast $bast, BastKegiatan $bastKegiatan): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $bastKegiatan->loadMissing('kegiatan');

        $this->ensureBastKegiatanBelongsToBast($bast, $bastKegiatan);
        abort_unless($this->userCanManageLampiran($request, $bastKegiatan) && $this->userCanAccessBast($request, $bast), 403);

        $draftFilename = 'LAMPIRAN_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf';
        $signedFilename = 'LAMPIRAN_SIGNED_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf';

        // Priority: signed file if exists, then generated draft file.
        if ($bastKegiatan->signed_file_path) {
            $absoluteSignedPath = $this->resolveDocumentAbsolutePath($bastKegiatan->signed_file_path);
            if ($absoluteSignedPath && file_exists($absoluteSignedPath)) {
                return response()->download($absoluteSignedPath, $signedFilename, [
                    'Content-Type' => 'application/pdf',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]);
            }
        }

        if ($bastKegiatan->file_path) {
            $absoluteDraftPath = $this->resolveDocumentAbsolutePath($bastKegiatan->file_path);
            if ($absoluteDraftPath && file_exists($absoluteDraftPath)) {
                return response()->download($absoluteDraftPath, $draftFilename, [
                    'Content-Type' => 'application/pdf',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]);
            }
        }

        // Generate, save, then download
        $viewData = $this->prepareStoredBastViewData($bast);
        $kegiatanPayload = collect($viewData['bast']->kegiatan_list)->first(function (array $item) use ($bastKegiatan) {
            return (int) ($item['kegiatan_id'] ?? 0) === (int) $bastKegiatan->kegiatan_id
                && (int) ($item['periode_alokasi_id'] ?? 0) === (int) $bastKegiatan->periode_alokasi_id;
        });

        abort_if(! $kegiatanPayload, 404, 'Lampiran kegiatan tidak dapat disiapkan dari data alokasi saat ini.');
        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Lampiran hanya bisa diunduh setelah kegiatan berakhir.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];

        $pdfLampiran = Pdf::loadView('bast-lampiran-spk', $viewData)
            ->setPaper('a4', 'landscape');

        $this->deleteStoredDocument($bastKegiatan->file_path);
        $this->deleteStoredDocument($bastKegiatan->signed_file_path);

        $filePath = $this->writePdfToPublicDirectory($draftFilename, $pdfLampiran->output(), 'lampiran');

        $bastKegiatan->update([
            'file_path' => $filePath,
            'signed_file_path' => null,
            'generated_at' => now(),
            'signed_uploaded_at' => null,
        ]);

        $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));

        $absolutePath = $this->resolveDocumentAbsolutePath($filePath);
        abort_unless($absolutePath && file_exists($absolutePath), 500, 'Gagal menyimpan file lampiran.');

        return response()->download($absolutePath, $draftFilename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function generateLampiran(Request $request, Bast $bast, BastKegiatan $bastKegiatan): RedirectResponse
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $bastKegiatan->loadMissing('kegiatan');

        $this->ensureBastKegiatanBelongsToBast($bast, $bastKegiatan);
        abort_unless($this->userCanManageLampiran($request, $bastKegiatan) && $this->userCanAccessBast($request, $bast), 403);

        $viewData = $this->prepareStoredBastViewData($bast);
        $kegiatanPayload = collect($viewData['bast']->kegiatan_list)->first(function (array $item) use ($bastKegiatan) {
            return (int) ($item['kegiatan_id'] ?? 0) === (int) $bastKegiatan->kegiatan_id
                && (int) ($item['periode_alokasi_id'] ?? 0) === (int) $bastKegiatan->periode_alokasi_id;
        });

        if (! $kegiatanPayload) {
            return redirect()->back()->with('error', 'Lampiran kegiatan tidak dapat disiapkan dari data alokasi saat ini.');
        }

        if (! $this->isLampiranGenerationAllowed($kegiatanPayload)) {
            return redirect()->back()->with('error', 'Lampiran hanya bisa digenerate setelah kegiatan berakhir.');
        }

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];

        $pdfLampiran = Pdf::loadView('bast-lampiran-spk', $viewData)
            ->setPaper('a4', 'landscape');

        $filename = 'LAMPIRAN_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf';

        $this->deleteStoredDocument($bastKegiatan->file_path);
        $this->deleteStoredDocument($bastKegiatan->signed_file_path);

        $filePath = $this->writePdfToPublicDirectory($filename, $pdfLampiran->output(), 'lampiran');

        $bastKegiatan->update([
            'file_path' => $filePath,
            'signed_file_path' => null,
            'generated_at' => now(),
            'signed_uploaded_at' => null,
        ]);

        $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));

        return redirect()->back()->with('success', 'Lampiran berhasil digenerate.');
    }

    public function downloadLampiran(Request $request, Bast $bast, BastKegiatan $bastKegiatan): \Symfony\Component\HttpFoundation\Response
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $bastKegiatan->loadMissing('kegiatan');

        $this->ensureBastKegiatanBelongsToBast($bast, $bastKegiatan);
        abort_unless($this->userCanManageLampiran($request, $bastKegiatan) && $this->userCanAccessBast($request, $bast), 403);

        $path = $bastKegiatan->signed_file_path ?: $bastKegiatan->file_path;
        $absolutePath = $this->resolveDocumentAbsolutePath($path);
        abort_unless($absolutePath && file_exists($absolutePath), 404, 'Lampiran belum tersedia untuk diunduh.');

        $filenamePrefix = $bastKegiatan->signed_file_path ? 'LAMPIRAN_SIGNED_' : 'LAMPIRAN_';
        $filename = $filenamePrefix.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'.pdf';

        return response()->download($absolutePath, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function uploadLampiranSigned(Request $request, Bast $bast, BastKegiatan $bastKegiatan): RedirectResponse
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $bastKegiatan->loadMissing('kegiatan');

        $this->ensureBastKegiatanBelongsToBast($bast, $bastKegiatan);
        abort_unless($this->userCanManageLampiran($request, $bastKegiatan) && $this->userCanAccessBast($request, $bast), 403);

        if (! $bastKegiatan->file_path) {
            $this->rememberOpenDetailFiltersFromBast($request, $bast);

            return $this->redirectToLocalPath($request, route('bast.open-detail-by-petugas', absolute: false))
                ->with('error', 'Lampiran belum digenerate.');
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $this->deleteStoredDocument($bastKegiatan->signed_file_path);

        $uploadedFile = $request->file('file');
        $filename = 'LAMPIRAN_SIGNED_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'_'.$this->sanitizeDocumentSegment($bastKegiatan->kode_kegiatan).'_'.time().'.pdf';
        $targetDirectory = $this->ensureBastExportDirectory('lampiran-signed');
        $uploadedFile->move($targetDirectory, $filename);

        $bastKegiatan->update([
            'signed_file_path' => trim('bast-export/lampiran-signed/'.$filename, '/'),
            'signed_uploaded_at' => now(),
        ]);

        $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));

        $this->rememberOpenDetailFiltersFromBast($request, $bast);

        return $this->redirectToLocalPath($request, route('bast.open-detail-by-petugas', absolute: false))
            ->with('success', 'Lampiran bertanda tangan berhasil diunggah.');
    }

    /**
     * Download all BAST files in a month as ZIP
     */
    public function downloadAll(Request $request)
    {
        $user = $this->getRequestUser($request);
        $isKetuaTim = $user?->active_role === 'ketua_tim';

        abort_unless($this->userCanManageBastMain($request) || $isKetuaTim, 403);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('bast.index')->with('error', 'Bulan dan tahun harus diisi');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $isLegacyMode = (int) $tahun < 2026 || ((int) $tahun === 2026 && (int) $bulan < 4);

        $allBast = Bast::query()
            ->whereHas('periodeAlokasi', function ($query) use ($tahun, $bulanFormatted) {
                $query->where('tahun', $tahun)
                    ->where('bulan', $bulanFormatted);
            })
            ->when($isKetuaTim, function ($query) use ($user, $tahun, $bulanFormatted) {
                $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $tahun, $bulanFormatted) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun)
                        ->whereHas('kegiatan', function ($qk) use ($user) {
                            $qk->where(function ($sub) use ($user) {
                                $sub->where('ketua_tim_user_id', $user?->id)
                                    ->orWhere('pj_lainnya_id', $user?->id);
                            });
                        });
                })->pluck('id')->toArray();

                if (empty($alokasiIds)) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->whereHas('spk', function ($q) use ($alokasiIds) {
                    $q->where(function ($inner) use ($alokasiIds) {
                        foreach ($alokasiIds as $id) {
                            $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                        }
                    });
                });
            })
            ->orderBy('nomor_bast')
            ->get();

        // Abort 403 if not all files ready for download
        if ($isLegacyMode) {
            abort_unless(
                $allBast->isNotEmpty() && $allBast->every(fn (Bast $b) => filled($b->signed_file_path)),
                403
            );
        } else {
            abort_unless(
                $allBast->isNotEmpty() && $allBast->every(fn (Bast $b) => filled($b->compiled_file_path)),
                403
            );
        }

        $documents = $allBast->map(function (Bast $bast) use ($isLegacyMode) {
            $path = $isLegacyMode ? $bast->signed_file_path : $bast->compiled_file_path;

            return [
                'bast' => $bast,
                'path' => $path,
            ];
        })->filter(fn (array $item) => filled($item['path']))->values();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada BAST dengan file untuk diunduh');
        }

        // Create ZIP file with deterministic name (no timestamp for CDN caching)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $userSuffix = $isKetuaTim ? "_{$user->id}" : '';
        $zipFileName = $isLegacyMode
            ? "BAST_Signed_{$bulanLabel}_{$tahun}{$userSuffix}.zip"
            : "BAST_{$bulanLabel}_{$tahun}{$userSuffix}.zip";

        // Ensure downloads directory exists
        $downloadsDir = public_path('downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir.'/'.$zipFileName;

        // Check if ZIP exists and validate cache
        $shouldRegenerate = true;
        if (file_exists($zipPath)) {
            $zipModTime = filemtime($zipPath);

            // Check if any BAST was updated after ZIP creation
            $latestBastUpdate = $documents->max(fn (array $item) => $item['bast']->updated_at?->timestamp ?? 0) ?? 0;

            // Reuse if ZIP is newer than latest BAST update
            if ($zipModTime > $latestBastUpdate) {
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
        // Add each BAST file to ZIP - prioritize signed_file_path if available
        foreach ($documents as $document) {
            $filePath = $this->resolveDocumentAbsolutePath($document['path']);
            if ($filePath && file_exists($filePath)) {
                $zip->addFile($filePath, basename($document['path']));
                $filesAdded++;
            }
        }

        // Check if any files were actually added
        if ($filesAdded === 0) {
            $zip->close();
            @unlink($zipPath); // Delete empty zip

            return redirect()->back()->with('error', 'Tidak ada file BAST yang valid untuk diunduh. File mungkin sudah dihapus atau dipindahkan.');
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

    /**
     * Generate preview PDF for BAST
     */
    public function preview(Request $request)
    {
        // First do basic validation
        $basicValidated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        // Ambil data kegiatan
        $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

        // Nomor dan tanggal
        $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);
        $tanggalBast = Carbon::parse($validated['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // PPK
        $penandatangan = Penandatangan::ppk()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $namaPpk = $penandatangan ? $this->stripGelar($penandatangan->nama) : 'N/A';

        // Periode
        $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
        $bulanLabel = $targetPeriode?->bulan
            ? Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
            : $tanggalBast->isoFormat('MMMM');
        $tahunPeriode = $targetPeriode?->tahun ?? (int) $tanggalBast->year;

        // Kepala BPS
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();
        $kepalaBps = $kepala ? $this->stripGelar($kepala->nama) : null;

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            }
        }

        // Data utama
        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'tanggal_angka' => $tanggalBast->day,
            'bulan_label' => $bulanLabel,
            'tahun' => $tahunPeriode,
            'nama_ppk' => $namaPpk,
            'nip_ppk' => $penandatangan->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $validated['menggunakan_fasih'],
            'petugas' => $validated['petugas'],
            'dokumen_rekap' => $validated['dokumen_rekap'] ?? [],
            'instrumen_listing' => $validated['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $validated['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => $kepalaBps,
        ];

        // PDF utama
        $pdfMain = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // PDF lampiran: selalu render sebagai halaman terpisah
        $pdfLampiran = Pdf::loadView('bast-lampiran', $viewData)
            ->setPaper('a4', 'landscape');
        $lampiranContent = $pdfLampiran->output();

        // Gabungkan PDF
        $merged = $this->mergePdfStrings([$mainContent, $lampiranContent]);
        $fileName = 'BAST_PREVIEW_'.str_replace('/', '-', $nomorBast).'.pdf';

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath.'/bast_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($tempFile, $merged);
        unset($merged);

        return response()->file($tempFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // First do basic validation
        $basicValidated = $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'bulan' => 'nullable|string|min:1|max:2',
            'tahun' => 'nullable|integer',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ]);

        // Check if we need to validate instruments based on data availability
        $alokasiPetugas = collect($basicValidated['petugas']);

        // Check for listing data (pencacah roles) - treat null as 0
        $hasActualListingData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_listing'] ?? 0) > 0;
        });

        // Check for pendataan data (pencacah roles) - treat null as 0
        $hasActualPendataanData = $alokasiPetugas->some(function ($petugas) {
            return ($petugas['hasil_pendataan_lapangan'] ?? 0) > 0;
        });

        // Dynamic validation for instruments based on data
        $instrumentValidation = [];
        if ($hasActualListingData || $hasActualPendataanData) {
            // Only require if we have actual data to report
            if ($hasActualListingData) {
                $instrumentValidation['instrumen_listing'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_listing'] = 'nullable|string';
            }
            if ($hasActualPendataanData) {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'required|string';
            } else {
                $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
            }
        } else {
            // No data, instruments are optional
            $instrumentValidation['instrumen_listing'] = 'nullable|string';
            $instrumentValidation['instrumen_pendataan_lapangan'] = 'nullable|string';
        }

        // Re-validate with conditional instrument rules
        $validated = $request->validate(array_merge([
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'tanggal_bast' => 'required|date',
            'menggunakan_fasih' => 'required|boolean',
            'petugas' => 'required|array|min:1',
            'petugas.*.petugas_id' => 'required|exists:petugas,id',
            'petugas.*.spk_id' => 'required|exists:spk,id',
            'petugas.*.nomor_spk' => 'required|string',
            'petugas.*.nama_petugas' => 'required|string',
            'petugas.*.hasil_listing' => 'nullable|numeric',
            'petugas.*.satuan_listing' => 'nullable|string',
            'petugas.*.instrumen_listing' => 'nullable|string',
            'petugas.*.hasil_pendataan_lapangan' => 'nullable|numeric',
            'petugas.*.satuan_pendataan_lapangan' => 'nullable|string',
            'petugas.*.instrumen_pendataan_lapangan' => 'nullable|string',
            'petugas.*.hasil_pengolahan' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan' => 'nullable|string',
            'petugas.*.hasil_pengolahan_listing' => 'nullable|numeric',
            'petugas.*.satuan_pengolahan_listing' => 'nullable|string',
            'petugas.*.catatan' => 'nullable|string',
            'dokumen_rekap' => 'nullable|array',
            'dokumen_rekap.*.nama' => 'nullable|string',
            'dokumen_rekap.*.kode' => 'nullable|string',
            'dokumen_rekap.*.didata' => 'nullable|numeric',
            'dokumen_rekap.*.non_respon' => 'nullable|numeric',
            'dokumen_rekap.*.keterangan' => 'nullable|string',
        ], $instrumentValidation));

        DB::beginTransaction();

        try {
            $kegiatan = Kegiatan::with('ketuaTim')->findOrFail($validated['kegiatan_id']);

            // Get periode alokasi ID - use bulan/tahun if provided
            $targetPeriode = null;
            if (! empty($validated['bulan']) && ! empty($validated['tahun'])) {
                $targetPeriode = PeriodeAlokasi::where('kegiatan_id', $validated['kegiatan_id'])
                    ->where('bulan', $validated['bulan'])
                    ->where('tahun', $validated['tahun'])
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->orderBy('status', 'desc') // perubahan > dikirim alphabetically
                    ->first();
            }

            // Fallback to getTargetPeriode if not found
            if (! $targetPeriode) {
                $targetPeriode = $this->getTargetPeriode($validated['kegiatan_id']);
            }

            if (! $targetPeriode) {
                throw new \RuntimeException('Periode alokasi tidak ditemukan untuk kegiatan ini.');
            }

            if (! ($targetPeriode instanceof PeriodeAlokasi)) {
                Log::error('Invalid targetPeriode type', [
                    'type' => gettype($targetPeriode),
                    'class' => is_object($targetPeriode) ? get_class($targetPeriode) : null,
                ]);
                throw new \RuntimeException('Periode alokasi tidak valid.');
            }

            $periodeId = $targetPeriode->id;

            // Get PPK
            // Get active PPK from penandatangan (by jenis_penandatangan + active + valid date range)
            $penandatangan = Penandatangan::ppk()
                ->active()
                ->where(function ($q) {
                    $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
                })
                ->where(function ($q) {
                    $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
                })
                ->orderByDesc('periode_mulai')
                ->first();

            // Generate nomor BAST
            $nomorBast = $this->generateNomorBast($validated['kegiatan_id']);

            $bulanLabel = $targetPeriode?->bulan
                ? Carbon::create((int) $targetPeriode->tahun, (int) $targetPeriode->bulan)->isoFormat('MMMM')
                : Carbon::parse($validated['tanggal_bast'])->isoFormat('MMMM');
            $tahunPeriode = $targetPeriode?->tahun ?? (int) Carbon::parse($validated['tanggal_bast'])->year;

            // Ambil data petugas dari AlokasiPetugas untuk periode ini
            // Fetch ALL allocations for each petugas in the same bulan/tahun across all kegiatan
            $bulanTarget = $targetPeriode->bulan;
            $tahunTarget = $targetPeriode->tahun;

            // Get all periode alokasi in the same bulan/tahun
            $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanTarget)
                ->where('tahun', $tahunTarget)
                ->whereIn('status', ['dikirim', 'perubahan', 'disetujui'])
                ->pluck('id');

            // Get all alokasi petugas in that period
            $allAlokasiPetugas = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->where(function ($query) {
                    $query->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->with([
                    'spk' => function ($query) {
                        $query->orderByDesc('created_at')->limit(1);
                    },
                    'petugas',
                    'periodeAlokasi.kegiatan',
                ])
                ->get();

            // Group by petugas and aggregate their work across all activities
            $petugasGrouped = $allAlokasiPetugas->groupBy('petugas_id');

            // Collect kegiatan details for attachments
            $kegiatanAttachments = $allAlokasiPetugas->map(function ($alokasi) {
                return [
                    'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
                    'periode_alokasi_id' => $alokasi->periode_alokasi_id,
                    'kode_kegiatan' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                    'bulan' => $alokasi->periodeAlokasi->bulan,
                    'tahun' => $alokasi->periodeAlokasi->tahun,
                    'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan,
                ];
            })->unique(function ($item) {
                return $item['kegiatan_id'].'_'.$item['periode_alokasi_id'];
            })->values();

            $petugas = $petugasGrouped->map(function ($alokasiGroup, $petugasId) {
                // Get the first alokasi to get petugas info and latest SPK
                $firstAlokasi = $alokasiGroup->first();
                $latestSpk = $firstAlokasi->spk?->first(); // relasi HasMany, ambil yang pertama

                // Aggregate results across all activities for this petugas
                $totalHasilListing = 0;
                $totalHasilPendataanLapangan = 0;
                $totalHasilPengolahan = 0;
                $totalHasilPengolahanListing = 0;

                $satuanListing = null;
                $satuanPendataanLapangan = null;
                $satuanPengolahan = null;
                $satuanPengolahanListing = null;

                $peranList = [];
                $catatanList = [];

                foreach ($alokasiGroup as $alokasi) {
                    $isPendataanRole = in_array($alokasi->peran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                    $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);
                    $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
                    $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();

                    if ($isPendataanRole) {
                        $totalHasilListing += $effectiveListingVolume;
                        $totalHasilPendataanLapangan += $effectivePencacahanVolume;
                        // Get satuan from the first non-null value
                        $satuanListing = $satuanListing ?? $alokasi->satuan_listing;
                        $satuanPendataanLapangan = $satuanPendataanLapangan ?? $alokasi->satuan_pendataan_lapangan;
                    }

                    if ($isPengolahanRole) {
                        $totalHasilPengolahan += $effectivePencacahanVolume;
                        $totalHasilPengolahanListing += $effectiveListingVolume;
                        // Get satuan from the first non-null value
                        $satuanPengolahan = $satuanPengolahan ?? $alokasi->satuan_pengolahan;
                        $satuanPengolahanListing = $satuanPengolahanListing ?? $alokasi->satuan_listing;
                    }

                    // Collect unique peran
                    if (! in_array($alokasi->peran, $peranList)) {
                        $peranList[] = $alokasi->peran;
                    }

                    // Collect non-empty catatan
                    if (! empty($alokasi->catatan)) {
                        $catatanList[] = $alokasi->catatan;
                    }
                }

                // Determine primary peran (use the first one or most common)
                $primaryPeran = $peranList[0] ?? 'pcl_ppl';
                $isPendataanRole = in_array($primaryPeran, ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'], true);
                $isPengolahanRole = in_array($primaryPeran, ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'], true);

                return [
                    'petugas_id' => $petugasId,
                    'spk_id' => $latestSpk?->id,
                    'nomor_spk' => $latestSpk?->nomor_spk ?? '-',
                    'nama_petugas' => $firstAlokasi->petugas?->nama ?? '-',
                    'peran' => $primaryPeran,
                    'hasil_listing' => $isPendataanRole && $totalHasilListing > 0 ? $totalHasilListing : null,
                    'satuan_listing' => $isPendataanRole ? $satuanListing : null,
                    'instrumen_listing' => $isPendataanRole ? ($firstAlokasi->instrumen_listing ?? null) : null,
                    'hasil_pendataan_lapangan' => $isPendataanRole && $totalHasilPendataanLapangan > 0 ? $totalHasilPendataanLapangan : null,
                    'satuan_pendataan_lapangan' => $isPendataanRole ? $satuanPendataanLapangan : null,
                    'instrumen_pendataan_lapangan' => $isPendataanRole ? ($firstAlokasi->instrumen_pendataan_lapangan ?? null) : null,
                    'hasil_pengolahan' => $isPengolahanRole && $totalHasilPengolahan > 0 ? $totalHasilPengolahan : null,
                    'satuan_pengolahan' => $isPengolahanRole ? $satuanPengolahan : null,
                    'hasil_pengolahan_listing' => $isPengolahanRole && $totalHasilPengolahanListing > 0 ? $totalHasilPengolahanListing : null,
                    'satuan_pengolahan_listing' => $isPengolahanRole ? $satuanPengolahanListing : null,
                    'catatan' => implode('; ', $catatanList),
                ];
            })->values()->toArray();

            // Filter hanya petugas yang punya SPK
            $petugas = array_filter($petugas, function ($p) {
                return ! empty($p['spk_id']);
            });
            $petugas = array_values($petugas); // Re-index array

            // Inject ke $validated agar generateBastPdf menggunakan data petugas dari alokasi
            $validated['petugas'] = $petugas;

            // Cari NIP ketua tim dari data petugas dengan nama yang sama
            $namaKetuaTim = $kegiatan->ketuaTim->name ?? null;
            $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;

            // Prioritas: cari dari data petugas dengan nama yang sama
            if ($namaKetuaTim) {
                $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
                if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                    $nipKetuaTim = $petugasKetuaTim->nip;
                }
            }

            // Create BAST (without file first)
            $bast = Bast::create([
                'nomor_bast' => $nomorBast,
                'kegiatan_id' => $validated['kegiatan_id'],
                'periode_alokasi_id' => $periodeId,
                'spk_id' => $validated['petugas'][0]['spk_id'], // Take first SPK as reference
                'tanggal_bast' => $validated['tanggal_bast'],
                'tanggal_serah_terima' => $validated['tanggal_bast'],
                'menggunakan_fasih' => $validated['menggunakan_fasih'],
                'uraian_pekerjaan' => $kegiatan->nama_kegiatan.' Bulan '.$bulanLabel.' Tahun '.$tahunPeriode,
                'nama_ketua_tim' => $namaKetuaTim,
                'nip_ketua_tim' => $nipKetuaTim,
                'nama_ppk' => $penandatangan ? $this->stripGelar($penandatangan->nama) : null,
                'nip_ppk' => $penandatangan?->nip ?? null,
                'file_path' => null, // Will be updated after PDF generation
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create BAST Petugas records
            foreach ($validated['petugas'] as $petugasData) {
                BastPetugas::create([
                    'bast_id' => $bast->id,
                    'petugas_id' => $petugasData['petugas_id'],
                    'spk_id' => $petugasData['spk_id'],
                    'nomor_spk' => $petugasData['nomor_spk'],
                    'nama_petugas' => $petugasData['nama_petugas'],
                    'hasil_listing' => $petugasData['hasil_listing'],
                    'satuan_listing' => $petugasData['satuan_listing'],
                    'instrumen_listing' => $petugasData['instrumen_listing'] ?? null,
                    'hasil_pendataan_lapangan' => $petugasData['hasil_pendataan_lapangan'],
                    'satuan_pendataan_lapangan' => $petugasData['satuan_pendataan_lapangan'],
                    'instrumen_pendataan_lapangan' => $petugasData['instrumen_pendataan_lapangan'] ?? null,
                    'hasil_pengolahan_listing' => $petugasData['hasil_pengolahan_listing'],
                    'satuan_pengolahan_listing' => $petugasData['satuan_pengolahan_listing'],
                    'hasil_pengolahan' => $petugasData['hasil_pengolahan'],
                    'satuan_pengolahan' => $petugasData['satuan_pengolahan'],
                    'catatan' => $petugasData['catatan'] ?? null,
                ]);
            }

            // Create BAST Kegiatan records (activity attachments)
            foreach ($kegiatanAttachments as $attachment) {
                BastKegiatan::create([
                    'bast_id' => $bast->id,
                    'kegiatan_id' => $attachment['kegiatan_id'],
                    'periode_alokasi_id' => $attachment['periode_alokasi_id'],
                    'kode_kegiatan' => $attachment['kode_kegiatan'],
                    'nama_kegiatan' => $attachment['nama_kegiatan'],
                    'bulan' => $attachment['bulan'],
                    'tahun' => $attachment['tahun'],
                    'jenis_kegiatan' => $attachment['jenis_kegiatan'],
                ]);
            }

            // Generate PDF after data saved to database
            $filePath = $this->generateBastPdf(
                $kegiatan,
                $validated,
                $nomorBast,
                $penandatangan,
                $bulanLabel,
                $tahunPeriode
            );

            // Update BAST with file path
            $bast->update(['file_path' => $filePath]);

            $this->adoptPreviewLampiranFiles($bast->fresh('bastKegiatan'));

            DB::commit();

            ActivityLog::log(
                'Buat BAST',
                'bast',
                "Berhasil membuat BAST: {$nomorBast} untuk {$kegiatan->nama_kegiatan} periode {$bulanLabel} {$tahunPeriode}",
                'success',
                [
                    'bast_id' => $bast->id,
                    'nomor_bast' => $nomorBast,
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'bulan' => $bulanLabel,
                    'tahun' => $tahunPeriode,
                    'jumlah_petugas' => count($validated['petugas']),
                ]
            );

            return redirect()->route('bast.index')
                ->with('success', 'BAST berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal membuat BAST: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Bast $bast): Response
    {
        $bast->load([
            'kegiatan',
            'periodeAlokasi',
            'bastPetugas.petugas',
            'bastPetugas.spk',
            'bastKegiatan.kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,ketua_tim_user_id,pj_lainnya_id',
            'createdBy:id,name',
            'spk.alokasiPetugas.petugas',
        ]);

        $user = $this->getRequestUser($request);
        $canManageMain = $this->userCanManageBastMain($request);
        $periode = $bast->periodeAlokasi;
        $bulanFormatted = str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT);

        $isKetuaTim = $user?->active_role === 'ketua_tim';

        abort_unless($canManageMain || $isKetuaTim, 403);

        $bulanLabel = $this->getBulanLabel((int) $periode->bulan);
        $viewData = $this->prepareStoredBastViewData($bast);
        $this->syncBastKegiatanFromPayload($bast, $viewData['bast']->kegiatan_list ?? []);
        $bast->load('bastKegiatan.kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,ketua_tim_user_id,pj_lainnya_id');
        $kegiatanPayloadMap = collect($viewData['bast']->kegiatan_list)
            ->keyBy(fn (array $item) => $this->makeBastKegiatanKey($item['kegiatan_id'], $item['periode_alokasi_id']));
        $isLegacyMode = (int) $periode->tahun < 2026
            || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4);

        $bastList = $this->buildBastListForPeriod($periode, $canManageMain, $isKetuaTim, $request, $bast);

        // Include eligible petugas without BAST in the period so unavailable data is still visible.
        $existingBastSpkIds = Bast::whereHas('periodeAlokasi', function ($q) use ($periode) {
            $q->where('bulan', $periode->bulan)->where('tahun', $periode->tahun);
        })->pluck('spk_id')->filter()->unique();

        $eligibleWithoutBast = Spk::with('alokasiPetugas.petugas')
            ->whereNotIn('id', $existingBastSpkIds)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $periode) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
            ->when($isKetuaTim, function ($query) use ($user, $bulanFormatted, $periode) {
                $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $bulanFormatted, $periode) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $periode->tahun)
                        ->whereHas('kegiatan', function ($qk) use ($user) {
                            $qk->where(function ($sub) use ($user) {
                                $sub->where('ketua_tim_user_id', $user?->id)
                                    ->orWhere('pj_lainnya_id', $user?->id);
                            });
                        });
                })
                    ->pluck('id')
                    ->toArray();

                if (empty($alokasiIds)) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->where(function ($inner) use ($alokasiIds) {
                    foreach ($alokasiIds as $id) {
                        $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                    }
                });
            })
            ->get()
            ->map(function ($spk) {
                $petugas = $spk->alokasiPetugas?->petugas;

                return [
                    'petugas_nama' => $petugas?->nama ?? 'Petugas tidak diketahui',
                    'petugas_id' => $petugas?->id,
                ];
            })
            ->sortBy('petugas_nama')
            ->values()
            ->toArray();

        $spk = $bast->spk;
        $petugas = $spk?->alokasiPetugas?->petugas;
        $lampiranList = $bast->bastKegiatan
            ->sortBy('nama_kegiatan')
            ->values()
            ->map(function (BastKegiatan $item) use ($kegiatanPayloadMap, $request) {
                $payload = $kegiatanPayloadMap->get(
                    $this->makeBastKegiatanKey($item->kegiatan_id, $item->periode_alokasi_id),
                    []
                );
                $canManageLampiran = $this->userCanManageLampiran($request, $item);
                $readyToGenerate = ! empty($payload) && $this->isLampiranGenerationAllowed($payload);

                return [
                    'id' => $item->id,
                    'kegiatan_id' => $item->kegiatan_id,
                    'periode_alokasi_id' => $item->periode_alokasi_id,
                    'kode_kegiatan' => $item->kode_kegiatan,
                    'nama_kegiatan' => $item->nama_kegiatan,
                    'jenis_kegiatan' => $item->jenis_kegiatan,
                    'peran' => $payload['peran'] ?? null,
                    'tanggal_selesai' => $payload['tanggal_selesai'] ?? null,
                    'tanggal_selesai_formatted' => $payload['tanggal_selesai_formatted'] ?? '-',
                    'ketua_tim_nama' => $payload['ketua_tim']['nama'] ?? null,
                    'file_path' => $item->file_path,
                    'signed_file_path' => $item->signed_file_path,
                    'generated_at' => $item->generated_at?->format('d M Y H:i'),
                    'signed_uploaded_at' => $item->signed_uploaded_at?->format('d M Y H:i'),
                    'status' => $item->signed_file_path ? 'signed' : ($item->file_path ? 'generated' : 'pending'),
                    'can_download' => $canManageLampiran && ($readyToGenerate || filled($item->file_path) || filled($item->signed_file_path)),
                    'can_generate' => $canManageLampiran && $readyToGenerate,
                    'can_upload_signed' => $canManageLampiran && filled($item->file_path),
                    'can_preview' => $readyToGenerate || filled($item->file_path) || filled($item->signed_file_path),
                    'ready_to_generate' => $readyToGenerate,
                ];
            });

        // Ketua tim only sees lampiran for kegiatan they manage.
        if ($isKetuaTim) {
            $lampiranList = $lampiranList
                ->filter(fn (array $item) => $item['can_download'])
                ->values();
        }

        $generatedLampiranCount = $lampiranList->whereNotNull('file_path')->count();
        $signedLampiranCount = $lampiranList->whereNotNull('signed_file_path')->count();
        $allLampiranGenerated = $lampiranList->isNotEmpty() && $generatedLampiranCount === $lampiranList->count();
        $allLampiranSigned = $lampiranList->isNotEmpty() && $signedLampiranCount === $lampiranList->count();
        $finalSignedReady = $isLegacyMode
            ? filled($bast->signed_file_path)
            : filled($bast->main_signed_file_path) && $allLampiranSigned && filled($bast->signed_file_path);

        return Inertia::render('Bast/Show', [
            'bast' => [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast->format('d M Y'),
                'tanggal_serah_terima' => $bast->tanggal_serah_terima->format('d M Y'),
                'menggunakan_fasih' => $bast->menggunakan_fasih,
                'uraian_pekerjaan' => $bast->uraian_pekerjaan,
                'nama_ketua_tim' => $bast->nama_ketua_tim,
                'nip_ketua_tim' => $bast->nip_ketua_tim,
                'nama_ppk' => $bast->nama_ppk,
                'nip_ppk' => $bast->nip_ppk,
                'hasil_pekerjaan' => $bast->hasil_pekerjaan,
                'file_path' => $bast->file_path,
                'compiled_file_path' => $bast->compiled_file_path,
                'main_signed_file_path' => $bast->main_signed_file_path,
                'signed_file_path' => $bast->signed_file_path,
                'lokasi_kegiatan' => $bast->lokasi_kegiatan,
                'status' => $bast->status,
                'catatan' => $bast->catatan,
                'created_by' => $bast->createdBy?->name ?? 'System',
                'created_at' => $bast->created_at->format('d M Y H:i'),
                'is_legacy_mode' => $isLegacyMode,
            ],
            'spk' => $spk ? [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'tanggal_spk' => $spk->tanggal_spk->format('d M Y'),
                'nilai_kontrak' => $spk->nilai_kontrak,
            ] : null,
            'petugas' => $petugas ? [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'alamat' => $petugas->alamat,
                'no_hp' => $petugas->no_hp,
            ] : null,
            'kegiatan' => [
                'id' => $bast->kegiatan->id,
                'hashed_id' => $bast->kegiatan->hashed_id,
                'kode_kegiatan' => $bast->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $bast->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $bast->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $bast->kegiatan->tahun_anggaran,
            ],
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => (int) $periode->bulan,
                'tahun' => $periode->tahun,
                'bulan_label' => $bulanLabel,
            ],
            'lampiran' => $lampiranList->toArray(),
            'bast_list' => $bastList->values()->toArray(),
            'eligible_without_bast' => array_values(array_filter(
                $eligibleWithoutBast,
                function ($item) use ($bastList) {
                    $idsWithBast = $bastList->pluck('petugas_id')->filter()->unique()->toArray();

                    return ! in_array($item['petugas_id'] ?? null, $idsWithBast, true);
                }
            )),
            'permissions' => [
                'can_manage_main' => $canManageMain,
                'is_ketua_tim' => $isKetuaTim,
                'can_upload_main' => in_array($user?->active_role, ['admin', 'operator'], true),
            ],
            'summary' => [
                'total_lampiran' => $lampiranList->count(),
                'generated_lampiran' => $generatedLampiranCount,
                'signed_lampiran' => $signedLampiranCount,
                'all_lampiran_generated' => $allLampiranGenerated,
                'all_lampiran_signed' => $allLampiranSigned,
                'main_signed_uploaded' => filled($bast->main_signed_file_path),
                'final_signed_ready' => $finalSignedReady,
            ],
            'bulan' => (int) $periode->bulan,
            'tahun' => $periode->tahun,
            'bulan_label' => $bulanLabel,
        ]);
    }

    private function buildBastListForPeriod(
        PeriodeAlokasi $periode,
        bool $canManageMain,
        bool $isKetuaTim,
        Request $request,
        ?Bast $currentBast = null,
    ): Collection {
        $user = $this->getRequestUser($request);

        $user = $this->getRequestUser($request);

        return Bast::with([
            'spk.alokasiPetugas.petugas',
            'createdBy:id,name',
            'bastKegiatan.kegiatan:id,ketua_tim_user_id,pj_lainnya_id',
        ])
            ->whereHas('periodeAlokasi', function ($query) use ($periode) {
                $query->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun);
            })
            ->when($isKetuaTim, function ($query) use ($user) {
                $query->whereHas('bastKegiatan.kegiatan', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });
                });
            })
            ->orderBy('nomor_bast')
            ->get()
            ->filter(function (Bast $item) use ($canManageMain, $isKetuaTim, $request) {
                if ($canManageMain || $isKetuaTim) {
                    return true;
                }

                return $this->userCanAccessBast($request, $item);
            })
            ->map(function (Bast $bast) use ($currentBast) {
                $petugasNama = $bast->spk?->alokasiPetugas?->petugas?->nama ?? 'Unknown';
                $petugasId = $bast->spk?->alokasiPetugas?->petugas?->id;

                return [
                    'id' => $bast->id,
                    'hashed_id' => $bast->hashed_id,
                    'nomor_bast' => $bast->nomor_bast,
                    'petugas_nama' => $petugasNama,
                    'petugas_id' => $petugasId,
                    'file_path' => $bast->file_path,
                    'compiled_file_path' => $bast->compiled_file_path,
                    'main_signed_file_path' => $bast->main_signed_file_path,
                    'signed_file_path' => $bast->signed_file_path,
                    'is_current' => $currentBast?->id === $bast->id,
                ];
            });
    }

    /**
     * Upload signed BAST file
     */
    public function uploadSigned(Request $request, Bast $bast): RedirectResponse
    {
        $bast->loadMissing('bastKegiatan.kegiatan');
        $periode = $bast->periodeAlokasi;
        $isLegacyMode = (int) $periode->tahun < 2026
            || ((int) $periode->tahun === 2026 && (int) $periode->bulan < 4);

        abort_unless($this->userCanManageBastMain($request) && $this->userCanAccessBast($request, $bast), 403);

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // max 10MB
        ]);

        try {
            $file = $request->file('file');
            $filename = 'BAST_MAIN_SIGNED_'.$this->sanitizeDocumentSegment($bast->nomor_bast).'-'.time().'.pdf';
            $directory = $this->ensureBastExportDirectory('main-signed');
            $file->move($directory, $filename);

            $mainSignedPath = trim('bast-export/main-signed/'.$filename, '/');

            // Legacy mode (before Apr 2026) does not require per-lampiran signed upload.
            // Re-uploading main signed file should keep final signed download available.
            if ($isLegacyMode) {
                $this->deleteStoredDocument($bast->main_signed_file_path);
                $this->deleteStoredDocument($bast->signed_file_path);

                $bast->update([
                    'main_signed_file_path' => $mainSignedPath,
                    'signed_file_path' => $mainSignedPath,
                ]);

                return redirect()->back()->with('success', 'BAST bertanda tangan berhasil diunggah');
            }

            if ($bast->bastKegiatan->isEmpty()) {
                $this->deleteStoredDocument($bast->signed_file_path);
                $bast->update([
                    'main_signed_file_path' => $mainSignedPath,
                    'signed_file_path' => $mainSignedPath,
                ]);

                return redirect()->back()->with('success', 'BAST bertanda tangan berhasil diunggah');
            }

            $this->deleteStoredDocument($bast->main_signed_file_path);

            $bast->update([
                'main_signed_file_path' => $mainSignedPath,
            ]);

            $this->syncCompiledBastFiles($bast->fresh('bastKegiatan'));

            return redirect()->back()->with('success', 'BAST main bertanda tangan berhasil diunggah');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah file: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bast $bast)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bast $bast)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bast $bast)
    {
        //
    }

    /**
     * Generate nomor BAST
     */
    private function generateNomorBast(int $kegiatanId): string
    {
        $year = now()->year;

        // Get last number for this kegiatan (current year)
        $lastBast = Bast::where('kegiatan_id', $kegiatanId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastBast) {
            $extracted = $this->extractBastSequence($lastBast->nomor_bast);
            $lastNumber = $extracted ?? 0;
        }

        $nextNumber = $lastNumber + 1;

        // Format: PPIS/13730/{NO_URUT_AUTO_INCREMENT}/BAST/{YEAR}
        return sprintf('PPIS/13730/%d/BAST/%d', $nextNumber, $year);
    }

    /**
     * Generate BAST PDF
     */
    private function generateBastPdf(Kegiatan $kegiatan, array $data, string $nomorBast, ?Penandatangan $ppk, ?string $bulanLabel = null, ?int $tahunPeriode = null): string
    {
        $tanggalBast = Carbon::parse($data['tanggal_bast']);
        $hari = $this->getHariIndonesia($tanggalBast->dayOfWeek);
        $tanggalFormatted = $tanggalBast->isoFormat('D MMMM YYYY');

        // Sanitize petugas entries to ensure fields only present for matching roles
        $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
        $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
        foreach ($data['petugas'] as $i => $pEntry) {
            $peran = $pEntry['peran'] ?? null;
            if (! in_array($peran, $pendataanRoles, true)) {
                $data['petugas'][$i]['hasil_pendataan_lapangan'] = null;
                $data['petugas'][$i]['satuan_pendataan_lapangan'] = null;
                $data['petugas'][$i]['instrumen_pendataan_lapangan'] = null;
                // also clear listing values if not a pendataan role
                $data['petugas'][$i]['hasil_listing'] = null;
                $data['petugas'][$i]['satuan_listing'] = null;
                $data['petugas'][$i]['instrumen_listing'] = null;
            }
            if (! in_array($peran, $pengolahanRoles, true)) {
                $data['petugas'][$i]['hasil_pengolahan'] = null;
                $data['petugas'][$i]['hasil_pengolahan_listing'] = null;
                $data['petugas'][$i]['satuan_pengolahan_listing'] = null;
                $data['petugas'][$i]['satuan_pengolahan'] = null;
            }
        }

        // Check if listing, pendataan, or pengolahan exists after sanitization
        $hasListing = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_listing']);
        });
        $hasPengolahan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pengolahan']);
        });
        $hasPengolahanListing = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pengolahan_listing']);
        });
        $hasPendataan = collect($data['petugas'])->contains(function ($p) {
            return ! empty($p['hasil_pendataan_lapangan']);
        });

        // Cari NIP ketua tim dari data petugas dengan nama yang sama
        $namaKetuaTim = $kegiatan->ketuaTim->name ?? 'N/A';
        $nipKetuaTim = null;

        // Prioritas: cari dari data petugas dengan nama yang sama
        if ($namaKetuaTim !== 'N/A') {
            $petugasKetuaTim = Petugas::whereRaw('LOWER(nama) = ?', [strtolower($namaKetuaTim)])->first();
            if ($petugasKetuaTim && $petugasKetuaTim->nip) {
                $nipKetuaTim = $petugasKetuaTim->nip;
            } else {
                // Fallback ke profile ketua tim
                $nipKetuaTim = $kegiatan->ketuaTim->nip ?? null;
            }
        }

        $viewData = [
            'nomor_bast' => $nomorBast,
            'hari' => $hari,
            'tanggal_bast' => $tanggalFormatted,
            'bulan_label' => $bulanLabel ?? $tanggalBast->isoFormat('MMMM'),
            'tahun' => $tahunPeriode ?? (int) $tanggalBast->year,
            'nama_ppk' => $ppk->nama ?? 'N/A',
            'nip_ppk' => $ppk->nip ?? 'N/A',
            'nama_ketua_tim' => $namaKetuaTim,
            'nip_ketua_tim' => $nipKetuaTim,
            'nama_kegiatan' => $kegiatan->nama_kegiatan,
            'nama_instansi' => config('app.instansi_name', 'Badan Pusat Statistik Kota Sawahlunto'),
            'menggunakan_fasih' => $data['menggunakan_fasih'],
            'petugas' => $data['petugas'],
            'has_listing' => $hasListing,
            'has_pendataan' => $hasPendataan,
            'has_pengolahan' => $hasPengolahan,
            'has_pengolahan_listing' => $hasPengolahanListing,
            'dokumen_rekap' => $data['dokumen_rekap'] ?? [],
            'instrumen_listing' => $data['instrumen_listing'] ?? null,
            'instrumen_pendataan_lapangan' => $data['instrumen_pendataan_lapangan'] ?? null,
            'kepalaBps' => null,
        ];

        // Attach Kepala BPS if available
        $kepala = Penandatangan::kepala()
            ->active()
            ->where(function ($q) {
                $q->whereNull('periode_mulai')->orWhere('periode_mulai', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('periode_selesai')->orWhereDate('periode_selesai', '>=', today());
            })
            ->orderByDesc('periode_mulai')
            ->first();

        if ($kepala) {
            $viewData['kepalaBps'] = $this->stripGelar($kepala->nama) ?: $kepala->nama;
        }

        $useLandscape = false;
        if (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0) {
            $useLandscape = true;
        }
        if ($viewData['has_listing'] || $viewData['has_pengolahan'] || ($viewData['has_pendataan'] ?? false)) {
            $useLandscape = true;
        }

        $orientation = $useLandscape ? 'landscape' : 'portrait';

        // Render main (without lampiran)
        $viewDataMain = $viewData;
        $pdfMain = Pdf::loadView('bast', $viewDataMain)
            ->setPaper('a4', 'portrait');
        $mainContent = $pdfMain->output();

        // If no lampiran, save main PDF directly
        $hasLampiran = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || $viewData['has_pengolahan_listing'] || ($viewData['has_pendataan'] ?? false);

        if (! $hasLampiran) {
            $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.($targetPeriode?->bulan ?? 'unknown').'_'.time().'.pdf';
            $filePath = 'storage/bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
            $fullPath = public_path($filePath);
            file_put_contents($fullPath, $mainContent);

            return $filePath;
        }

        // Render lampiran only (landscape) using bast-lampiran.blade.php directly
        $viewDataLamp = $viewData;
        $lampOrientation = (! empty($viewData['dokumen_rekap']) && count($viewData['dokumen_rekap']) > 0)
            || $viewData['has_listing'] || $viewData['has_pengolahan'] || $viewData['has_pengolahan_listing'] || ($viewData['has_pendataan'] ?? false) ? 'landscape' : 'portrait';
        $pdfLamp = Pdf::loadView('bast-lampiran', $viewDataLamp)
            ->setPaper('a4', $lampOrientation);
        $lampContent = $pdfLamp->output();

        // Merge and save
        $merged = $this->mergePdfStrings([$mainContent, $lampContent]);

        $directory = public_path('bast-export/'.now()->year.'/'.now()->month);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $periodeAlokasi = $kegiatan->periodeAlokasi()->latest('id')->first();
        $bulan = $periodeAlokasi?->bulan ?? 'unknown';
        $fileName = 'BAST_'.$kegiatan->nama_kegiatan.'_'.$bulan.'_'.time().'.pdf';
        $filePath = 'bast-export/'.now()->year.'/'.now()->month.'/'.$fileName;
        $fullPath = public_path($filePath);
        file_put_contents($fullPath, $merged);

        return 'storage/'.$filePath;
    }

    /**
     * Merge multiple PDF binary strings into one PDF preserving page orientations.
     */
    private function mergePdfStrings(array $pdfStrings): string
    {
        // Use FPDI TCPDF implementation
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pdfStrings as $str) {
            if (empty($str)) {
                continue;
            }
            // use StreamReader to feed string directly
            $reader = StreamReader::createByString($str);
            $pageCount = $pdf->setSourceFile($reader);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Get hari in Indonesian
     */
    private function getHariIndonesia(int $dayOfWeek): string
    {
        $hari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $hari[$dayOfWeek] ?? 'Senin';
    }

    /**
     * Strip academic titles / honorifics from a full name.
     */
    private function stripGelar(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        // Remove anything after the first comma (common suffixes like ", S.Si., M.Sc.")
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);

        // Remove common prefixes like Dr, Drs, Ir, H, Prof (with optional dot)
        $name = preg_replace('/^(Drs?|Ir|H|Prof)\.?\s+/i', '', $name);

        return trim($name);
    }

    /**
     * Ambil periode target: prioritas status perubahan (terbaru), jika tidak ada ambil dikirim (terbaru).
     */
    private function getTargetPeriode(int $kegiatanId): ?PeriodeAlokasi
    {
        $perubahanWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($perubahanWithSpk) {
            return $perubahanWithSpk;
        }

        $dikirimWithSpk = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->whereHas('spk')
            ->orderByDesc('id')
            ->first();

        if ($dikirimWithSpk) {
            return $dikirimWithSpk;
        }

        $perubahan = PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'perubahan')
            ->orderByDesc('id')
            ->first();

        if ($perubahan) {
            return $perubahan;
        }

        return PeriodeAlokasi::where('kegiatan_id', $kegiatanId)
            ->where('status', 'dikirim')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get bulan label (Indonesian month name)
     */
    private function getBulanLabel(int $bulan): string
    {
        $bulanLabels = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanLabels[$bulan] ?? '';
    }
}
