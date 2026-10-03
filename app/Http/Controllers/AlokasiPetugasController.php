<?php

namespace App\Http\Controllers;

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
use App\Http\Controllers\Concerns\AlokasiPetugasImportSupport;
use App\Http\Controllers\Concerns\AlokasiPetugasMonitoringSupport;
use App\Http\Controllers\Concerns\AlokasiPetugasValidationSupport;
use App\Http\Controllers\Concerns\AlokasiPetugasPeriodSupport;
use App\Http\Controllers\Concerns\AlokasiPetugasRecommendationSupport;
use App\Http\Controllers\Concerns\AlokasiPetugasPeriodActions;
use App\Http\Controllers\Concerns\AlokasiPetugasImportActions;

class AlokasiPetugasController extends Controller
{
    use AlokasiPetugasImportActions;
    use AlokasiPetugasPeriodActions;
    use AlokasiPetugasRecommendationSupport;
    use AlokasiPetugasPeriodSupport;
    use AlokasiPetugasValidationSupport;
    use AlokasiPetugasMonitoringSupport;
    use AlokasiPetugasImportSupport;
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        // Get filters using only() to get values after merge in prepareForValidation
        // Filter out empty values like SbmlReportController does
        $filters = array_filter($request->only(['search', 'status', 'bulan']), fn ($value) => $value !== null && $value !== '');

