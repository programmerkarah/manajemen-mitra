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
use App\Services\SensusEkonomiReplacementReadService;
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

trait SpkAddendumSupport
{
    public function createAddendum(Request $request, string $periodeHashedId): Response|RedirectResponse
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        // Support both GET (encrypted query param, for browser refresh) and POST (encrypted body).
        // Falls back to plain query params when no encrypted payload is present (e.g. direct URL access).
        $rawPayload = $request->input('payload');

        if ($rawPayload) {
            $payload = decryptData($rawPayload);
            $bulan = $payload['bulan'] ?? null;
            $tahun = $payload['tahun'] ?? null;
            $requestedMode = $payload['mode'] ?? null;
        } else {
            $bulan = $request->query('bulan');
            $tahun = $request->query('tahun');
            $requestedMode = $request->query('mode');
        }

        if (! $periodeId || ! $bulan || ! $tahun) {
            abort(403);
        }

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        $monthPeriodes = PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->with('spk')
            ->get();

        if ($monthPeriodes->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada periode valid untuk bulan ini.');
        }

        $allPeriodeInMonth = $monthPeriodes->pluck('id');

        $monthDecisions = $this->spkActionDecisionService->resolveForMonth((int) $tahun, (int) $bulan);
        if ($monthDecisions->contains(
            fn (array $item): bool => ($item['final_action'] ?? null) === 'regenerate_pk'
        )) {
            return redirect()->route('spk.index')
                ->with('warning', 'Silakan selesaikan re-generate SPK terlebih dahulu sebelum membuat addendum.');
        }

        $candidateSummary = $monthDecisions
            ->filter(fn (array $item): bool => in_array(
                $item['final_action'] ?? null,
                ['generate_addendum', 'regenerate_addendum'],
                true
            ))
            ->values();
        $candidateDecisions = $candidateSummary->keyBy('petugas_id');

        $eligiblePetugasIds = $candidateSummary
            ->pluck('petugas_id')
            ->map(static fn ($petugasId) => (int) $petugasId)
            ->unique()
            ->values()
            ->all();

        // Load the exact candidates decided by the service. Do not run a
        // second honor-based eligibility query that can disagree with Index.
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereIn('petugas_id', $eligiblePetugasIds)
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        $petugasWithAddendum = $candidateSummary
            ->filter(fn (array $item): bool => (bool) ($item['has_addendum'] ?? false))
            ->pluck('petugas_id')
            ->values()
            ->all();

        $resolvedMode = in_array($requestedMode, ['addendum', 'regenerate'], true)
            ? $requestedMode
            : (! empty($petugasWithAddendum) ? 'regenerate' : 'addendum');
        $isRegenerateAddendum = $resolvedMode === 'regenerate';

