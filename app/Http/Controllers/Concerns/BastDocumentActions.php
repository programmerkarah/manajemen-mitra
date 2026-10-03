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

trait BastDocumentActions
{
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

        $seInputForPreview = $this->resolveSensusPreviewInput($spk, $request);

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
            $ppk,
            $seInputForPreview
        );

        $previewSensusReference = $this->isSensusEkonomiSpk($spk)
            ? $this->buildSensusReferencePayload(
                $spk,
                (int) Carbon::parse($tanggalAkhir)->month,
                (int) Carbon::parse($tanggalAkhir)->year,
            )
            : null;

        $viewData['bast']->kegiatan_list = $this->mergeSharedSensusScreenshotIntoKegiatanList(
            $viewData['bast']->kegiatan_list ?? [],
            $previewSensusReference['fasih_screenshot_path'] ?? null,
        );

        if ($previewSensusReference !== null && isset($previewSensusReference['bapp_termin_ii_complete'])) {
            $terminIIComplete = (bool) $previewSensusReference['bapp_termin_ii_complete'];
            $viewData['bast']->kegiatan_list = collect($viewData['bast']->kegiatan_list ?? [])
                ->map(function (array $item) use ($terminIIComplete): array {
                    $item['bapp_termin_ii_complete'] = $terminIIComplete;

                    return $item;
                })
                ->all();
        }

        $kegiatanId = $request->input('kegiatan_id');
        $periodeAlokasiId = (int) $request->input('periode_alokasi_id', 0);

        if ($kegiatanId) {
            $selectedKegiatanPayload = collect($viewData['bast']->kegiatan_list)
                ->first(function (array $item) use ($kegiatanId, $periodeAlokasiId) {
                    $sameKegiatan = (int) ($item['kegiatan_id'] ?? 0) === (int) $kegiatanId;

                    if (! $sameKegiatan) {
                        return false;
                    }

                    if ($periodeAlokasiId <= 0) {
                        return true;
                    }

                    return (int) ($item['periode_alokasi_id'] ?? 0) === $periodeAlokasiId;
                });

            abort_if(! $selectedKegiatanPayload, 404, 'Kegiatan lampiran tidak ditemukan.');
        }

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

        abort_if(
            collect($viewData['bast']->kegiatan_list)->contains(
                fn (array $item) => ! $this->isLampiranGenerationAllowed($item)
            ),
            422,
            'Preview lampiran hanya bisa dibuka setelah screenshot Fasih diunggah dan kegiatan berakhir.'
        );

        $mainContent = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait')
            ->output();

        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset(null, $mainContent);
        $viewData['pageNumberOffset'] += max(0, ((int) ($selectedKegiatanPayload['lampiran_nomor'] ?? 1)) - 1);

        $pdfLampiran = Pdf::loadView($this->resolveBastLampiranSpkView($viewData), $viewData)
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

        $previewSensusReference = $this->isSensusEkonomiSpk($spk)
            ? $this->buildSensusReferencePayload(
                $spk,
                (int) Carbon::parse($tanggalAkhir)->month,
                (int) Carbon::parse($tanggalAkhir)->year,
            )
            : null;

        $viewData['bast']->kegiatan_list = $this->mergeSharedSensusScreenshotIntoKegiatanList(
            $viewData['bast']->kegiatan_list ?? [],
            $previewSensusReference['fasih_screenshot_path'] ?? null,
        );

        if ($previewSensusReference !== null && isset($previewSensusReference['bapp_termin_ii_complete'])) {
            $terminIIComplete = (bool) $previewSensusReference['bapp_termin_ii_complete'];
            $viewData['bast']->kegiatan_list = collect($viewData['bast']->kegiatan_list ?? [])
                ->map(function (array $item) use ($terminIIComplete): array {
                    $item['bapp_termin_ii_complete'] = $terminIIComplete;

                    return $item;
                })
                ->all();
        }

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
        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Lampiran hanya bisa diunduh setelah screenshot Fasih diunggah dan kegiatan berakhir.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];

        $mainContent = Pdf::loadView('bast', $viewData)
            ->setPaper('a4', 'portrait')
            ->output();

        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset(null, $mainContent);
        $viewData['pageNumberOffset'] += max(0, ((int) ($kegiatanPayload['lampiran_nomor'] ?? 1)) - 1);

        $pdfLampiran = Pdf::loadView($this->resolveBastLampiranSpkView($viewData), $viewData)
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

    public function uploadLampiranFasihScreenshotByReference(Request $request): RedirectResponse
    {
        if ($request->filled('bast_hashed_id')) {
            $bast = $this->resolveBastFromHashedId((string) $request->input('bast_hashed_id'));

            if ($bast) {
                $this->rememberOpenDetailFiltersFromBast($request, $bast);
            }
        }

        return redirect()->to(route('bast.open-detail-by-petugas', absolute: false))
            ->with('error', 'Upload screenshot Fasih per lampiran sudah dihapus. Gunakan upload screenshot Fasih utama pada referensi sensus.');
    }

    public function previewStoredLampiran(Request $request, Bast $bast, BastKegiatan $bastKegiatan): \Symfony\Component\HttpFoundation\Response
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

        abort_if(! $kegiatanPayload, 404, 'Lampiran kegiatan tidak ditemukan.');

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

        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Preview lampiran hanya bisa dibuka setelah screenshot Fasih diunggah dan kegiatan berakhir.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];
        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset($bast);
        $viewData['pageNumberOffset'] += max(0, ((int) ($kegiatanPayload['lampiran_nomor'] ?? 1)) - 1);

        $pdfLampiran = Pdf::loadView($this->resolveBastLampiranSpkView($viewData), $viewData)
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
        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Lampiran hanya bisa diunduh setelah screenshot Fasih diunggah dan kegiatan berakhir.');

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];
        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset($bast);
        $viewData['pageNumberOffset'] += max(0, ((int) ($kegiatanPayload['lampiran_nomor'] ?? 1)) - 1);

        $pdfLampiran = Pdf::loadView($this->resolveBastLampiranSpkView($viewData), $viewData)
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
            return redirect()->back()->with('error', 'Lampiran hanya bisa digenerate setelah screenshot Fasih diunggah dan kegiatan berakhir.');
        }

        $viewData['bast']->kegiatan_list = [$kegiatanPayload];
        $viewData['pageNumberOffset'] = $this->resolveLampiranPageNumberOffset($bast);
        $viewData['pageNumberOffset'] += max(0, ((int) ($kegiatanPayload['lampiran_nomor'] ?? 1)) - 1);

        $pdfLampiran = Pdf::loadView($this->resolveBastLampiranSpkView($viewData), $viewData)
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

        $viewData = $this->prepareStoredBastViewData($bast);
        $kegiatanPayload = collect($viewData['bast']->kegiatan_list)->first(function (array $item) use ($bastKegiatan) {
            return (int) ($item['kegiatan_id'] ?? 0) === (int) $bastKegiatan->kegiatan_id
                && (int) ($item['periode_alokasi_id'] ?? 0) === (int) $bastKegiatan->periode_alokasi_id;
        });

        abort_if(! $kegiatanPayload, 404, 'Lampiran kegiatan tidak ditemukan.');
        abort_if(! $this->isLampiranGenerationAllowed($kegiatanPayload), 422, 'Lampiran hanya bisa diunduh setelah screenshot Fasih diunggah dan kegiatan berakhir.');

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

    public function uploadLampiranFasihScreenshot(Request $request, Bast $bast, BastKegiatan $bastKegiatan): RedirectResponse
    {
        $this->rememberOpenDetailFiltersFromBast($request, $bast);

        return $this->redirectToLocalPath(
            $request,
            route('bast.open-detail-by-petugas', absolute: false)
        )->with('error', 'Upload screenshot Fasih per lampiran sudah dihapus. Gunakan upload screenshot Fasih utama pada referensi sensus.');
    }

    public function uploadPreviewLampiranFasihScreenshot(Request $request): RedirectResponse
    {
        return $this->redirectToLocalPath(
            $request,
            route('bast.index', absolute: false)
        )->with('error', 'Upload screenshot Fasih per lampiran sudah dihapus. Gunakan upload screenshot Fasih utama pada referensi sensus.');
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
        $bast->refresh()->load('bastKegiatan');

        $this->rememberOpenDetailFiltersFromBast($request, $bast);

        $allSignedSourcesReady = filled($bast->main_signed_file_path)
            && $bast->bastKegiatan->isNotEmpty()
            && $bast->bastKegiatan->every(fn (BastKegiatan $item) => filled($item->signed_file_path));

        if ($allSignedSourcesReady && blank($bast->signed_file_path)) {
            return $this->redirectToLocalPath($request, route('bast.open-detail-by-petugas', absolute: false))
                ->with('error', 'Semua file signed sudah diunggah, tetapi PDF gabungan gagal dibuat. Periksa laravel.log untuk detail engine PDF.');
        }

        return $this->redirectToLocalPath($request, route('bast.open-detail-by-petugas', absolute: false))
            ->with('success', 'Lampiran bertanda tangan berhasil diunggah.');
    }

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

        // Newer periods store the signed main BAST and signed attachments
        // separately. Rebuild the final signed bundle before validating the ZIP.
        if (! $isLegacyMode) {
            $allBast = $allBast->map(function (Bast $bast) {
                $this->syncCompiledBastFiles($bast);

                return $bast->fresh();
            });
        }

        abort_unless(
            $allBast->isNotEmpty() && $allBast->every(fn (Bast $b) => filled($b->signed_file_path)),
            403
        );

        $documents = $allBast->map(function (Bast $bast) {
            return [
                'bast' => $bast,
                'path' => $bast->signed_file_path,
            ];
        })->filter(fn (array $item) => filled($item['path']))->values();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada BAST dengan file untuk diunduh');
        }

        // Create ZIP file with deterministic name (no timestamp for CDN caching)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $userSuffix = $isKetuaTim ? "_{$user->id}" : '';
        $zipFileName = "BAST_Signed_{$bulanLabel}_{$tahun}{$userSuffix}.zip";

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
}
