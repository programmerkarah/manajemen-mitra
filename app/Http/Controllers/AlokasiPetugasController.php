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
use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\Spk;
use App\Services\ActiveYearService;
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
use Vinkla\Hashids\Facades\Hashids;

class AlokasiPetugasController extends Controller
{
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
                'alokasiPetugas:id,periode_alokasi_id,petugas_id,total_honor,total_honor_listing',
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

                    return $periode->alokasiPetugas->sum(function ($alokasi) {
                        return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                    });
                })->sortKeys();
            });

        $honorPerKegiatanPerBulanAll = $deduplicatedPeriodes
            ->groupBy('kegiatan_id')
            ->map(function ($periodesByKegiatan) {
                return $periodesByKegiatan->groupBy('bulan')->map(function ($periodeInMonth) {
                    $periode = $periodeInMonth->first();

                    return $periode->alokasiPetugas->sum(function ($alokasi) {
                        return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                    });
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
            // Hitung ulang total honor untuk periode ini
            $totalHonorPencacahan = $periode->alokasiPetugas->sum('total_honor');
            $totalHonorListing = $periode->alokasiPetugas->sum('total_honor_listing');
            $estimasiHonor = $totalHonorPencacahan + $totalHonorListing;

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

    private function resolvePeriodeDisplayBulan(PeriodeAlokasi $periode): string
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return Carbon::create()->month((int) $periode->bulan)->translatedFormat('F').' '.$periode->tahun;
        }

        $tanggalMulai = $periode->tanggal_mulai->copy()->startOfDay();
        $tanggalSelesai = $periode->tanggal_selesai->copy()->startOfDay();

        if ($tanggalMulai->format('Y-m') === $tanggalSelesai->format('Y-m')) {
            return $tanggalMulai->translatedFormat('F Y');
        }

        if ($tanggalMulai->year === $tanggalSelesai->year) {
            return $tanggalMulai->translatedFormat('F').' - '.$tanggalSelesai->translatedFormat('F Y');
        }

        return $tanggalMulai->translatedFormat('F Y').' - '.$tanggalSelesai->translatedFormat('F Y');
    }

    /**
     * @return array<int, string>
     */
    private function resolvePeriodeFilterBulans(PeriodeAlokasi $periode): array
    {
        if (! $periode->tanggal_mulai || ! $periode->tanggal_selesai) {
            return [str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT)];
        }

        $tanggalMulai = $periode->tanggal_mulai->copy()->startOfMonth();
        $tanggalSelesai = $periode->tanggal_selesai->copy()->startOfMonth();
        $filterBulans = [];

        while ($tanggalMulai->lte($tanggalSelesai)) {
            if ((int) $tanggalMulai->year === (int) $periode->tahun) {
                $filterBulans[] = $tanggalMulai->format('m');
            }

            $tanggalMulai->addMonth();
        }

        if ($filterBulans === []) {
            $filterBulans[] = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);
        }

        return array_values(array_unique($filterBulans));
    }

    private function resolveKegiatanFromPeriodeRoute(string $kegiatanRouteKey, int $tahun, string $bulan): Kegiatan
    {
        $resolvedKegiatan = $this->resolveKegiatanRouteBinding($kegiatanRouteKey);

        if ($resolvedKegiatan instanceof Kegiatan) {
            return $resolvedKegiatan;
        }

        $resolvedPeriode = $this->resolvePeriodeRouteBinding($kegiatanRouteKey);
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        if (
            $resolvedPeriode instanceof PeriodeAlokasi
            && (int) $resolvedPeriode->tahun === $tahun
            && str_pad((string) $resolvedPeriode->bulan, 2, '0', STR_PAD_LEFT) === $bulanFormatted
        ) {
            if ($resolvedPeriode->relationLoaded('kegiatan') && $resolvedPeriode->kegiatan instanceof Kegiatan) {
                return $resolvedPeriode->kegiatan;
            }

            return $resolvedPeriode->kegiatan()->firstOrFail();
        }

        abort(404);
    }

    /**
     * @return array<int, string>
     */
    private function resolveBulanCandidates(string $bulan): array
    {
        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

        return array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $alokasiItems
     * @return array<int, string>
     */
    private function validateSampleFrameAllocations(array $alokasiItems, Kegiatan $kegiatan): array
    {
        if ($kegiatan->jenis_kegiatan !== 'survei') {
            return [];
        }

        $frameById = KegiatanFrameSampel::query()
            ->where('kegiatan_id', $kegiatan->id)
            ->get(['id', 'tahapan', 'target_unit_sampel'])
            ->keyBy('id');

        if ($frameById->isEmpty()) {
            return [];
        }

        $errors = [];

        foreach ($alokasiItems as $index => $item) {
            $rowNumber = $index + 1;

            $frameIds = collect($item['frame_sampel_ids'] ?? [])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            $jumlahUnitSampel = (int) ($item['jumlah_unit_sampel'] ?? 0);

            if ($frameIds->isEmpty()) {
                $errors[] = "Baris #{$rowNumber}: frame sampel wajib dipilih.";

                continue;
            }

            $invalidFrameIds = $frameIds->filter(fn ($frameId) => ! $frameById->has($frameId));
            if ($invalidFrameIds->isNotEmpty()) {
                $errors[] = "Baris #{$rowNumber}: terdapat frame sampel tidak valid untuk kegiatan ini.";

                continue;
            }

            $expectedTahapan = match ($item['tahapan'] ?? 'both') {
                'listing_only' => 'listing',
                'pencacahan_only' => 'pencacahan',
                default => null,
            };

            if ($expectedTahapan !== null) {
                $invalidTahapanFrame = $frameIds->first(function ($frameId) use ($frameById, $expectedTahapan) {
                    return ($frameById->get($frameId)?->tahapan ?? null) !== $expectedTahapan;
                });

                if ($invalidTahapanFrame !== null) {
                    $errors[] = "Baris #{$rowNumber}: frame sampel harus sesuai tahapan {$expectedTahapan}.";

                    continue;
                }
            }

            if ($jumlahUnitSampel <= 0) {
                $errors[] = "Baris #{$rowNumber}: jumlah unit sampel wajib lebih dari 0.";

                continue;
            }

            $maxUnitSampel = (int) $frameIds->sum(fn ($frameId) => array_sum((array) ($frameById->get($frameId)?->target_unit_sampel ?? [])));
            if ($jumlahUnitSampel > $maxUnitSampel) {
                $errors[] = "Baris #{$rowNumber}: jumlah unit sampel ({$jumlahUnitSampel}) melebihi kumulatif target frame terpilih ({$maxUnitSampel}).";
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, int|string>  $frameIds
     */
    private function syncAlokasiFrameSampel(AlokasiPetugas $alokasiPetugas, array $frameIds): void
    {
        $normalizedFrameIds = collect($frameIds)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $alokasiPetugas->frameSampelAllocations()->delete();

        if ($normalizedFrameIds->isEmpty()) {
            return;
        }

        $alokasiPetugas->frameSampelAllocations()->createMany(
            $normalizedFrameIds
                ->map(fn ($frameId) => ['kegiatan_frame_sampel_id' => $frameId])
                ->all()
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $alokasiItems
     * @return array<int, array<string, mixed>>
     */
    private function mergeAlokasiRowsForStorage(array $alokasiItems): array
    {
        $merged = [];

        foreach ($alokasiItems as $item) {
            $key = implode('|', [
                (string) ($item['petugas_id'] ?? ''),
                Str::lower((string) ($item['peran'] ?? '')),
                (string) ($item['bulan'] ?? ''),
                (string) ($item['tahun'] ?? ''),
                (string) ($item['jenis_kegiatan'] ?? ''),
                (string) ($item['tahapan'] ?? 'both'),
            ]);

            if (! isset($merged[$key])) {
                $merged[$key] = $item;
                $merged[$key]['frame_sampel_ids'] = array_values(array_unique(array_map('intval', $item['frame_sampel_ids'] ?? [])));

                continue;
            }

            $existing = $merged[$key];
            $mergedFrameIds = array_values(array_unique(array_merge(
                array_map('intval', $existing['frame_sampel_ids'] ?? []),
                array_map('intval', $item['frame_sampel_ids'] ?? [])
            )));

            $merged[$key]['jumlah_satuan'] = (float) ($existing['jumlah_satuan'] ?? 0) + (float) ($item['jumlah_satuan'] ?? 0);
            $merged[$key]['jumlah_satuan_listing'] = (int) ($existing['jumlah_satuan_listing'] ?? 0) + (int) ($item['jumlah_satuan_listing'] ?? 0);
            $merged[$key]['jumlah_unit_sampel'] = (int) ($existing['jumlah_unit_sampel'] ?? 0) + (int) ($item['jumlah_unit_sampel'] ?? 0);
            $merged[$key]['partial_jumlah_satuan'] = (float) ($existing['partial_jumlah_satuan'] ?? 0) + (float) ($item['partial_jumlah_satuan'] ?? 0);
            $merged[$key]['partial_jumlah_satuan_listing'] = (int) ($existing['partial_jumlah_satuan_listing'] ?? 0) + (int) ($item['partial_jumlah_satuan_listing'] ?? 0);
            $merged[$key]['is_partial_payment'] = (bool) ($existing['is_partial_payment'] ?? false) || (bool) ($item['is_partial_payment'] ?? false);
            $merged[$key]['is_partial_payment_listing'] = (bool) ($existing['is_partial_payment_listing'] ?? false) || (bool) ($item['is_partial_payment_listing'] ?? false);
            $merged[$key]['frame_sampel_ids'] = $mergedFrameIds;

            $existingCatatan = trim((string) ($existing['catatan'] ?? ''));
            $newCatatan = trim((string) ($item['catatan'] ?? ''));
            $catatanParts = array_values(array_unique(array_filter([$existingCatatan, $newCatatan], fn (string $text): bool => $text !== '')));
            $merged[$key]['catatan'] = implode('; ', $catatanParts);
        }

        return array_values($merged);
    }

    protected function resolveKegiatanRouteBinding(string $kegiatanRouteKey): ?Kegiatan
    {
        return (new Kegiatan)->resolveRouteBinding($kegiatanRouteKey);
    }

    protected function resolvePeriodeRouteBinding(string $kegiatanRouteKey): ?PeriodeAlokasi
    {
        return (new PeriodeAlokasi)->resolveRouteBinding($kegiatanRouteKey);
    }

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
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
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
            $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
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
            $totalHonor = $rateHonor->rate * $pencacahanWorkload;

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
                $estimasiHonorPartial = $rateHonor->rate * $partialWorkload;
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
            $periodeKey = $alokasiData['bulan'].'_'.$alokasiData['tahun'].'_'.$alokasiData['jenis_kegiatan'];
            if (! isset($periodeGroups[$periodeKey])) {
                $periodeGroups[$periodeKey] = [
                    'bulan' => str_pad($alokasiData['bulan'], 2, '0', STR_PAD_LEFT),
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
                'status_kepegawaian' => $rateHonor->status_kepegawaian,
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
                    ->whereIn('status', ['draft', 'dikirim', 'direvisi'])
                    ->sum(function ($p) {
                        return $p->alokasiPetugas->sum('total_honor');
                    });

                $totalSpentListing = $kegiatan->periodeAlokasi
                    ->whereIn('status', ['draft', 'dikirim', 'direvisi'])
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
                    ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
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
                    ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
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
                        'bulan' => $periodeData['bulan'],
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
                        $query->select('id', 'kegiatan_id', 'tahapan', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
                            ->orderBy('tahapan')
                            ->orderBy('id');
                    },
                ])
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
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
                        $query->select('id', 'kegiatan_id', 'tahapan', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
                            ->orderBy('tahapan')
                            ->orderBy('id');
                    },
                ])
                ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
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
        }
        // Calculate budget info for all kegiatans
        $budgetInfo = [];
        $usedMonthsInfo = [];
        foreach ($kegiatans as $kegiatan) {
            $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $budgetInfo[$kegiatan->id] = [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
            ];

            // Calculate used months/periods for this kegiatan
            // For kegiatan with listing, track which tahapan is used for each month
            $periodeList = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
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
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
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
                                $query->select('id', 'kegiatan_id', 'tahapan', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
                                    ->orderBy('tahapan')
                                    ->orderBy('id');
                            },
                        ])
                        ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
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
                                // Calculate kegiatan duration
                                $tanggalMulai = Carbon::parse($kegiatan->tanggal_mulai);
                                $tanggalSelesai = Carbon::parse($kegiatan->tanggal_selesai);
                                $durationMonths = $tanggalMulai->diffInMonths($tanggalSelesai) + 1;

                                // Only allow copy if kegiatan spans multiple months
                                if ($durationMonths > 1) {
                                    $copiedAlokasi = $sourcePeriodeData->alokasiPetugas->map(function ($alokasi) {
                                        return [
                                            'petugas_id' => $alokasi->petugas_id,
                                            'petugas_nama' => $alokasi->petugas->nama,
                                            'status_kepegawaian' => $alokasi->status_kepegawaian,
                                            'peran' => $alokasi->peran,
                                            'jumlah_satuan' => $alokasi->jumlah_satuan,
                                            'jumlah_satuan_listing' => $alokasi->jumlah_satuan_listing,
                                            'total_honor' => (float) ($alokasi->total_honor ?? 0),
                                            'total_honor_listing' => (float) ($alokasi->total_honor_listing ?? 0),
                                            'is_partial_payment' => (bool) $alokasi->is_partial_payment,
                                            'partial_jumlah_satuan' => $alokasi->partial_jumlah_satuan,
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
                }
            } catch (\Exception $e) {
                // Invalid data, just ignore
            }
        }

        // Calculate budget info for selected kegiatan
        if ($selectedKegiatan && ! isset($budgetInfo[$selectedKegiatan->id])) {
            $selectedTotalSpent = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor');
                });

            $selectedTotalSpentListing = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
                ->with('alokasiPetugas')
                ->get()
                ->sum(function ($p) {
                    return $p->alokasiPetugas->sum('total_honor_listing');
                });

            $budgetInfo[$selectedKegiatan->id] = [
                'pagu_pencacahan' => $selectedKegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $selectedTotalSpent,
                'pagu_listing' => $selectedKegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $selectedTotalSpentListing,
            ];

            $selectedPeriodeList = PeriodeAlokasi::where('kegiatan_id', $selectedKegiatan->id)
                ->where('tahun', $activeYear)
                ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
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
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
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
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $pencacahanWorkload = $this->resolvePencacahanWorkload(
            $kegiatan,
            (float) ($data['jumlah_satuan'] ?? 0)
        );
        $totalHonor = $rateHonor->rate * $pencacahanWorkload;

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
            $rateHonor->status_kepegawaian,
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
                $rateHonor->status_kepegawaian,
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
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;
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
        $hasListing = $kegiatan->has_listing_updating;

        // Calculate total honor for pencacahan
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $pencacahanWorkload = $this->resolvePencacahanWorkload(
            $kegiatan,
            (float) ($data['jumlah_satuan'] ?? 0)
        );
        $totalHonor = $rateHonor->rate * $pencacahanWorkload;

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
            $rateHonor->status_kepegawaian,
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
                $rateHonor->status_kepegawaian,
                $kegiatan
            );
            if ($petugasTotalError) {
                return back()->withErrors(['sbml_constraint' => $petugasTotalError])->withInput();
            }
        }

        $data['total_honor'] = $totalHonor;
        $data['total_honor_listing'] = $totalHonorListing;
        $data['jumlah_satuan_listing'] = $jumlahSatuanListing;
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;

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

    /**
     * Show detail of a specific periode with all its alokasi
     */
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
                'submittedBy:id,name',
            ])
            ->firstOrFail();

        $kegiatan->loadMissing([
            'rateHonors' => function ($query) use ($tahun) {
                $query->where('tahun_berlaku', $tahun)
                    ->where('status', 'aktif');
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
                'hashed_id' => $kegiatan->hashed_id,
                'has_listing_updating' => $kegiatan->has_listing_updating ?? false,
            ],
            'alokasi_petugas' => $periode->alokasiPetugas->map(function ($alokasi) use ($resolveRateHonor) {
                $effectivePencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                    ? (float) $alokasi->estimasi_honor_partial
                    : (float) ($alokasi->total_honor ?? 0);
                $effectiveListingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                    ? (float) $alokasi->estimasi_honor_partial_listing
                    : (float) ($alokasi->total_honor_listing ?? 0);
                $paidJumlahSatuan = $alokasi->is_partial_payment && $alokasi->partial_jumlah_satuan !== null
                    ? (int) $alokasi->partial_jumlah_satuan
                    : (int) ($alokasi->jumlah_satuan ?? 0);
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
                    'jumlah_satuan' => $alokasi->jumlah_satuan,
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
            'total_estimasi' => $totalEstimasi,
            'total_estimasi_pencacahan' => $totalEstimasiPencacahan,
            'total_estimasi_listing' => $totalEstimasiListing,
            'jumlah_petugas' => $jumlahPetugas,
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
                            ? (int) $alokasi->partial_jumlah_satuan
                            : (int) ($alokasi->jumlah_satuan ?? 0);
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
                            'jumlah_satuan' => $alokasi->jumlah_satuan,
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

    /**
     * Edit all alokasi in a periode
     */
    public function editPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): Response|RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);

        $normalizedBulan = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);
        $bulanCandidates = array_values(array_unique([$bulan, (string) ((int) $bulan), $normalizedBulan]));

        // Check if this is revisi mode from session
        $isRevisiMode = $request->session()->get('is_revisi_mode', false);

        if ($isRevisiMode) {
            // Load data from parent periode for revision
            $parentPeriodeId = $request->session()->get('revisi_parent_periode_id');
            $periode = PeriodeAlokasi::with(['alokasiPetugas.petugas', 'alokasiPetugas.frameSampelAllocations'])->findOrFail($parentPeriodeId);
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

        if ($periode->alokasiPetugas->isEmpty()) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada alokasi untuk periode ini.');
        }

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
                    $query->select('id', 'kegiatan_id', 'tahapan', 'nama_frame', 'target_unit_sampel', 'identitas_tambahan')
                        ->orderBy('tahapan')
                        ->orderBy('id');
                },
            ])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'deskripsi', 'jenis_kegiatan', 'pagu_pencacahan', 'ketua_tim_user_id', 'pj_lainnya_id', 'has_listing_updating', 'pagu_listing', 'tanggal_mulai', 'tanggal_selesai')
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
                'jumlah_satuan' => $alok->jumlah_satuan,
                'jumlah_satuan_listing' => $alok->jumlah_satuan_listing,
                'total_honor' => (float) ($alok->total_honor ?? 0),
                'total_honor_listing' => (float) ($alok->total_honor_listing ?? 0),
                'is_partial_payment' => (bool) $alok->is_partial_payment,
                'partial_jumlah_satuan' => $alok->partial_jumlah_satuan,
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
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->pluck('bulan')
            ->map(fn ($b) => (int) $b)
            ->toArray();

        // Calculate budget info - exclude current periode being edited
        $totalSpent = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('id', '!=', $periode->id) // Exclude current periode
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor');
            });

        $totalSpentListing = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('id', '!=', $periode->id) // Exclude current periode
            ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->with('alokasiPetugas')
            ->get()
            ->sum(function ($p) {
                return $p->alokasiPetugas->sum('total_honor_listing');
            });

        $budgetInfo = [
            $kegiatan->id => [
                'pagu_pencacahan' => $kegiatan->pagu_pencacahan ?? 0,
                'current_total_spent' => $totalSpent,
                'pagu_listing' => $kegiatan->pagu_listing ?? 0,
                'current_total_spent_listing' => $totalSpentListing,
            ],
        ];

        // Used months info
        $usedMonthsInfo = [
            $kegiatan->id => $usedMonths,
        ];

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

    /**
     * Build unique kegiatan allocation counts per petugas in active year.
     *
     * @return array<int, int>
     */
    private function buildPetugasUniqueKegiatanCounts(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as unique_kegiatan_count')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('unique_kegiatan_count', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($count, $petugasId) => [(int) $petugasId => (int) $count])
            ->toArray();
    }

    /**
     * Build unique kegiatan allocation counts per petugas in active year.
     *
     * @return array<int, int>
     */
    private function buildPetugasAllocationCounts(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as allocation_count')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('allocation_count', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($count, $petugasId) => [(int) $petugasId => (int) $count])
            ->toArray();
    }

    /**
     * Build total honor per petugas in active year.
     *
     * @return array<int, float>
     */
    private function buildPetugasTotalHonorByYear(int $activeYear): array
    {
        return AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'])
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id')
            ->pluck('total_honor_combined', 'alokasi_petugas.petugas_id')
            ->mapWithKeys(fn ($total, $petugasId) => [(int) $petugasId => (float) $total])
            ->toArray();
    }

    /**
     * Build suggestion data for petugas ordering in allocation form.
     *
     * @param  Collection<int, Kegiatan>  $kegiatans
     * @return array<int, array{previous_allocations: array<int, array{petugas_id:int, bulan:int, tahun:int}>, smallest_allocation_petugas_ids: array<int, int>}>
     */
    private function buildPetugasSuggestions(Collection $kegiatans, int $activeYear): array
    {
        $kegiatanIds = $kegiatans->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        if (empty($kegiatanIds)) {
            return [];
        }

        $activeStatuses = ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'];

        $previousAllocations = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->whereIn('pa.kegiatan_id', $kegiatanIds)
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('pa.kegiatan_id')
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('CAST(pa.bulan AS UNSIGNED) as bulan')
            ->selectRaw('pa.tahun')
            ->groupBy('pa.kegiatan_id', 'alokasi_petugas.petugas_id', 'pa.bulan', 'pa.tahun')
            ->orderByDesc('pa.tahun')
            ->orderByDesc('pa.bulan')
            ->get();

        $smallestAllocationPetugasIds = AlokasiPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'alokasi_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('alokasi_petugas.petugas_id')
            ->selectRaw('COUNT(DISTINCT pa.kegiatan_id) as alokasi_count')
            ->selectRaw('SUM((CASE WHEN alokasi_petugas.is_partial_payment = 1 AND alokasi_petugas.estimasi_honor_partial IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial, 0) ELSE COALESCE(alokasi_petugas.total_honor, 0) END) + (CASE WHEN alokasi_petugas.is_partial_payment_listing = 1 AND alokasi_petugas.estimasi_honor_partial_listing IS NOT NULL THEN COALESCE(alokasi_petugas.estimasi_honor_partial_listing, 0) ELSE COALESCE(alokasi_petugas.total_honor_listing, 0) END)) as total_honor_combined')
            ->groupBy('alokasi_petugas.petugas_id')
            ->orderBy('alokasi_count')
            ->orderBy('total_honor_combined')
            ->orderBy('alokasi_petugas.petugas_id')
            ->pluck('alokasi_petugas.petugas_id')
            ->map(fn ($petugasId) => (int) $petugasId)
            ->values()
            ->all();

        $groupedPreviousAllocations = $previousAllocations
            ->groupBy(fn ($row) => (int) $row->kegiatan_id)
            ->map(function (Collection $rows) {
                return $rows
                    ->map(fn ($row) => [
                        'petugas_id' => (int) $row->petugas_id,
                        'bulan' => (int) $row->bulan,
                        'tahun' => (int) $row->tahun,
                    ])
                    ->values()
                    ->all();
            });

        $result = [];
        foreach ($kegiatanIds as $kegiatanId) {
            $result[$kegiatanId] = [
                'previous_allocations' => $groupedPreviousAllocations->get($kegiatanId, []),
                'smallest_allocation_petugas_ids' => $smallestAllocationPetugasIds,
            ];
        }

        return $result;
    }

    /**
     * Build review-based recommendation metadata per petugas.
     *
     * @return array{has_review_data: bool, global_avg_rating: float, by_petugas: array<int, array{review_count:int, avg_rating:float, balanced_score:float, status:string}>}
     */
    private function buildPetugasReviewRecommendations(int $activeYear): array
    {
        $activeStatuses = ['draft', 'dikirim', 'direvisi', 'disetujui', 'perubahan'];

        $reviewRows = ReviewPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'review_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->selectRaw('review_petugas.petugas_id')
            ->selectRaw('COUNT(*) as review_count')
            ->selectRaw('AVG(review_petugas.rating) as avg_rating')
            ->groupBy('review_petugas.petugas_id')
            ->get();

        if ($reviewRows->isEmpty()) {
            return [
                'has_review_data' => false,
                'global_avg_rating' => 0,
                'by_petugas' => [],
            ];
        }

        $globalAvgRating = (float) (ReviewPetugas::query()
            ->join('periode_alokasi as pa', 'pa.id', '=', 'review_petugas.periode_alokasi_id')
            ->where('pa.tahun', $activeYear)
            ->whereIn('pa.status', $activeStatuses)
            ->avg('review_petugas.rating') ?? 0);

        $byPetugas = $reviewRows
            ->mapWithKeys(function ($row) use ($globalAvgRating) {
                $reviewCount = (int) $row->review_count;
                $avgRating = (float) $row->avg_rating;
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($globalAvgRating * 0.3)) * $confidence
                    + ($globalAvgRating * (1 - $confidence));

                $status = 'neutral';
                if ($reviewCount >= 2 && $avgRating >= 4.0) {
                    $status = 'recommended';
                } elseif ($reviewCount >= 2 && $avgRating < 3.0) {
                    $status = 'not_recommended';
                }

                return [
                    (int) $row->petugas_id => [
                        'review_count' => $reviewCount,
                        'avg_rating' => round($avgRating, 2),
                        'balanced_score' => round($balancedScore, 3),
                        'status' => $status,
                    ],
                ];
            })
            ->toArray();

        return [
            'has_review_data' => true,
            'global_avg_rating' => round($globalAvgRating, 2),
            'by_petugas' => $byPetugas,
        ];
    }

    /**
     * Update alokasi periode - replaces all alokasi for the periode
     */
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
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Format new alokasi for comparison
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
                    ];
                })->sortBy('petugas_id')->values()->all();

                // Check if there are changes
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
                $petugasType = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
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
                $totalHonor = $rateHonor->rate * $pencacahanWorkload;

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
                    $estimasiHonorPartial = $rateHonor->rate * $partialWorkload;
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
                    'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                    'jumlah_satuan_listing' => $jumlahSatuanListing,
                    'jumlah_frame_sampel' => count(array_unique(array_map('intval', $alokasiData['frame_sampel_ids'] ?? []))),
                    'jumlah_unit_sampel' => (int) ($alokasiData['jumlah_unit_sampel'] ?? 0),
                    'total_honor' => $totalHonor,
                    'total_honor_listing' => $totalHonorListing,
                    'is_partial_payment' => $isPartialPayment,
                    'partial_jumlah_satuan' => $isPartialPayment ? $partialJumlahSatuan : null,
                    'estimasi_honor_partial' => $isPartialPayment ? $estimasiHonorPartial : null,
                    'is_partial_payment_listing' => $isPartialPaymentListing,
                    'partial_jumlah_satuan_listing' => $isPartialPaymentListing ? $partialJumlahSatuanListing : null,
                    'estimasi_honor_partial_listing' => $isPartialPaymentListing ? $estimasiHonorPartialListing : null,
                    'peran' => $jenisPenugasan,
                    'status_kepegawaian' => $petugasType,
                    'catatan' => $alokasiData['catatan'] ?? null,
                ];

                if ($hasKegiatanIdColumn) {
                    $alokasiPayload['kegiatan_id'] = $kegiatan->id;
                }

                if ($hasBulanColumn) {
                    $alokasiPayload['bulan'] = (int) $bulan;
                }

                if ($hasTahunColumn) {
                    $alokasiPayload['tahun'] = (int) $tahun;
                }

                $alokasiPetugas = AlokasiPetugas::create($alokasiPayload);
                $this->syncAlokasiFrameSampel($alokasiPetugas, $alokasiData['frame_sampel_ids'] ?? []);

                $created++;
            }

            // Check if there were any errors during validation
            if (! empty($errors)) {
                DB::rollBack();

                $bulanNameErr = Carbon::create()->month((int) $bulan)->translatedFormat('F');
                ActivityLog::logError(
                    $isRevision ? 'Gagal Revisi Alokasi Periode' : 'Gagal Update Alokasi Periode',
                    'alokasi',
                    'Validasi alokasi gagal untuk '.$kegiatan->nama_kegiatan.' ('.$bulanNameErr.' '.$tahun.'): '.implode(' | ', $errors),
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'kegiatan_nama' => $kegiatan->nama_kegiatan,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'errors' => $errors,
                        'is_revision' => $isRevision,
                    ]
                );

                return redirect()->back()->withErrors([
                    'error' => 'Terdapat kesalahan pada alokasi petugas: '.implode(' | ', $errors),
                ]);
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

            if (count($errors) > 0) {
                $warningMessage = $isRevision
                    ? "Berhasil mengirim revisi untuk {$created} alokasi, namun ada beberapa yang gagal."
                    : "Berhasil memperbarui {$created} alokasi, namun ada beberapa yang gagal.";

                return back()->withErrors(['validation' => $errors])
                    ->with('warning', $warningMessage);
            }

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

    /**
     * Mark periode as deleted (status = dihapus)
     */
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

    /**
     * Revert a submitted (dikirim) periode back to draft status.
     * Allowed only when at least one officer's Perjanjian Kerja has not been printed.
     */
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

        // Block if any non-organik officer in this periode already has SPK in the same periode
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

        if ($hasGeneratedSpk) {
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

    /**
     * Revisi: Prepare revision data in session without creating database records
     */
    public function revisiPeriode(Request $request, string $kegiatanRouteKey, int $tahun, string $bulan): RedirectResponse
    {
        $kegiatan = $this->resolveKegiatanFromPeriodeRoute($kegiatanRouteKey, $tahun, $bulan);
        $bulanCandidates = $this->resolveBulanCandidates($bulan);

        // Get existing periode (could be original 'dikirim' or previous 'perubahan')
        $oldPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanCandidates)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->orderByDesc('revision_number')
            ->with('alokasiPetugas')
            ->first();

        if (! $oldPeriode) {
            return back()->with('error', 'Tidak ada alokasi terkirim untuk direvisi.');
        }

        // Store parent periode info in session for later comparison
        $request->session()->put('revisi_parent_periode_id', $oldPeriode->id);
        $request->session()->put('revisi_kegiatan_id', $kegiatan->id);
        $request->session()->put('revisi_tahun', $tahun);
        $request->session()->put('revisi_bulan', $bulan);
        $request->session()->put('is_revisi_mode', true);

        // Redirect to edit page - will load data from parent periode
        return redirect('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$tahun.'/'.$bulan.'/edit')
            ->with('success', 'Mode revisi. Silakan edit data sesuai kebutuhan.');
    }

    /**
     * Batalkan revisi periode yang sudah dikirim (status perubahan) - admin only.
     */
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

    private function validateDecimalSatuanRules(array $alokasiItems): array
    {
        $errors = [];

        foreach ($alokasiItems as $index => $alokasiData) {
            $jenisKegiatan = $alokasiData['jenis_kegiatan'] ?? null;
            if ($jenisKegiatan === 'sensus') {
                continue;
            }

            if ($this->hasDecimalPart($alokasiData['jumlah_satuan'] ?? null)) {
                $errors[] = 'Baris alokasi #'.($index + 1).': jumlah satuan desimal hanya diperbolehkan untuk kegiatan sensus.';
            }

            $isPartialPayment = (bool) ($alokasiData['is_partial_payment'] ?? false);
            if ($isPartialPayment && $this->hasDecimalPart($alokasiData['partial_jumlah_satuan'] ?? null)) {
                $errors[] = 'Baris alokasi #'.($index + 1).': jumlah satuan parsial desimal hanya diperbolehkan untuk kegiatan sensus.';
            }
        }

        return $errors;
    }

    private function hasDecimalPart(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $numericValue = (float) $value;

        return abs($numericValue - round($numericValue)) > 0.000001;
    }

    /**
     * Check if total honor exceeds SBML maximum constraint
     */
    private function checkSbmlConstraint(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan,
        float $totalHonor,
        ?Kegiatan $kegiatan = null
    ): ?string {
        $sbml = Sbml::where('tahun_anggaran', $tahun)
            ->where('jenis_kegiatan', $jenisKegiatan)
            ->where('status_kepegawaian', $statusKepegawaian)
            ->where('jenis_penugasan', $jenisPenugasan)
            ->where('status', 'aktif')
            ->first();

        if (! $sbml) {
            return 'SBML untuk kombinasi ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $limitMultiplier = $this->getSbmlLimitMultiplier($kegiatan);
        $adjustedHonorMax = (float) $sbml->honor_max * $limitMultiplier;

        if ($totalHonor > $adjustedHonorMax) {
            return 'Total honor (Rp '.number_format($totalHonor, 0, ',', '.').') melebihi batas maksimal SBML (Rp '.number_format($adjustedHonorMax, 0, ',', '.').") untuk tahun {$tahun}.";
        }

        return null;
    }

    private function resolvePencacahanWorkload(Kegiatan $kegiatan, float $jumlahSatuan): float
    {
        if ($jumlahSatuan <= 0) {
            return 0;
        }

        if ($this->isSensusEkonomi2026($kegiatan)) {
            return $jumlahSatuan * 2.5;
        }

        return $jumlahSatuan;
    }

    private function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return $kegiatan->jenis_kegiatan === 'sensus'
            && mb_strtolower(trim((string) $kegiatan->nama_kegiatan)) === 'sensus ekonomi';
    }

    private function getSbmlLimitMultiplier(?Kegiatan $kegiatan): float
    {
        if ($kegiatan && $this->isSensusEkonomi2026($kegiatan)) {
            return 2.5;
        }

        return 1.0;
    }

    /**
     * Check if petugas total honor in a month exceeds their maximum SBML limit
     * across all their assignments (kegiatan)
     * Now checks SBML based on jenis penugasan from allocations
     */
    private function checkPetugasTotalHonorInMonth(
        int $petugasId,
        int $tahun,
        int $bulan,
        float $newHonor,
        ?int $excludePeriodeId = null,
        ?string $newPeran = null,
        ?string $newJenisKegiatan = null,
        ?string $newStatusKepegawaian = null,
        ?Kegiatan $kegiatan = null
    ): ?string {
        if ($kegiatan && $this->isSensusEkonomi2026($kegiatan)) {
            return null;
        }

        $petugas = Petugas::find($petugasId);
        if (! $petugas) {
            return 'Petugas tidak ditemukan.';
        }

        // Get all existing allocations for this petugas in this month
        $existingAlokasis = AlokasiPetugas::whereHas('periodeAlokasi', function ($query) use ($tahun, $bulan, $excludePeriodeId) {
            $query->where('tahun', $tahun)
                ->where('bulan', str_pad($bulan, 2, '0', STR_PAD_LEFT))
                ->whereIn('status', ['draft', 'dikirim', 'perubahan']);

            if ($excludePeriodeId) {
                $query->where('id', '!=', $excludePeriodeId);
            }
        })
            ->where('petugas_id', $petugasId)
            ->get();

        $existingTotalHonor = $existingAlokasis->sum(function ($alokasi) {
            $pencacahanHonor = $alokasi->is_partial_payment && $alokasi->estimasi_honor_partial !== null
                ? (float) $alokasi->estimasi_honor_partial
                : (float) ($alokasi->total_honor ?? 0);

            $listingHonor = $alokasi->is_partial_payment_listing && $alokasi->estimasi_honor_partial_listing !== null
                ? (float) $alokasi->estimasi_honor_partial_listing
                : (float) ($alokasi->total_honor_listing ?? 0);

            return $pencacahanHonor + $listingHonor;
        });
        $totalHonorInMonth = $existingTotalHonor + $newHonor;

        // Collect all jenis penugasan (peran) from existing allocations
        $jenisPenugasanList = $existingAlokasis->pluck('peran')->unique();

        // Add new peran if provided
        if ($newPeran) {
            $jenisPenugasanList->push($newPeran);
            $jenisPenugasanList = $jenisPenugasanList->unique();
        }

        // Map peran ke jenis_penugasan dan ambil honor_max SBML untuk tiap penugasan yang sudah diberikan
        $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
        // Ambil kombinasi unik dari alokasi: [jenis_kegiatan, jenis_penugasan, status_kepegawaian]
        $alokasiKombinasi = $existingAlokasis->map(function ($alokasi) {
            return [
                'jenis_kegiatan' => $alokasi->periodeAlokasi->jenis_kegiatan ?? null,
                'jenis_penugasan' => $alokasi->peran,
                'status_kepegawaian' => $alokasi->status_kepegawaian,
            ];
        });
        // Tambahkan kombinasi baru jika ada
        if ($newPeran) {
            $alokasiKombinasi->push([
                'jenis_kegiatan' => $newJenisKegiatan ?? $existingAlokasis->first()?->periodeAlokasi?->jenis_kegiatan ?? null,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' => $newStatusKepegawaian ?? $statusKepegawaian,
            ]);
        }

        // Jika petugas belum pernah dialokasikan (penugasan perdana), gunakan kombinasi dari penugasan baru
        if ($alokasiKombinasi->isEmpty() && $newPeran && $newJenisKegiatan && $newStatusKepegawaian) {
            $alokasiKombinasi->push([
                'jenis_kegiatan' => $newJenisKegiatan,
                'jenis_penugasan' => $newPeran,
                'status_kepegawaian' => $newStatusKepegawaian,
            ]);
        }
        $uniqueKombinasi = $alokasiKombinasi->unique(function ($item) {
            return $item['jenis_kegiatan'].'|'.$item['jenis_penugasan'].'|'.$item['status_kepegawaian'];
        });

        $honorMaxList = $uniqueKombinasi->map(function ($kombinasi) use ($tahun) {
            $sbml = Sbml::where('tahun_anggaran', $tahun)
                ->where('jenis_kegiatan', $kombinasi['jenis_kegiatan'])
                ->where('status_kepegawaian', $kombinasi['status_kepegawaian'])
                ->where('jenis_penugasan', $kombinasi['jenis_penugasan'])
                ->where('status', 'aktif')
                ->first();

            return $sbml ? $sbml->honor_max : null;
        })->filter();

        if ($honorMaxList->isEmpty()) {
            return 'SBML untuk penugasan yang diberikan ke petugas ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        $minAllowed = $honorMaxList->min();

        if ($totalHonorInMonth > $minAllowed) {
            return sprintf(
                'Total honor petugas %s di bulan %s %d (Rp %s) melebihi batas maksimal SBML terendah (Rp %s). Honor yang sudah dialokasikan: Rp %s, Honor baru: Rp %s.',
                $petugas->nama,
                Carbon::create()->month($bulan)->translatedFormat('F'),
                $tahun,
                number_format($totalHonorInMonth, 0, ',', '.'),
                number_format($minAllowed, 0, ',', '.'),
                number_format($existingTotalHonor, 0, ',', '.'),
                number_format($newHonor, 0, ',', '.')
            );
        }

        return null;
    }

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

                // Update non response
                $alokasi->update([
                    'non_response' => $alokasiData['non_response'] ?? null,
                    'non_response_listing' => $alokasiData['non_response_listing'] ?? null,
                ]);
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
    public function exportTemplateCreate(Request $request, string $type = 'create'): BinaryFileResponse
    {
        $kegiatan = null;
        $tahapan = $request->query('tahapan');

        if ($kegiatanHash = $request->query('kegiatan')) {
            $decoded = Hashids::decode((string) $kegiatanHash);
            if (! empty($decoded)) {
                $kegiatan = Kegiatan::find((int) $decoded[0]);
            }
        }

        return Excel::download(
            new AlokasiPetugasTemplateExport(null, $type, $kegiatan, $tahapan),
            "alokasi-petugas-template-{$type}.xlsx"
        );
    }

    /**
     * Download alokasi petugas template for import (edit mode).
     * Kegiatan and tahapan are derived from the existing PeriodeAlokasi record.
     */
    public function exportTemplate(?string $periodeAlokasiHash = null, string $type = 'create'): BinaryFileResponse
    {
        $periodeAlokasiId = null;
        $kegiatan = null;
        $tahapan = null;

        if ($periodeAlokasiHash !== null) {
            $decodedId = Hashids::decode($periodeAlokasiHash)[0] ?? null;
            $periodeAlokasiId = $decodedId !== null ? (int) $decodedId : (is_numeric($periodeAlokasiHash) ? (int) $periodeAlokasiHash : null);
        }

        if ($periodeAlokasiId) {
            $periode = PeriodeAlokasi::find($periodeAlokasiId);
            $kegiatan = $periode?->kegiatan;
            $tahapan = $periode?->tahapan;
        }

        return Excel::download(
            new AlokasiPetugasTemplateExport($periodeAlokasiId, $type, $kegiatan, $tahapan),
            "alokasi-petugas-template-{$type}.xlsx"
        );
    }

    /**
     * Import alokasi petugas data from Excel
     */
    public function import(Request $request, int $periodeAlokasiId): RedirectResponse
    {
        // Get periode alokasi for reference
        $periode = PeriodeAlokasi::findOrFail($periodeAlokasiId);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        try {
            $isCreate = $request->input('is_create', false) === 'true' || $request->input('is_create') === true;
            $import = new AlokasiPetugasImport($periodeAlokasiId, $isCreate);
            Excel::import($import, $validated['file']);

            ActivityLog::log(
                'Import Alokasi Petugas',
                'alokasi',
                "Berhasil mengimport alokasi petugas untuk {$periode->jenis_kegiatan} bulan {$periode->bulan}/{$periode->tahun} ({$import->getSuccessCount()} petugas)",
                'success',
                [
                    'periode_id' => $periodeAlokasiId,
                    'imported_count' => $import->getSuccessCount(),
                    'kegiatan_id' => $periode->kegiatan_id,
                ]
            );

            $backUrl = '/alokasi/periode/'.$periode->kegiatan->hashed_id.'/'.$periode->tahun.'/'.str_pad($periode->bulan, 2, '0', STR_PAD_LEFT);

            return redirect($backUrl)
                ->with('success', "Berhasil mengimport {$import->getSuccessCount()} data alokasi petugas");
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessage = 'Gagal mengimport file. Errors: ';
            $errorDetails = [];
            foreach ($failures as $failure) {
                $errorDetails[] = "Baris {$failure->row()}: ".implode('; ', $failure->errors());
            }

            return back()->withErrors(['file' => $errorMessage.implode(' | ', array_slice($errorDetails, 0, 3))])
                ->withInput();
        } catch (\Exception $e) {
            Log::error('AlokasiPetugasImport Error', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            return back()->withErrors(['file' => 'Gagal mengimport file: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Preview alokasi petugas data from Excel without persisting to database.
     */
    public function importPreview(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'tahapan' => ['nullable', 'in:both,listing_only,pencacahan_only'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        $tahapan = $validated['tahapan'] ?? ($kegiatan->has_listing_updating ? 'both' : 'pencacahan_only');

        $import = new AlokasiPetugasPreviewImport;
        Excel::import($import, $validated['file']);

        $rows = $import->rows();
        $previewRows = [];
        $errors = [];

        $rateByKey = $kegiatan->rateHonors()
            ->where('status', 'aktif')
            ->get()
            ->keyBy(fn ($rate) => $rate->status_kepegawaian.'|'.$rate->jenis_penugasan);
        $allowDecimalPencacahan = $kegiatan->jenis_kegiatan === 'sensus';
        $frameSampelQuery = $kegiatan->kegiatanFrameSampel()->select('id', 'tahapan', 'target_unit_sampel', 'identitas_tambahan');

        if ($tahapan === 'listing_only') {
            $frameSampelQuery->where('tahapan', 'listing');
        }

        if ($tahapan === 'pencacahan_only') {
            $frameSampelQuery->where('tahapan', 'pencacahan');
        }

        $frameSampelRows = $frameSampelQuery->get()->values();
        $frameMetadataColumns = $this->extractFrameSampelMetadataColumns($frameSampelRows);
        $requiresFrameSampelInput = $frameSampelRows->isNotEmpty();
        $sensusUnitSampleColumnKeys = $this->sensusUnitSampleColumnKeys($kegiatan);

        // NIK is encrypted in the DB — load all petugas and build a decrypted NIK → Petugas map.
        $petugasByNik = Petugas::query()
            ->get()
            ->keyBy(fn (Petugas $p) => $p->getAttribute('nik'));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isReferencePetugasSheetRow($row)) {
                continue;
            }

            $nik = $this->parseImportNik($this->extractImportNikCellValue($row));
            $kodePenugasan = trim((string) $this->extractImportPeranCellValue($row));

            // Skip empty rows and instruction/note rows (NIK must be all digits).
            if ($nik === '' || ! ctype_digit($nik)) {
                continue;
            }

            $petugas = $petugasByNik->get($nik);
            if (! $petugas) {
                $errors[] = "Baris {$rowNumber}: Petugas dengan NIK {$nik} tidak ditemukan.";

                continue;
            }

            $peranCode = $this->normalizeImportPeranCode($kodePenugasan);
            if (! $peranCode) {
                $errors[] = "Baris {$rowNumber}: Kode penugasan '{$kodePenugasan}' tidak valid.";

                continue;
            }

            $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
            $rate = $rateByKey->get($statusKepegawaian.'|'.$peranCode);

            if (! $rate) {
                $errors[] = "Baris {$rowNumber}: Rate honor tidak ditemukan untuk {$petugas->nama} ({$statusKepegawaian}, {$peranCode}).";

                continue;
            }

            $jumlahSatuanRaw = $row['jumlah_satuan_pencacahan'] ?? $row['jumlah_satuan'] ?? 0;
            $jumlahSatuanPencacahan = $this->parseImportSatuan($jumlahSatuanRaw);
            $hasSensusUnitSampleInput = false;

            if ($kegiatan->jenis_kegiatan === 'sensus' && ! empty($sensusUnitSampleColumnKeys)) {
                $jumlahSatuanPencacahan = 0;
                foreach ($sensusUnitSampleColumnKeys as $columnKey) {
                    $unitValue = $this->parseImportSatuan($row[$columnKey] ?? 0);
                    if ($unitValue > 0) {
                        $hasSensusUnitSampleInput = true;
                    }
                    $jumlahSatuanPencacahan += $unitValue;
                }
            }
            $jumlahSatuanListing = $this->parseImportInteger($row['jumlah_satuan_listing'] ?? 0);
            $metadataValues = $this->extractImportFrameMetadataValues($row, $frameMetadataColumns);
            $hasAnyMetadataValue = collect($metadataValues)
                ->contains(fn (string $value): bool => trim($value) !== '');

            if ($requiresFrameSampelInput && ! $hasAnyMetadataValue) {
                $errors[] = "Baris {$rowNumber}: Kolom metadata frame sampel wajib diisi.";

                continue;
            }

            $validFrameSampelIds = [];
            $jumlahUnitSampel = 0;

            if ($hasAnyMetadataValue) {
                $matchedFrameSampel = $this->resolveFrameSampelByMetadata(
                    $frameSampelRows,
                    $metadataValues,
                    $rowNumber,
                    $errors
                );

                if ($matchedFrameSampel === null) {
                    continue;
                }

                $validFrameSampelIds[] = (int) $matchedFrameSampel->id;
                $jumlahUnitSampel = max(0, array_sum((array) ($matchedFrameSampel->target_unit_sampel ?? [])));
            }

            if ($requiresFrameSampelInput && $kegiatan->jenis_kegiatan === 'survei') {
                $jumlahSatuanPencacahan = (float) $jumlahUnitSampel;
                $jumlahSatuanListing = $jumlahUnitSampel;

                if ($tahapan === 'listing_only') {
                    $jumlahSatuanPencacahan = 0;
                }

                if ($tahapan === 'pencacahan_only') {
                    $jumlahSatuanListing = 0;
                }
            }

            if ($requiresFrameSampelInput && $kegiatan->jenis_kegiatan === 'sensus' && $hasAnyMetadataValue && ! $hasSensusUnitSampleInput) {
                $jumlahSatuanPencacahan = (float) $jumlahUnitSampel;
            }

            if (! $allowDecimalPencacahan && $this->hasDecimalPart($jumlahSatuanPencacahan)) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan pencacahan desimal hanya diperbolehkan untuk kegiatan sensus.";

                continue;
            }

            if ($tahapan === 'listing_only') {
                $jumlahSatuanPencacahan = 0;
            }

            if ($tahapan === 'pencacahan_only') {
                $jumlahSatuanListing = 0;
            }

            $isPartialPayment = $this->parseImportBoolean($row['pembayaran_parsial'] ?? false);
            $partialJumlahSatuanRaw = $row['jumlah_satuan_parsial_pencacahan'] ?? $row['jumlah_satuan_parsial'] ?? 0;
            $partialJumlahSatuan = $this->parseImportSatuan($partialJumlahSatuanRaw);
            $partialJumlahSatuanListing = $this->parseImportInteger($row['jumlah_satuan_parsial_listing'] ?? 0);

            if ($isPartialPayment && ! $allowDecimalPencacahan && $this->hasDecimalPart($partialJumlahSatuan)) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial pencacahan desimal hanya diperbolehkan untuk kegiatan sensus.";

                continue;
            }

            if (! $isPartialPayment) {
                $partialJumlahSatuan = 0;
                $partialJumlahSatuanListing = 0;
            }

            if ($partialJumlahSatuan > $jumlahSatuanPencacahan) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial pencacahan tidak boleh lebih besar dari jumlah satuan pencacahan.";

                continue;
            }

            if ($partialJumlahSatuanListing > $jumlahSatuanListing) {
                $errors[] = "Baris {$rowNumber}: Jumlah satuan parsial listing tidak boleh lebih besar dari jumlah satuan listing.";

                continue;
            }

            $estimasiHonor = (float) ($rate->rate ?? 0) * $jumlahSatuanPencacahan;
            $estimasiHonorListing = (float) ($rate->rate_listing ?? 0) * $jumlahSatuanListing;
            $estimasiHonorPartial = $isPartialPayment ? (float) ($rate->rate ?? 0) * $partialJumlahSatuan : 0;
            $estimasiHonorPartialListing = $isPartialPayment ? (float) ($rate->rate_listing ?? 0) * $partialJumlahSatuanListing : 0;

            $previewRows[] = [
                'petugas_id' => (string) $petugas->id,
                'petugas_nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'peran' => $this->mapPeranCodeToDisplayLabel($peranCode),
                'jumlah_satuan' => (string) $jumlahSatuanPencacahan,
                'estimasi_honor' => $estimasiHonor,
                'jumlah_satuan_listing' => (string) $jumlahSatuanListing,
                'estimasi_honor_listing' => $estimasiHonorListing,
                'catatan' => '',
                'is_partial_payment' => $isPartialPayment,
                'partial_jumlah_satuan' => $isPartialPayment ? (string) $partialJumlahSatuan : '',
                'estimasi_honor_partial' => $estimasiHonorPartial,
                'is_partial_payment_listing' => $isPartialPayment,
                'partial_jumlah_satuan_listing' => $isPartialPayment ? (string) $partialJumlahSatuanListing : '',
                'estimasi_honor_partial_listing' => $estimasiHonorPartialListing,
                'frame_sampel_ids' => array_values($validFrameSampelIds),
                'jumlah_unit_sampel' => $jumlahUnitSampel,
                'frame_sampel_metadata' => $metadataValues,
            ];
        }

        if (count($previewRows) === 0 && count($errors) === 0 && $rows->count() > 0) {
            $errors[] = 'Tidak ada baris data yang bisa dipreview. Pastikan kolom [Nama - NIK] dan [Kode Penugasan] sudah dipilih dari dropdown template.';
        }

        return response()->json([
            'rows' => $previewRows,
            'errors' => $errors,
            'frame_metadata_columns' => $frameMetadataColumns,
            'summary' => [
                'total_rows' => $rows->count(),
                'valid_rows' => count($previewRows),
                'error_count' => count($errors),
            ],
        ]);
    }

    /**
     * Import alokasi petugas for create mode (will create draft periode first).
     */
    public function importCreate(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'tahapan' => ['nullable', 'in:both,listing_only,pencacahan_only'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_mulai_listing' => ['nullable', 'date'],
            'tanggal_selesai_listing' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_listing'],
            'jadwal_pengolahan_listing_mulai' => ['nullable', 'date'],
            'jadwal_pengolahan_listing_selesai' => ['nullable', 'date', 'after_or_equal:jadwal_pengolahan_listing_mulai'],
            'jadwal_pengolahan_pencacahan_mulai' => ['nullable', 'date'],
            'jadwal_pengolahan_pencacahan_selesai' => ['nullable', 'date', 'after_or_equal:jadwal_pengolahan_pencacahan_mulai'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        $isSensusKegiatan = $kegiatan->jenis_kegiatan === 'sensus';

        $existingPeriodeQuery = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $validated['tahun'])
            ->whereIn('status', ['draft', 'dikirim', 'perubahan', 'direvisi']);

        if (! $isSensusKegiatan) {
            $existingPeriodeQuery->where('bulan', str_pad((string) $validated['bulan'], 2, '0', STR_PAD_LEFT));
        }

        $existingPeriode = $existingPeriodeQuery->first();

        if ($existingPeriode) {
            return back()->withErrors([
                'file' => $isSensusKegiatan
                    ? 'Untuk kegiatan sensus hanya diperbolehkan satu periode/perjanjian kerja dalam satu tahun. Gunakan mode edit untuk import ulang.'
                    : 'Periode untuk bulan/tahun tersebut sudah ada. Gunakan mode edit untuk import ulang.',
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $periode = PeriodeAlokasi::create([
                'kegiatan_id' => $kegiatan->id,
                'bulan' => str_pad((string) $validated['bulan'], 2, '0', STR_PAD_LEFT),
                'tahun' => $validated['tahun'],
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'status' => 'draft',
                'tahapan' => $validated['tahapan'] ?? ($kegiatan->has_listing_updating ? 'both' : 'pencacahan_only'),
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'tanggal_mulai_listing' => $validated['tanggal_mulai_listing'] ?? null,
                'tanggal_selesai_listing' => $validated['tanggal_selesai_listing'] ?? null,
                'jadwal_pengolahan_listing_mulai' => $validated['jadwal_pengolahan_listing_mulai'] ?? null,
                'jadwal_pengolahan_listing_selesai' => $validated['jadwal_pengolahan_listing_selesai'] ?? null,
                'jadwal_pengolahan_pencacahan_mulai' => $validated['jadwal_pengolahan_pencacahan_mulai'] ?? null,
                'jadwal_pengolahan_pencacahan_selesai' => $validated['jadwal_pengolahan_pencacahan_selesai'] ?? null,
                'revision_number' => 0,
            ]);

            $import = new AlokasiPetugasImport($periode->id, true);
            Excel::import($import, $validated['file']);

            ActivityLog::log(
                'Import Alokasi Petugas (Create)',
                'alokasi',
                "Berhasil mengimport alokasi {$kegiatan->nama_kegiatan} {$periode->bulan}/{$periode->tahun} ({$import->getSuccessCount()} petugas)",
                'success',
                [
                    'kegiatan_id' => $kegiatan->id,
                    'periode_id' => $periode->id,
                    'imported_count' => $import->getSuccessCount(),
                ]
            );

            DB::commit();

            return redirect('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$periode->tahun.'/'.$periode->bulan)
                ->with('success', "Berhasil import {$import->getSuccessCount()} data alokasi petugas");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('AlokasiPetugas import create gagal', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Gagal mengimport file: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Validate schedule dates for sensus activities within kegiatan execution period.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */
    private function validateDatesWithinKegiatanPeriod(Kegiatan $kegiatan, array $validated, string $tahapan): array
    {
        if (! $kegiatan->tanggal_mulai || ! $kegiatan->tanggal_selesai) {
            return [];
        }

        $periodStart = Carbon::parse($kegiatan->tanggal_mulai)->startOfDay();
        $periodEnd = Carbon::parse($kegiatan->tanggal_selesai)->endOfDay();
        $errors = [];

        $checkRange = static function (?string $value, string $label) use ($periodStart, $periodEnd, &$errors): void {
            if (! $value) {
                return;
            }

            $date = Carbon::parse($value);
            if ($date->lt($periodStart) || $date->gt($periodEnd)) {
                $errors[] = $label.' harus berada dalam rentang periode pelaksanaan kegiatan.';
            }
        };

        if ($tahapan !== 'listing_only') {
            $checkRange($validated['tanggal_mulai'] ?? null, 'Tanggal mulai');
            $checkRange($validated['tanggal_selesai'] ?? null, 'Tanggal selesai');
        }

        if ($tahapan === 'both' || $tahapan === 'listing_only') {
            $checkRange($validated['tanggal_mulai_listing'] ?? null, 'Tanggal mulai listing');
            $checkRange($validated['tanggal_selesai_listing'] ?? null, 'Tanggal selesai listing');
        }

        return array_values(array_unique($errors));
    }

    private function parseImportNik(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return sprintf('%.0f', $value);
        }

        $value = trim((string) $value);

        // Handle scientific notation strings like "1.373012410970002E+15"
        if ($value !== '' && is_numeric($value) && stripos($value, 'E') !== false) {
            $value = sprintf('%.0f', (float) $value);
        }

        if (preg_match_all('/\d{8,}/', $value, $matches) === 1) {
            return $matches[0][0];
        }

        if (preg_match_all('/\d{8,}/', $value, $matches) > 1) {
            usort($matches[0], static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            return $matches[0][0];
        }

        return $value;
    }

    private function extractImportNikCellValue(Collection|array $row): mixed
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['nik', 'nik_petugas', 'nama_nik', 'nama_nik_nip', 'nama_niknip'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return $rowArray[$key];
            }
        }

        foreach ($rowArray as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));

            if (str_contains($normalizedKey, 'nik') || str_contains($normalizedKey, 'nip')) {
                return $value;
            }
        }

        return '';
    }

    private function extractImportPeranCellValue(Collection|array $row): mixed
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['kode_penugasan', 'jenis_penugasan', 'jenis_penugasan_kode', 'peran'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return $rowArray[$key];
            }
        }

        foreach ($rowArray as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));

            if (str_contains($normalizedKey, 'penugasan') || $normalizedKey === 'peran') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array<int, array{code:string,label:string}>
     */
    private function extractFrameSampelMetadataColumns(Collection $frameRows): array
    {
        $preferredOrder = ['kdkec', 'kddes', 'kdsls', 'kdsubsls', 'idsegmen', 'kdsegmen'];
        $columns = [];

        foreach ($frameRows as $frameRow) {
            if (! $frameRow instanceof KegiatanFrameSampel) {
                continue;
            }

            $identitas = is_array($frameRow->identitas_tambahan)
                ? $frameRow->identitas_tambahan
                : [];

            foreach ($identitas as $key => $value) {
                if (! is_scalar($value) || Str::endsWith((string) $key, '_label')) {
                    continue;
                }

                $code = trim((string) $key);
                if ($code === '') {
                    continue;
                }

                $normalizedCode = Str::lower($code);
                if (collect($columns)->contains(fn (array $column): bool => Str::lower($column['code']) === $normalizedCode)) {
                    continue;
                }

                $columns[] = [
                    'code' => $code,
                    'label' => $this->formatMetadataLabel($code),
                ];
            }
        }

        usort($columns, static function (array $left, array $right) use ($preferredOrder): int {
            $leftIndex = array_search(Str::lower((string) $left['code']), $preferredOrder, true);
            $rightIndex = array_search(Str::lower((string) $right['code']), $preferredOrder, true);

            $leftOrder = $leftIndex === false ? 999 : $leftIndex;
            $rightOrder = $rightIndex === false ? 999 : $rightIndex;

            if ($leftOrder === $rightOrder) {
                return strcmp((string) $left['code'], (string) $right['code']);
            }

            return $leftOrder <=> $rightOrder;
        });

        return array_values($columns);
    }

    private function formatMetadataLabel(string $code): string
    {
        $normalizedCode = Str::lower(trim($code));

        return match ($normalizedCode) {
            'kdkec', 'kode_kecamatan' => 'Kecamatan',
            'kddes', 'kode_desa' => 'Desa/Kelurahan',
            'kdsls', 'kode_sls' => 'SLS',
            'kdsubsls', 'kode_sub_sls' => 'Sub SLS',
            'idsegmen', 'kdsegmen', 'kode_segmen' => 'ID Segmen',
            default => Str::title(str_replace('_', ' ', $code)),
        };
    }

    /**
     * @param  array<int, array{code:string,label:string}>  $metadataColumns
     * @return array<string, string>
     */
    private function extractImportFrameMetadataValues(Collection|array $row, array $metadataColumns): array
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;
        $result = [];

        foreach ($metadataColumns as $metadataColumn) {
            $code = trim((string) ($metadataColumn['code'] ?? ''));
            $label = trim((string) ($metadataColumn['label'] ?? ''));

            if ($code === '') {
                continue;
            }

            $candidates = array_filter([
                $code,
                Str::slug($code, '_'),
                Str::snake($code),
                $label,
                Str::slug($label, '_'),
                Str::snake($label),
            ], fn (string $value): bool => trim($value) !== '');

            $value = '';

            foreach ($rowArray as $key => $rawValue) {
                $normalizedKey = Str::lower(trim((string) $key));
                $isMatch = collect($candidates)->contains(
                    fn (string $candidate): bool => $normalizedKey === Str::lower(trim($candidate))
                );

                if (! $isMatch) {
                    continue;
                }

                $value = trim((string) $rawValue);
                break;
            }

            $result[$code] = $value;
        }

        return $result;
    }

    private function isReferencePetugasSheetRow(Collection|array $row): bool
    {
        $rowArray = $row instanceof Collection ? $row->all() : $row;

        foreach (['nip_nik', 'nama_petugas', 'pilihan_dropdown', 'kode_penugasan_dropdown'] as $key) {
            if (array_key_exists($key, $rowArray)) {
                return true;
            }
        }

        return false;
    }

    private function parseImportSatuan(mixed $value): float
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 0.0;
        }

        $normalized = str_replace(' ', '', $stringValue);
        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return max(0.0, (float) $normalized);
    }

    private function parseImportInteger(mixed $value): int
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 0;
        }

        $normalized = str_replace(['.', ','], '', $stringValue);

        return is_numeric($normalized) ? max(0, (int) $normalized) : 0;
    }

    /**
     * @return array<int, string>
     */
    private function sensusUnitSampleColumnKeys(Kegiatan $kegiatan): array
    {
        if ($kegiatan->jenis_kegiatan !== 'sensus') {
            return [];
        }

        $orderedNames = $this->orderedSensusUnitSampleNames($kegiatan);
        if (count($orderedNames) <= 1) {
            return [];
        }

        return array_map(
            static fn (string $name): string => Str::snake('jumlah '.$name),
            $orderedNames
        );
    }

    /**
     * @return array<int, string>
     */
    private function orderedSensusUnitSampleNames(Kegiatan $kegiatan): array
    {
        $items = $kegiatan->unitSampelPencacahanItems();

        if ($items->isEmpty()) {
            return [];
        }

        return $items
            ->sortBy(function ($item): array {
                $name = Str::lower((string) ($item->nama ?? ''));

                if (Str::contains($name, 'usaha')) {
                    return [0, $name];
                }

                if (Str::contains($name, 'keluarga')) {
                    return [1, $name];
                }

                return [2, $name];
            })
            ->map(fn ($item): string => trim((string) ($item->nama ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, KegiatanFrameSampel>  $frameRows
     * @param  array<string, string>  $metadataValues
     * @param  array<int, string>  $errors
     */
    private function resolveFrameSampelByMetadata(Collection $frameRows, array $metadataValues, int $rowNumber, array &$errors): ?KegiatanFrameSampel
    {
        $filledMetadata = collect($metadataValues)
            ->filter(fn (string $value): bool => trim($value) !== '');

        if ($filledMetadata->isEmpty()) {
            return null;
        }

        $candidates = $frameRows->filter(function (KegiatanFrameSampel $frameRow) use ($filledMetadata): bool {
            $identitas = is_array($frameRow->identitas_tambahan)
                ? $frameRow->identitas_tambahan
                : [];

            foreach ($filledMetadata as $code => $expectedValue) {
                $actualValue = '';

                foreach ($identitas as $identitasKey => $identitasValue) {
                    if (
                        Str::lower((string) $identitasKey) === Str::lower((string) $code) &&
                        is_scalar($identitasValue)
                    ) {
                        $actualValue = trim((string) $identitasValue);
                        break;
                    }
                }

                if (Str::lower($actualValue) !== Str::lower(trim((string) $expectedValue))) {
                    return false;
                }
            }

            return true;
        })->values();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->isEmpty()) {
            $summary = $filledMetadata
                ->map(fn (string $value, string $key): string => $key.'='.$value)
                ->implode(', ');
            $errors[] = "Baris {$rowNumber}: Frame sampel tidak ditemukan untuk metadata [{$summary}].";

            return null;
        }

        $errors[] = "Baris {$rowNumber}: Metadata frame sampel ambigu, cocok ke lebih dari satu frame. Lengkapi kolom metadata hingga unik.";

        return null;
    }

    private function parseImportBoolean(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'ya', 'yes', 'y'], true);
    }

    private function normalizeImportPeranCode(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'pcl_ppl' => 'pcl_ppl',
            'pcl/ppl' => 'pcl_ppl',
            'pml' => 'pml',
            'pengolahan' => 'pengolahan',
            'petugas pengolahan' => 'pengolahan',
            'pengawas_pengolahan', 'pengawasan_pengolahan' => 'pengawas_pengolahan',
            'pengawas pengolahan' => 'pengawas_pengolahan',
            'koseka' => 'koseka',
            default => null,
        };
    }

    private function mapPeranCodeToDisplayLabel(string $peranCode): string
    {
        return match ($peranCode) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            'koseka' => 'Koseka',
            default => 'PCL',
        };
    }
}
