<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\AlokasiPetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Http\Requests\UpdateNonResponseRequest;
use App\Imports\AlokasiPetugasImport;
use App\Imports\AlokasiPetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\AlokasiPetugasFrameSampel;
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Vinkla\Hashids\Facades\Hashids;

trait AlokasiPetugasPeriodActions
{
    public function submitPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        // Allow submitting 'draft' or re-submitting 'perubahan'
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->whereIn('status', ['draft', 'perubahan'])
            ->orderByDesc('revision_number')
            ->firstOrFail();

        // If this is a revision (has parent_periode_id), keep status as 'perubahan'
        // Otherwise, set to 'dikirim' for first submission
        $newStatus = $periode->parent_periode_id ? 'perubahan' : 'dikirim';

        $periode->update([
            'status' => $newStatus,
            'submitted_by' => effectiveUser($request)->id,
            'submitted_at' => now(),
        ]);

        $bulanName = Carbon::create()->month((int) $bulan)->translatedFormat('F');
        $totalPetugas = $periode->alokasiPetugas()->count();

        ActivityLog::log(
            $newStatus === 'perubahan' ? 'Kirim Perubahan Alokasi' : 'Kirim Alokasi',
            'alokasi',
            "Berhasil mengirim alokasi {$kegiatan->nama_kegiatan} untuk {$bulanName} {$tahun} ({$totalPetugas} petugas)",
            'success',
            [
                'periode_id' => $periode->id,
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_nama' => $kegiatan->nama_kegiatan,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_petugas' => $totalPetugas,
                'status' => $newStatus,
            ]
        );

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dikirim untuk pembuatan SK KPA dan SPK.');
    }

    public function showPeriode(string $kegiatanRouteKey, string $tahun, string $bulan): Response
    {
        $tahun = (int) $tahun;
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        // Get the latest periode
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->orderByDesc('revision_number')
            ->with([
                'alokasiPetugas.petugas',
                'alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel',
                'submittedBy:id,name',
            ])
            ->firstOrFail();

        $kegiatan->loadMissing([
            'rateHonors' => function ($query) use ($tahun) {
                $query->where('tahun_berlaku', $tahun)
                    ->where('status', 'aktif');
            },
            'kegiatanFrameSampel' => function ($query) {
                $query->select('id', 'kegiatan_id', 'tahapan', 'nama_target', 'sample_role', 'is_active', 'nama_frame', 'kode_kecamatan', 'kode_desa', 'kode_sls', 'kode_sub_sls', 'kode_segmen', 'target_unit_sampel', 'identitas_tambahan')
                    ->orderBy('tahapan')
                    ->orderBy('id');
            },
        ]);

        $rateHonorByKey = $kegiatan->rateHonors->keyBy(function ($rateHonor) {
            return $rateHonor->status_kepegawaian.'|'.$rateHonor->jenis_penugasan;
        });

        $resolveRateHonor = static function (AlokasiPetugas $alokasi) use ($rateHonorByKey) {
            $statusKepegawaian = $alokasi->status_kepegawaian
                ?? (($alokasi->petugas->jenis_petugas ?? 'non-organik') === 'organik' ? 'organik' : 'non_organik');

            return $rateHonorByKey->get($statusKepegawaian.'|'.$alokasi->peran);
        };

        $resolveEffectivePencacahanHonor = static function (AlokasiPetugas $alokasi): float {
            return $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                ? (float) $alokasi->estimasi_honor_partial
                : (float) ($alokasi->total_honor ?? 0);
        };

        $resolveEffectiveListingHonor = static function (AlokasiPetugas $alokasi): float {
            return $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                ? (float) $alokasi->estimasi_honor_partial_listing
                : (float) ($alokasi->total_honor_listing ?? 0);
        };

        $frameMetadataColumns = $this->extractFrameSampelMetadataColumns($kegiatan->kegiatanFrameSampel);

        $mapFrameSampleDetail = function (KegiatanFrameSampel $frameSample) use ($frameMetadataColumns): array {
            $targetUnitSampel = is_array($frameSample->target_unit_sampel)
                ? $frameSample->target_unit_sampel
                : [];

            $totalTargetUnit = array_sum(array_map('floatval', $targetUnitSampel));

            $metadataItems = collect($frameMetadataColumns)
                ->map(function (array $column) use ($frameSample): array {
                    $code = trim((string) ($column['code'] ?? ''));
                    $label = trim((string) ($column['label'] ?? ''));

                    if ($code === '') {
                        return [];
                    }

                    $normalizedCode = Str::lower($code);
                    $codeValue = $this->resolveFrameMetadataRawValue($frameSample, $normalizedCode);
                    $labelValue = $this->resolveFrameMetadataLabelValue($frameSample, $normalizedCode);

                    if ($codeValue === '-' && $labelValue === '-') {
                        return [];
                    }

                    $hasCodeValue = $codeValue !== '-' && trim($codeValue) !== '';
                    $hasLabelValue = $labelValue !== '-' && trim($labelValue) !== '';

                    return [
                        'code' => $code,
                        'label' => $label !== '' ? $label : $this->formatMetadataLabel($code),
                        'codeValue' => $hasCodeValue ? $codeValue : '',
                        'labelValue' => $hasLabelValue ? $labelValue : '',
                        'displayMode' => $hasCodeValue && $hasLabelValue
                            ? 'code_name'
                            : ($hasCodeValue ? 'code_only' : 'name_only'),
                    ];
                })
                ->filter(static fn (array $item): bool => ! empty($item))
                ->values()
                ->all();

            return [
                'id' => $frameSample->id,
                'kegiatan_frame_sampel_id' => $frameSample->id,
                'tahapan' => $frameSample->tahapan,
                'nama_target' => $frameSample->nama_target,
                'sample_role' => $frameSample->sample_role,
                'is_active' => (bool) $frameSample->is_active,
                'nama_frame' => $frameSample->nama_frame,
                'kode_kecamatan' => $frameSample->kode_kecamatan,
                'kode_desa' => $frameSample->kode_desa,
                'kode_sls' => $frameSample->kode_sls,
                'kode_sub_sls' => $frameSample->kode_sub_sls,
                'kode_segmen' => $frameSample->kode_segmen,
                'nks' => $frameSample->kode_sls
                    ?: data_get($frameSample->identitas_tambahan, 'kdsls')
                    ?: data_get($frameSample->identitas_tambahan, 'kode_sls')
                    ?: data_get($frameSample->identitas_tambahan, 'sls')
                    ?: data_get($frameSample->identitas_tambahan, 'nks')
                    ?: data_get($frameSample->identitas_tambahan, 'kode_nks')
                    ?: $frameSample->kode_segmen,
                'nama_usaha_penggilingan' => $frameSample->nama_target ?: $frameSample->nama_frame,
                'target_unit_sampel' => $targetUnitSampel,
                'target_unit_total' => $totalTargetUnit > 0 ? $totalTargetUnit : 0,
                'identitas_tambahan' => $frameSample->identitas_tambahan,
                'metadata_items' => $metadataItems,
            ];
        };

        $frameSamplePool = $kegiatan->kegiatanFrameSampel
            ->map($mapFrameSampleDetail)
            ->values()
            ->all();

        // Calculate totals
        $totalEstimasiPencacahan = $periode->alokasiPetugas->sum(fn ($alokasi) => $resolveEffectivePencacahanHonor($alokasi));
        $totalEstimasiListing = $periode->alokasiPetugas->sum(fn ($alokasi) => $resolveEffectiveListingHonor($alokasi));
        $totalEstimasi = $totalEstimasiPencacahan + $totalEstimasiListing;
        $jumlahPetugas = $periode->alokasiPetugas->count();

        // Format periode data
        $periodeData = [
            'id' => $periode->id,
            'kegiatan_id' => $periode->kegiatan_id,
            'bulan' => $periode->bulan,
            'tahun' => $periode->tahun,
            'jenis_kegiatan' => $periode->jenis_kegiatan,
            'tahapan' => $periode->tahapan,
            'tanggal_mulai' => $periode->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $periode->tanggal_selesai?->format('Y-m-d'),
            'tanggal_mulai_listing' => $periode->tanggal_mulai_listing?->format('Y-m-d'),
            'tanggal_selesai_listing' => $periode->tanggal_selesai_listing?->format('Y-m-d'),
            'status' => $periode->status,
            'revision_number' => $periode->revision_number,
            'parent_periode_id' => $periode->parent_periode_id,
            'submitted_at' => $periode->submitted_at,
            'submitted_by_name' => $periode->submittedBy?->name,
            'kegiatan' => [
                'id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'metode_sampling' => $kegiatan->metode_sampling,
                'hashed_id' => $kegiatan->hashed_id,
                'has_listing_updating' => $kegiatan->has_listing_updating ?? false,
                'frame_metadata_columns' => $this->extractFrameSampelMetadataColumns($kegiatan->kegiatanFrameSampel),
                'kegiatan_frame_sampel' => $frameSamplePool,
                'rate_honors' => $kegiatan->rateHonors->map(static function (RateHonor $rateHonor): array {
                    return [
                        'status_kepegawaian' => $rateHonor->status_kepegawaian,
                        'jenis_penugasan' => $rateHonor->jenis_penugasan,
                        'rate' => (float) ($rateHonor->rate ?? 0),
                        'rate_listing' => (float) ($rateHonor->rate_listing ?? 0),
                    ];
                })->values()->all(),
            ],
            'alokasi_petugas' => $periode->alokasiPetugas->map(function ($alokasi) use ($resolveRateHonor, $mapFrameSampleDetail) {
                $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                    ? (float) $alokasi->estimasi_honor_partial
                    : (float) ($alokasi->total_honor ?? 0);
                $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                    ? (float) $alokasi->estimasi_honor_partial_listing
                    : (float) ($alokasi->total_honor_listing ?? 0);
                $paidJumlahSatuan = $alokasi->is_partial_payment && $alokasi->partial_jumlah_satuan !== null
                    ? $this->normalizeSatuanForResponse($alokasi->partial_jumlah_satuan)
                    : $this->normalizeSatuanForResponse($alokasi->jumlah_satuan);
                $paidJumlahSatuanListing = $alokasi->is_partial_payment_listing && $alokasi->partial_jumlah_satuan_listing !== null
                    ? (int) $alokasi->partial_jumlah_satuan_listing
                    : (int) ($alokasi->jumlah_satuan_listing ?? 0);
                $rateHonor = $resolveRateHonor($alokasi);
                $frameSampleDetails = $alokasi->frameSampelAllocations
                    ->map(function (AlokasiPetugasFrameSampel $frameAllocation) use ($mapFrameSampleDetail) {
                        if (! $frameAllocation->kegiatanFrameSampel) {
                            return null;
                        }

                        return array_merge(
                            $mapFrameSampleDetail($frameAllocation->kegiatanFrameSampel),
                            [
                                'frame_allocation_id' => $frameAllocation->id,
                                'is_non_response' => (bool) ($frameAllocation->is_non_response ?? false),
                            ],
                        );
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $alokasi->id,
                    'petugas' => [
                        'id' => $alokasi->petugas->id,
                        'nama' => $alokasi->petugas->nama,
                        'jenis_petugas' => $alokasi->petugas->jenis_petugas,
                    ],
                    'peran' => $alokasi->peran,
                    'jumlah_satuan' => $this->normalizeSatuanForResponse($alokasi->jumlah_satuan),
                    'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                    'jumlah_satuan_dibayarkan' => $paidJumlahSatuan,
                    'jumlah_satuan_listing_dibayarkan' => $paidJumlahSatuanListing,
                    'total_honor' => $effectivePencacahanHonor,
                    'total_honor_listing' => $effectiveListingHonor,
                    'rate_pencacahan' => (float) ($rateHonor?->rate ?? 0),
                    'rate_listing' => (float) ($rateHonor?->rate_listing ?? 0),
                    'catatan' => $alokasi->catatan,
                    'non_response' => $alokasi->non_response,
                    'non_response_listing' => $alokasi->non_response_listing,
                    'frame_sampel_details' => $frameSampleDetails,
                ];
            }),
            'total_estimasi' => $totalEstimasi,
            'total_estimasi_pencacahan' => $totalEstimasiPencacahan,
            'total_estimasi_listing' => $totalEstimasiListing,
            'jumlah_petugas' => $jumlahPetugas,
            'kegiatan_frame_sampel' => $frameSamplePool,
        ];

        // Get revision history - include all previous versions (status 'direvisi' and older active versions)
        $revisions = [];

        // Get all periode with same kegiatan, tahun, bulan but different revision numbers or status 'direvisi'
        $allRevisions = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where(function ($query) use ($periode) {
                // Get older revision numbers OR status 'direvisi'
                $query->where('revision_number', '<', $periode->revision_number)
                    ->orWhere('status', 'direvisi');
            })
            ->orderByDesc('revision_number')
            ->with([
                'alokasiPetugas.petugas',
                'submittedBy:id,name',
            ])
            ->get()
            ->map(function ($rev) use ($resolveEffectivePencacahanHonor, $resolveEffectiveListingHonor, $resolveRateHonor) {
                $totalPencacahan = $rev->alokasiPetugas->sum(fn ($alokasi) => $resolveEffectivePencacahanHonor($alokasi));
                $totalListing = $rev->alokasiPetugas->sum(fn ($alokasi) => $resolveEffectiveListingHonor($alokasi));

                return [
                    'id' => $rev->id,
                    'revision_number' => $rev->revision_number,
                    'status' => $rev->status,
                    'submitted_at' => $rev->submitted_at,
                    'submitted_by_name' => $rev->submittedBy?->name,
                    'alokasi_petugas' => $rev->alokasiPetugas->map(function ($alokasi) use ($resolveRateHonor) {
                        $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                            ? (float) $alokasi->estimasi_honor_partial
                            : (float) ($alokasi->total_honor ?? 0);
                        $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                            ? (float) $alokasi->estimasi_honor_partial_listing
                            : (float) ($alokasi->total_honor_listing ?? 0);
                        $paidJumlahSatuan = $alokasi->is_partial_payment && $alokasi->partial_jumlah_satuan !== null
                            ? $this->normalizeSatuanForResponse($alokasi->partial_jumlah_satuan)
                            : $this->normalizeSatuanForResponse($alokasi->jumlah_satuan);
                        $paidJumlahSatuanListing = $alokasi->is_partial_payment_listing && $alokasi->partial_jumlah_satuan_listing !== null
                            ? (int) $alokasi->partial_jumlah_satuan_listing
                            : (int) ($alokasi->jumlah_satuan_listing ?? 0);
                        $rateHonor = $resolveRateHonor($alokasi);

                        return [
                            'id' => $alokasi->id,
                            'petugas' => [
                                'id' => $alokasi->petugas->id,
                                'nama' => $alokasi->petugas->nama,
                                'jenis_petugas' => $alokasi->petugas->jenis_petugas,
                            ],
                            'peran' => $alokasi->peran,
                            'jumlah_satuan' => $this->normalizeSatuanForResponse($alokasi->jumlah_satuan),
                            'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                            'jumlah_satuan_dibayarkan' => $paidJumlahSatuan,
                            'jumlah_satuan_listing_dibayarkan' => $paidJumlahSatuanListing,
                            'total_honor' => $effectivePencacahanHonor,
                            'total_honor_listing' => $effectiveListingHonor,
                            'rate_pencacahan' => (float) ($rateHonor?->rate ?? 0),
                            'rate_listing' => (float) ($rateHonor?->rate_listing ?? 0),
                            'catatan' => $alokasi->catatan,
                            'non_response' => $alokasi->non_response,
                            'non_response_listing' => $alokasi->non_response_listing,
                        ];
                    }),
                    'total_estimasi' => $totalPencacahan + $totalListing,
                    'total_estimasi_pencacahan' => $totalPencacahan,
                    'total_estimasi_listing' => $totalListing,
                    'jumlah_petugas' => $rev->alokasiPetugas->count(),
                ];
            })
            ->toArray();

        $revisions = $allRevisions;

        return Inertia::render('Alokasi/ShowPeriode', [
            'periode' => $periodeData,
            'revisions' => $revisions,
        ]);
    }

    public function exportMonitoringPdf(string $kegiatanRouteKey, int $tahun, string $bulan): HttpResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);

        $periode = PeriodeAlokasi::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $this->resolveBulanCandidates($bulan))
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->orderByDesc('revision_number')
            ->with([
                'alokasiPetugas.petugas',
                'alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel',
                'submittedBy:id,name',
            ])
            ->firstOrFail();

        $kegiatan->loadMissing([
            'ketuaTim:id,name',
            'kegiatanFrameSampel' => function ($query) {
                $query->select('id', 'kegiatan_id', 'tahapan', 'nama_target', 'sample_role', 'is_active', 'nama_frame', 'kode_kecamatan', 'kode_desa', 'kode_sls', 'kode_sub_sls', 'kode_segmen', 'target_unit_sampel', 'identitas_tambahan')
                    ->orderBy('tahapan')
                    ->orderBy('id');
            },
        ]);

        $kepala = Penandatangan::active()->kepala()->first();
        $reportData = $this->buildMonitoringReportData($kegiatan, $periode, $kepala);
        $pdf = Pdf::loadView('monitoring-pdf', $reportData)
            ->setPaper('a4', 'landscape');

        $filename = sprintf(
            'monitoring_%s_%s_%s_%s.pdf',
            $kegiatan->nama_kegiatan,
            $tahun,
            str_pad((string) $reportData['bulan'], 2, '0', STR_PAD_LEFT),
            now()->format('Ymd_His'),
        );

        return $pdf->download($filename);
    }

    public function exportMonitoringSkgbPdf(string $kegiatanRouteKey, int $tahun, string $bulan): HttpResponse
    {
        return $this->exportMonitoringPdf($kegiatanRouteKey, $tahun, $bulan);
    }

    public function editPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): Response|RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $resolvedRoutePeriode = $this->resolvePeriodeRouteBinding($kegiatanRouteKey);

        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);
        $bulanCandidates = array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));

        // Revision mode must be explicit on the URL. Session is still preferred, but a valid
        // revision URL should remain readable even if the session was lost after redirect.
        $hasRevisionUrl = $request->query('mode') === 'revisi';
        $routeAllowsRevision = $resolvedRoutePeriode instanceof PeriodeAlokasi
            && (int) $resolvedRoutePeriode->kegiatan_id === (int) $kegiatan->id
            && (int) $resolvedRoutePeriode->tahun === $tahun
            && in_array(
                str_pad((string) $resolvedRoutePeriode->bulan, 2, '0', STR_PAD_LEFT),
                $bulanCandidates,
                true,
            )
            && in_array($resolvedRoutePeriode->status, ['dikirim', 'perubahan'], true);

        $isRevisiMode = $hasRevisionUrl
            && (
                $request->session()->get('is_revisi_mode', false)
                || $routeAllowsRevision
            );

        if ($isRevisiMode) {
            $sessionKegiatanId = (int) $request->session()->get('revisi_kegiatan_id', 0);
            $sessionTahun = (int) $request->session()->get('revisi_tahun', 0);
            $sessionBulan = (string) $request->session()->get('revisi_bulan', '');

            if (
                $sessionKegiatanId !== (int) $kegiatan->id ||
                $sessionTahun !== $tahun ||
                ! in_array($sessionBulan, $bulanCandidates, true)
            ) {
                $request->session()->forget([
                    'is_revisi_mode',
                    'revisi_parent_periode_id',
                    'revisi_kegiatan_id',
                    'revisi_tahun',
                    'revisi_bulan',
                ]);

                $isRevisiMode = false;
            }
        }

        if ($isRevisiMode) {
            // Load data from parent periode for revision
            $parentPeriodeId = $request->session()->get('revisi_parent_periode_id');
            $periode = $parentPeriodeId
                ? PeriodeAlokasi::with(['alokasiPetugas.petugas', 'alokasiPetugas.frameSampelAllocations'])->find($parentPeriodeId)
                : null;

            if (! $periode) {
                $resolvedPeriode = $this->resolvePeriodeRouteBinding($kegiatanRouteKey);

                if (
                    $resolvedPeriode instanceof PeriodeAlokasi
                    && (int) $resolvedPeriode->kegiatan_id === (int) $kegiatan->id
                    && (int) $resolvedPeriode->tahun === $tahun
                    && in_array(
                        str_pad((string) $resolvedPeriode->bulan, 2, '0', STR_PAD_LEFT),
                        $bulanCandidates,
                        true,
                    )
                    && in_array($resolvedPeriode->status, ['dikirim', 'perubahan'], true)
                ) {
                    $periode = $resolvedPeriode->load([
                        'alokasiPetugas.petugas',
                        'alokasiPetugas.frameSampelAllocations',
                    ]);
                }
            }

            if (! $periode) {
                return redirect()->route('alokasi.index')
                    ->with('error', 'Periode alokasi tidak ditemukan atau tidak dapat diedit.');
            }
        } else {
            if (
                $resolvedRoutePeriode instanceof PeriodeAlokasi
                && (int) $resolvedRoutePeriode->kegiatan_id === (int) $kegiatan->id
                && (int) $resolvedRoutePeriode->tahun === $tahun
                && in_array(
                    str_pad((string) $resolvedRoutePeriode->bulan, 2, '0', STR_PAD_LEFT),
                    $bulanCandidates,
                    true,
                )
            ) {
                $periode = $resolvedRoutePeriode->load([
                    'alokasiPetugas.petugas',
                    'alokasiPetugas.frameSampelAllocations',
                ]);
            } else {
                // Load existing draft/perubahan periode for editing
                $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('tahun', $tahun)
                    ->whereIn('bulan', $bulanCandidates)
                    ->whereIn('status', ['draft', 'perubahan'])
                    ->orderByDesc('revision_number')
                    ->with(['alokasiPetugas.petugas', 'alokasiPetugas.frameSampelAllocations'])
                    ->first();

                if (! $periode) {
                    return redirect()->route('alokasi.index')
                        ->with('error', 'Periode alokasi tidak ditemukan atau tidak dapat diedit.');
                }
            }
        }

        if ($periode->alokasiPetugas->isEmpty()) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada alokasi untuk periode ini.');
        }

        $excludedBudgetPeriodeIds = [$periode->id];

        // Load kegiatan with active-year rate honors and enrich each rate with SBML limit,
        // matching the structure used by create mode.
        $activeYear = ActiveYearService::get();

        $kegiatanWithRates = Kegiatan::where('id', $kegiatan->id)
            ->with([
                'rateHonors' => function ($query) use ($activeYear) {
                    $query->where('status', 'aktif')
                        ->where('tahun_berlaku', $activeYear)
                        ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'rate_listing', 'satuan_id', 'satuan_listing_id')
                        ->with([
                            'satuan:id,kode,nama',
                            'satuanListing:id,kode,nama',
                        ]);
                },
                'kegiatanFrameSampel' => function ($query) {
                    $query->select('id', 'kegiatan_id', 'tahapan', 'nama_target', 'sample_role', 'is_active', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
                        ->orderBy('tahapan')
                        ->orderBy('id');
                },
            ])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'metode_sampling', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai', 'unit_sampel_pencacahan_ids', 'unit_sampel_listing_ids')
            ->firstOrFail();

        foreach ($kegiatanWithRates->rateHonors as $rateHonor) {
            $sbml = Sbml::where('tahun_anggaran', $activeYear)
                ->where('jenis_kegiatan', $rateHonor->jenis_kegiatan)
                ->where('status_kepegawaian', $rateHonor->status_kepegawaian)
                ->where('jenis_penugasan', $rateHonor->jenis_penugasan)
                ->where('status', 'aktif')
                ->first();

            $rateHonor->sbml_limit = $sbml ? $sbml->honor_max : null;
        }

        $kegiatanWithRates->setAttribute(
            'unit_sampel_pencacahan_items',
            $kegiatanWithRates->unitSampelPencacahanItems()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ])
                ->values()
                ->all()
        );

        $kegiatanWithRates->setAttribute(
            'unit_sampel_listing_items',
            $kegiatanWithRates->unitSampelListingItems()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ])
                ->values()
                ->all()
        );

        // Load all petugas
        $petugas = Petugas::select('id', 'nama', 'jenis_petugas', 'golongan', 'jabatan', 'desa_kelurahan')
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'jenis_petugas' => $p->jenis_petugas,
                    'jabatan' => $p->jabatan,
                    'desa_kelurahan' => $p->desa_kelurahan,
                ];
            });

        $petugasSuggestions = $this->buildPetugasSuggestions(collect([$kegiatanWithRates]), $activeYear);
        $petugasUniqueKegiatanCounts = $this->buildPetugasUniqueKegiatanCounts($activeYear);
        $petugasAllocationCounts = $this->buildPetugasAllocationCounts($activeYear);
        $petugasTotalHonor = $this->buildPetugasTotalHonorByYear($activeYear);
        $petugasReviewRecommendations = $this->buildPetugasReviewRecommendations($activeYear);

        // Convert existing alokasi to format expected by Manage view
        $existingAlokasi = $periode->alokasiPetugas->map(function ($alok) {
            return [
                'petugas_id' => $alok->petugas_id,
                'petugas_nama' => $alok->petugas->nama,
                'status_kepegawaian' => $alok->petugas->jenis_petugas,
                'peran' => $alok->peran,
                'jumlah_satuan' => $this->normalizeSatuanForResponse($alok->jumlah_satuan),
                'jumlah_satuan_listing' => $alok->jumlah_satuan_listing,
                'total_honor' => (float) ($alok->total_honor ?? 0),
                'total_honor_listing' => (float) ($alok->total_honor_listing ?? 0),
                'is_partial_payment' => (bool) $alok->is_partial_payment,
                'partial_jumlah_satuan' => $this->normalizeSatuanForResponse($alok->partial_jumlah_satuan),
                'estimasi_honor_partial' => $alok->estimasi_honor_partial,
                'is_partial_payment_listing' => (bool) $alok->is_partial_payment_listing,
                'partial_jumlah_satuan_listing' => $alok->partial_jumlah_satuan_listing,
                'estimasi_honor_partial_listing' => $alok->estimasi_honor_partial_listing,
                'jumlah_unit_sampel' => (int) ($alok->jumlah_unit_sampel ?? 0),
                'frame_sampel_ids' => $alok->frameSampelAllocations->pluck('kegiatan_frame_sampel_id')->map(fn ($value) => (int) $value)->values()->all(),
                'catatan' => $alok->catatan,
            ];
        });

        // Existing allocations by petugas in month/year for SBML toggle check (exclude current periode)
        $existingAllocations = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $tahun)
            ->whereIn('pa.bulan', $bulanCandidates)
            ->whereIn('pa.status', ['draft', 'dikirim', 'perubahan', 'direvisi'])
            ->where('pa.id', '!=', $periode->id)
            ->select('alokasi_petugas.petugas_id', 'pa.bulan', 'pa.tahun')
            ->selectRaw('SUM(CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) as total_honor_pencacahan')
            ->selectRaw('SUM(CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END) as total_honor_listing')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id', 'pa.bulan', 'pa.tahun')
            ->get()
            ->map(function ($item) {
                return [
                    'petugas_id' => (int) $item->petugas_id,
                    'bulan' => (int) $item->bulan,
                    'tahun' => (int) $item->tahun,
                    'total_honor_pencacahan' => (float) $item->total_honor_pencacahan,
                    'total_honor_listing' => (float) $item->total_honor_listing,
                    'total_honor_combined' => (float) $item->total_honor_combined,
                ];
            })
            ->toArray();

        // Get used months for this kegiatan to prevent duplicates (exclude current month being edited)
        $usedMonths = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereNotIn('bulan', $bulanCandidates)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan', 'direvisi', 'disetujui'])
            ->whereNotIn('id', $excludedBudgetPeriodeIds)
            ->pluck('bulan')
            ->map(fn ($b) => (int) $b)
            ->toArray();

        // Calculate budget info from all periods except the active edit/revision chain.
        $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->whereNotIn('id', $excludedBudgetPeriodeIds)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor');
            });

        $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->whereNotIn('id', $excludedBudgetPeriodeIds)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor_listing');
            });

        $budgetInfo = [
            $kegiatan->id => [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'current_total_spent_other_periods' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
                'current_total_spent_listing_other_periods' => $totalSpentListing,
            ],
        ];

        // Used months info
        $usedMonthsInfo = [
            $kegiatan->id => $usedMonths,
        ];

        $effectiveBudgetPeriodes = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereNotIn('bulan', $bulanCandidates)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->whereNotIn('id', $excludedBudgetPeriodeIds)
            ->get()
            ->groupBy(fn (PeriodeAlokasi $periodeItem) => str_pad((string) ((int) $periodeItem->bulan), 2, '0', STR_PAD_LEFT))
            ->map(fn (Collection $periodesByMonth) => $this->resolveEffectiveBudgetPeriode($periodesByMonth))
            ->filter()
            ->values();

        $totalSpent = $effectiveBudgetPeriodes->sum(function (PeriodeAlokasi $periodeItem) {
            return $periodeItem->alokasiPetugas->sum(function (AlokasiPetugas $alokasi) {
                return $alokasi->getEffectiveTotalHonor();
            });
        });

        $totalSpentListing = $effectiveBudgetPeriodes->sum(function (PeriodeAlokasi $periodeItem) {
            return $periodeItem->alokasiPetugas->sum(function (AlokasiPetugas $alokasi) {
                return $alokasi->getEffectiveTotalHonorListing();
            });
        });

        // Keep revisi mode in session for updatePeriode
        // Don't forget it here, will be handled in updatePeriode

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => [$kegiatanWithRates],
            'petugas' => $petugas,
            'selectedKegiatan' => $kegiatanWithRates,
            'active_year' => $activeYear,
            'copiedAlokasi' => $existingAlokasi,
            'sourcePeriode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'tahapan' => $periode->tahapan ?? 'both',
                'tanggal_mulai' => $periode->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai' => $periode->tanggal_selesai?->format('Y-m-d'),
                'tanggal_mulai_listing' => $periode->tanggal_mulai_listing?->format('Y-m-d'),
                'tanggal_selesai_listing' => $periode->tanggal_selesai_listing?->format('Y-m-d'),
                'jadwal_pengolahan_listing_mulai' => $periode->jadwal_pengolahan_listing_mulai?->format('Y-m-d'),
                'jadwal_pengolahan_listing_selesai' => $periode->jadwal_pengolahan_listing_selesai?->format('Y-m-d'),
                'jadwal_pengolahan_pencacahan_mulai' => $periode->jadwal_pengolahan_pencacahan_mulai?->format('Y-m-d'),
                'jadwal_pengolahan_pencacahan_selesai' => $periode->jadwal_pengolahan_pencacahan_selesai?->format('Y-m-d'),
            ],
            'existing_allocations' => $existingAllocations,
            'budget_info' => $budgetInfo,
            'used_months_info' => $usedMonthsInfo,
            'petugas_suggestions' => $petugasSuggestions,
            'petugas_unique_kegiatan_counts' => $petugasUniqueKegiatanCounts,
            'petugas_allocation_counts' => $petugasAllocationCounts,
            'petugas_total_honor' => $petugasTotalHonor,
            'petugas_review_recommendations' => $petugasReviewRecommendations,
            'isEditMode' => true,
            'isRevisiMode' => $isRevisiMode,
        ]);
    }

    public function updatePeriode(Request $request, string $kegiatanRouteKey, string $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, (int) $tahun, $bulan);

        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);
        $bulanCandidates = array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));

        // Convert tahun to int for consistency
        $tahun = (int) $tahun;

        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa diperbarui untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim can only update alokasi for their own kegiatan
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->hasActiveRole('ketua_tim') && ! ($kegiatan->ketua_tim_user_id === $effectiveUser->id || $kegiatan->pj_lainnya_id === $effectiveUser->id)) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui alokasi kegiatan ini.');
        }

        // Validate that kegiatan has rate honors
        if ($kegiatan->rateHonors()->count() === 0) {
            return redirect()->back()->withErrors([
                'error' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.petugas_id' => 'required|exists:petugas,id',
            'alokasi.*.peran' => 'required|string|in:PCL,PML,Koseka,Pengolahan,Petugas Pengolahan,Pengawas Pengolahan',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|numeric|min:0',
            'alokasi.*.jumlah_satuan_listing' => 'nullable|integer|min:0',
            'alokasi.*.jenis_kegiatan' => 'required|in:sensus,survei',
            'alokasi.*.tahapan' => 'nullable|in:both,listing_only,pencacahan_only',
            'alokasi.*.catatan' => 'nullable|string',
            'alokasi.*.is_partial_payment' => 'nullable|boolean',
            'alokasi.*.partial_jumlah_satuan' => 'nullable|numeric|min:0',
            'alokasi.*.is_partial_payment_listing' => 'nullable|boolean',
            'alokasi.*.partial_jumlah_satuan_listing' => 'nullable|integer|min:0',
            'alokasi.*.frame_sampel_ids' => 'nullable|array',
            'alokasi.*.frame_sampel_ids.*' => 'integer|exists:kegiatan_frame_sampel,id',
            'alokasi.*.jumlah_unit_sampel' => 'nullable|integer|min:0',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'tanggal_mulai_listing' => 'nullable|date',
            'tanggal_selesai_listing' => 'nullable|date|after_or_equal:tanggal_mulai_listing',
            'jadwal_pengolahan_listing_mulai' => 'nullable|date',
            'jadwal_pengolahan_listing_selesai' => 'nullable|date|after_or_equal:jadwal_pengolahan_listing_mulai',
            'jadwal_pengolahan_pencacahan_mulai' => 'nullable|date',
            'jadwal_pengolahan_pencacahan_selesai' => 'nullable|date|after_or_equal:jadwal_pengolahan_pencacahan_mulai',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'tanggal_selesai_listing.after_or_equal' => 'Tanggal selesai listing harus setelah atau sama dengan tanggal mulai listing.',
            'jadwal_pengolahan_listing_selesai.after_or_equal' => 'Tanggal selesai pengolahan listing harus setelah atau sama dengan tanggal mulai.',
            'jadwal_pengolahan_pencacahan_selesai.after_or_equal' => 'Tanggal selesai pengolahan pencacahan harus setelah atau sama dengan tanggal mulai.',
        ]);

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';

        $decimalValidationErrors = $this->validateDecimalSatuanRules($validated['alokasi']);
        if (! empty($decimalValidationErrors)) {
            return redirect()->back()->withErrors([
                'decimal_validation' => implode("\n", array_unique($decimalValidationErrors)),
            ])->withInput();
        }

        $tahapan = $validated['alokasi'][0]['tahapan'] ?? 'both';
        $dateValidationErrors = [];

        if ($isSensusKegiatan) {
            $dateValidationErrors = $this->validateDatesWithinKegiatanPeriod($kegiatan, $validated, $tahapan);
        } else {
            // Use the new bulan/tahun from form data (user may have changed the period)
            $periodeBulan = isset($validated['alokasi'][0]['bulan']) ? (int) $validated['alokasi'][0]['bulan'] : (int) $bulan;
            $periodeTahun = isset($validated['alokasi'][0]['tahun']) ? (int) $validated['alokasi'][0]['tahun'] : (int) $tahun;

            if ($tahapan !== 'listing_only' && isset($validated['tanggal_mulai'])) {
                $tanggalMulaiBulan = Carbon::parse($validated['tanggal_mulai'])->month;
                $tanggalMulaiTahun = Carbon::parse($validated['tanggal_mulai'])->year;
                if ($tanggalMulaiBulan !== $periodeBulan || $tanggalMulaiTahun !== $periodeTahun) {
                    $dateValidationErrors[] = 'Tanggal mulai harus dalam bulan yang sama dengan periode alokasi.';
                }
            }

            if ($tahapan !== 'listing_only' && isset($validated['tanggal_selesai'])) {
                $tanggalSelesaiBulan = Carbon::parse($validated['tanggal_selesai'])->month;
                $tanggalSelesaiTahun = Carbon::parse($validated['tanggal_selesai'])->year;
                if ($tanggalSelesaiBulan !== $periodeBulan || $tanggalSelesaiTahun !== $periodeTahun) {
                    $dateValidationErrors[] = 'Tanggal selesai harus dalam bulan yang sama dengan periode alokasi.';
                }
            }

            if (($tahapan === 'both' || $tahapan === 'listing_only') && isset($validated['tanggal_mulai_listing'])) {
                $tanggalMulaiListingBulan = Carbon::parse($validated['tanggal_mulai_listing'])->month;
                $tanggalMulaiListingTahun = Carbon::parse($validated['tanggal_mulai_listing'])->year;
                if ($tanggalMulaiListingBulan !== $periodeBulan || $tanggalMulaiListingTahun !== $periodeTahun) {
                    $dateValidationErrors[] = 'Tanggal mulai listing harus dalam bulan yang sama dengan periode alokasi.';
                }
            }

            if (($tahapan === 'both' || $tahapan === 'listing_only') && isset($validated['tanggal_selesai_listing'])) {
                $tanggalSelesaiListingBulan = Carbon::parse($validated['tanggal_selesai_listing'])->month;
                $tanggalSelesaiListingTahun = Carbon::parse($validated['tanggal_selesai_listing'])->year;
                if ($tanggalSelesaiListingBulan !== $periodeBulan || $tanggalSelesaiListingTahun !== $periodeTahun) {
                    $dateValidationErrors[] = 'Tanggal selesai listing harus dalam bulan yang sama dengan periode alokasi.';
                }
            }
        }

        if (! empty($dateValidationErrors)) {
            return redirect()->back()->withErrors([
                'date_validation' => implode("\n", $dateValidationErrors),
            ])->withInput();
        }

        $partialValidationErrors = [];
        foreach ($validated['alokasi'] as $alokasiData) {
            $isPartialPayment = (bool) ($alokasiData['is_partial_payment'] ?? false);
            $partialJumlahSatuan = isset($alokasiData['partial_jumlah_satuan']) ? (float) $alokasiData['partial_jumlah_satuan'] : 0;
            $jumlahSatuan = (float) ($alokasiData['jumlah_satuan'] ?? 0);

            if ($isPartialPayment && $partialJumlahSatuan > $jumlahSatuan) {
                $partialValidationErrors[] = 'Jumlah beban tugas parsial pencacahan tidak boleh melebihi jumlah beban tugas awal.';
            }

            $isPartialPaymentListing = (bool) ($alokasiData['is_partial_payment_listing'] ?? false);
            $partialJumlahSatuanListing = isset($alokasiData['partial_jumlah_satuan_listing']) ? (int) $alokasiData['partial_jumlah_satuan_listing'] : 0;
            $jumlahSatuanListing = isset($alokasiData['jumlah_satuan_listing']) ? (int) $alokasiData['jumlah_satuan_listing'] : 0;

            if ($isPartialPaymentListing && $partialJumlahSatuanListing > $jumlahSatuanListing) {
                $partialValidationErrors[] = 'Jumlah beban tugas parsial listing tidak boleh melebihi jumlah beban tugas listing awal.';
            }
        }

        if (! empty($partialValidationErrors)) {
            return redirect()->back()->withErrors([
                'partial_validation' => implode("\n", array_unique($partialValidationErrors)),
            ])->withInput();
        }

        $sampleFrameValidationErrors = $this->validateSampleFrameAllocations($validated['alokasi'], $kegiatan);
        if (! empty($sampleFrameValidationErrors)) {
            return redirect()->back()->withErrors([
                'sample_frame_validation' => implode("\n", array_unique($sampleFrameValidationErrors)),
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            $hasKegiatanIdColumn = Schema::hasColumn('alokasi_petugas', 'kegiatan_id');
            $hasBulanColumn = Schema::hasColumn('alokasi_petugas', 'bulan');
            $hasTahunColumn = Schema::hasColumn('alokasi_petugas', 'tahun');

            // Check if this is a revision from session
            $isRevision = $request->session()->get('is_revisi_mode', false);
            $parentPeriodeId = $request->session()->get('revisi_parent_periode_id');

            if ($isRevision && $parentPeriodeId) {
                $parentPeriode = PeriodeAlokasi::with('alokasiPetugas')->findOrFail($parentPeriodeId);

                // Get original alokasi from parent periode
                $originalAlokasi = $parentPeriode->alokasiPetugas->map(function ($a) {
                    return [
                        'petugas_id' => (int) $a->petugas_id,
                        'peran' => $a->peran,
                        'jumlah_satuan' => (float) $a->jumlah_satuan,
                        'is_partial_payment' => (bool) $a->is_partial_payment,
                        'partial_jumlah_satuan' => $a->partial_jumlah_satuan !== null ? (float) $a->partial_jumlah_satuan : null,
                        'jumlah_satuan_listing' => $a->jumlah_satuan_listing !== null ? (int) $a->jumlah_satuan_listing : null,
                        'is_partial_payment_listing' => (bool) $a->is_partial_payment_listing,
                        'partial_jumlah_satuan_listing' => $a->partial_jumlah_satuan_listing !== null ? (int) $a->partial_jumlah_satuan_listing : null,
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Format new alokasi for comparison, including partial-payment fields.
                $newAlokasi = collect($validated['alokasi'])->map(function ($a) {
                    return [
                        'petugas_id' => (int) $a['petugas_id'],
                        'peran' => match ($a['peran']) {
                            'PCL' => 'pcl_ppl',
                            'PML' => 'pml',
                            'Koseka' => 'koseka',
                            'Pengolahan' => 'pengolahan',
                            'Pengawas Pengolahan' => 'pengawas_pengolahan',
                            default => null,
                        },
                        'jumlah_satuan' => (float) $a['jumlah_satuan'],
                        'is_partial_payment' => (bool) ($a['is_partial_payment'] ?? false),
                        'partial_jumlah_satuan' => isset($a['partial_jumlah_satuan']) ? (float) $a['partial_jumlah_satuan'] : null,
                        'jumlah_satuan_listing' => isset($a['jumlah_satuan_listing']) ? (int) $a['jumlah_satuan_listing'] : null,
                        'is_partial_payment_listing' => (bool) ($a['is_partial_payment_listing'] ?? false),
                        'partial_jumlah_satuan_listing' => isset($a['partial_jumlah_satuan_listing']) ? (int) $a['partial_jumlah_satuan_listing'] : null,
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Check if there are changes, including partial-payment adjustments.
                $hasChanges = json_encode($originalAlokasi) !== json_encode($newAlokasi);
                // If no changes, just redirect without creating anything
                if (! $hasChanges) {
                    // Clear session
                    $request->session()->forget(['is_revisi_mode', 'revisi_parent_periode_id', 'revisi_kegiatan_id', 'revisi_tahun', 'revisi_bulan']);

                    DB::commit();

                    return redirect()->route('alokasi.index')
                        ->with('info', 'Tidak ada perubahan data. Revisi dibatalkan.');
                }

                // If there are changes, create new periode with 'perubahan' status
                $revisionNumber = ($parentPeriode->revision_number ?? 0) + 1;

                $periode = PeriodeAlokasi::create([
                    'kegiatan_id' => $parentPeriode->kegiatan_id,
                    'parent_periode_id' => $parentPeriode->parent_periode_id ?? $parentPeriode->id,
                    'revision_number' => $revisionNumber,
                    'bulan' => $parentPeriode->bulan,
                    'tahun' => $parentPeriode->tahun,
                    'jenis_kegiatan' => $parentPeriode->jenis_kegiatan,
                    'tahapan' => $validated['alokasi'][0]['tahapan'] ?? $parentPeriode->tahapan ?? 'both',
                    'status' => 'perubahan',
                ]);

                // Mark parent as 'direvisi'
                $parentPeriode->update(['status' => 'direvisi']);

                // Session is cleared after commit to preserve revisi state if validation fails
                // Now create alokasi for new periode (continue to loop below)
            } else {
                // Normal edit - find existing periode

                $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('tahun', $tahun)
                    ->whereIn('bulan', $bulanCandidates)
                    ->whereIn('status', ['draft', 'perubahan'])
                    ->orderByDesc('revision_number')
                    ->first();

                if (! $periode) {
                    DB::rollBack();

                    return redirect()->back()->withErrors(['error' => 'Periode tidak ditemukan atau tidak dapat diedit.']);
                }

                // Update tahapan, and also bulan/tahun if they were changed
                $periode->update([
                    'tahapan' => $validated['alokasi'][0]['tahapan'] ?? 'both',
                    'bulan' => $validated['alokasi'][0]['bulan'] ?? $periode->bulan,
                    'tahun' => $validated['alokasi'][0]['tahun'] ?? $periode->tahun,
                ]);

                // Delete existing alokasi for update
                AlokasiPetugas::where('periode_alokasi_id', $periode->id)->delete();
            }

            // Create new alokasi entries (only executed if not early return above)

            $errors = [];
            $created = 0;

            $runningHonorByPetugas = [];

            foreach ($validated['alokasi'] as $index => $alokasiData) {
                // Get petugas to determine jenis_petugas
                $petugas = Petugas::find($alokasiData['petugas_id']);
                if (! $petugas) {
                    $errors[] = 'Petugas tidak ditemukan.';

                    continue;
                }

                // Map peran to jenis_penugasan
                $jenisPenugasan = match ($alokasiData['peran']) {
                    'PCL' => 'pcl_ppl',
                    'PML' => 'pml',
                    'Koseka' => 'koseka',
                    'Pengolahan' => 'pengolahan',
                    'Petugas Pengolahan' => 'pengolahan',
                    'Pengawas Pengolahan' => 'pengawas_pengolahan',
                    default => null,
                };

                if (! $jenisPenugasan) {
                    $errors[] = $petugas->nama.': Peran tidak valid.';

                    continue;
                }

                // Get rate honor for this petugas
                $petugasType = $this->resolveStatusKepegawaianFromPetugas($petugas);
                $rateHonor = $kegiatan->rateHonors()
                    ->where('status_kepegawaian', $petugasType)
                    ->where('jenis_penugasan', $jenisPenugasan)
                    ->first();

                if (! $rateHonor) {
                    $errors[] = $petugas->nama.': Rate honor tidak ditemukan untuk ('.$petugasType.') sebagai '.$alokasiData['peran'];

                    continue;
                }

                // Calculate pencacahan honor (can be 0 if listing_only)
                $pencacahanWorkload = $this->resolvePencacahanWorkload(
                    $kegiatan,
                    (float) ($alokasiData['jumlah_satuan'] ?? 0)
                );
                $totalHonor = $this->isSensusEkonomi2026($kegiatan)
                    ? (float) $rateHonor->rate * 2.5
                    : (float) $rateHonor->rate * $pencacahanWorkload;

                // Calculate listing honor if kegiatan has listing phase
                $totalHonorListing = 0;
                $jumlahSatuanListing = 0;
                if ($kegiatan->has_listing_updating) {
                    $jumlahSatuanListing = $alokasiData['jumlah_satuan_listing'] ?? 0;
                    if ($jumlahSatuanListing > 0 && $rateHonor->rate_listing) {
                        $totalHonorListing = $rateHonor->rate_listing * $jumlahSatuanListing;
                    }
                }

                $isPartialPayment = (bool) ($alokasiData['is_partial_payment'] ?? false);
                $partialJumlahSatuan = isset($alokasiData['partial_jumlah_satuan']) ? (float) $alokasiData['partial_jumlah_satuan'] : null;
                $estimasiHonorPartial = null;

                if ($isPartialPayment && $partialJumlahSatuan !== null) {
                    $partialWorkload = $this->resolvePencacahanWorkload(
                        $kegiatan,
                        (float) $partialJumlahSatuan
                    );
                    $estimasiHonorPartial = $this->isSensusEkonomi2026($kegiatan)
                        ? (float) $rateHonor->rate * 2.5
                        : $rateHonor->rate * $partialWorkload;
                }

                $isPartialPaymentListing = (bool) ($alokasiData['is_partial_payment_listing'] ?? false);
                $partialJumlahSatuanListing = isset($alokasiData['partial_jumlah_satuan_listing']) ? (int) $alokasiData['partial_jumlah_satuan_listing'] : null;
                $estimasiHonorPartialListing = null;

                if ($isPartialPaymentListing && $partialJumlahSatuanListing !== null && $rateHonor->rate_listing) {
                    $estimasiHonorPartialListing = $rateHonor->rate_listing * $partialJumlahSatuanListing;
                }

                $effectivePencacahanHonor = $isPartialPayment
                    ? ($estimasiHonorPartial ?? 0)
                    : $totalHonor;
                $effectiveListingHonor = $isPartialPaymentListing
                    ? ($estimasiHonorPartialListing ?? 0)
                    : $totalHonorListing;

                // Check SBML constraint per assignment (skip if honor is 0)
                if ($effectivePencacahanHonor > 0) {
                    $constraintError = $this->checkSbmlConstraint(
                        (int) $tahun,
                        $kegiatan->jenis_kegiatan,
                        $petugasType,
                        $jenisPenugasan,
                        $effectivePencacahanHonor,
                        $kegiatan
                    );

                    if ($constraintError) {
                        $errors[] = $petugas->nama.': '.$constraintError;

                        continue;
                    }
                }

                // Check petugas total honor in month across all assignments (skip if honor is 0)
                // For edit/revision, exclude current periode from calculation
                $combinedNewHonor = $effectivePencacahanHonor + $effectiveListingHonor;
                if ($combinedNewHonor > 0) {
                    $runningCurrentHonor = $runningHonorByPetugas[$alokasiData['petugas_id']] ?? 0;

                    $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                        $alokasiData['petugas_id'],
                        (int) $tahun,
                        (int) $bulan,
                        $combinedNewHonor + $runningCurrentHonor,
                        $periode->id,
                        $jenisPenugasan,
                        $kegiatan->jenis_kegiatan,
                        $petugasType,
                        $kegiatan
                    );

                    if ($petugasTotalError) {
                        $errors[] = $petugas->nama.': '.$petugasTotalError;

                        continue;
                    }

                    $runningHonorByPetugas[$alokasiData['petugas_id']] = $runningCurrentHonor + $combinedNewHonor;
                }

                // Create new alokasi
                $alokasiPayload = [
                    'periode_alokasi_id' => $periode->id,
                    'petugas_id' => $alokasiData['petugas_id'],
                    'peran' => $jenisPenugasan,
                    'status_kepegawaian' => $petugasType,
                    'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                    'jumlah_satuan_listing' => $jumlahSatuanListing,
                    'jumlah_frame_sampel' => count(array_unique(array_map('intval', $alokasiData['frame_sampel_ids'] ?? []))),
                    'jumlah_unit_sampel' => (int) ($alokasiData['jumlah_unit_sampel'] ?? 0),
                    'total_honor' => $totalHonor,
                    'total_honor_listing' => $totalHonorListing,
                    'is_partial_payment' => $isPartialPayment,
                    'partial_jumlah_satuan' => $isPartialPayment ? $partialJumlahSatuan : null,
                    'estimasi_honor_partial' => $isPartialPayment ? $estimasiHonorPartial : null,
                    'error' => 'Terdapat kesalahan pada alokasi petugas: '.implode(' | ', $errors),
                ];

                $alokasiPetugas = AlokasiPetugas::create($alokasiPayload);
                $this->syncAlokasiFrameSampel($alokasiPetugas, $alokasiData['frame_sampel_ids'] ?? []);
            }

            // Recalculate periode total and sisa_pagu
            $periode->load('alokasiPetugas');
            $newPeriodeTotalHonor = $periode->alokasiPetugas->sum('total_honor');
            $newPeriodeTotalHonorListing = $periode->alokasiPetugas->sum('total_honor_listing');

            // Calculate sisa_pagu based on previous periods
            $kegiatan->load('periodeAlokasi.alokasiPetugas');
            $paguAnggaran = $kegiatan->pagu_pencacahan ?? 0;
            $paguListing = $kegiatan->pagu_listing ?? 0;

            // For revision, we need to adjust calculation
            if ($isRevision && $parentPeriodeId) {
                // Get parent periode total (old value that was revised)
                $parentPeriode = PeriodeAlokasi::with('alokasiPetugas')->find($parentPeriodeId);
                $oldPeriodeTotalHonor = $parentPeriode ? $parentPeriode->alokasiPetugas->sum('total_honor') : 0;
                $oldPeriodeTotalHonorListing = $parentPeriode ? $parentPeriode->alokasiPetugas->sum('total_honor_listing') : 0;

                // Find periode BEFORE the parent periode (the one that was just revised)
                $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($tahun, $bulan) {
                        $query->where('tahun', '<', $tahun)
                            ->orWhere(function ($q) use ($tahun, $bulan) {
                                $q->where('tahun', $tahun)
                                    ->where('bulan', '<', $bulan);
                            });
                    })
                    ->where('id', '!=', $parentPeriodeId) // Exclude parent periode
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                // Sisa pagu = (sisa pagu periode sebelumnya) + (total honor lama yang direvisi) - (total honor baru)
                // This way, we "add back" the old allocation and subtract the new one
                $sisaPaguPeriode = $previousPeriode
                    ? $previousPeriode->sisa_pagu + $oldPeriodeTotalHonor - $newPeriodeTotalHonor
                    : $paguAnggaran - $newPeriodeTotalHonor;

                $sisaPaguPeriodeListing = $previousPeriode
                    ? ($previousPeriode->sisa_pagu_listing ?? 0) + $oldPeriodeTotalHonorListing - $newPeriodeTotalHonorListing
                    : $paguListing - $newPeriodeTotalHonorListing;
            } else {
                // Normal edit - use standard calculation
                $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($tahun, $bulan) {
                        $query->where('tahun', '<', $tahun)
                            ->orWhere(function ($q) use ($tahun, $bulan) {
                                $q->where('tahun', $tahun)
                                    ->where('bulan', '<', $bulan);
                            });
                    })
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                $sisaPaguPeriode = $previousPeriode
                    ? $previousPeriode->sisa_pagu - $newPeriodeTotalHonor
                    : $paguAnggaran - $newPeriodeTotalHonor;

                $sisaPaguPeriodeListing = $previousPeriode
                    ? ($previousPeriode->sisa_pagu_listing ?? 0) - $newPeriodeTotalHonorListing
                    : $paguListing - $newPeriodeTotalHonorListing;
            }

            $periode->update([
                'sisa_pagu' => $sisaPaguPeriode,
                'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? $periode->tanggal_mulai,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? $periode->tanggal_selesai,
                'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? $periode->tanggal_mulai_listing,
                'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? $periode->tanggal_selesai_listing,
                'jadwal_pengolahan_listing_mulai' => $validated['jadwal_pengolahan_listing_mulai'] ?? $periode->jadwal_pengolahan_listing_mulai,
                'jadwal_pengolahan_listing_selesai' => $validated['jadwal_pengolahan_listing_selesai'] ?? $periode->jadwal_pengolahan_listing_selesai,
                'jadwal_pengolahan_pencacahan_mulai' => $validated['jadwal_pengolahan_pencacahan_mulai'] ?? $periode->jadwal_pengolahan_pencacahan_mulai,
                'jadwal_pengolahan_pencacahan_selesai' => $validated['jadwal_pengolahan_pencacahan_selesai'] ?? $periode->jadwal_pengolahan_pencacahan_selesai,
            ]);

            // Always recalculate sisa_pagu for all subsequent periods when any periode is updated
            // This ensures that changes to any periode cascade correctly to future periods
            $subsequentPeriods = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where(function ($query) use ($tahun, $bulan) {
                    $query->where('tahun', '>', $tahun)
                        ->orWhere(function ($q) use ($tahun, $bulan) {
                            $q->where('tahun', $tahun)
                                ->where('bulan', '>', $bulan);
                        });
                })
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();

            // Recalculate sisa_pagu for all subsequent periods sequentially
            if ($subsequentPeriods->isNotEmpty()) {
                $currentSisaPagu = $sisaPaguPeriode;
                $currentSisaPaguListing = $sisaPaguPeriodeListing;

                foreach ($subsequentPeriods as $nextPeriode) {
                    $nextPeriode->load('alokasiPetugas');
                    $nextPeriodeTotal = $nextPeriode->alokasiPetugas->sum('total_honor');
                    $nextPeriodeTotalListing = $nextPeriode->alokasiPetugas->sum('total_honor_listing');

                    $currentSisaPagu = $currentSisaPagu - $nextPeriodeTotal;
                    $currentSisaPaguListing = $currentSisaPaguListing - $nextPeriodeTotalListing;

                    $nextPeriode->update([
                        'sisa_pagu' => $currentSisaPagu,
                        'sisa_pagu_listing' => $currentSisaPaguListing,
                    ]);
                }
            }

            if (count($errors) > 0) {
                DB::rollBack();

                return back()->withErrors(['validation' => $errors])
                    ->withInput();
            }

            DB::commit();

            // Clear revisi session only after successful commit
            if ($isRevision) {
                $request->session()->forget(['is_revisi_mode', 'revisi_parent_periode_id', 'revisi_kegiatan_id', 'revisi_tahun', 'revisi_bulan']);
            }

            $bulanName = Carbon::create()->month((int) $bulan)->translatedFormat('F');
            ActivityLog::log(
                $isRevision ? 'Revisi Alokasi Periode' : 'Update Alokasi Periode',
                'alokasi',
                'Berhasil '.($isRevision ? 'merevisi' : 'memperbarui').' alokasi '.$kegiatan->nama_kegiatan.' untuk '.$bulanName.' '.$tahun.' ('.$created.' petugas)',
                'success',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'total_petugas' => $created,
                    'is_revision' => $isRevision,
                ]
            );

            $successMessage = $isRevision
                ? 'Revisi alokasi berhasil dikirim.'
                : 'Alokasi periode berhasil diperbarui.';

            return redirect()->route('alokasi.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();

            ActivityLog::logError(
                'Gagal Update Alokasi Periode',
                'alokasi',
                'Terjadi exception saat update alokasi '.$kegiatan->nama_kegiatan.' '.$bulan.'/'.$tahun.': '.$e->getMessage(),
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'request_alokasi_count' => count($validated['alokasi'] ?? []),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return back()->with('error', 'Gagal memperbarui alokasi: '.$e->getMessage());
        }
    }

    public function destroyPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser || ! ($effectiveUser->hasActiveRole('admin') || $effectiveUser->hasActiveRole('operator'))) {
            abort(403, 'Hanya admin atau operator yang dapat membatalkan alokasi periode.');
        }

        // Only allow canceling draft (dikirim can be reverted to draft via kembalikanKeDraft)
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->where('status', 'draft')
            ->orderByDesc('revision_number')
            ->first();

        if (! $periode) {
            return back()->with('error', 'Tidak ada alokasi periode berstatus draft yang dapat dibatalkan.');
        }

        $hasGeneratedSpk = Spk::query()
            ->whereHas('alokasiPetugas', function ($query) use ($periode) {
                $query->where('periode_alokasi_id', $periode->id);
            })
            ->exists();

        if ($hasGeneratedSpk) {
            ActivityLog::log(
                'Batalkan Alokasi Periode',
                'alokasi',
                'Gagal membatalkan alokasi '.$kegiatan->nama_kegiatan.' '.$bulan.'/'.$tahun.' karena Perjanjian Kerja sudah digenerate.',
                'warning',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'periode_id' => $periode->id,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ]
            );

            return back()->with('warning', 'Alokasi tidak dapat dibatalkan karena Perjanjian Kerja sudah digenerate.');
        }

        // If this is a revision being deleted, restore the parent periode status
        if ($periode->parent_periode_id) {
            $parentPeriode = PeriodeAlokasi::find($periode->parent_periode_id);
            if ($parentPeriode && $parentPeriode->status === 'direvisi') {
                // Restore parent to 'dikirim' or 'perubahan' based on whether it has parent
                $parentPeriode->update([
                    'status' => $parentPeriode->parent_periode_id ? 'perubahan' : 'dikirim',
                ]);
            }
        }

        // Delete the periode and its alokasi
        $deletedPetugasCount = $periode->alokasiPetugas()->count();
        $periode->alokasiPetugas()->delete();
        $periode->delete();

        ActivityLog::log(
            'Batalkan Alokasi Periode',
            'alokasi',
            'Berhasil membatalkan alokasi '.$kegiatan->nama_kegiatan.' '.$bulan.'/'.$tahun,
            'success',
            [
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_nama' => $kegiatan->nama_kegiatan,
                'periode_id' => $periode->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'deleted_petugas_count' => $deletedPetugasCount,
            ]
        );

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dibatalkan.');
    }

    public function kembalikanKeDraft(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser || ! ($effectiveUser->hasActiveRole('admin') || $effectiveUser->hasActiveRole('operator'))) {
            abort(403, 'Hanya admin atau operator yang dapat mengembalikan periode ke draft.');
        }

        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->where('status', 'dikirim')
            ->orderByDesc('revision_number')
            ->first();

        if (! $periode) {
            return back()->with('error', 'Tidak ada alokasi periode berstatus dikirim yang dapat dikembalikan ke draft.');
        }

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';

        // Block if any non-organik officer in this periode already has SPK in the same periode,
        // except for sensus activities where draft reversion should remain available.
        $hasGeneratedSpk = DB::table('alokasi_petugas as ap_current')
            ->where('ap_current.periode_alokasi_id', $periode->id)
            ->where('ap_current.status_kepegawaian', 'non_organik')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('spk')
                    ->join('alokasi_petugas as ap_spk', 'ap_spk.id', '=', 'spk.alokasi_petugas_id')
                    ->whereColumn('ap_spk.petugas_id', 'ap_current.petugas_id')
                    ->whereColumn('ap_spk.periode_alokasi_id', 'ap_current.periode_alokasi_id')
                    ->whereNull('spk.deleted_at')
                    ->where('spk.status', '!=', 'dibatalkan');
            })
            ->exists();

        if ($hasGeneratedSpk && ! $isSensusKegiatan) {
            ActivityLog::log(
                'Kembalikan Alokasi ke Draft',
                'alokasi',
                'Gagal mengembalikan alokasi '.$kegiatan->nama_kegiatan.' '.$bulan.'/'.$tahun.' ke draft karena Perjanjian Kerja sudah dibuat.',
                'warning',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'periode_id' => $periode->id,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ]
            );

            return back()->with('warning', 'Periode tidak dapat dikembalikan ke draft karena Perjanjian Kerja sudah dibuat.');
        }

        $periode->update([
            'status' => 'draft',
            'submitted_at' => null,
            'submitted_by' => null,
        ]);

        ActivityLog::log(
            'Kembalikan Alokasi ke Draft',
            'alokasi',
            'Berhasil mengembalikan alokasi '.$kegiatan->nama_kegiatan.' '.$bulan.'/'.$tahun.' ke draft.',
            'success',
            [
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_nama' => $kegiatan->nama_kegiatan,
                'periode_id' => $periode->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ]
        );

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dikembalikan ke draft.');
    }

    public function revisiPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        $resolvedPeriode = $this->resolvePeriodeRouteBinding($kegiatanRouteKey);

        if (
            $resolvedPeriode instanceof PeriodeAlokasi
            && (int) $resolvedPeriode->kegiatan_id === (int) $kegiatan->id
            && (int) $resolvedPeriode->tahun === $tahun
            && in_array(
                str_pad((string) $resolvedPeriode->bulan, 2, '0', STR_PAD_LEFT),
                $bulanCandidates,
                true,
            )
            && in_array($resolvedPeriode->status, ['dikirim', 'perubahan'], true)
        ) {
            $oldPeriode = $resolvedPeriode->load('alokasiPetugas');
        } else {
            // Revisi hanya boleh dimulai dari periode yang sudah dikirim atau hasil revisi sebelumnya.
            $oldPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', $bulanCandidates)
                ->whereIn('status', ['perubahan', 'dikirim'])
                ->orderByRaw("CASE status WHEN 'perubahan' THEN 2 WHEN 'dikirim' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('revision_number')
                ->with('alokasiPetugas')
                ->first();
        }

        if (! $oldPeriode) {
            return back()->with('error', 'Revisi hanya dapat dilakukan untuk alokasi berstatus dikirim.');
        }

        // Store parent periode info in session for later comparison
        $request->session()->put('revisi_parent_periode_id', $oldPeriode->id);
        $request->session()->put('revisi_kegiatan_id', $kegiatan->id);
        $request->session()->put('revisi_tahun', $tahun);
        $request->session()->put('revisi_bulan', $bulan);
        $request->session()->put('is_revisi_mode', true);

        // Redirect to edit page - will load data from parent periode
        return redirect('/alokasi/periode/'.$oldPeriode->hashed_id.'/'.$tahun.'/'.$bulan.'/edit?mode=revisi')
            ->with('success', 'Mode revisi. Silakan edit data sesuai kebutuhan.');
    }

    public function batalkanRevisiPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser || ! $effectiveUser->hasActiveRole('admin')) {
            abort(403, 'Hanya admin yang dapat membatalkan revisi periode.');
        }

        $periodePerubahan = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->where('status', 'perubahan')
            ->orderByDesc('revision_number')
            ->with('alokasiPetugas:id,periode_alokasi_id,petugas_id')
            ->first();

        if (! $periodePerubahan) {
            return back()->with('error', 'Tidak ada revisi terkirim (status perubahan) yang dapat dibatalkan.');
        }

        $petugasIds = $periodePerubahan->alokasiPetugas
            ->pluck('petugas_id')
            ->filter()
            ->unique();

        $hasAddendumOnCurrentRevision = Spk::query()
            ->where('addendum_number', '>', 0)
            ->whereHas('alokasiPetugas', function ($query) use ($periodePerubahan) {
                $query->where('periode_alokasi_id', $periodePerubahan->id);
            })
            ->exists();

        $hasAddendumOnRevisionPetugas = false;
        if ($petugasIds->isNotEmpty()) {
            $hasAddendumOnRevisionPetugas = Spk::query()
                ->where('addendum_number', '>', 0)
                ->whereIn('petugas_id', $petugasIds)
                ->whereYear('tanggal_spk', $tahun)
                ->whereMonth('tanggal_spk', (int) $bulan)
                ->exists();
        }

        if ($hasAddendumOnCurrentRevision || $hasAddendumOnRevisionPetugas) {
            return back()->with('warning', 'Revisi tidak dapat dibatalkan karena Addendum Perjanjian Kerja sudah dibuat.');
        }

        $periodeDirevisi = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status', 'direvisi')
            ->orderByDesc('revision_number')
            ->first();

        if (! $periodeDirevisi) {
            return back()->with('error', 'Periode asal dengan status direvisi tidak ditemukan.');
        }

        DB::beginTransaction();

        try {
            $deletedPetugasCount = $periodePerubahan->alokasiPetugas()->count();

            $periodePerubahan->alokasiPetugas()->delete();
            $periodePerubahan->delete();

            $periodeDirevisi->update([
                'status' => 'dikirim',
            ]);

            DB::commit();

            $bulanName = Carbon::create()->month((int) $bulan)->translatedFormat('F');
            ActivityLog::log(
                'Batalkan Revisi Alokasi Periode',
                'alokasi',
                "Berhasil membatalkan revisi {$kegiatan->nama_kegiatan} periode {$bulanName} {$tahun}",
                'success',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'deleted_periode_id' => $periodePerubahan->id,
                    'restored_periode_id' => $periodeDirevisi->id,
                    'deleted_petugas_count' => $deletedPetugasCount,
                ]
            );

            return redirect()->route('alokasi.index')
                ->with('success', 'Revisi periode berhasil dibatalkan. Status periode dikembalikan menjadi dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();

            ActivityLog::log(
                'Batalkan Revisi Alokasi Periode',
                'alokasi',
                'Gagal membatalkan revisi periode',
                'error',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'error' => $e->getMessage(),
                ]
            );

            return back()->with('error', 'Gagal membatalkan revisi periode: '.$e->getMessage());
        }
    }
}