        // Build base query
        $baseQuery = PeriodeAlokasi::query()
            ->select('periode_alokasi.*')
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,deskripsi,ketua_tim_user_id,pagu_pencacahan,pagu_listing,has_listing_updating',
                'alokasiPetugas:id,periode_alokasi_id,petugas_id,jumlah_satuan,total_honor,is_partial_payment,partial_jumlah_satuan,estimasi_honor_partial,jumlah_satuan_listing,total_honor_listing,is_partial_payment_listing,partial_jumlah_satuan_listing,estimasi_honor_partial_listing',
            ])
            ->withCount('alokasiPetugas as jumlah_petugas')
            ->where('status', '!=', 'dihapus') // Exclude deleted periods
            ->whereIn('status', ['dikirim', 'perubahan', 'direvisi', 'draft']) // Show all relevant statuses
            ->where('tahun', $activeYear);

        // Search by kegiatan
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $baseQuery->whereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $baseQuery->where('status', $filters['status']);
        }

        $bulanFilter = ! empty($filters['bulan'])
            ? str_pad((string) $filters['bulan'], 2, '0', STR_PAD_LEFT)
            : null;

        // Filter for Ketua Tim - only their kegiatan (only applies when active role is ketua_tim)
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->hasActiveRole('ketua_tim')) {
            $baseQuery->whereHas('kegiatan', function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        // Get all results first to handle deduplication
        $allPeriodes = $baseQuery
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('created_at')
            ->get();

        // Pre-calculate total honor terpakai for all kegiatan in one query
        $totalHonorTerpakaiByKegiatan = AlokasiPetugas::select(
            'periode_alokasi.kegiatan_id',
            DB::raw('SUM(alokasi_petugas.total_honor) as total_pencacahan'),
            DB::raw('SUM(alokasi_petugas.total_honor_listing) as total_listing')
        )
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->where('periode_alokasi.tahun', $activeYear)
            ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan', 'direvisi', 'draft'])
            ->groupBy('periode_alokasi.kegiatan_id')
            ->pluck('total_pencacahan', 'kegiatan_id');

        $totalHonorTerpakaiListingByKegiatan = AlokasiPetugas::select(
            'periode_alokasi.kegiatan_id',
            DB::raw('SUM(alokasi_petugas.total_honor_listing) as total_listing')
        )
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->where('periode_alokasi.tahun', $activeYear)
            ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan', 'direvisi', 'draft'])
            ->groupBy('periode_alokasi.kegiatan_id')
            ->pluck('total_listing', 'kegiatan_id');

        // Deduplicate: If there are both 'direvisi' and 'perubahan' for same kegiatan+bulan,
        // keep only 'perubahan' (the latest change)
        $deduplicatedPeriodes = $allPeriodes->groupBy(function ($periode) {
            return $periode->kegiatan_id.'_'.$periode->bulan.'_'.$periode->tahun;
        })->map(function ($group) {
            // If group has 'perubahan', use that (it's the latest)
            $perubahan = $group->firstWhere('status', 'perubahan');
            if ($perubahan) {
                return $perubahan;
            }

            // Otherwise, return the first item (most recent by created_at)
            return $group->first();
        })->values();

        if ($bulanFilter) {
            $deduplicatedPeriodes = $deduplicatedPeriodes
                ->filter(fn (PeriodeAlokasi $periode) => in_array($bulanFilter, $this->resolvePeriodeFilterBulans($periode), true))
                ->values();
        }

        // Pre-calculate total honor terpakai per kegiatan per bulan (using ALL deduplicated data before pagination)
        // This ensures we have complete data for cumulative calculation
        // Create two versions: one for validated only, one for all (including draft)
        $honorPerKegiatanPerBulanValidated = $deduplicatedPeriodes
            ->filter(function ($periode) {
                // Only count validated periods
                return in_array($periode->status, ['dikirim', 'perubahan', 'direvisi']);
            })
            ->groupBy('kegiatan_id')
            ->map(function ($periodesByKegiatan) {
                return $periodesByKegiatan->groupBy('bulan')->map(function ($periodeInMonth) {
                    $periode = $periodeInMonth->first();

                    return $this->sumEffectiveCombinedHonor($periode->alokasiPetugas);
                })->sortKeys();
            });

        $honorPerKegiatanPerBulanAll = $deduplicatedPeriodes
            ->groupBy('kegiatan_id')
            ->map(function ($periodesByKegiatan) {
                return $periodesByKegiatan->groupBy('bulan')->map(function ($periodeInMonth) {
                    $periode = $periodeInMonth->first();

                    return $this->sumEffectiveCombinedHonor($periode->alokasiPetugas);
                })->sortKeys();
            });

        // Get latest month for each kegiatan (for revisi button logic)
        // Only show revisi for 'dikirim' or 'perubahan' status
        $latestMonthsByKegiatan = PeriodeAlokasi::query()
            ->select('kegiatan_id', DB::raw('MAX(bulan) as latest_bulan'))
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->where('tahun', $activeYear)
            ->groupBy('kegiatan_id')
            ->pluck('latest_bulan', 'kegiatan_id');

        $periodeIds = $deduplicatedPeriodes->pluck('id')->filter()->values();
        $periodeIdsWithGeneratedSpk = collect();
        $periodeIdsWithNonOrganikSpkInKegiatan = collect();
        if ($periodeIds->isNotEmpty()) {
            // SPK that are directly linked to this exact periode (used by Batalkan Alokasi on draft)
            $periodeIdsWithGeneratedSpk = Spk::query()
                ->join('alokasi_petugas', 'spk.alokasi_petugas_id', '=', 'alokasi_petugas.id')
                ->whereIn('alokasi_petugas.periode_alokasi_id', $periodeIds)
                ->whereNull('spk.deleted_at')
                ->pluck('alokasi_petugas.periode_alokasi_id')
                ->unique();

            // Non-organik officers in this periode that already have SPK in the same periode
            $periodeIdsWithNonOrganikSpkInKegiatan = DB::table('alokasi_petugas as ap_current')
                ->whereIn('ap_current.periode_alokasi_id', $periodeIds)
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
                ->pluck('ap_current.periode_alokasi_id')
                ->unique();
        }

        // Transform the result to include necessary data (client-side filtering and pagination)
        $allAlokasiData = $deduplicatedPeriodes->map(function ($periode) use ($latestMonthsByKegiatan, $honorPerKegiatanPerBulanValidated, $honorPerKegiatanPerBulanAll, $periodeIdsWithGeneratedSpk, $periodeIdsWithNonOrganikSpkInKegiatan) {
            $estimasiHonor = $this->sumEffectiveCombinedHonor($periode->alokasiPetugas);

            // Ambil pagu dari kegiatan
            $paguPencacahan = $periode->kegiatan->pagu_pencacahan ?? 0;
            $paguListing = $periode->kegiatan->pagu_listing ?? 0;

            $currentBulan = (int) $periode->bulan;

            // For draft: use ALL periods (including other drafts) to calculate sisa pagu
            // For validated: use only validated periods
            if ($periode->status === 'draft') {
                // Calculate honor from all periods (validated + draft) up to current month (inclusive)
                $honorByMonth = $honorPerKegiatanPerBulanAll->get($periode->kegiatan_id, collect());
                $totalHonorSampaiDenganBulanIni = $honorByMonth->filter(function ($honor, $bulan) use ($currentBulan) {
                    return (int) $bulan <= $currentBulan;
                })->sum();

                // Sisa pagu = total pagu - all honor (validated + draft) sampai bulan ini
                $sisaPagu = ($paguPencacahan + $paguListing) - $totalHonorSampaiDenganBulanIni;
                $totalTerpakaiUntukBudgetInfo = $totalHonorSampaiDenganBulanIni;
            } else {
                // For validated: only count validated periods
                $honorByMonth = $honorPerKegiatanPerBulanValidated->get($periode->kegiatan_id, collect());
                $totalHonorSampaiDenganBulanIni = $honorByMonth->filter(function ($honor, $bulan) use ($currentBulan) {
                    return (int) $bulan <= $currentBulan;
                })->sum();

                // Sisa pagu = total pagu - validated honor sampai bulan ini
                $sisaPagu = ($paguPencacahan + $paguListing) - $totalHonorSampaiDenganBulanIni;
                $totalTerpakaiUntukBudgetInfo = $totalHonorSampaiDenganBulanIni;
            }

            // Pagu terpakai = total honor untuk periode ini saja
            $paguTerpakai = $estimasiHonor;

            $isLatestPeriode = $periode->status === 'dikirim' &&
                isset($latestMonthsByKegiatan[$periode->kegiatan_id]) &&
                $periode->bulan == $latestMonthsByKegiatan[$periode->kegiatan_id];

            // Check if this kegiatan+bulan has both 'direvisi' AND 'perubahan' status
            $hasCompletedRevisionCycle = PeriodeAlokasi::query()
                ->where('kegiatan_id', $periode->kegiatan_id)
                ->where('bulan', $periode->bulan)
                ->where('tahun', $periode->tahun)
                ->whereIn('status', ['direvisi', 'perubahan'])
                ->distinct('status')
                ->count() >= 2; // Has both direvisi and perubahan

            return [
                'kegiatan_id' => $periode->kegiatan_id,
                'periode_id' => $periode->id,
                'periode_hashed_id' => $periode->hashed_id,
                'bulan' => str_pad($periode->bulan, 2, '0', STR_PAD_LEFT),
                'display_bulan' => $this->resolvePeriodeDisplayBulan($periode),
                'filter_bulan' => $this->resolvePeriodeFilterBulans($periode),
                'tahun' => $periode->tahun,
                'jenis_kegiatan' => $periode->jenis_kegiatan,
                'status' => $periode->status,
                'jumlah_petugas' => $periode->jumlah_petugas,
                'total_honor' => $estimasiHonor,
                'estimasi_honor' => $estimasiHonor,
                'sisa_pagu' => $sisaPagu,
                'pagu_pencacahan' => $paguPencacahan,
                'pagu_listing' => $paguListing,
                'pagu_terpakai' => $paguTerpakai,
                'total_terpakai_untuk_budget_info' => $totalTerpakaiUntukBudgetInfo,
                'latest_created_at' => $periode->created_at,
                'is_latest_periode' => $isLatestPeriode,
                'has_completed_revision_cycle' => $hasCompletedRevisionCycle,
                'has_spk_generated' => $periodeIdsWithGeneratedSpk->contains($periode->id),
                'has_non_organik_spk_in_kegiatan' => $periodeIdsWithNonOrganikSpkInKegiatan->contains($periode->id),
                'kegiatan' => [
                    'id' => $periode->kegiatan->id,
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'deskripsi' => $periode->kegiatan->deskripsi,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                ],
            ];
        });

        $allAlokasiData = $this->sortAlokasiIndexData($allAlokasiData);

        // Check if any kegiatan exists
        $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->when($effectiveUser->hasActiveRole('ketua_tim'), function ($query) use ($effectiveUser) {
                $query->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            })
            ->exists();

        // Encrypt all data for client-side filtering and pagination
        $encryptedData = encryptData($allAlokasiData->values()->toArray());

        $totalCount = $allAlokasiData->count();

        return Inertia::render('Alokasi/Index', [
            'alokasi' => [
                'encrypted' => $encryptedData,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $totalCount,
                    'total' => $totalCount,
                    'from' => $totalCount > 0 ? 1 : 0,
                    'to' => $totalCount,
                ],
                'links' => [], // No pagination links needed for client-side pagination
            ],
            'filters' => [
                'encrypted' => encryptFilters($filters),
                'decrypted' => $filters,
            ],
            'active_year' => $activeYear,
            'hasKegiatans' => $hasKegiatans,
        ]);
    }


    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */


    /**
     * @param  Collection<int, AlokasiPetugas>  $alokasiPetugas
     */


    /**
     * @param  Collection<int, PeriodeAlokasi>  $periodesByMonth
     */


    /**
     * @return array<int, string>
     */


    /**
     * @return array<int, string>
     */


    /**
     * @param  array<int, array<string, mixed>>  $alokasiItems
     * @return array<int, string>
     */


    /**
     * @param  array<int, int|string>  $frameIds
     */


    /**
     * @param  array<int, array<string, mixed>>  $alokasiItems
     * @return array<int, array<string, mixed>>
     */


    /**
     * Store multiple alokasi for a kegiatan.
     */
    public function storeMultiple(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa ditambahkan untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim dapat menambah alokasi jika dia adalah ketua_tim_user_id atau pj_lainnya_id
        $effectiveUser = effectiveUser($request);
        if ($effectiveUser->hasActiveRole('ketua_tim') && ! ($kegiatan->ketua_tim_user_id === $effectiveUser->id || $kegiatan->pj_lainnya_id === $effectiveUser->id)) {
            abort(403, 'Anda tidak memiliki akses untuk menambahkan alokasi pada kegiatan ini.');
        }
        // Validate that kegiatan has rate honors
        if ($kegiatan->rateHonors()->count() === 0) {
            return back()->withErrors([
                'rate_honor' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
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
        ]);

        $validated['alokasi'] = $this->mergeAlokasiRowsForStorage($validated['alokasi']);

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';

        if ($isSensusKegiatan) {
            $sensusTahun = (int) ($validated['alokasi'][0]['tahun'] ?? ActiveYearService::get());
            $existingSensusPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $sensusTahun)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->exists();

            if ($existingSensusPeriode) {
                return back()->withErrors([
                    'periode_sensus' => 'Untuk kegiatan sensus hanya diperbolehkan satu periode/perjanjian kerja dalam satu tahun.',
                ])->withInput();
            }
        }

        $decimalValidationErrors = $this->validateDecimalSatuanRules($validated['alokasi']);
        if (! empty($decimalValidationErrors)) {
            return back()->withErrors([
                'decimal_validation' => implode("\n", array_unique($decimalValidationErrors)),
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
            return back()->withErrors([
                'partial_validation' => implode("\n", array_unique($partialValidationErrors)),
            ])->withInput();
        }

        $sampleFrameValidationErrors = $this->validateSampleFrameAllocations($validated['alokasi'], $kegiatan);
        if (! empty($sampleFrameValidationErrors)) {
            return back()->withErrors([
                'sample_frame_validation' => implode("\n", array_unique($sampleFrameValidationErrors)),
            ])->withInput();
        }

        // Get tahapan from first alokasi item (all should have same tahapan in a batch)
        $tahapan = $validated['alokasi'][0]['tahapan'] ?? 'both';

        // Conditional validation based on tahapan
        if ($tahapan === 'listing_only') {
            $request->validate([
                'tanggal_mulai_listing' => 'required|date',
                'tanggal_selesai_listing' => 'required|date|after_or_equal:tanggal_mulai_listing',
            ], [
                'tanggal_mulai_listing.required' => 'Tanggal mulai listing wajib diisi.',
                'tanggal_selesai_listing.required' => 'Tanggal selesai listing wajib diisi.',
                'tanggal_selesai_listing.after_or_equal' => 'Tanggal selesai listing harus setelah atau sama dengan tanggal mulai listing.',
            ]);
            $validated['tanggal_mulai_listing'] = $request->tanggal_mulai_listing;
            $validated['tanggal_selesai_listing'] = $request->tanggal_selesai_listing;
        } else {
            $request->validate([
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ], [
                'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
                'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
                'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            ]);
            $validated['tanggal_mulai'] = $request->tanggal_mulai;
            $validated['tanggal_selesai'] = $request->tanggal_selesai;

            // Also validate listing dates if tahapan is 'both'
            if ($tahapan === 'both') {
                $request->validate([
                    'tanggal_mulai_listing' => 'nullable|date',
                    'tanggal_selesai_listing' => 'nullable|date|after_or_equal:tanggal_mulai_listing',
                ]);
                $validated['tanggal_mulai_listing'] = $request->tanggal_mulai_listing;
                $validated['tanggal_selesai_listing'] = $request->tanggal_selesai_listing;
            }
        }

        $dateValidationErrors = [];

        if ($isSensusKegiatan) {
            $dateValidationErrors = $this->validateDatesWithinKegiatanPeriod($kegiatan, $validated, $tahapan);
        } else {
            $periodeBulan = (int) $validated['alokasi'][0]['bulan'];
            $periodeTahun = (int) $validated['alokasi'][0]['tahun'];

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
            return back()->withErrors([
                'date_validation' => implode("\n", $dateValidationErrors),
            ])->withInput();
        }

        // Validate jadwal pengolahan fields (optional, based on rate honor configuration)
        $request->validate([
            'jadwal_pengolahan_listing_mulai' => 'nullable|date',
            'jadwal_pengolahan_listing_selesai' => 'nullable|date|after_or_equal:jadwal_pengolahan_listing_mulai',
            'jadwal_pengolahan_pencacahan_mulai' => 'nullable|date',
            'jadwal_pengolahan_pencacahan_selesai' => 'nullable|date|after_or_equal:jadwal_pengolahan_pencacahan_mulai',
        ], [
            'jadwal_pengolahan_listing_selesai.after_or_equal' => 'Tanggal selesai pengolahan listing harus setelah atau sama dengan tanggal mulai.',
            'jadwal_pengolahan_pencacahan_selesai.after_or_equal' => 'Tanggal selesai pengolahan pencacahan harus setelah atau sama dengan tanggal mulai.',
        ]);

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';
        $validated['jadwal_pengolahan_listing_mulai'] = $request->jadwal_pengolahan_listing_mulai;
        $validated['jadwal_pengolahan_listing_selesai'] = $request->jadwal_pengolahan_listing_selesai;
        $validated['jadwal_pengolahan_pencacahan_mulai'] = $request->jadwal_pengolahan_pencacahan_mulai;
        $validated['jadwal_pengolahan_pencacahan_selesai'] = $request->jadwal_pengolahan_pencacahan_selesai;

        DB::beginTransaction();
        $created = 0;
        $errors = [];
        $hasKegiatanIdColumn = Schema::hasColumn('alokasi_petugas', 'kegiatan_id');
        $hasBulanColumn = Schema::hasColumn('alokasi_petugas', 'bulan');
        $hasTahunColumn = Schema::hasColumn('alokasi_petugas', 'tahun');

        // Group by periode (bulan+tahun+jenis_kegiatan) to create PeriodeAlokasi first
        $periodeGroups = [];
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
                'Pengolahan', 'Petugas Pengolahan' => 'pengolahan',
                'Pengawas Pengolahan' => 'pengawas_pengolahan',
                default => null,
            };

            if (! $jenisPenugasan) {
                $errors[] = $petugas->nama.': Peran tidak valid.';

                continue;
            }

            // Find matching rate honor based on petugas type, jenis_kegiatan, and jenis_penugasan
            $statusKepegawaian = $this->resolveStatusKepegawaianFromPetugas($petugas);
            $rateHonor = $kegiatan->rateHonors()
                ->where('status_kepegawaian', $statusKepegawaian)
                ->where('jenis_kegiatan', $alokasiData['jenis_kegiatan'])
                ->where('jenis_penugasan', $jenisPenugasan)
                ->where('status', 'aktif')
                ->where('tahun_berlaku', $alokasiData['tahun'])
                ->first();

            if (! $rateHonor) {
                $errors[] = $petugas->nama.': Rate honor untuk '.$alokasiData['peran'].' ('.$statusKepegawaian.', '.$alokasiData['jenis_kegiatan'].') tidak ditemukan.';

                continue;
            }

            $pencacahanWorkload = $this->resolvePencacahanWorkload(
                $kegiatan,
                (float) ($alokasiData['jumlah_satuan'] ?? 0)
            );
            $totalHonor = $this->isSensusEkonomi2026($kegiatan)
                ? (float) $rateHonor->rate * 2.5
                : (float) $rateHonor->rate * $pencacahanWorkload;

            // Calculate listing honor if kegiatan has listing phase
            $totalHonorListing = 0;
            $jumlahSatuanListing = null;
            if ($kegiatan->has_listing_updating && isset($alokasiData['jumlah_satuan_listing']) && $alokasiData['jumlah_satuan_listing'] > 0) {
                $jumlahSatuanListing = $alokasiData['jumlah_satuan_listing'];
                if ($rateHonor->rate_listing) {
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
                    $alokasiData['tahun'],
                    $alokasiData['jenis_kegiatan'],
                    $rateHonor->status_kepegawaian,
                    $rateHonor->jenis_penugasan,
                    $effectivePencacahanHonor,
                    $kegiatan
                );

                if ($constraintError) {
                    $errors[] = $petugas->nama.': '.$constraintError;

                    continue;
                }
            }

            // Check petugas total honor in month across all assignments (skip if honor is 0)
            $combinedNewHonor = $effectivePencacahanHonor + $effectiveListingHonor;
            if ($combinedNewHonor > 0) {
                $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                    $alokasiData['petugas_id'],
                    $alokasiData['tahun'],
                    $alokasiData['bulan'],
                    $combinedNewHonor,
                    null,
                    $jenisPenugasan,
                    $alokasiData['jenis_kegiatan'],
                    $statusKepegawaian,
                    $kegiatan
                );

                if ($petugasTotalError) {
                    $errors[] = $petugas->nama.': '.$petugasTotalError;

                    continue;
                }
            }

            // Store data grouped by periode
            $normalizedBulan = str_pad((string) ((int) $alokasiData['bulan']), 2, '0', STR_PAD_LEFT);
            $periodeKey = $normalizedBulan.'_'.$alokasiData['tahun'].'_'.$alokasiData['jenis_kegiatan'];
            if (! isset($periodeGroups[$periodeKey])) {
                $periodeGroups[$periodeKey] = [
                    'bulan' => $normalizedBulan,
                    'tahun' => $alokasiData['tahun'],
                    'jenis_kegiatan' => $alokasiData['jenis_kegiatan'],
                    'tahapan' => $alokasiData['tahapan'] ?? 'both',
                    'alokasi' => [],
                ];
            }

            $periodeGroups[$periodeKey]['alokasi'][] = [
                'petugas_id' => $alokasiData['petugas_id'],
                'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                'jumlah_satuan_listing' => $jumlahSatuanListing,
                'jumlah_frame_sampel' => count(array_unique(array_map('intval', $alokasiData['frame_sampel_ids'] ?? []))),
                'jumlah_unit_sampel' => (int) ($alokasiData['jumlah_unit_sampel'] ?? 0),
                'frame_sampel_ids' => array_values(array_unique(array_map('intval', $alokasiData['frame_sampel_ids'] ?? []))),
                'total_honor' => $totalHonor,
                'total_honor_listing' => $totalHonorListing,
                'is_partial_payment' => $isPartialPayment,
                'partial_jumlah_satuan' => $isPartialPayment ? $partialJumlahSatuan : null,
                'estimasi_honor_partial' => $isPartialPayment ? $estimasiHonorPartial : null,
                'is_partial_payment_listing' => $isPartialPaymentListing,
                'partial_jumlah_satuan_listing' => $isPartialPaymentListing ? $partialJumlahSatuanListing : null,
                'estimasi_honor_partial_listing' => $isPartialPaymentListing ? $estimasiHonorPartialListing : null,
                'peran' => $jenisPenugasan,
                'status_kepegawaian' => $statusKepegawaian,
                'catatan' => $alokasiData['catatan'] ?? null,
            ];

            $created++;
        }

        // If there are any validation errors, rollback and return errors
        if (count($errors) > 0) {
            DB::rollBack();
            $errorMessage = implode("\n", $errors);

            Log::error('Store multiple alokasi validation failed before save', [
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_nama' => $kegiatan->nama_kegiatan,
                'error_count' => count($errors),
                'errors' => $errors,
                'request_alokasi_count' => count($validated['alokasi'] ?? []),
                'user_id' => effectiveUser($request)->id,
            ]);

            ActivityLog::logError(
                'Gagal Buat Alokasi Periode',
                'alokasi',
                'Validasi pembuatan alokasi gagal untuk '.$kegiatan->nama_kegiatan.': '.implode(' | ', $errors),
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'errors' => $errors,
                    'request_alokasi_count' => count($validated['alokasi'] ?? []),
                ]
            );

            return back()->withErrors(['sbml_constraint' => $errorMessage]);
        }

        // Create PeriodeAlokasi and AlokasiPetugas with proper error handling
        try {
            foreach ($periodeGroups as $periodeData) {
                // Calculate new periode's total honor
                $newPeriodeTotalHonor = collect($periodeData['alokasi'])->sum('total_honor');
                $newPeriodeTotalHonorListing = collect($periodeData['alokasi'])->sum('total_honor_listing');

                // Check budget constraint before creating periode
                $kegiatan->load('periodeAlokasi.alokasiPetugas');
                $paguAnggaran = $kegiatan->pagu_pencacahan ?? 0;
                $paguListing = $kegiatan->has_listing_updating ? ($kegiatan->pagu_listing ?? 0) : 0;

                // Calculate total spent across all active periods
                $totalSpent = $kegiatan->periodeAlokasi
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->sum(function ($p) {
                        return $p->alokasiPetugas->sum('total_honor');
                    });

                $totalSpentListing = $kegiatan->periodeAlokasi
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->sum(function ($p) {
                        return $p->alokasiPetugas->sum('total_honor_listing');
                    });

                $sisaPagu = $paguAnggaran - $totalSpent;
                $sisaPaguListing = $paguListing - $totalSpentListing;
                // Validate that sisa pagu is sufficient for new periode
                if ($newPeriodeTotalHonor > $sisaPagu || $newPeriodeTotalHonorListing > $sisaPaguListing) {
                    DB::rollBack();

                    return back()->withErrors([
                        'budget' => 'Anggaran tidak mencukupi untuk menambahkan periode ini. '.
                            'Sisa pagu: '.number_format($sisaPagu, 0, ',', '.').', '.
                            'Estimasi honor periode baru: '.number_format($newPeriodeTotalHonor, 0, ',', '.'),
                        ' Sisa pagu listing: '.number_format($sisaPaguListing, 0, ',', '.').', '.
                        'Estimasi honor listing periode baru: '.number_format($newPeriodeTotalHonorListing, 0, ',', '.'),
                    ]);
                }

                // Calculate sisa_pagu based on previous periods (sequential by month)
                $previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($periodeData) {
                        $query->where('tahun', '<', $periodeData['tahun'])
                            ->orWhere(function ($q) use ($periodeData) {
                                $q->where('tahun', $periodeData['tahun'])
                                    ->where('bulan', '<', $periodeData['bulan']);
                            });
                    })
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                $previousPeriodeListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where(function ($query) use ($periodeData) {
                        $query->where('tahun', '<', $periodeData['tahun'])
                            ->orWhere(function ($q) use ($periodeData) {
                                $q->where('tahun', $periodeData['tahun'])
                                    ->where('bulan', '<', $periodeData['bulan']);
                            });
                    })
                    ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                // Calculate sisa_pagu for this new periode
                $sisaPaguPeriode = $previousPeriode
                    ? $previousPeriode->sisa_pagu - $newPeriodeTotalHonor
                    : $paguAnggaran - $newPeriodeTotalHonor;

                $sisaPaguPeriodeListing = $previousPeriodeListing
                    ? $previousPeriodeListing->sisa_pagu_listing - $newPeriodeTotalHonorListing
                    : $paguListing - $newPeriodeTotalHonorListing;

                // Check for existing periode (including dihapus status)
                $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $periodeData['bulan'])
                    ->where('tahun', $periodeData['tahun'])
                    ->first();

                if ($periode && $periode->status === 'dihapus') {
                    // Reuse periode that was marked as deleted
                    $periode->update([
                        'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                        'tahapan' => $periodeData['tahapan'] ?? 'both',
                        'status' => 'draft',
                        'sisa_pagu' => $sisaPaguPeriode,
                        'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                        'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                        'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                        'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                        'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                        'jadwal_pengolahan_listing_mulai' => $validated['jadwal_pengolahan_listing_mulai'] ?? null,
                        'jadwal_pengolahan_listing_selesai' => $validated['jadwal_pengolahan_listing_selesai'] ?? null,
                        'jadwal_pengolahan_pencacahan_mulai' => $validated['jadwal_pengolahan_pencacahan_mulai'] ?? null,
                        'jadwal_pengolahan_pencacahan_selesai' => $validated['jadwal_pengolahan_pencacahan_selesai'] ?? null,
                    ]);
                } elseif (! $periode) {
                    // Create new periode
                    $periode = PeriodeAlokasi::create([
                        'kegiatan_id' => $kegiatan->id,
                        'bulan' => str_pad((string) $periodeData['bulan'], 2, '0', STR_PAD_LEFT),
                        'tahun' => $periodeData['tahun'],
                        'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                        'tahapan' => $periodeData['tahapan'] ?? 'both',
                        'status' => 'draft',
                        'sisa_pagu' => $sisaPaguPeriode,
                        'sisa_pagu_listing' => $sisaPaguPeriodeListing,
                        'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                        'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                        'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                        'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                        'jadwal_pengolahan_listing_mulai' => $validated['jadwal_pengolahan_listing_mulai'] ?? null,
                        'jadwal_pengolahan_listing_selesai' => $validated['jadwal_pengolahan_listing_selesai'] ?? null,
                        'jadwal_pengolahan_pencacahan_mulai' => $validated['jadwal_pengolahan_pencacahan_mulai'] ?? null,
                        'jadwal_pengolahan_pencacahan_selesai' => $validated['jadwal_pengolahan_pencacahan_selesai'] ?? null,
                    ]);
                }

                // Create AlokasiPetugas for this periode
                foreach ($periodeData['alokasi'] as $alokasiItem) {
                    $alokasiPayload = [
                        'periode_alokasi_id' => $periode->id,
                        'petugas_id' => $alokasiItem['petugas_id'],
                        'jumlah_satuan' => $alokasiItem['jumlah_satuan'],
                        'jumlah_satuan_listing' => $alokasiItem['jumlah_satuan_listing'],
                        'jumlah_frame_sampel' => $alokasiItem['jumlah_frame_sampel'] ?? 0,
                        'jumlah_unit_sampel' => $alokasiItem['jumlah_unit_sampel'] ?? 0,
                        'total_honor' => $alokasiItem['total_honor'],
                        'total_honor_listing' => $alokasiItem['total_honor_listing'],
                        'is_partial_payment' => $alokasiItem['is_partial_payment'] ?? false,
                        'partial_jumlah_satuan' => $alokasiItem['partial_jumlah_satuan'] ?? null,
                        'estimasi_honor_partial' => $alokasiItem['estimasi_honor_partial'] ?? null,
                        'is_partial_payment_listing' => $alokasiItem['is_partial_payment_listing'] ?? false,
                        'partial_jumlah_satuan_listing' => $alokasiItem['partial_jumlah_satuan_listing'] ?? null,
                        'estimasi_honor_partial_listing' => $alokasiItem['estimasi_honor_partial_listing'] ?? null,
                        'peran' => $alokasiItem['peran'],
                        'status_kepegawaian' => $alokasiItem['status_kepegawaian'],
                        'catatan' => $alokasiItem['catatan'],
                    ];

                    if ($hasKegiatanIdColumn) {
                        $alokasiPayload['kegiatan_id'] = $kegiatan->id;
                    }

                    if ($hasBulanColumn) {
                        $alokasiPayload['bulan'] = (int) $periodeData['bulan'];
                    }

                    if ($hasTahunColumn) {
                        $alokasiPayload['tahun'] = (int) $periodeData['tahun'];
                    }

                    $alokasiPetugas = AlokasiPetugas::create($alokasiPayload);
                    $this->syncAlokasiFrameSampel($alokasiPetugas, $alokasiItem['frame_sampel_ids'] ?? []);
                }
            }

            DB::commit();

            // Log the activity (use first periode for logging summary)
            if (! empty($periodeGroups)) {
                $firstPeriode = array_values($periodeGroups)[0];
                $bulanName = Carbon::create()->month((int) $firstPeriode['bulan'])->translatedFormat('F');

                ActivityLog::log(
                    'Buat Alokasi Periode',
                    'alokasi',
                    "Berhasil membuat alokasi {$kegiatan->nama_kegiatan} untuk {$bulanName} {$firstPeriode['tahun']} ({$created} petugas)",
                    'success',
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'kegiatan_nama' => $kegiatan->nama_kegiatan,
                        'bulan' => $firstPeriode['bulan'],
                        'tahun' => $firstPeriode['tahun'],
                        'total_petugas' => $created,
                        'total_periode' => count($periodeGroups),
                    ]
                );
            }

            return redirect()->route('alokasi.index')
                ->with('success', "{$created} alokasi petugas berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to store multiple alokasi', [
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_nama' => $kegiatan->nama_kegiatan,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_alokasi_count' => count($validated['alokasi'] ?? []),
                'user_id' => effectiveUser($request)->id,
            ]);

            ActivityLog::logError(
                'Gagal Buat Alokasi Periode',
                'alokasi',
                'Terjadi exception saat menyimpan alokasi untuk '.$kegiatan->nama_kegiatan.': '.$e->getMessage(),
                [
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_nama' => $kegiatan->nama_kegiatan,
                    'request_alokasi_count' => count($validated['alokasi'] ?? []),
                    'error' => $e->getMessage(),
                ]
            );

            return back()->withErrors([
                'error' => 'Terjadi kesalahan saat menyimpan alokasi: '.$e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $activeYear = ActiveYearService::get();
        $effectiveUser = effectiveUser($request);

        // Check if any kegiatan exists before allowing access
        if ($effectiveUser->hasActiveRole('ketua_tim')) {
            $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
                ->where(function ($q) use ($effectiveUser) {
                    $q->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
                ->exists();
        } else {
            $hasKegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])->exists();
        }

        if (! $hasKegiatans) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada kegiatan yang tersedia untuk membuat periode alokasi.');
        }

        if ($effectiveUser->hasActiveRole('ketua_tim')) {
            $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
                ->where(function ($q) use ($effectiveUser) {
                    $q->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
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
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai', 'unit_sampel_pencacahan_ids', 'unit_sampel_listing_ids')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($kegiatan) {
                    // Only show kegiatan that has at least one rate honor
                    return $kegiatan->rateHonors->isNotEmpty();
                })
                ->values();
        } else {
            $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
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
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai', 'unit_sampel_pencacahan_ids', 'unit_sampel_listing_ids')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($kegiatan) {
                    // Only show kegiatan that has at least one rate honor
                    return $kegiatan->rateHonors->isNotEmpty();
                })
                ->values();
        }

        // Pastikan field rate_listing dan satuan_listing_id selalu ada di setiap rateHonors
        foreach ($kegiatans as $kegiatan) {
            foreach ($kegiatan->rateHonors as $rateHonor) {
                // Pastikan field rate_listing dan satuan_listing_id selalu ada
                if (! array_key_exists('rate_listing', $rateHonor->getAttributes())) {
                    $rateHonor->rate_listing = null;
                }
                if (! array_key_exists('satuan_listing_id', $rateHonor->getAttributes())) {
                    $rateHonor->satuan_listing_id = null;
                }

                // Add SBML limit for this rate honor
                $sbml = Sbml::where('tahun_anggaran', $activeYear)
                    ->where('jenis_kegiatan', $rateHonor->jenis_kegiatan)
                    ->where('status_kepegawaian', $rateHonor->status_kepegawaian)
                    ->where('jenis_penugasan', $rateHonor->jenis_penugasan)
                    ->where('status', 'aktif')
                    ->first();

                $rateHonor->sbml_limit = $sbml ? $sbml->honor_max : null;
            }

            $kegiatan->setAttribute(
                'unit_sampel_pencacahan_items',
                $kegiatan->unitSampelPencacahanItems()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'nama' => $item->nama,
                    ])
                    ->values()
                    ->all()
            );

            $kegiatan->setAttribute(
                'unit_sampel_listing_items',
                $kegiatan->unitSampelListingItems()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'nama' => $item->nama,
                    ])
                    ->values()
                    ->all()
            );
        }
        // Calculate budget info for all kegiatans
        $budgetInfo = [];
        $usedMonthsInfo = [];
        foreach ($kegiatans as $kegiatan) {
            $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $budgetInfo[$kegiatan->id] = [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'current_total_spent_other_periods' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
                'current_total_spent_listing_other_periods' => $totalSpentListing,
            ];

            // Calculate used months/periods for this kegiatan
            // For kegiatan with listing, track which tahapan is used for each month
            $periodeList = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->select('bulan', 'tahapan')
                ->get();

            if ($kegiatan->jenis_kegiatan === 'sensus') {
                $usedMonthsInfo[$kegiatan->id] = [
                    'has_listing' => false,
                    'months' => $periodeList->isNotEmpty() ? range(1, 12) : [],
                ];

                continue;
            }

            if ($kegiatan->has_listing_updating) {
                // For listing kegiatan, track tahapan per month
                $usedPeriodsMap = [];
                foreach ($periodeList as $periode) {
                    $bulan = (int) $periode->bulan;
                    if (! isset($usedPeriodsMap[$bulan])) {
                        $usedPeriodsMap[$bulan] = [];
                    }
                    // Add tahapan to this month
                    if ($periode->tahapan === 'both') {
                        $usedPeriodsMap[$bulan][] = 'listing';
                        $usedPeriodsMap[$bulan][] = 'pencacahan';
                    } elseif ($periode->tahapan === 'listing_only') {
                        $usedPeriodsMap[$bulan][] = 'listing';
                    } elseif ($periode->tahapan === 'pencacahan_only') {
                        $usedPeriodsMap[$bulan][] = 'pencacahan';
                    }
                }
                $usedMonthsInfo[$kegiatan->id] = [
                    'has_listing' => true,
                    'periods' => $usedPeriodsMap,
                ];
            } else {
                // For non-listing kegiatan, just list of used months
                $usedMonthsInfo[$kegiatan->id] = [
                    'has_listing' => false,
                    'months' => $periodeList->pluck('bulan')->map(fn ($b) => (int) $b)->toArray(),
                ];
            }
        }

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas', 'jabatan', 'desa_kelurahan')
            ->get();

        $petugasSuggestions = $this->buildPetugasSuggestions($kegiatans, $activeYear);
        $petugasUniqueKegiatanCounts = $this->buildPetugasUniqueKegiatanCounts($activeYear);
        $petugasAllocationCounts = $this->buildPetugasAllocationCounts($activeYear);
        $petugasTotalHonor = $this->buildPetugasTotalHonorByYear($activeYear);
        $petugasReviewRecommendations = $this->buildPetugasReviewRecommendations($activeYear);

        // Get existing allocations per petugas per bulan (for SBML toggle check)
        $existingAllocations = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('CAST(pa.bulan AS UNSIGNED) as bulan')
            ->selectRaw('pa.tahun')
            ->selectRaw('SUM(CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) as total_honor_pencacahan')
            ->selectRaw('SUM(CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END) as total_honor_listing')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id', 'pa.bulan', 'pa.tahun')
            ->get()
            ->map(function ($row) {
                return [
                    'petugas_id' => (int) $row->petugas_id,
                    'bulan' => (int) $row->bulan,
                    'tahun' => (int) $row->tahun,
                    'total_honor_pencacahan' => (float) $row->total_honor_pencacahan,
                    'total_honor_listing' => (float) $row->total_honor_listing,
                    'total_honor_combined' => (float) $row->total_honor_combined,
                ];
            })
            ->values()
            ->all();

        // Handle pre-selected kegiatan from query string
        $selectedKegiatan = null;
        if ($request->filled('kegiatan_id')) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $selectedKegiatan = Kegiatan::where('id', $decodedId)
                        ->whereIn('status', ['divalidasi', 'aktif'])
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
                        ->first();

                    // Add SBML limits to selected kegiatan's rate honors
                    if ($selectedKegiatan) {
                        foreach ($selectedKegiatan->rateHonors as $rateHonor) {
                            $sbml = Sbml::where('tahun_anggaran', $activeYear)
                                ->where('jenis_kegiatan', $rateHonor->jenis_kegiatan)
                                ->where('status_kepegawaian', $rateHonor->status_kepegawaian)
                                ->where('jenis_penugasan', $rateHonor->jenis_penugasan)
                                ->where('status', 'aktif')
                                ->first();

                            $rateHonor->sbml_limit = $sbml ? $sbml->honor_max : null;
                        }

                        $selectedKegiatan->setAttribute(
                            'unit_sampel_pencacahan_items',
                            $selectedKegiatan->unitSampelPencacahanItems()
                                ->map(fn ($item) => [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                ])
                                ->values()
                                ->all()
                        );

                        $selectedKegiatan->setAttribute(
                            'unit_sampel_listing_items',
                            $selectedKegiatan->unitSampelListingItems()
                                ->map(fn ($item) => [
                                    'id' => $item->id,
                                    'nama' => $item->nama,
                                ])
                                ->values()
                                ->all()
                        );
                    }
                }
            } catch (\Exception $e) {
                // Invalid hashed_id, just ignore
            }
        }

        // Handle copy from existing periode
        $copiedAlokasi = null;
        $sourcePeriode = null;

        if ($request->filled(['kegiatan_id', 'copy_from_bulan', 'copy_from_tahun'])) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $kegiatan = Kegiatan::find($decodedId);
                    if ($kegiatan) {
                        // Ketua Tim can only copy from their own kegiatan
                        $effectiveUser = effectiveUser($request);
                        if ($effectiveUser->hasActiveRole('ketua_tim') && ! ($kegiatan->ketua_tim_user_id === $effectiveUser->id || $kegiatan->pj_lainnya_id === $effectiveUser->id)) {
                            // Don't copy data if ketua_tim tries to copy from other's kegiatan
                            $copiedAlokasi = null;
                            $sourcePeriode = null;
                        } else {
                            $sourcePeriodeData = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                                ->where('tahun', $request->copy_from_tahun)
                                ->where('bulan', $request->copy_from_bulan)
                                ->with(['alokasiPetugas.petugas', 'alokasiPetugas.frameSampelAllocations'])
                                ->first();

                            if ($sourcePeriodeData && $sourcePeriodeData->alokasiPetugas->isNotEmpty()) {
                                $copiedAlokasi = $sourcePeriodeData->alokasiPetugas->map(function ($alokasi) {
                                    return [
                                        'petugas_id' => $alokasi->petugas_id,
                                        'petugas_nama' => $alokasi->petugas->nama,
                                        'status_kepegawaian' => $alokasi->status_kepegawaian,
                                        'peran' => $alokasi->peran,
                                        'jumlah_satuan' => $this->normalizeSatuanForResponse($alokasi->jumlah_satuan),
                                        'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                                        'total_honor' => (float) ($alokasi->total_honor ?? 0),
                                        'total_honor_listing' => (float) ($alokasi->total_honor_listing ?? 0),
                                        'is_partial_payment' => (bool) $alokasi->is_partial_payment,
                                        'partial_jumlah_satuan' => $this->normalizeSatuanForResponse($alokasi->partial_jumlah_satuan),
                                        'estimasi_honor_partial' => $alokasi->estimasi_honor_partial,
                                        'is_partial_payment_listing' => (bool) $alokasi->is_partial_payment_listing,
                                        'partial_jumlah_satuan_listing' => $alokasi->partial_jumlah_satuan_listing,
                                        'estimasi_honor_partial_listing' => $alokasi->estimasi_honor_partial_listing,
                                        'jumlah_unit_sampel' => (int) ($alokasi->jumlah_unit_sampel ?? 0),
                                        'frame_sampel_ids' => $alokasi->frameSampelAllocations->pluck('kegiatan_frame_sampel_id')->map(fn ($value) => (int) $value)->values()->all(),
                                        'catatan' => $alokasi->catatan,
                                    ];
                                });

                                $sourcePeriode = [
                                    'id' => $sourcePeriodeData->id,
                                    'hashed_id' => $sourcePeriodeData->hashed_id,
                                    'bulan' => str_pad($request->copy_from_bulan, 2, '0', STR_PAD_LEFT),
                                    'tahun' => $request->copy_from_tahun,
                                    'tahapan' => $sourcePeriodeData->tahapan ?? 'both',
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Invalid data, just ignore
            }
        }

        // Calculate budget info for selected kegiatan
        if ($selectedKegiatan && ! isset($budgetInfo[$selectedKegiatan->id])) {
            $selectedTotalSpent = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $selectedTotalSpentListing = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $budgetInfo[$selectedKegiatan->id] = [
                'pagu_pencacahan' => $selectedKegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $selectedTotalSpent,
                'current_total_spent_other_periods' => $selectedTotalSpent,
                'pagu_listing' => $selectedKegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $selectedTotalSpentListing,
                'current_total_spent_listing_other_periods' => $selectedTotalSpentListing,
            ];

            $selectedPeriodeList = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->select('bulan', 'tahapan')
                ->get();

            if ($selectedKegiatan->has_listing_updating) {
                $selectedUsedPeriodsMap = [];
                foreach ($selectedPeriodeList as $periode) {
                    $bulanInt = (int) $periode->bulan;
                    if (! isset($selectedUsedPeriodsMap[$bulanInt])) {
                        $selectedUsedPeriodsMap[$bulanInt] = [];
                    }

                    if ($periode->tahapan === 'both') {
                        $selectedUsedPeriodsMap[$bulanInt][] = 'listing';
                        $selectedUsedPeriodsMap[$bulanInt][] = 'pencacahan';
                    } elseif ($periode->tahapan === 'listing_only') {
                        $selectedUsedPeriodsMap[$bulanInt][] = 'listing';
                    } elseif ($periode->tahapan === 'pencacahan_only') {
                        $selectedUsedPeriodsMap[$bulanInt][] = 'pencacahan';
                    }
                }

                $usedMonthsInfo[$selectedKegiatan->id] = [
                    'has_listing' => true,
                    'periods' => $selectedUsedPeriodsMap,
                ];
            } else {
                $usedMonthsInfo[$selectedKegiatan->id] = [
                    'has_listing' => false,
                    'months' => $selectedPeriodeList->pluck('bulan')->map(fn ($b) => (int) $b)->toArray(),
                ];
            }
        }

        $paguAnggaran = $selectedKegiatan ? $selectedKegiatan->anggaran : 0;
        $currentTotalSpent = $selectedKegiatan ? PeriodeAlokasi::where('kegiatan_id', $decodedId)
            ->where('tahun', $activeYear)
            ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor');
            }) : 0;

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'selectedKegiatan' => $selectedKegiatan,
            'active_year' => $activeYear,
            'copiedAlokasi' => $copiedAlokasi,
            'sourcePeriode' => $sourcePeriode,
            'budget_info' => $budgetInfo,
            'used_months_info' => $usedMonthsInfo,
            'existing_allocations' => $existingAllocations,
            'petugas_suggestions' => $petugasSuggestions,
            'petugas_unique_kegiatan_counts' => $petugasUniqueKegiatanCounts,
            'petugas_allocation_counts' => $petugasAllocationCounts,
            'petugas_total_honor' => $petugasTotalHonor,
            'petugas_review_recommendations' => $petugasReviewRecommendations,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlokasiPetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $petugas = Petugas::findOrFail($data['petugas_id']);
        $statusKepegawaian = $this->resolveStatusKepegawaianFromPetugas($petugas);
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        if ($rateHonor->status_kepegawaian !== $statusKepegawaian) {
            return back()->withErrors([
                'rate_honor_id' => 'Rate honor tidak sesuai dengan status kepegawaian petugas terpilih.',
            ])->withInput();
        }

        $pencacahanWorkload = $this->resolvePencacahanWorkload(
            $kegiatan,
            (float) ($data['jumlah_satuan'] ?? 0)
        );
        $totalHonor = $this->isSensusEkonomi2026($kegiatan)
            ? (float) $rateHonor->rate * 2.5
            : (float) $rateHonor->rate * $pencacahanWorkload;

        // Calculate total honor for listing if present
        $jumlahSatuanListing = $data['jumlah_satuan_listing'] ?? null;
        $totalHonorListing = null;
        if ($hasListing && $jumlahSatuanListing !== null) {
            $totalHonorListing = ($rateHonor->rate_listing ?? 0) * $jumlahSatuanListing;
        }

        // Check SBML constraint for pencacahan
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $statusKepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor,
            $kegiatan
        );
        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        // Check petugas monthly total honor across all kegiatan (including listing)
        $combinedNewHonor = $totalHonor + ($totalHonorListing ?? 0);
        if ($combinedNewHonor > 0) {
            $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                $data['petugas_id'],
                $data['tahun'],
                $data['bulan'],
                $combinedNewHonor,
                null,
                $rateHonor->jenis_penugasan,
                $data['jenis_kegiatan'],
                $statusKepegawaian,
                $kegiatan
            );
            if ($petugasTotalError) {
                return back()->withErrors(['sbml_constraint' => $petugasTotalError])->withInput();
            }
        }

        // Optionally check SBML for listing phase if needed

        $data['total_honor'] = $totalHonor;
        $data['total_honor_listing'] = $totalHonorListing;
        $data['jumlah_satuan_listing'] = $jumlahSatuanListing;
        $data['peran'] = $this->resolvePeranCodeFromRateHonor($rateHonor);
        $data['status_kepegawaian'] = $statusKepegawaian;
        $data['submitted_by'] = effectiveUser($request)->id;

        AlokasiPetugas::create($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AlokasiPetugas $alokasi): Response
    {
        $alokasi->load([
            'kegiatan.ketuaTim',
            'kegiatan.rateHonor.satuan',
            'petugas',
            'submittedBy',
            'approvedBy',
        ]);

        return Inertia::render('Alokasi/Show', [
            'alokasi' => $alokasi,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AlokasiPetugas $alokasi): Response
    {
        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan')
            ->get();

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas', 'jabatan', 'golongan')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        return Inertia::render('Alokasi/Edit', [
            'alokasi' => $alokasi,
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'rateHonors' => $rateHonors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlokasiPetugasRequest $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $data = $request->validated();
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);
        $petugas = Petugas::findOrFail($data['petugas_id']);
        $statusKepegawaian = $this->resolveStatusKepegawaianFromPetugas($petugas);
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        if ($rateHonor->status_kepegawaian !== $statusKepegawaian) {
            return back()->withErrors([
                'rate_honor_id' => 'Rate honor tidak sesuai dengan status kepegawaian petugas terpilih.',
            ])->withInput();
        }

        $pencacahanWorkload = $this->resolvePencacahanWorkload(
            $kegiatan,
            (float) ($data['jumlah_satuan'] ?? 0)
        );
        $totalHonor = $this->isSensusEkonomi2026($kegiatan)
            ? (float) $rateHonor->rate * 2.5
            : (float) $rateHonor->rate * $pencacahanWorkload;

        // Calculate total honor for listing if present
        $jumlahSatuanListing = $data['jumlah_satuan_listing'] ?? null;
        $totalHonorListing = null;
        if ($hasListing && $jumlahSatuanListing !== null) {
            $totalHonorListing = ($rateHonor->rate_listing ?? 0) * $jumlahSatuanListing;
        }

        // Check SBML constraint for pencacahan
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $statusKepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor,
            $kegiatan
        );
        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        // Check petugas monthly total honor across all kegiatan (including listing)
        // Exclude current alokasi's periode so it doesn't double-count itself
        $combinedNewHonor = $totalHonor + ($totalHonorListing ?? 0);
        if ($combinedNewHonor > 0) {
            $petugasTotalError = $this->checkPetugasTotalHonorInMonth(
                $data['petugas_id'],
                $data['tahun'],
                $data['bulan'],
                $combinedNewHonor,
                $alokasi->periode_alokasi_id,
                $rateHonor->jenis_penugasan,
                $data['jenis_kegiatan'],
                $statusKepegawaian,
                $kegiatan
            );
            if ($petugasTotalError) {
                return back()->withErrors(['sbml_constraint' => $petugasTotalError])->withInput();
            }
        }

        $data['total_honor'] = $totalHonor;
        $data['total_honor_listing'] = $totalHonorListing;
        $data['jumlah_satuan_listing'] = $jumlahSatuanListing;
        $data['peran'] = $this->resolvePeranCodeFromRateHonor($rateHonor);
        $data['status_kepegawaian'] = $statusKepegawaian;

        $alokasi->update($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlokasiPetugas $alokasi): RedirectResponse
    {
        $alokasi->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil dihapus.');
    }

    /**
     * Submit alokasi for approval.
     */
    public function submit(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if ($alokasi->status !== 'draft') {
            return back()->with('error', 'Hanya alokasi dengan status draft yang dapat diajukan.');
        }

        $alokasi->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Alokasi berhasil diajukan untuk persetujuan.');
    }

    /**
     * Approve alokasi.
     */
    public function approve(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui',
            'approved_by' => $effectiveUser->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui.');
    }

    /**
     * Reject alokasi.
     */
    public function reject(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'required|string',
        ]);

        $alokasi->update([
            'status' => 'ditolak',
            'approved_by' => $effectiveUser->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'],
        ]);

        return back()->with('success', 'Alokasi ditolak.');
    }

    /**
     * Approve alokasi by Ketua Tim.
     */
    public function approvePj(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $effectiveUser = effectiveUser($request);
        if (! $effectiveUser->hasActiveRole('ketua_tim')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        // Check if user is the Ketua Tim of the kegiatan
        if ($alokasi->kegiatan->ketua_tim_user_id !== $effectiveUser->id) {
            return back()->with('error', 'Anda bukan ketua tim kegiatan ini.');
        }

        if ($alokasi->status !== 'diajukan') {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui_pj',
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui. Menunggu persetujuan final dari Approver.');
    }

    /**
     * Submit all alokasi in a periode (kegiatan + bulan)
     */


    /**
     * Show detail of a specific periode with all its alokasi
     */


    public function replaceFrameSampel(Request $request, AlokasiPetugasFrameSampel $frameAllocation): RedirectResponse
    {
        $validated = $request->validate([
            'kegiatan_frame_sampel_id' => ['required', 'integer', 'exists:kegiatan_frame_sampel,id'],
        ]);

        $frameAllocation->load([
            'alokasiPetugas.periodeAlokasi.kegiatan.rateHonors',
            'kegiatanFrameSampel',
        ]);

        $alokasi = $frameAllocation->alokasiPetugas;
        $periode = $alokasi->periodeAlokasi;
        $currentFrame = $frameAllocation->kegiatanFrameSampel;
        $currentPeran = strtolower(trim((string) $alokasi->peran));

        if (! $periode) {
            return back()->withErrors(['error' => 'Periode alokasi tidak ditemukan.']);
        }

        $targetFrame = KegiatanFrameSampel::query()->findOrFail((int) $validated['kegiatan_frame_sampel_id']);

        if ((int) $targetFrame->kegiatan_id !== (int) $periode->kegiatan_id) {
            return back()->withErrors(['kegiatan_frame_sampel_id' => 'Frame sampel pengganti harus berasal dari kegiatan yang sama.']);
        }

        if ($currentFrame && $targetFrame->tahapan !== $currentFrame->tahapan) {
            return back()->withErrors(['kegiatan_frame_sampel_id' => 'Frame sampel pengganti harus berada pada tahapan yang sama.']);
        }

        $periode->loadMissing(['alokasiPetugas.frameSampelAllocations']);

        $usedFrameIdsInPeriode = $periode->alokasiPetugas
            ->filter(fn (AlokasiPetugas $alokasiItem): bool => strtolower(trim((string) $alokasiItem->peran)) === $currentPeran)
            ->flatMap(fn (AlokasiPetugas $alokasiItem) => $alokasiItem->frameSampelAllocations->pluck('kegiatan_frame_sampel_id'))
            ->map(fn ($frameId) => (int) $frameId)
            ->filter(fn (int $frameId) => $frameId > 0)
            ->unique();

        $duplicateInPeriode = $usedFrameIdsInPeriode
            ->reject(fn (int $frameId) => $frameId === (int) $frameAllocation->kegiatan_frame_sampel_id)
            ->contains((int) $targetFrame->id);

        if ($duplicateInPeriode) {
            return back()->withErrors(['kegiatan_frame_sampel_id' => 'Frame sampel tersebut sudah dipakai pada alokasi periode ini.']);
        }

        $currentFrameAllocationIds = $periode->alokasiPetugas
            ->flatMap(fn (AlokasiPetugas $alokasiItem) => $alokasiItem->frameSampelAllocations)
            ->filter(function (AlokasiPetugasFrameSampel $allocationFrame) use ($currentFrame): bool {
                return (int) $allocationFrame->kegiatan_frame_sampel_id === (int) $currentFrame->id;
            })
            ->pluck('id')
            ->values();

        if ($currentFrameAllocationIds->isEmpty()) {
            return back()->withErrors(['kegiatan_frame_sampel_id' => 'Frame sampel yang dipilih tidak ditemukan pada alokasi periode ini.']);
        }

        $selectedFrameTarget = max(1, (int) round(array_sum((array) $targetFrame->target_unit_sampel)));
        $kegiatan = $periode->kegiatan;

        $currentFrameAllocationIds->each(function (int $frameAllocationId) use (
            $kegiatan,
            $targetFrame,
            $selectedFrameTarget,
            $periode,
            $currentFrame
        ): void {
            $allocationFrame = AlokasiPetugasFrameSampel::query()
                ->with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan.rateHonors'])
                ->findOrFail($frameAllocationId);

            $alokasi = $allocationFrame->alokasiPetugas;

            if (! $alokasi || (int) $alokasi->periode_alokasi_id !== (int) $periode->id) {
                return;
            }

            if ((int) $allocationFrame->kegiatan_frame_sampel_id !== (int) $currentFrame->id) {
                return;
            }

            $petugasType = $alokasi->status_kepegawaian
                ?? (($alokasi->petugas->jenis_petugas ?? 'non_organik') === 'organik' ? 'organik' : 'non_organik');
            $rateHonor = $kegiatan->rateHonors
                ->first(fn (RateHonor $rateHonor): bool => $rateHonor->status_kepegawaian === $petugasType && $rateHonor->jenis_penugasan === $alokasi->peran);

            $totalHonorPencacahan = $this->resolvePencacahanWorkload($kegiatan, (float) $selectedFrameTarget) * (float) ($rateHonor?->rate ?? 0);
            $totalHonorListing = $kegiatan->has_listing_updating
                ? (float) ($rateHonor?->rate_listing ?? 0) * $selectedFrameTarget
                : (float) ($alokasi->total_honor_listing ?? 0);

            $allocationFrame->update([
                'kegiatan_frame_sampel_id' => $targetFrame->id,
            ]);

            $alokasi->update([
                'jumlah_satuan' => $selectedFrameTarget,
                'jumlah_unit_sampel' => $selectedFrameTarget,
                'jumlah_satuan_listing' => $kegiatan->has_listing_updating ? $selectedFrameTarget : $alokasi->jumlah_satuan_listing,
                'total_honor' => $totalHonorPencacahan,
                'total_honor_listing' => $totalHonorListing,
            ]);
        });

        return back()->with('success', 'Sampel berhasil diganti.');
    }

    /**
     * @return array{
     *     judul: string,
     *     lokasi: string,
     *     kegiatan_nama: string,
     *     tahun: int,
     *     bulan: string,
     *     periode_label: string,
     *     generated_at: string,
     *     tanggal_pengesahan: string,
     *     ketua_tim_nama: string,
     *     kepala_nama: string,
     *     frame_metadata_columns: array<int, array{code:string,label:string}>,
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{total_frame:int, total_alokasi:int, total_belum_alokasi:int, total_unit:int}
     * }
     */


    /**
     * @return array{code:string,label:string}
     */


    /**
     * Edit all alokasi in a periode
     */


    /**
     * Build unique kegiatan allocation counts per petugas in active year.
     *
     * @return array<int, int>
     */


    /**
     * Build unique kegiatan allocation counts per petugas in active year.
     *
     * @return array<int, int>
     */


    /**
     * Build total honor per petugas in active year.
     *
     * @return array<int, float>
     */


    /**
     * Build suggestion data for petugas ordering in allocation form.
     *
     * @param  Collection<int, Kegiatan>  $kegiatans
     * @return array<int, array{previous_allocations: array<int, array{petugas_id:int, bulan:int, tahun:int}>, smallest_allocation_petugas_ids: array<int, int>}>
     */


    /**
     * Build review-based recommendation metadata per petugas.
     *
     * @return array{has_review_data: bool, global_avg_rating: float, by_petugas: array<int, array{review_count:int, avg_rating:float, balanced_score:float, status:string}>}
     */


    /**
     * Update alokasi periode - replaces all alokasi for the periode
     */


    /**
     * Mark periode as deleted (status = dihapus)
     */


    /**
     * Revert a submitted (dikirim) periode back to draft status.
     * Allowed only when at least one officer's Perjanjian Kerja has not been printed.
     */


    /**
     * Revisi: Prepare revision data in session without creating database records
     */


    /**
     * Batalkan revisi periode yang sudah dikirim (status perubahan) - admin only.
     */


    /**
     * Check if total honor exceeds SBML maximum constraint
     */


    /**
     * Check if petugas total honor in a month exceeds their maximum SBML limit
     * across all their assignments (kegiatan)
     * Now checks SBML based on jenis penugasan from allocations
     */


    /**
     * Update non response untuk hasil pelaksanaan kegiatan
     * Bisa dilakukan oleh ketua tim, admin, atau operator
     */
    public function updateNonResponse(UpdateNonResponseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $effectiveUser = effectiveUser($request);

            foreach ($validated['alokasi_petugas'] as $alokasiData) {
                $alokasi = AlokasiPetugas::findOrFail($alokasiData['id']);

                // Validasi bahwa user adalah ketua tim dari kegiatan ini, atau admin/operator
                $periode = $alokasi->periodeAlokasi;
                $kegiatan = $periode->kegiatan;

                $isKetuaTim = $effectiveUser->id === $kegiatan->ketua_tim_user_id;
                $isAdminOrOperator = $effectiveUser->hasActiveRole('admin') || $effectiveUser->hasActiveRole('operator');

                if (! $isKetuaTim && ! $isAdminOrOperator) {
                    throw new \Exception('Anda tidak memiliki akses untuk mengupdate non response kegiatan ini.');
                }

                $selectedFrameAllocationIds = array_map(
                    'intval',
                    $alokasiData['frame_allocation_ids'] ?? [],
                );

                // Update non response
                $alokasi->update([
                    'non_response' => $alokasiData['non_response'] ?? null,
                    'non_response_listing' => $alokasiData['non_response_listing'] ?? null,
                ]);

                $alokasi->frameSampelAllocations()->update(['is_non_response' => false]);

                if ($selectedFrameAllocationIds !== []) {
                    $alokasi->frameSampelAllocations()
                        ->whereIn('id', $selectedFrameAllocationIds)
                        ->update(['is_non_response' => true]);
                }
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Data non response berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal memperbarui data non response: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Download alokasi petugas template for import (create mode).
     * Accepts optional ?kegiatan=<hash>&tahapan=<value> query params to produce a dynamically-structured template.
     */


    /**
     * Download alokasi petugas template for import (edit mode).
     * Kegiatan and tahapan are derived from the existing PeriodeAlokasi record.
     */


    /**
     * Import alokasi petugas data from Excel
     */


    /**
     * Preview alokasi petugas data from Excel without persisting to database.
     */


    /**
     * Import alokasi petugas for create mode (will create draft periode first).
     */


    /**
     * Validate schedule dates for sensus activities within kegiatan execution period.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */


    /**
     * @return array<int, array{code:string,label:string}>
     */


    /**
     * @param  array<int, array{code:string,label:string}>  $metadataColumns
     * @return array<string, string>
     */


    /**
     * @return array<int, string>
     */


    /**
     * @return array<int, string>
     */


    /**
     * @param  Collection<int, KegiatanFrameSampel>  $frameRows
     * @param  array<string, string>  $metadataValues
     * @param  array<int, string>  $errors
     */


}
