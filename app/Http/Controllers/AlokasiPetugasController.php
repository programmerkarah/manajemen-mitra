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
use App\Http\Controllers\Concerns\AlokasiPetugasCrudActions;
use App\Http\Controllers\Concerns\AlokasiPetugasApprovalActions;

class AlokasiPetugasController extends Controller
{
    use AlokasiPetugasApprovalActions;
    use AlokasiPetugasCrudActions;
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


    /**
     * Show the form for creating a new resource.
     */


    /**
     * Store a newly created resource in storage.
     */


    /**
     * Display the specified resource.
     */


    /**
     * Show the form for editing the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */


    /**
     * Submit alokasi for approval.
     */


    /**
     * Approve alokasi.
     */


    /**
     * Reject alokasi.
     */


    /**
     * Approve alokasi by Ketua Tim.
     */


    /**
     * Submit all alokasi in a periode (kegiatan + bulan)
     */


    /**
     * Show detail of a specific periode with all its alokasi
     */


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