        // Group by petugas_id and aggregate their data
        $petugasListRaw = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) use ($bulanFormatted, $tahun, $petugasWithAddendum, $eligiblePetugasIds, $candidateDecisions) {
                $firstAlokasi = $alokasiGroup->first();

                if (! in_array((int) $firstAlokasi->petugas_id, $eligiblePetugasIds, true)) {
                    return null;
                }

                // Get existing SPK for this petugas in this month
                $existingSpk = $this->spkActionDecisionService->resolveOriginalSpkForPetugasMonth(
                    (int) $firstAlokasi->petugas_id,
                    (int) $tahun,
                    (int) $bulanFormatted,
                );

                if (! $existingSpk) {
                    return null; // Skip petugas without original SPK
                }

                // Get current effective allocations (latest status for each kegiatan)
                $effectiveAlokasiByKegiatan = $this->spkActionDecisionService->getEffectiveAlokasiByKegiatan($alokasiGroup);

                // Calculate current total honor
                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi) {
                    return $alokasi->getEffectiveCombinedHonor();
                });

                $totalHonor = $currentTotalHonor;

                // Get all effective kegiatan with their peran (perubahan if exists, otherwise latest revisi)
                $kegiatanList = $effectiveAlokasiByKegiatan
                    ->map(function ($alokasi) {
                        return [
                            'kegiatan_kode' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                            'kegiatan_nama' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                            'peran' => $alokasi->peran,
                        ];
                    })
                    ->unique(function (array $item): string {
                        return $item['kegiatan_kode'].'|'.$item['peran'];
                    })
                    ->values()
                    ->all();

                // Get last addendum number for this petugas
                $lastAddendum = Spk::where('parent_spk_id', $existingSpk->id)
                    ->orderBy('addendum_number', 'desc')
                    ->first();

                $nextAddendumNumber = $lastAddendum ? $lastAddendum->addendum_number + 1 : 1;

                return [
                    'alokasi_id' => $firstAlokasi->id,
                    'alokasi_hashed_id' => $firstAlokasi->hashed_id,
                    'existing_spk_id' => $existingSpk->id,
                    'existing_spk_hashed_id' => $existingSpk->hashed_id,
                    'existing_spk_nomor' => $existingSpk->nomor_spk,
                    'next_addendum_number' => $nextAddendumNumber,
                    'petugas' => [
                        'id' => $firstAlokasi->petugas->id,
                        'hashed_id' => $firstAlokasi->petugas->hashed_id,
                        'nama' => $firstAlokasi->petugas->nama,
                        'nik' => $firstAlokasi->petugas->nik,
                        'jenis_petugas' => $firstAlokasi->petugas->jenis_petugas,
                    ],
                    'jumlah_kegiatan' => count($kegiatanList),
                    'kegiatan_list' => $kegiatanList,
                    'total_honor' => $totalHonor,
                    'perubahan' => $this->spkActionDecisionService->buildChangeSummariesForPetugasMonth(
                        (int) $firstAlokasi->petugas_id,
                        (int) $tahun,
                        (int) $bulanFormatted,
                    ),
                    'has_addendum' => in_array($firstAlokasi->petugas_id, $petugasWithAddendum),
                    'final_action' => (string) ($candidateDecisions->get((int) $firstAlokasi->petugas_id)['final_action'] ?? 'no_action'),
                ];
            })
            ->filter() // Remove nulls
            ->filter(function ($item) use ($isRegenerateAddendum) {
                return $isRegenerateAddendum
                    ? $item['final_action'] === 'regenerate_addendum'
                    : $item['final_action'] === 'generate_addendum';
            })
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        // If no eligible petugas for addendum, block access
        if ($petugasListRaw->isEmpty()) {
            return redirect()->route('spk.index')
                ->with('warning', 'Tidak ada petugas yang dapat dibuatkan addendum Perjanjian Kerja untuk periode tersebut.');
        }

        return Inertia::render('Spk/Addendum', [
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'tahun' => $tahun,
                'bulan' => (int) $bulan,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
                'kegiatan' => [
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'tahun_anggaran' => $periode->kegiatan->tahun_anggaran,
                ],
            ],
            'petugas_list' => $petugasListRaw->values()->all(),
            'is_regenerate_addendum' => $isRegenerateAddendum,
        ]);
    }

    private function isMeaningfulAllocation(object $alokasi): bool
    {
        $unitSampelVolume = (int) ($alokasi->jumlah_unit_sampel ?? 0);
        $totalVolume = $unitSampelVolume > 0
            ? $unitSampelVolume
            : (int) ($alokasi->jumlah_satuan ?? 0) + (int) ($alokasi->jumlah_satuan_listing ?? 0);
        $totalHonor = (float) ($alokasi->total_honor ?? 0) + (float) ($alokasi->total_honor_listing ?? 0);

        return $totalVolume > 0 && $totalHonor > 0;
    }

    private function hasMeaningfulAllocationSnapshotDelta(array $referenceSnapshot, array $currentSnapshot): bool
    {
        foreach (array_intersect(array_keys($referenceSnapshot), array_keys($currentSnapshot)) as $kegiatanId) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $reference || ! $current) {
                continue;
            }

            if (
                $current['peran'] !== $reference['peran'] ||
                $current['jumlah_satuan'] !== $reference['jumlah_satuan'] ||
                $current['jumlah_satuan_listing'] !== $reference['jumlah_satuan_listing'] ||
                abs($current['total_honor'] - $reference['total_honor']) > 0.01 ||
                abs($current['total_honor_listing'] - $reference['total_honor_listing']) > 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    public function previewAddendum(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => 'required|date',
            'sampai_tanggal' => 'required|date',
            'parent_spk_id' => 'required|exists:spk,id',
            'addendum_number' => 'required|integer|min:1',
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        // Get parent SPK to retrieve original details
        $parentSpk = Spk::with(['alokasiPetugas.periodeAlokasi.kegiatan'])->findOrFail($validated['parent_spk_id']);

        // Get periode alokasi for this addendum
        $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);

        // Get petugas details
        $petugas = Petugas::findOrFail($petugasId);

        // Get bulan and tahun from periode
        $bulan = $periode->bulan;
        $tahun = $periode->tahun;
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periode in the same month with status 'dikirim' and 'perubahan'
        $allPeriodeInMonth = PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        $allAlokasi = $this->spkActionDecisionService->getEffectiveAddendumAlokasiForPetugas($petugasId, $tahun, (int) $bulan);

        // Calculate total honor (from both 'dikirim' and 'perubahan' status)
        $totalHonor = $allAlokasi->sum(function ($alokasi) {
            return $alokasi->getEffectiveCombinedHonor();
        });

        // Build kegiatan list
        $kegiatanList = $allAlokasi->map(function ($alokasi) {
            $periode = $alokasi->periodeAlokasi;

            // Get satuan from rate honor
            $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                    && $rate->jenis_penugasan === $alokasi->peran;
            });

            $satuanKode = $rateHonor && $rateHonor->satuan ? $rateHonor->satuan->kode : 'PAKET';

            return [
                'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                'kode_coa' => $periode->kegiatan->kode_coa,
                'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                'peran' => $alokasi->peran,
                'peran_label' => $this->getPeranLabel($alokasi->peran),
                'jumlah_satuan' => $alokasi->getEffectiveJumlahSatuan(),
                'jumlah_satuan_listing' => $alokasi->getEffectiveJumlahSatuanListing(),
                'total_honor' => $alokasi->getEffectiveTotalHonor(),
                'total_honor_listing' => $alokasi->getEffectiveTotalHonorListing(),
                'satuan_kode' => $satuanKode,
                'periode_mulai' => $periode->tanggal_mulai,
                'periode_selesai' => $periode->tanggal_selesai,
                'periode_bulan' => $periode->bulan,
                'periode_tahun' => $periode->tahun,
                'periode_bulan_label' => $this->getBulanLabel((int) $periode->bulan),
            ];
        })->values()->all();

        // Format nomor SPK with addendum suffix
        $nomorSpkParts = explode('/', $parentSpk->nomor_spk);
        $nomorSpkParts[2] = $nomorSpkParts[2].'/ADD-'.$validated['addendum_number'];
        $nomorSpk = implode('/', $nomorSpkParts);

        $data = [
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => $validated['tanggal_spk'],
            'sampai_tanggal' => $validated['sampai_tanggal'],
            'addendum_number' => $validated['addendum_number'],
            'parent_nomor_spk' => $parentSpk->nomor_spk,
            'petugas' => [
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'tempat_lahir' => $petugas->tempat_lahir,
                'tanggal_lahir' => $petugas->tanggal_lahir,
                'alamat' => $petugas->alamat,
                'no_rekening' => $petugas->no_rekening,
                'nama_bank' => $petugas->nama_bank,
                'npwp' => $petugas->npwp,
            ],
            'kegiatan_list' => $kegiatanList,
            'total_honor' => $totalHonor,
            'periode' => [
                'bulan' => (int) $bulan,
                'tahun' => $tahun,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
            ],
        ];

        try {
            $pdfContent = $this->generateAddendumPdfContent($data);

            // Sanitize filename untuk menghindari masalah karakter khusus
            $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
            $filename = 'preview-addendum-spk-'.$sanitizedName.'.pdf';

            if (($validated['response_mode'] ?? 'binary') === 'url') {
                $tempFile = $this->storePublicPreviewTemporaryPdf($pdfContent);
                if (! $tempFile) {
                    return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
                }

                $previewUrl = $this->buildPublicPreviewSignedFileUrl($tempFile, $filename, 'inline');
                if (! $previewUrl) {
                    return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
                }

                return response()->json([
                    'preview_url' => $previewUrl,
                    'filename' => $filename,
                ]);
            }

            // Return with proper headers for inline display
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'.$filename.'"')
                ->header('Content-Length', strlen($pdfContent))
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, must-revalidate, max-age=0')
                ->header('Pragma', 'public')
                ->header('X-Content-Type-Options', 'nosniff');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal generate preview addendum SPK: '.$e->getMessage());
        }
    }

    public function generateAddendum(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => 'required|date',
            'sampai_tanggal' => 'required|date',
            'parent_spk_id' => 'required|exists:spk,id',
            'addendum_number' => 'required|integer|min:1',
        ]);

        try {
            $generatedSpk = $this->generateAndStoreAddendumDocument(
                $periodeId,
                $petugasId,
                $validated['tanggal_spk'],
                $validated['sampai_tanggal'],
                (int) $validated['parent_spk_id'],
                (int) $validated['addendum_number'],
            );

            ActivityLog::log(
                'Generate Addendum SPK',
                'spk',
                "Berhasil generate addendum SPK: {$generatedSpk->nomor_spk}",
                'success',
                [
                    'spk_id' => $generatedSpk->id,
                    'nomor_spk' => $generatedSpk->nomor_spk,
                    'petugas_id' => $petugasId,
                    'periode_id' => $periodeId,
                    'parent_spk_id' => (int) $validated['parent_spk_id'],
                    'addendum_number' => (int) $validated['addendum_number'],
                ]
            );

            // Return JSON response for AJAX requests
            return response()->json([
                'success' => true,
                'message' => 'Addendum SPK berhasil di-generate',
            ]);
        } catch (\Exception $e) {
            ActivityLog::log(
                'Generate Addendum SPK',
                'spk',
                'Gagal generate addendum SPK',
                'error',
                [
                    'petugas_id' => $petugasId,
                    'periode_id' => $periodeId,
                    'parent_spk_id' => (int) ($validated['parent_spk_id'] ?? 0),
                    'addendum_number' => (int) ($validated['addendum_number'] ?? 0),
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate addendum SPK: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateBatchAddendum(Request $request, string $periodeHashedId): RedirectResponse
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => 'required|date',
            'sampai_tanggal' => 'required|date',
            'batch_items' => 'required|array|min:1',
            'batch_items.*.petugas_hashed_id' => 'required|string',
            'batch_items.*.parent_spk_id' => 'required|integer|exists:spk,id',
            'batch_items.*.addendum_number' => 'required|integer|min:1',
        ]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($validated['batch_items'] as $item) {
            $petugasId = Hashids::decode($item['petugas_hashed_id'])[0] ?? null;

            if (! $petugasId) {
                $failedCount++;

                continue;
            }

            $parentSpk = Spk::where('id', (int) $item['parent_spk_id'])
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->first();

            if (! $parentSpk) {
                $failedCount++;

                continue;
            }

            try {
                $generatedSpk = $this->generateAndStoreAddendumDocument(
                    (int) $periodeId,
                    (int) $petugasId,
                    $validated['tanggal_spk'],
                    $validated['sampai_tanggal'],
                    (int) $parentSpk->id,
                    (int) $item['addendum_number'],
                );

                ActivityLog::log(
                    'Generate Batch Addendum SPK',
                    'spk',
                    "Berhasil generate addendum batch untuk SPK: {$generatedSpk->nomor_spk}",
                    'success',
                    [
                        'spk_id' => $generatedSpk->id,
                        'nomor_spk' => $generatedSpk->nomor_spk,
                        'petugas_id' => (int) $petugasId,
                        'periode_id' => (int) $periodeId,
                        'parent_spk_id' => (int) $parentSpk->id,
                        'addendum_number' => (int) $item['addendum_number'],
                    ]
                );

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;

                ActivityLog::log(
                    'Generate Batch Addendum SPK',
                    'spk',
                    'Gagal generate addendum batch untuk petugas',
                    'error',
                    [
                        'petugas_id' => (int) ($petugasId ?? 0),
                        'periode_id' => (int) $periodeId,
                        'parent_spk_id' => (int) ($item['parent_spk_id'] ?? 0),
                        'addendum_number' => (int) ($item['addendum_number'] ?? 0),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        ActivityLog::log(
            'Generate Batch Addendum SPK',
            'spk',
            "Selesai generate batch addendum SPK: {$successCount} berhasil, {$failedCount} gagal",
            $failedCount > 0 ? 'warning' : 'success',
            [
                'periode_id' => (int) $periodeId,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'requested_count' => count($validated['batch_items']),
            ]
        );

        if ($successCount === 0) {
            return redirect()->route('spk.index')
                ->with('error', 'Gagal generate addendum Perjanjian Kerja. Tidak ada dokumen yang berhasil dibuat.');
        }

        if ($failedCount > 0) {
            return redirect()->route('spk.index')
                ->with('warning', "Generate batch addendum selesai: {$successCount} berhasil, {$failedCount} gagal.");
        }

        return redirect()->route('spk.index')
            ->with('success', 'Berhasil generate semua Addendum Perjanjian Kerja.');
    }

    private function generateAndStoreAddendumDocument(
        int $periodeId,
        int $petugasId,
        string $tanggalSpk,
        string $sampaiTanggal,
        int $parentSpkId,
        int $addendumNumber,
    ): Spk {
        DB::beginTransaction();

        try {
            $parentSpk = Spk::findOrFail($parentSpkId);
            $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);
            $petugas = Petugas::findOrFail($petugasId);

            $bulan = $periode->bulan;
            $tahun = $periode->tahun;
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $allAlokasi = $this->spkActionDecisionService->getEffectiveAddendumAlokasiForPetugas($petugasId, $tahun, (int) $bulan);

            $mainAlokasi = $allAlokasi->first();

            if (! $mainAlokasi) {
                throw new \Exception('Tidak ditemukan alokasi untuk petugas ini');
            }

            $totalHonor = $allAlokasi->sum(function ($alokasi) {
                return $alokasi->getEffectiveCombinedHonor();
            });

            $kegiatanList = $allAlokasi->map(function ($alokasi) {
                $periode = $alokasi->periodeAlokasi;

                $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alokasi) {
                    return $rate->status_kepegawaian === $alokasi->status_kepegawaian
                        && $rate->jenis_penugasan === $alokasi->peran;
                });

                $satuanKode = $rateHonor && $rateHonor->satuan ? $rateHonor->satuan->kode : 'PAKET';

                return [
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'kode_coa' => $periode->kegiatan->kode_coa,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'peran' => $alokasi->peran,
                    'peran_label' => $this->getPeranLabel($alokasi->peran),
                    'jumlah_satuan' => $alokasi->getEffectiveJumlahSatuan(),
                    'jumlah_satuan_listing' => $alokasi->getEffectiveJumlahSatuanListing(),
                    'total_honor' => $alokasi->getEffectiveTotalHonor(),
                    'total_honor_listing' => $alokasi->getEffectiveTotalHonorListing(),
                    'satuan_kode' => $satuanKode,
                    'periode_mulai' => $periode->tanggal_mulai,
                    'periode_selesai' => $periode->tanggal_selesai,
                    'periode_bulan' => $periode->bulan,
                    'periode_tahun' => $periode->tahun,
                    'periode_bulan_label' => $this->getBulanLabel((int) $periode->bulan),
                ];
            })->values()->all();

            $nomorSpkParts = explode('/', $parentSpk->nomor_spk);
            $baseNomorUrut = $nomorSpkParts[2];
            if (str_contains($baseNomorUrut, '/ADD-')) {
                $baseNomorUrut = explode('/ADD-', $baseNomorUrut)[0];
            }
            $nomorSpkParts[2] = $baseNomorUrut.'/ADD-'.$addendumNumber;
            $nomorSpk = implode('/', $nomorSpkParts);

            $data = [
                'nomor_spk' => $nomorSpk,
                'tanggal_spk' => $tanggalSpk,
                'sampai_tanggal' => $sampaiTanggal,
                'addendum_number' => $addendumNumber,
                'parent_nomor_spk' => $parentSpk->nomor_spk,
                'petugas' => [
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'tempat_lahir' => $petugas->tempat_lahir,
                    'tanggal_lahir' => $petugas->tanggal_lahir,
                    'alamat' => $petugas->alamat,
                    'no_rekening' => $petugas->no_rekening,
                    'nama_bank' => $petugas->nama_bank,
                    'npwp' => $petugas->npwp,
                ],
                'kegiatan_list' => $kegiatanList,
                'total_honor' => $totalHonor,
                'periode' => [
                    'bulan' => (int) $bulan,
                    'tahun' => $tahun,
                    'bulan_label' => $this->getBulanLabel((int) $bulan),
                ],
            ];

            $pdfContent = $this->generateAddendumPdfContent($data);

            $sanitizedNamaPetugas = preg_replace('/[\/\\:*?"<>|]/', '', $petugas->nama);
            $fileName = 'SPK-ADDENDUM-'.$addendumNumber.'-'.$sanitizedNamaPetugas.'-'.$bulanFormatted.'-'.$tahun.'.pdf';
            $filePath = "spk-export/{$tahun}/{$bulanFormatted}/{$fileName}";

            $publicPath = public_path("spk-export/{$tahun}/{$bulanFormatted}");
            if (! file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            file_put_contents(public_path($filePath), $pdfContent);

            $generatedSpk = Spk::create([
                'petugas_id' => $petugasId,
                'alokasi_petugas_id' => $mainAlokasi->id,
                'alokasi_petugas_ids' => $allAlokasi->pluck('id')->toArray(),
                'nomor_spk' => $nomorSpk,
                'tanggal_spk' => $tanggalSpk,
                'tanggal_mulai_kerja' => $parentSpk->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $parentSpk->tanggal_selesai_kerja,
                'nilai_kontrak' => $totalHonor,
                'lampiran_template' => $parentSpk->lampiran_template ?? 'default',
                'lampiran_payload' => $parentSpk->lampiran_payload,
                'nama_ppk' => $parentSpk->nama_ppk,
                'nip_ppk' => $parentSpk->nip_ppk,
                'file_path' => $filePath,
                'status' => 'draft',
                'parent_spk_id' => $parentSpkId,
                'addendum_number' => $addendumNumber,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return $generatedSpk;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateAddendumPdfContent(array $data): string
    {
        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Check if any kegiatan contains 'Ubinan'
        $hasUbinanKegiatan = collect($data['kegiatan_list'])->contains(function ($kegiatan) {
            return stripos($kegiatan['nama_kegiatan'], 'Ubinan') !== false;
        });

        $pdfData = [
            'nomorSpk' => $data['nomor_spk'],
            'tanggalSpk' => Carbon::parse($data['tanggal_spk']),
            'sampaiTanggal' => Carbon::parse($data['sampai_tanggal']),
            'addendum_number' => $data['addendum_number'],
            'parent_nomor_spk' => $data['parent_nomor_spk'],
            'petugas' => (object) $data['petugas'],
            'kegiatan_list' => $data['kegiatan_list'],
            'total_honor' => $data['total_honor'],
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'bulan_label' => $data['periode']['bulan_label'],
            'tahun' => $data['periode']['tahun'],
            'hasUbinanKegiatan' => $hasUbinanKegiatan,
        ];

        // Generate addendum main PDF
        $pdfMain = Pdf::loadView('spk-addendum-main', $pdfData)
            ->setPaper('a4', 'portrait');

        // Generate addendum lampiran PDF
        $pdfLampiran = Pdf::loadView('spk-addendum-lampiran', $pdfData)
            ->setPaper('a4', 'landscape');

        // Save temporary PDFs
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_addendum_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_addendum_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_addendum_merged_'.$timestamp.'.pdf';

        file_put_contents($mainPath, $pdfMain->output());
        file_put_contents($lampiranPath, $pdfLampiran->output());

        // Try to merge PDFs
        $merged = PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath],
            $mergedPath
        );

        $pdfOutput = null;
        if ($merged && file_exists($mergedPath)) {
            $pdfOutput = file_get_contents($mergedPath);
        } else {
            // Fallback to main PDF only if merge failed
            $pdfOutput = $pdfMain->output();
        }

        // Cleanup temporary files
        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($mergedPath);

        return $pdfOutput;
    }
}
