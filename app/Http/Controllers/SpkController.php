<?php

namespace App\Http\Controllers;

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
use App\Http\Controllers\Concerns\SpkPublicPreviewSupport;
use App\Http\Controllers\Concerns\SpkAddendumSupport;
use App\Http\Controllers\Concerns\SpkPdfSupport;

class SpkController extends Controller
{
    use SpkPdfSupport;
    use SpkAddendumSupport;
    use SpkPublicPreviewSupport;
    public function __construct(private readonly SpkActionDecisionService $spkActionDecisionService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response|RedirectResponse
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();
        $canAccessSensusMode = $this->canAccessSensusMode($this->getRequestUser($request), $activeYear);

        // Mode halaman disimpan di session. URL tetap /spk sehingga refresh browser
        // tidak mengulang POST dan tidak mengekspos parameter mode.
        $requestedMode = (string) $request->session()->get('spk_index_mode', 'regular');
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';

        if ($mode !== $requestedMode) {
            $request->session()->put('spk_index_mode', $mode);
        }

        // Get periode alokasi yang sudah validated grouped by month
        $query = PeriodeAlokasi::query()
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tahun_anggaran',
                // Keep every column used by hasPositiveEffectiveHonor() and
                // SpkActionDecisionService::isMeaningfulAllocation().  When
                // these columns are omitted Eloquent exposes them as null,
                // making every allocation look like it has zero volume.  That
                // previously emptied kegiatan_list and returned a zero
                // non-organic officer count to Index.tsx.
                'alokasiPetugas:id,periode_alokasi_id,petugas_id,peran,jumlah_unit_sampel,jumlah_satuan,jumlah_satuan_listing,total_honor,total_honor_listing,is_partial_payment,estimasi_honor_partial,is_partial_payment_listing,estimasi_honor_partial_listing',
                'alokasiPetugas.petugas:id,nama,nik,jenis_petugas',
                'spk:spk.id,alokasi_petugas_id,addendum_number,regeneration_count,spk.created_at',
            ])
            ->select('periode_alokasi.*') // Select all columns from periode_alokasi
            ->whereHas('kegiatan', function ($q) use ($activeYear) {
                $q->where('tahun_anggaran', $activeYear);
            })
            // Apply sensus filter conditionally depending on requested mode.
            // - regular: exclude sensus kegiatan
            // - sensus-ekonomi: include only sensus kegiatan
            ->when($mode === 'regular', function ($q) {
                $q->whereHas('kegiatan', fn ($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));
            })
            ->when($mode === 'sensus-ekonomi', function ($q) {
                $q->whereHas('kegiatan', fn ($qq) => $qq->where('jenis_kegiatan', 'sensus'));
            })
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->where('tahun', $activeYear);

        $periodes = $query->latest()->get()
            ->filter(function (PeriodeAlokasi $periode) use ($mode) {
                $isPeriodBased = $this->usesPeriodBasedSpkFlow($periode);

                if ($mode === 'sensus-ekonomi') {
                    return $isPeriodBased;
                }

                return ! $isPeriodBased;
            })
            ->values();

        // Group by month for regular activities, but keep Sensus Ekonomi period-based.
        $groupedByMonth = $periodes->groupBy(function ($periode) {
            return $this->resolveSpkIndexGroupKey($periode);
        })->map(function ($monthPeriodes) {
            $primaryPeriode = $this->resolveSpkIndexPrimaryPeriode($monthPeriodes);
            $tahun = (int) $primaryPeriode->tahun;
            $bulan = (int) $primaryPeriode->bulan;
            $isPeriodBased = $this->usesPeriodBasedSpkFlow($primaryPeriode);

            // The backend is the single source of truth for action eligibility.
            // Period-based (Sensus) flows currently use their own workflow.
            $actionDecisions = $isPeriodBased
                ? collect()
                : $this->spkActionDecisionService->resolveForMonth($tahun, $bulan);
            $actionCounts = collect([
                'generate_pk' => 0,
                'regenerate_pk' => 0,
                'generate_addendum' => 0,
                'regenerate_addendum' => 0,
                'no_action' => 0,
            ])->merge($actionDecisions->countBy('final_action'));

            // Count unique non-organik petugas across all kegiatan in this month
            // Only count petugas with honor > 0
            // Use effective allocations (latest status per kegiatan per petugas)

            // Get all alokasi for this month and set periodeAlokasi relation to avoid N+1
            $allAlokasi = $monthPeriodes->flatMap(function ($periode) {
                return $periode->alokasiPetugas->each(function ($alokasi) use ($periode) {
                    $alokasi->setRelation('periodeAlokasi', $periode);
                });
            });

            // Group by petugas_id, then by kegiatan_id to get effective allocations
            $effectivePetugasIds = $allAlokasi
                ->filter(function ($alokasi) {
                    return $alokasi->petugas &&
                        $alokasi->petugas->jenis_petugas === 'non-organik';
                })
                ->groupBy('petugas_id')
                ->flatMap(function ($petugasAlokasi) {
                    // For each petugas, get effective allocation per kegiatan
                    $byKegiatan = $petugasAlokasi->groupBy(function ($alokasi) {
                        return $alokasi->periodeAlokasi->kegiatan_id;
                    });

                    $effectiveAlokasi = $byKegiatan->map(function ($kegiatanAlokasi) {
                        return $this->spkActionDecisionService->getEffectiveAlokasiByKegiatan($kegiatanAlokasi);
                    })->flatten(1)->filter();

                    // Only include petugas if they have positive effective honor (respects partial payment)
                    $hasPositiveHonor = $effectiveAlokasi->contains(
                        fn ($alokasi) => $this->hasPositiveEffectiveHonor($alokasi)
                    );

                    return $hasPositiveHonor ? [$effectiveAlokasi->first()->petugas_id] : [];
                })
                ->unique();

            $totalPetugasNonOrganik = $effectivePetugasIds->count();

            // Count total SPK created
            $totalSpk = $monthPeriodes->sum(function ($periode) {
                return $periode->spk->count();
            });

            // Get unique kegiatan in this month with petugas count based on effective allocations
            // Use the same $allAlokasi collection that already has periodeAlokasi relation set
            $kegiatanList = $allAlokasi
                ->filter(function ($alokasi) {
                    return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
                })
                ->groupBy('petugas_id')
                ->flatMap(function ($petugasAlokasi) {
                    // Get effective allocation per kegiatan for this petugas
                    $byKegiatan = $petugasAlokasi->groupBy(function ($alokasi) {
                        return $alokasi->periodeAlokasi->kegiatan_id;
                    });

                    return $byKegiatan->map(function ($kegiatanAlokasi) {
                        return $this->spkActionDecisionService->getEffectiveAlokasiByKegiatan($kegiatanAlokasi);
                    })->flatten(1);
                })
                ->filter(function ($alokasi) {
                    // Only include allocations with positive effective honor (respects partial payment)
                    return $this->hasPositiveEffectiveHonor($alokasi);
                })
                ->groupBy(function ($alokasi) {
                    return $alokasi->periodeAlokasi->kegiatan_id;
                })
                ->map(function ($kegiatanAlokasi, $kegiatanId) {
                    $firstAlokasi = $kegiatanAlokasi->first();
                    $periode = $firstAlokasi->periodeAlokasi;

                    // Count unique petugas for this kegiatan
                    $uniquePetugasCount = $kegiatanAlokasi->pluck('petugas_id')->unique()->count();

                    return [
                        'periode_id' => $periode->id,
                        'periode_hashed_id' => $periode->hashed_id,
                        'kegiatan_hashed_id' => $periode->kegiatan->hashed_id,
                        'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                        'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                        'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                        'jumlah_petugas_non_organik' => $uniquePetugasCount,
                    ];
                })->values();

            // SPK status for the month
            $spkStatus = $totalSpk > 0 ? 'Sudah Dibuat' : 'Belum Dibuat';
            $spkStatusType = $totalSpk > 0 ? 'created' : 'not_created';

            // Check if there are any revision/perubahan periods
            $hasRevision = $monthPeriodes->contains(function ($periode) {
                return in_array($periode->status, ['direvisi', 'perubahan']);
            });

            // Check if any SPK already has addendum
            $hasAddendum = $monthPeriodes->flatMap(function ($periode) {
                return $periode->spk;
            })->contains(function ($spk) {
                return $spk->addendum_number > 0;
            });

            // Check for new kegiatan/petugas after SPK was generated
            $hasNewKegiatanAfterSpk = $isPeriodBased
                ? false
                : $this->hasNewKegiatanAfterSpk($tahun, $bulan, $monthPeriodes);

            // Check for new revisions after addendum was generated
            $hasNewRevisionAfterAddendum = $isPeriodBased
                ? false
                : $this->hasNewRevisionAfterAddendum($tahun, $bulan, $monthPeriodes);

            // Check if SPK has been regenerated (regeneration_count > 0)
            $hasBeenRegenerated = $monthPeriodes->flatMap(function ($periode) {
                return $periode->spk;
            })->contains(function ($spk) {
                return ($spk->regeneration_count ?? 0) > 0;
            });

            // Check for incomplete addendum (some petugas with revision don't have addendum yet)
            $hasIncompleteAddendum = $isPeriodBased
                ? false
                : $this->hasIncompleteAddendum($tahun, $bulan, $monthPeriodes);

            // Check for addendum changes (petugas who already have addendum but have allocation changes)
            $hasAddendumChanges = $isPeriodBased
                ? false
                : $this->hasAddendumChanges($tahun, $bulan, $monthPeriodes);

            return [
                'entry_key' => $this->resolveSpkIndexGroupKey($primaryPeriode),
                'display_label' => $this->resolveSpkIndexDisplayLabel($primaryPeriode),
                'is_period_based' => $isPeriodBased,
                'primary_periode_hashed_id' => $primaryPeriode->hashed_id,
                'tahun' => (int) $tahun,
                'bulan' => (int) $bulan,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
                'total_petugas_non_organik' => $totalPetugasNonOrganik,
                'total_spk' => $totalSpk,
                'spk_status' => $spkStatus,
                'spk_status_type' => $spkStatusType,
                'has_revision' => $hasRevision,
                'has_addendum' => $hasAddendum,
                'has_new_kegiatan_after_spk' => $hasNewKegiatanAfterSpk,
                'has_new_revision_after_addendum' => $hasNewRevisionAfterAddendum,
                'has_been_regenerated' => $hasBeenRegenerated,
                'has_incomplete_addendum' => $hasIncompleteAddendum,
                'has_addendum_changes' => $hasAddendumChanges,
                'action_counts' => $actionCounts->all(),
                'can_generate_pk' => $isPeriodBased
                    ? $totalPetugasNonOrganik > $totalSpk
                    : ($actionCounts['generate_pk'] ?? 0) > 0,
                'can_regenerate_pk' => ! $isPeriodBased
                    && ($actionCounts['regenerate_pk'] ?? 0) > 0,
                'can_generate_addendum' => ! $isPeriodBased
                    && ($actionCounts['generate_addendum'] ?? 0) > 0,
                'can_regenerate_addendum' => ! $isPeriodBased
                    && ($actionCounts['regenerate_addendum'] ?? 0) > 0,
                'kegiatan_list' => $kegiatanList,
            ];
        })->sortByDesc(function ($item) {
            return $item['tahun'].str_pad($item['bulan'], 2, '0', STR_PAD_LEFT);
        })->values();

        // Paginate manually
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedPeriodeList = $groupedByMonth->slice($offset, $perPage)->values();

        $periodeListPaginator = new LengthAwarePaginator(
            $paginatedPeriodeList,
            $groupedByMonth->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Spk/Index', [
            'periodeList' => [
                'encrypted' => encryptData($paginatedPeriodeList->all()),
                'meta' => [
                    'current_page' => $periodeListPaginator->currentPage(),
                    'last_page' => $periodeListPaginator->lastPage(),
                    'per_page' => $periodeListPaginator->perPage(),
                    'total' => $periodeListPaginator->total(),
                    'from' => $periodeListPaginator->firstItem(),
                    'to' => $periodeListPaginator->lastItem(),
                ],
                'links' => $periodeListPaginator->linkCollection()->map(function ($link) {
                    return [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => $link['active'],
                    ];
                })->all(),
            ],
            'filters' => [
                'encrypted' => encryptFilters($validated),
                'decrypted' => $validated,
            ],
            'mode' => $mode,
            'can_access_sensus_mode' => $canAccessSensusMode,
        ]);
    }

    public function switchIndexMode(Request $request): RedirectResponse
    {
        $decrypted = decryptFilters((string) $request->input('encrypted_filters', ''));
        $mode = (string) ($decrypted['mode'] ?? 'regular');

        if (! in_array($mode, ['regular', 'sensus-ekonomi'], true)) {
            return back()->with('error', 'Mode Perjanjian Kerja tidak valid.');
        }

        $activeYear = ActiveYearService::get();
        if (
            $mode === 'sensus-ekonomi'
            && ! $this->canAccessSensusMode($this->getRequestUser($request), $activeYear)
        ) {
            return back()->with('error', 'Anda tidak memiliki akses ke mode Sensus Ekonomi.');
        }

        $request->session()->put('spk_index_mode', $mode);

        return redirect()->route('spk.index');
    }

    /**
     * Display list of SPKs for a specific month
     */
    public function listByMonth(Request $request): Response|RedirectResponse
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Get all SPKs created in this month
        $spkList = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan',
        ])
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get()
            ->map(function ($spk) use ($allPeriodeInMonth) {
                $petugas = $spk->alokasiPetugas->petugas;

                // Get all kegiatan for this petugas in this month
                $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
                    ->where('petugas_id', $petugas->id)
                    ->with([
                        'periodeAlokasi:id,kegiatan_id',
                        'periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan',
                    ])
                    ->get();

                $kegiatanList = $allAlokasi->map(function ($alokasi) {
                    return [
                        'kode_kegiatan' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                        'nama_kegiatan' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                        'peran' => $alokasi->peran,
                    ];
                })->values()->all();

                return [
                    'id' => $spk->id,
                    'hashed_id' => $spk->hashed_id,
                    'nomor_spk' => $spk->nomor_spk,
                    'tanggal_spk' => $spk->tanggal_spk,
                    'nilai_kontrak' => $spk->nilai_kontrak,
                    'status' => $spk->status,
                    'file_path' => $spk->file_path,
                    'petugas' => [
                        'id' => $petugas->id,
                        'hashed_id' => $petugas->hashed_id,
                        'nama' => $petugas->nama,
                        'nik' => $petugas->nik,
                    ],
                    'jumlah_kegiatan' => count($kegiatanList),
                    'kegiatan_list' => $kegiatanList,
                ];
            });

        return Inertia::render('Spk/List', [
            'spk_list' => $spkList,
            'bulan' => (int) $bulan,
            'tahun' => (int) $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
        ]);
    }

    /**
     * Show SPK for a specific month with petugas list (GET version)
     */
    public function showByMonthGet(Request $request): Response|RedirectResponse
    {
        // Canonical URL is intentionally clean: /spk/month.
        // The encrypted navigation payload is persisted in session so a hard
        // refresh does not lose the selected month/petugas/period.
        $context = (array) $request->session()->get('spk.month.context', []);

        // Backward compatibility for old bookmarked URLs containing ?state=...
        if ($request->filled('state')) {
            $legacyState = decryptFilters((string) $request->query('state'));
            if (! empty($legacyState)) {
                $context = array_merge($context, $legacyState);
                $request->session()->put('spk.month.context', $context);

                return redirect()->route('spk.show-by-month-get');
            }
        }

        $bulan = $context['bulan'] ?? null;
        $tahun = $context['tahun'] ?? null;
        $spkHashedId = $context['spk'] ?? null;
        $periodeHashedId = $context['periode_hashed_id'] ?? null;

        return $this->renderShowByMonth($request, $bulan, $tahun, $spkHashedId, $periodeHashedId);
    }

    /**
     * Show SPK for a specific month with petugas list (POST version)
     */
    public function showByMonth(Request $request): Response|RedirectResponse
    {
        $encryptedState = $request->input('state')
            ?? $request->input('encrypted_filters');

        $context = filled($encryptedState)
            ? decryptFilters((string) $encryptedState)
            : $request->only(['bulan', 'tahun', 'spk', 'periode_hashed_id']);

        if (empty($context['bulan']) || empty($context['tahun'])) {
            return redirect()->route('spk.index')
                ->with('error', 'Periode Perjanjian Kerja tidak ditemukan.');
        }

        $context = [
            'bulan' => (int) $context['bulan'],
            'tahun' => (int) $context['tahun'],
            'spk' => $context['spk'] ?? null,
            'periode_hashed_id' => $context['periode_hashed_id'] ?? null,
        ];

        $request->session()->put('spk.month.context', $context);

        return $this->renderShowByMonth(
            $request,
            (string) $context['bulan'],
            (string) $context['tahun'],
            $context['spk'],
            $context['periode_hashed_id'],
        );
    }

    /**
     * Internal method to render ShowByMonth view
     */
    private function renderShowByMonth(Request $request, ?string $bulan, ?string $tahun, ?string $spkHashedId, ?string $periodeHashedId = null): Response|RedirectResponse
    {

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index');
        }

        $canAccessSensusMode = $this->canAccessSensusMode($this->getRequestUser($request));

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $bulanNumeric = (string) ((int) $bulan);

        // For period-based flow (e.g. Sensus Ekonomi), lock detail to the selected periode.
        // For regular survei flow, keep month-wide scope so all petugas across kegiatan remain visible.
        if (filled($periodeHashedId)) {
            $periodeId = Hashids::decode((string) $periodeHashedId)[0] ?? null;

            if (! $periodeId) {
                return redirect()->route('spk.index')->with('error', 'Periode tidak ditemukan.');
            }

            $selectedPeriode = PeriodeAlokasi::query()
                ->with('kegiatan:id,jenis_kegiatan,nama_kegiatan')
                ->find($periodeId);

            if (! $selectedPeriode) {
                return redirect()->route('spk.index')->with('error', 'Periode tidak ditemukan.');
            }

            $shouldLockToSelectedPeriode = $this->usesPeriodBasedSpkFlow($selectedPeriode);

            if ($shouldLockToSelectedPeriode && ! $canAccessSensusMode) {
                return redirect()->route('spk.index', ['mode' => 'regular']);
            }

            if ($shouldLockToSelectedPeriode) {
                $allPeriodeInMonth = PeriodeAlokasi::query()
                    ->whereKey($periodeId)
                    ->where(function ($query) use ($bulanFormatted, $bulanNumeric) {
                        $query->where('bulan', $bulanFormatted)
                            ->orWhere('bulan', $bulanNumeric);
                    })
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                    ->pluck('id');
            } else {
                $allPeriodeInMonth = PeriodeAlokasi::where(function ($query) use ($bulanFormatted, $bulanNumeric) {
                    $query->where('bulan', $bulanFormatted)
                        ->orWhere('bulan', $bulanNumeric);
                })
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                    ->whereHas('kegiatan', function ($q) {
                        $q->where('jenis_kegiatan', 'survei'); // Default: survei activities
                    })
                    ->pluck('id');
            }
        } else {
            // Default month-detail flow keeps existing behavior for survei.
            $allPeriodeInMonth = PeriodeAlokasi::where(function ($query) use ($bulanFormatted, $bulanNumeric) {
                $query->where('bulan', $bulanFormatted)
                    ->orWhere('bulan', $bulanNumeric);
            })
                ->where('tahun', $tahun)
                ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                ->whereHas('kegiatan', function ($q) {
                    $q->where('jenis_kegiatan', 'survei'); // Default: survei activities
                })
                ->pluck('id');
        }

        $alokasiIdsInScope = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        // Get all SPKs in this month
        $allSpks = Spk::with(['petugas', 'alokasiPetugas.petugas'])
            ->where(function ($query) use ($alokasiIdsInScope) {
                $query->whereIn('alokasi_petugas_id', $alokasiIdsInScope->all());

                foreach ($alokasiIdsInScope as $alokasiId) {
                    $query->orWhereJsonContains('alokasi_petugas_ids', $alokasiId);
                }
            })
            ->orderBy('nomor_spk')
            ->get();

        if ($allSpks->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada SPK untuk periode ini');
        }

        // Determine which SPK to show
        $spk = null;
        if ($spkHashedId) {
            $spkId = Hashids::decode($spkHashedId)[0] ?? null;
            $spk = $allSpks->firstWhere('id', $spkId);
        }

        // If not found or not specified, use first SPK
        if (! $spk) {
            $spk = $allSpks->first();
        }

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;
        $bast = $spk->bast()->latest()->first();

        // Get all kegiatan for this petugas in this month
        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugas->id)
            ->with([
                'periodeAlokasi:id,kegiatan_id,status',
                'periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan',
            ])
            ->get();

        // Group by kegiatan only (not peran) - consolidate all peran under one row per kegiatan
        $grouped = $allAlokasi->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi->kegiatan->id;
        });

        $kegiatanList = $grouped->map(function ($alokasiGroup) {
            // Find the original (non-perubahan) and latest (perubahan if exists) for the entire kegiatan
            $original = $alokasiGroup->first(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'perubahan']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor for original periode (only from that specific periode)
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'perubahan']);
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            if ($originalTotalHonor === 0) {
                // Fallback: use the first allocation's honor
                $originalTotalHonor = ($original->total_honor ?? 0) + ($original->total_honor_listing ?? 0);
            }

            // Calculate total honor for latest periode only (sum all allocations from the latest periode for this kegiatan)
            $latestPeriodeId = $latest->periodeAlokasi->id;
            $latestTotalHonor = $alokasiGroup->filter(function ($a) use ($latestPeriodeId) {
                return $a->periodeAlokasi->id === $latestPeriodeId;
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            // Get all peran for this kegiatan
            $peranList = $alokasiGroup->pluck('peran')->unique()->implode(', ');

            $hasChange = $latestTotalHonor != $originalTotalHonor;

            return [
                'id' => $latest->periodeAlokasi->kegiatan->id,
                'hashed_id' => $latest->periodeAlokasi->kegiatan->hashed_id,
                'kode_kegiatan' => $latest->periodeAlokasi->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $latest->periodeAlokasi->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $latest->periodeAlokasi->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $latest->periodeAlokasi->kegiatan->tahun_anggaran,
                'peran' => $peranList,
                'total_honor' => $latestTotalHonor,
                'original' => [
                    'total_honor' => $originalTotalHonor,
                    'peran' => $peranList,
                ],
                'latest' => [
                    'total_honor' => $latestTotalHonor,
                    'peran' => $peranList,
                ],
                'has_change' => $hasChange,
            ];
        })->values()->all();

        // Build petugas list for sidebar without one SPK query per row.
        $rootSpkIds = $allSpks
            ->map(fn (Spk $item): int => (int) ($item->parent_spk_id ?: $item->id))
            ->unique()
            ->values();

        $latestDocumentsByRoot = Spk::query()
            ->where(function ($query) use ($rootSpkIds): void {
                $query->whereIn('id', $rootSpkIds->all())
                    ->orWhereIn('parent_spk_id', $rootSpkIds->all());
            })
            ->orderByDesc('addendum_number')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Spk $item): int => (int) ($item->parent_spk_id ?: $item->id))
            ->map(fn ($documents) => $documents->first());

        $petugasList = $allSpks->map(function ($s) use ($latestDocumentsByRoot) {
            $originalSpkId = (int) ($s->parent_spk_id ?: $s->id);
            $latestSpkDoc = $latestDocumentsByRoot->get($originalSpkId);

            return [
                'id' => $s->id,
                'hashed_id' => $s->hashed_id,
                'nomor_spk' => $s->nomor_spk,
                'petugas_nama' => $this->formatDisplayName($s->alokasiPetugas->petugas->nama),
                'petugas_nik' => $s->alokasiPetugas->petugas->nik,
                'status' => $s->status,
                'file_path' => $latestSpkDoc?->file_path,
                'signed_file_path' => $latestSpkDoc?->signed_file_path,
                'previous_file_path' => $latestSpkDoc?->previous_file_path,
            ];
        })->sortBy('petugas_nama')->values()->all();

        // Get all unique kegiatan and petugas mappings in two queries rather
        // than issuing another query for every kegiatan card.
        $allKegiatanIds = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->distinct()
            ->pluck('periode_alokasi_id');

        $petugasByKegiatan = DB::table('alokasi_petugas')
            ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
            ->whereIn('periode_alokasi.id', $allPeriodeInMonth)
            ->whereIn('periode_alokasi.status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->select('periode_alokasi.kegiatan_id', 'alokasi_petugas.petugas_id')
            ->distinct()
            ->get()
            ->groupBy('kegiatan_id')
            ->map(fn ($rows) => $rows->pluck('petugas_id')->map(fn ($id) => (int) $id));

        $uniqueKegiatanList = PeriodeAlokasi::whereIn('id', $allKegiatanIds)
            ->with('kegiatan')
            ->get()
            ->groupBy('kegiatan_id')
            ->map(function ($periodeGroup) use ($allSpks, $petugasByKegiatan) {
                $kegiatan = $periodeGroup->first()->kegiatan;
                $petugasIdsInKegiatan = $petugasByKegiatan->get($kegiatan->id, collect());

                // Get SPKs for these petugas in this month
                $spksForKegiatan = $allSpks->filter(function ($spk) use ($petugasIdsInKegiatan) {
                    return $petugasIdsInKegiatan->contains((int) $spk->petugas_id);
                })->unique('petugas_id');

                $spkCount = $spksForKegiatan->count();

                $hasDownloadableFile = $spksForKegiatan->contains(function ($spk) {
                    $fileToUse = $this->resolvePreferredSpkFilePathForZip($spk);

                    return ! empty($fileToUse) && file_exists(public_path($fileToUse));
                });

                return [
                    'id' => $kegiatan->id,
                    'hashed_id' => $kegiatan->hashed_id,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'jumlah_spk' => $spkCount,
                    'all_signed' => $hasDownloadableFile,
                ];
            })
            ->filter(function ($kegiatan) {
                return $kegiatan['jumlah_spk'] > 0 && $kegiatan['all_signed'];
            })
            ->values()
            ->sortBy('kode_kegiatan')
            ->values()
            ->all();

        // Get all SPK documents for this petugas (original + addendums)
        $originalSpk = $spk->parent_spk_id ? $spk->parentSpk : $spk;
        $allSpkDocuments = Spk::where(function ($q) use ($originalSpk) {
            $q->where('id', $originalSpk->id)
                ->orWhere('parent_spk_id', $originalSpk->id);
        })
            ->orderBy('addendum_number', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'hashed_id' => $s->hashed_id,
                    'nomor_spk' => $s->nomor_spk,
                    'tanggal_spk' => $s->tanggal_spk,
                    'addendum_number' => $s->addendum_number,
                    'file_path' => $s->file_path,
                    'signed_file_path' => $s->signed_file_path,
                    'previous_file_path' => $s->previous_file_path,
                    'status' => $s->status,
                    'created_by' => $s->createdBy->name ?? 'System',
                    'created_at' => $s->created_at->format('d M Y H:i'),
                    'updated_at' => $s->updated_at->format('d M Y H:i'),
                ];
            });

        $downloadContext = $this->usesPeriodBasedSpkFlow($periode)
            ? 'sensus-ekonomi'
            : 'regular';

        $downloadAllState = encryptFilters([
            'bulan' => (int) $bulan,
            'tahun' => (int) $tahun,
            'context' => $downloadContext,
            'periode_hashed_id' => $downloadContext === 'sensus-ekonomi'
                ? $periode->hashed_id
                : null,
        ]);

        $uniqueKegiatanList = collect($uniqueKegiatanList)
            ->map(function (array $kegiatan) use ($bulan, $tahun): array {
                $kegiatan['download_state'] = encryptFilters([
                    'bulan' => (int) $bulan,
                    'tahun' => (int) $tahun,
                    'kegiatan_hashed_id' => $kegiatan['hashed_id'],
                ]);

                return $kegiatan;
            })
            ->all();

        // Encrypt sensitive data
        $encryptedSpkDocuments = encryptData($allSpkDocuments);
        $encryptedKegiatanList = encryptData($kegiatanList);
        $encryptedPetugasList = encryptData($petugasList);
        $encryptedUniqueKegiatanList = encryptData($uniqueKegiatanList);

        // Prepare SPK data
        $spkData = [
            'id' => $spk->id,
            'hashed_id' => $spk->hashed_id,
            'nomor_spk' => $spk->nomor_spk,
            'tanggal_spk' => $spk->tanggal_spk,
            'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja,
            'tanggal_selesai_kerja' => $spk->tanggal_selesai_kerja,
            'nilai_kontrak' => $spk->nilai_kontrak,
            'nama_ppk' => $spk->nama_ppk,
            'nip_ppk' => $spk->nip_ppk,
            'status' => $spk->status,
            'file_path' => $spk->file_path,
            'signed_file_path' => $spk->signed_file_path,
            'previous_file_path' => $spk->previous_file_path,
            'addendum_number' => $spk->addendum_number,
            'parent_spk_id' => $spk->parent_spk_id,
            'created_by' => $spk->createdBy->name ?? 'System',
            'created_at' => $spk->created_at->format('d M Y H:i'),
            'updated_at' => $spk->updated_at->format('d M Y H:i'),
        ];

        // echo json_encode($spkData);exit();
        $encryptedSpk = encryptData($spkData);

        // Prepare Petugas data
        $petugasData = [
            'id' => $petugas->id,
            'hashed_id' => $petugas->hashed_id,
            'nama' => $this->formatDisplayName($petugas->nama),
            'nik' => $petugas->nik,
            'jenis_petugas' => $petugas->jenis_petugas,
            'alamat' => $petugas->alamat,
        ];
        $encryptedPetugas = encryptData($petugasData);

        return Inertia::render('Spk/ShowByMonth', [
            'spk' => [
                'encrypted' => $encryptedSpk,
            ],
            'spk_documents' => [
                'encrypted' => $encryptedSpkDocuments,
            ],
            'petugas' => [
                'encrypted' => $encryptedPetugas,
            ],
            'kegiatan_list' => [
                'encrypted' => $encryptedKegiatanList,
            ],
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => $periode->bulan,
                'tahun' => $periode->tahun,
            ],
            'bast' => $bast ? [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast,
                'file_path' => $bast->signed_file_path ?? $bast->file_path,
            ] : null,
            'petugas_list' => [
                'encrypted' => $encryptedPetugasList,
            ],
            'unique_kegiatan_list' => [
                'encrypted' => $encryptedUniqueKegiatanList,
            ],
            'bulan' => (int) $bulan,
            'tahun' => (int) $tahun,
            'bulan_label' => $this->getBulanLabel((int) $bulan),
            'download_all_state' => $downloadAllState,
        ]);
    }

    /**
     * Download all SPK files in a month as ZIP
     */
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

    /**
     * Download all SPK files for a specific kegiatan in a periode as ZIP
     */
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

    /**
     * Download all SPK files for a specific kegiatan in a specific month as ZIP
     * Used by ketua tim to download all SPK for their activity
     */
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

    /**
     * Upload signed SPK document
     */
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

    /**
     * Regenerate the same SPK document in place after a technical application error.
     * This updates only the current record and does not create a new database row.
     * If a signed PDF was already uploaded, the document must be re-scanned and re-uploaded.
     */
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
        $namaPetugas = preg_replace('/[\/\\:*?"<>|]/', '', $petugas->nama);
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

    /**
     * Get next nomor urut for SPK based on year
     */
    private function getNextNomorUrut(int $tahun): int
    {
        $usedNumbers = Spk::query()
            ->where('nomor_spk', 'like', "PPIS/13730/%/K/{$tahun}")
            ->whereNull('deleted_at')
            ->get()
            ->map(fn (Spk $spk): int => $this->extractNomorUrut((string) $spk->nomor_spk))
            ->filter(fn (int $nomor): bool => $nomor > 0)
            ->unique()
            ->sort()
            ->values();

        if ($usedNumbers->isEmpty()) {
            return 1;
        }

        $maxNomor = (int) $usedNumbers->last();

        for ($candidate = 1; $candidate <= $maxNomor + 1; $candidate++) {
            if (! $usedNumbers->contains($candidate)) {
                return $candidate;
            }
        }

        return $maxNomor + 1;
    }

    private function getNextNomorUrutForPeriode(PeriodeAlokasi $periode): int
    {
        if (! $this->usesPeriodBasedSpkFlow($periode)) {
            return $this->getNextNomorUrut((int) $periode->tahun);
        }

        $lastSpk = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', (int) $periode->tahun)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($periode) {
                $query->where('kegiatan_id', $periode->kegiatan_id);
            })
            ->orderByDesc('nomor_urut_base')
            ->first();

        if (! $lastSpk) {
            return 1;
        }

        $lastUrut = (int) ($lastSpk->nomor_urut_base ?? 0);
        if ($lastUrut <= 0) {
            $lastUrut = $this->extractNomorUrut((string) $lastSpk->nomor_spk);
        }

        return $lastUrut + 1;
    }

    private function formatNomorSpkForPeriode(PeriodeAlokasi $periode, int $nomorUrut): string
    {
        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return sprintf('B-%03d/SPK-SE2026/1373/PL.200/%d', $nomorUrut, (int) $periode->tahun);
        }

        return 'PPIS/13730/'.$nomorUrut.'/K/'.$periode->tahun;
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

    /**
     * Extract nomor urut from SPK number.
     */
    private function extractNomorUrut(string $nomorSpk): int
    {
        if (preg_match('/^B-(\d+)/i', $nomorSpk, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        $parts = explode('/', $nomorSpk);
        if (! isset($parts[2])) {
            return 0;
        }

        // Remove suffix letters (e.g., "4A" -> "4")
        $nomorWithSuffix = $parts[2];

        return (int) preg_replace('/[^0-9]/', '', $nomorWithSuffix);
    }

    private function resolveDisplayNomorUrutSegment(string $nomorSpk, int $nomorUrutBase, ?string $fallbackSuffix = null): string
    {
        $nomorSpk = trim($nomorSpk);
        if ($nomorSpk !== '') {
            if (preg_match('/^B-(\d+)([A-Z])?(?:\/|$)/i', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }

            if (preg_match('/^PPIS\/13730\/(\d+)([A-Z])?(?:\/|$)/i', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }

            if (preg_match('/\/(\d+)([A-Z])?(?:\/|$)/', $nomorSpk, $matches) === 1) {
                $base = (string) ($matches[1] ?? $nomorUrutBase);
                $suffix = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : '';

                return $base.$suffix;
            }
        }

        $suffix = trim((string) ($fallbackSuffix ?? ''));

        return (string) $nomorUrutBase.($suffix !== '' ? strtoupper($suffix) : '');
    }

    /**
     * Public page for petugas to find and preview/download SPK draft document.
     */


    /**
     * Public preview/download action for SPK.
     */


    /**
     * Serve a manually uploaded replacement document using the same preview/download
     * contract as the regular /mitra documents.
     *
     * @param array<string,mixed> $validated
     */


    /**
     * @return array{filename:string,content?:string,cache_key:string,is_protected:bool,protected_path?:string}|null
     */


    /**
     * @param  array<int,string>  $absolutePdfPaths
     */


    /**
     * @return array{survei_periods:array<int,array{value:string,label:string}>,sensus_kegiatans:array<int,array{value:string,label:string}>}
     */


    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiCollection
     * @return array<string,string>
     */


    /**
     * @param  array<string,mixed>  $validated
     */


    /**
     * @param  array<string,mixed>  $validated
     */


    /**
     * Common PDF protection and serving logic for public preview.
     *
     * @param  array<string,mixed>  $validated
     */


    /**
     * Show the form to generate SPKs for a periode
     */
    public function createContext(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $encryptedState = $request->input('state')
                ?? $request->input('encrypted_filters');

            $context = filled($encryptedState)
                ? decryptFilters((string) $encryptedState)
                : $request->only(['periode', 'action']);

            if (empty($context['periode'])) {
                return redirect()->route('spk.index')
                    ->with('error', 'Periode Perjanjian Kerja tidak ditemukan.');
            }

            $context = [
                'periode' => (string) $context['periode'],
                'action' => in_array(($context['action'] ?? null), ['generate_pk', 'regenerate_pk'], true)
                    ? (string) $context['action']
                    : null,
            ];

            $request->session()->put('spk.generate.context', $context);
        }

        $context = (array) $request->session()->get('spk.generate.context', []);
        if (empty($context['periode'])) {
            return redirect()->route('spk.index')
                ->with('error', 'Periode Perjanjian Kerja tidak ditemukan.');
        }

        if (! empty($context['action'])) {
            $request->query->set('action', (string) $context['action']);
        } else {
            $request->query->remove('action');
        }

        return $this->create($request, (string) $context['periode']);
    }

    public function create(Request $request, string $periodeHashedId): Response|RedirectResponse
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $periode = PeriodeAlokasi::with([
            'kegiatan',
        ])->findOrFail($periodeId);

        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
        $hasDraftPeriode = $this->hasDraftPeriodeInSpkScope($periode);

        // Get all unique non-organik petugas from the SPK scope.
        // Only include alokasi with effective honor > 0 (respects partial payment)
        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0)
                    ->orWhere('estimasi_honor_partial', '>', 0)
                    ->orWhere('estimasi_honor_partial_listing', '>', 0);
            })
            ->with([
                'petugas:id,nama,nik,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,jenis_kegiatan,status',
                'periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan',
            ])
            ->get()
            ->filter(fn (AlokasiPetugas $alokasi) => $this->hasPositiveEffectiveHonor($alokasi));

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')
            ->map(function (Collection $alokasiGroup): ?array {
                $effectiveAlokasi = $this->spkActionDecisionService
                    ->getEffectiveAlokasiByKegiatan($alokasiGroup)
                    ->values();

                if ($effectiveAlokasi->isEmpty()) {
                    return null;
                }

                return $this->buildGeneratePetugasListItem($effectiveAlokasi);
            })
            ->filter()
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        $isPeriodBased = $this->usesPeriodBasedSpkFlow($periode);
        $requestedAction = (string) $request->query('action', '');
        if (! $isPeriodBased) {
            $allowedAction = in_array($requestedAction, ['generate_pk', 'regenerate_pk'], true)
                ? $requestedAction
                : null;
            $decisionsByPetugas = $this->spkActionDecisionService
                ->resolveForMonth((int) $periode->tahun, (int) $periode->bulan)
                ->keyBy('petugas_id');

            $petugasList = $petugasList->filter(function (array $item) use ($decisionsByPetugas, $allowedAction): bool {
                $decision = $decisionsByPetugas->get((int) ($item['petugas']['id'] ?? 0));
                $finalAction = $decision['final_action'] ?? 'no_action';

                return $allowedAction
                    ? $finalAction === $allowedAction
                    : in_array($finalAction, ['generate_pk', 'regenerate_pk'], true);
            })->values();
        }

        // Get next nomor urut for this year
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);

        // Check if there are existing SPKs in this month (for regenerate mode)
        $existingSpkQuery = $this->baseSpkScopeQuery($periode);
        $existingSpk = (clone $existingSpkQuery)->first();

        // If existing SPK found, use its dates and set readonly mode
        $isRegenerate = $requestedAction === 'regenerate_pk'
            || ($requestedAction === '' && $existingSpk !== null);
        $defaultTanggalSpk = $isRegenerate && $existingSpk && $existingSpk->tanggal_spk
            ? Carbon::parse($existingSpk->tanggal_spk)->format('Y-m-d')
            : null;

        // Get all existing SPKs for petugas in this month (map petugas_id => nomor_spk)
        $existingSpkMap = [];
        $lastNomorUrutInMonth = 0;
        $usesSuffixForNewPetugas = false;
        $existingSpks = collect();

        if ($isRegenerate) {
            // Get ALL existing SPKs in this month first (not limited to current petugasList)
            // This ensures we capture all petugas who already have SPK, even if they're not in current list
            $existingSpks = (clone $existingSpkQuery)
                ->with(['alokasiPetugas.periodeAlokasi.kegiatan'])
                ->get();

            foreach ($existingSpks as $spk) {
                // Baseline kegiatan harus diambil dari snapshot dokumen SPK awal,
                // bukan dari alokasi terbaru bulan berjalan.
                $baselineAlokasiIds = $spk->alokasi_petugas_ids ?? [];
                if (empty($baselineAlokasiIds)) {
                    $baselineAlokasiIds = [$spk->alokasi_petugas_id];
                }

                $baselineAlokasi = AlokasiPetugas::whereIn('id', $baselineAlokasiIds)
                    ->where('petugas_id', $spk->petugas_id)
                    ->whereIn('periode_alokasi_id', $scopePeriodeIds)
                    ->where(function ($query) {
                        $query->where('total_honor', '>', 0)
                            ->orWhere('total_honor_listing', '>', 0)
                            ->orWhere('estimasi_honor_partial', '>', 0)
                            ->orWhere('estimasi_honor_partial_listing', '>', 0);
                    })
                    ->with('periodeAlokasi.kegiatan')
                    ->get();

                $baselineKegiatanList = $baselineAlokasi
                    ->map(function (AlokasiPetugas $alokasi): array {
                        return [
                            'kegiatan_id' => (int) $alokasi->periodeAlokasi->kegiatan->id,
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

                $kegiatanIds = collect($baselineKegiatanList)
                    ->pluck('kegiatan_id')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                $baselineTotalHonor = $baselineAlokasi->sum(function (AlokasiPetugas $alokasi): float {
                    return $alokasi->getEffectiveCombinedHonor();
                });

                $jenisKegiatan = 'reguler';
                $relatedKegiatan = $spk->alokasiPetugas?->periodeAlokasi?->kegiatan;
                if ($relatedKegiatan && mb_strtolower((string) $relatedKegiatan->jenis_kegiatan) === 'sensus') {
                    $jenisKegiatan = 'sensus';
                }

                $existingSpkMap[$spk->petugas_id][] = [
                    'spk_id' => $spk->id,
                    'nomor_spk' => $spk->nomor_spk,
                    'nomor_urut' => $spk->nomor_urut_base,
                    'alokasi_ids' => collect($baselineAlokasiIds)
                        ->map(static fn ($alokasiId) => (int) $alokasiId)
                        ->unique()
                        ->values()
                        ->all(),
                    'kegiatan_ids' => $kegiatanIds,
                    'kegiatan_list' => $baselineKegiatanList,
                    'total_honor' => $baselineTotalHonor,
                    'jenis_kegiatan' => $jenisKegiatan,
                    'is_se2026' => str_contains($spk->nomor_spk, 'SPK-SE2026'),
                ];

                // Track the last nomor urut in this month
                if ($spk->nomor_urut_base > $lastNomorUrutInMonth) {
                    $lastNomorUrutInMonth = $spk->nomor_urut_base;
                }
            }

            // Check if next sequential number is already used in OTHER months
            $nextSequentialNumber = $lastNomorUrutInMonth + 1;
            $nextNumberUsedElsewhere = Spk::where('nomor_urut_base', $nextSequentialNumber)
                ->where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->where(function ($q) use ($periode) {
                    $q->whereMonth('tanggal_spk', '!=', $periode->bulan);
                })
                ->exists();

            // If next number is used elsewhere, use suffix mode (3A, 3B, etc)
            $usesSuffixForNewPetugas = $nextNumberUsedElsewhere;
        }

        if ($isRegenerate) {
            // Eligibility has already been decided by SpkActionDecisionService.
            // Do not apply a second ID-diff filter here: it can disagree with
            // the Index decision and produce an empty Generate page.
            $petugasList = $petugasList->map(function (array $item) use ($existingSpkMap) {
                $petugasId = (int) ($item['petugas']['id'] ?? 0);
                $item['perubahan'] = $this->buildGenerateChangeSummaries(
                    $item,
                    $existingSpkMap[$petugasId] ?? [],
                );

                return $item;
            })->values();
        }

        // Redirect if petugasList is empty
        if ($petugasList->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada petugas yang dapat dibuatkan SPK untuk periode ini');
        }

        return Inertia::render('Spk/Generate', [
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'tahun' => $periode->tahun,
                'bulan' => $periode->bulan,
                'bulan_label' => $this->getBulanLabel($periode->bulan),
                'kegiatan' => [
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'jenis_kegiatan' => $periode->kegiatan->jenis_kegiatan,
                    'tahun_anggaran' => $periode->kegiatan->tahun_anggaran,
                ],
            ],
            'petugas_list' => $petugasList,
            'has_draft_periode' => $hasDraftPeriode,
            'next_nomor_urut' => $nextNomorUrut,
            'is_regenerate' => $isRegenerate,
            'default_tanggal_spk' => $defaultTanggalSpk,
            'existing_spk_map' => $existingSpkMap,
            'last_nomor_urut_in_month' => $lastNomorUrutInMonth,
            'uses_suffix_for_new_petugas' => $usesSuffixForNewPetugas,
        ]);
    }

    /**
     * Show the form to generate Addendum SPKs for a periode with allocation changes.
     */


    /**
     * Determine whether two allocation snapshots differ on any kegiatan that exists in both snapshots.
     *
     * New kegiatan are intentionally ignored here so they can be handled by regenerate logic.
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $referenceSnapshot
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $currentSnapshot
     */


    /**
     * Preview addendum SPK PDF
     */


    /**
     * Generate and save addendum SPK
     */


    /**
     * Display the specified SPK
     */
    public function show(string $spkHashedId): Response
    {
        $spkId = Hashids::decode($spkHashedId)[0] ?? null;
        if (! $spkId) {
            abort(404);
        }

        $spk = Spk::with([
            'alokasiPetugas.petugas',
            'alokasiPetugas.periodeAlokasi.kegiatan',
            'bast' => function ($q) {
                $q->latest();
            },
            'createdBy',
            'addendums.alokasiPetugas.periodeAlokasi.kegiatan',
            'addendums.createdBy',
        ])->findOrFail($spkId);

        $periode = $spk->alokasiPetugas->periodeAlokasi;
        $petugas = $spk->alokasiPetugas->petugas;
        $bast = $spk->bast->first();

        // Get all addendums (ordered)
        $addendums = $spk->addendums->sortBy('addendum_number')->values()->map(function ($addendum) {
            return [
                'id' => $addendum->id,
                'hashed_id' => $addendum->hashed_id,
                'nomor_spk' => $addendum->nomor_spk,
                'tanggal_spk' => $addendum->tanggal_spk,
                'tanggal_mulai_kerja' => $addendum->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $addendum->tanggal_selesai_kerja,
                'nilai_kontrak' => $addendum->nilai_kontrak,
                'status' => $addendum->status,
                'file_path' => $addendum->file_path,
                'addendum_number' => $addendum->addendum_number,
                'created_by' => $addendum->createdBy->name ?? 'System',
                'created_at' => $addendum->created_at->format('d M Y H:i'),
            ];
        });

        // Get all alokasi for this petugas in the same month (all kegiatan, all statuses)
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugas->id)
            ->with([
                'periodeAlokasi:id,kegiatan_id,jenis_kegiatan,status',
                'periodeAlokasi.kegiatan:id,nama_kegiatan,kode_kegiatan',
            ])
            ->get();

        // Group by kegiatan only (not peran) - consolidate all peran under one row per kegiatan
        $grouped = $allAlokasi->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi->kegiatan->id;
        });

        $mergedKegiatanList = $grouped->map(function ($alokasiGroup) {
            // Find the original (non-perubahan) and latest (perubahan if exists) for the entire kegiatan
            $original = $alokasiGroup->first(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'perubahan']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor across all peran for this kegiatan
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'perubahan']);
            })->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            if ($originalTotalHonor === 0) {
                $originalTotalHonor = $alokasiGroup->sum(function ($a) {
                    return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
                });
            }

            $latestTotalHonor = $alokasiGroup->sum(function ($a) {
                return ($a->total_honor ?? 0) + ($a->total_honor_listing ?? 0);
            });

            // Get all peran for this kegiatan
            $peranList = $alokasiGroup->pluck('peran')->unique()->implode(', ');

            $hasChange = $latestTotalHonor != $originalTotalHonor;

            return [
                'id' => $latest->periodeAlokasi->kegiatan->id,
                'hashed_id' => $latest->periodeAlokasi->kegiatan->hashed_id,
                'kode_kegiatan' => $latest->periodeAlokasi->kegiatan->kode_kegiatan,
                'nama_kegiatan' => $latest->periodeAlokasi->kegiatan->nama_kegiatan,
                'jenis_kegiatan' => $latest->periodeAlokasi->kegiatan->jenis_kegiatan,
                'tahun_anggaran' => $latest->periodeAlokasi->kegiatan->tahun_anggaran,
                'peran' => $peranList,
                'total_honor' => $latestTotalHonor,
                'original' => [
                    'total_honor' => $originalTotalHonor,
                    'peran' => $peranList,
                ],
                'latest' => [
                    'total_honor' => $latestTotalHonor,
                    'peran' => $peranList,
                ],
                'has_change' => $hasChange,
            ];
        })->values()->all();

        // Get unique kegiatan list for download buttons - from all periodes in the same month/year
        $allPeriodeInMonthIds = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('kegiatan_id')
            ->unique()
            ->values()
            ->all();

        // Get all SPKs in this month/year to count per kegiatan
        $allSpksInMonth = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($periode) {
            $q->where('bulan', $periode->bulan)
                ->where('tahun', $periode->tahun);
        })->with('alokasiPetugas.periodeAlokasi.kegiatan')->get();

        $uniqueKegiatanList = Kegiatan::whereIn('id', $allPeriodeInMonthIds)
            ->select('id', 'kode_kegiatan', 'nama_kegiatan')
            ->get()
            ->map(function ($kegiatan) use ($allSpksInMonth) {
                // Get SPKs for this kegiatan
                $spksForKegiatan = $allSpksInMonth->filter(function ($spk) use ($kegiatan) {
                    return $spk->alokasiPetugas->periodeAlokasi->kegiatan_id === $kegiatan->id;
                });

                $spkCount = $spksForKegiatan->count();

                // Group by petugas
                $petugasGroups = $spksForKegiatan->groupBy('petugas_id');
                $allPetugasSigned = $petugasGroups->every(function ($spkGroup) {
                    // Main PK (addendum_number = 0) must be signed
                    $main = $spkGroup->first(function ($spk) {
                        return $spk->addendum_number == 0;
                    });
                    if (! $main || empty($main->signed_file_path)) {
                        return false;
                    }
                    // All addendums (addendum_number > 0) must be signed if exist
                    $addendums = $spkGroup->filter(function ($spk) {
                        return $spk->addendum_number > 0;
                    });
                    foreach ($addendums as $add) {
                        if (empty($add->signed_file_path)) {
                            return false;
                        }
                    }

                    return true;
                });

                return [
                    'id' => $kegiatan->id,
                    'hashed_id' => $kegiatan->hashed_id, // This is an appended attribute from HasHashedRouteKey trait
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'jumlah_spk' => $spkCount,
                    'all_signed' => $allPetugasSigned,
                ];
            })
            ->filter(function ($kegiatan) {
                // Only show kegiatan where jumlah_spk > 0 AND all PK/addendum for all petugas are signed
                return $kegiatan['jumlah_spk'] > 0 && $kegiatan['all_signed'];
            })
            ->values()
            ->all();

        return Inertia::render('Spk/Show', [
            'spk' => [
                'id' => $spk->id,
                'hashed_id' => $spk->hashed_id,
                'nomor_spk' => $spk->nomor_spk,
                'tanggal_spk' => $spk->tanggal_spk,
                'tanggal_mulai_kerja' => $spk->tanggal_mulai_kerja,
                'tanggal_selesai_kerja' => $spk->tanggal_selesai_kerja,
                'nilai_kontrak' => $spk->nilai_kontrak,
                'nama_ppk' => $spk->nama_ppk,
                'nip_ppk' => $spk->nip_ppk,
                'status' => $spk->status,
                'file_path' => $spk->file_path,
                'signed_file_path' => $spk->signed_file_path,
                'previous_file_path' => $spk->previous_file_path,
                'created_by' => $spk->createdBy->name ?? 'System',
                'created_at' => $spk->created_at->format('d M Y H:i'),
            ],
            'petugas' => [
                'id' => $petugas->id,
                'hashed_id' => $petugas->hashed_id,
                'nama' => $petugas->nama,
                'nik' => $petugas->nik,
                'jenis_petugas' => $petugas->jenis_petugas,
                'alamat' => $petugas->alamat,
            ],
            'kegiatan_list' => $mergedKegiatanList,
            'unique_kegiatan_list' => $uniqueKegiatanList ?: [],
            'addendums' => $addendums,
            'periode' => [
                'id' => $periode->id,
                'hashed_id' => $periode->hashed_id,
                'bulan' => $periode->bulan,
                'tahun' => $periode->tahun,
            ],
            'bast' => $bast ? [
                'id' => $bast->id,
                'hashed_id' => $bast->hashed_id,
                'nomor_bast' => $bast->nomor_bast,
                'tanggal_bast' => $bast->tanggal_bast,
                'file_path' => $bast->file_path,
            ] : null,
        ]);
    }

    /**
     * Download all SPK previews in a periode as ZIP without persisting generated documents.
     */
    public function previewAllSpk(Request $request, string $periodeHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
            'preview_items_json' => ['required', 'string'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        if ($this->hasDraftPeriodeInSpkScope($periode)) {
            return response()->json([
                'message' => 'Masih terdapat periode draft. Preview semua belum dapat diunduh.',
            ], 422);
        }

        $decodedPreviewItems = json_decode((string) $validated['preview_items_json'], true);

        if (! is_array($decodedPreviewItems)) {
            return response()->json([
                'message' => 'Format daftar preview tidak valid.',
            ], 422);
        }

        $previewItems = collect($decodedPreviewItems)
            ->filter(fn ($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
            ->unique('petugas_hashed_id')
            ->values();

        if ($previewItems->isEmpty()) {
            return response()->json([
                'message' => 'Daftar petugas preview tidak valid.',
            ], 422);
        }

        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return response()->json(['message' => 'Folder sementara tidak tersedia. Silakan coba lagi.'], 500);
        }

        $zipFileName = 'Preview_SPK_'.$this->getBulanLabel((int) $periode->bulan).'_'.$periode->tahun.'.zip';
        $zipPath = $tempPath.'/preview_spk_'.$periode->id.'_'.time().'_'.uniqid().'.zip';

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'message' => 'Gagal membuat arsip ZIP preview.',
            ], 500);
        }

        $usedFileNames = [];
        $filesAdded = 0;

        foreach ($previewItems as $item) {
            $petugasId = Hashids::decode($item['petugas_hashed_id'])[0] ?? null;

            if (! $petugasId) {
                continue;
            }

            $pdfPreview = $this->buildMergedSpkPreviewBinary(
                $periode,
                (int) $petugasId,
                (string) $item['nomor_spk'],
                (string) $validated['tanggal_spk'],
            );

            if ($pdfPreview === null) {
                continue;
            }

            $archiveFilename = $pdfPreview['filename'];
            $suffixCounter = 2;
            while (isset($usedFileNames[$archiveFilename])) {
                $archiveFilename = preg_replace('/\.pdf$/i', '', $pdfPreview['filename']).'_'.($suffixCounter++).'.pdf';
            }

            $usedFileNames[$archiveFilename] = true;
            $zip->addFromString($archiveFilename, $pdfPreview['content']);
            $filesAdded++;
        }

        $zip->close();

        if ($filesAdded === 0 || ! file_exists($zipPath)) {
            @unlink($zipPath);

            return response()->json([
                'message' => 'Tidak ada preview SPK yang dapat dibuat untuk petugas terpilih.',
            ], 422);
        }

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Merge main SPK PDFs for selected petugas into a single PDF, sorted alphabetically.
     */
    public function printSelectedMain(Request $request, string $periodeHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
            'preview_items_json' => ['required', 'string'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        $previewItems = $this->decodeAndSortPreviewItems((string) $validated['preview_items_json']);

        if ($previewItems->isEmpty()) {
            return response()->json(['message' => 'Daftar petugas tidak valid.'], 422);
        }

        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return response()->json([
                'message' => 'Folder sementara tidak tersedia. Silakan coba lagi.',
            ], 500);
        }

        $individualPaths = [];
        $timestamp = time().'_'.uniqid();

        foreach ($previewItems as $index => $item) {
            $petugasId = Hashids::decode($item['petugas_hashed_id'])[0] ?? null;

            if (! $petugasId) {
                continue;
            }

            $pdfBinary = $this->buildSpkMainPdfBinary(
                $periode,
                (int) $petugasId,
                (string) $item['nomor_spk'],
                (string) $validated['tanggal_spk'],
            );

            if ($pdfBinary === null) {
                continue;
            }

            $path = $tempPath.'/print_main_'.$timestamp.'_'.$index.'.pdf';

            if (@file_put_contents($path, $pdfBinary) === false) {
                continue;
            }

            $individualPaths[] = $path;
        }

        if (empty($individualPaths)) {
            return response()->json(['message' => 'Tidak ada PDF yang dapat dibuat.'], 422);
        }

        $mergedPath = $tempPath.'/print_main_merged_'.$timestamp.'.pdf';
        $filename = 'Print_PK_Main_'.$periode->bulan.'_'.$periode->tahun.'.pdf';

        $merged = PdfMergerService::mergePdfFiles($individualPaths, $mergedPath, $filename);

        foreach ($individualPaths as $path) {
            @unlink($path);
        }

        if (! $merged || ! file_exists($mergedPath)) {
            return response()->json(['message' => 'Gagal menggabungkan PDF.'], 500);
        }

        if (($validated['response_mode'] ?? 'binary') === 'url') {
            $previewUrl = $this->buildPublicPreviewSignedFileUrl($mergedPath, $filename, 'inline');

            if ($previewUrl === null) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        return response()->file($mergedPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Merge lampiran PDFs for selected petugas into a single PDF, sorted alphabetically.
     */
    public function printSelectedLampiran(Request $request, string $periodeHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;

        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
            'preview_items_json' => ['required', 'string'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        $previewItems = $this->decodeAndSortPreviewItems((string) $validated['preview_items_json']);

        if ($previewItems->isEmpty()) {
            return response()->json(['message' => 'Daftar petugas tidak valid.'], 422);
        }

        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return response()->json(['message' => 'Folder sementara tidak tersedia. Silakan coba lagi.'], 500);
        }

        $individualPaths = [];
        $timestamp = time().'_'.uniqid();

        foreach ($previewItems as $index => $item) {
            $petugasId = Hashids::decode($item['petugas_hashed_id'])[0] ?? null;

            if (! $petugasId) {
                continue;
            }

            $pdfBinary = $this->buildSpkLampiranPdfBinary(
                $periode,
                (int) $petugasId,
                (string) $item['nomor_spk'],
                (string) $validated['tanggal_spk'],
            );

            if ($pdfBinary === null) {
                continue;
            }

            $path = $tempPath.'/print_lampiran_'.$timestamp.'_'.$index.'.pdf';

            if (@file_put_contents($path, $pdfBinary) === false) {
                continue;
            }

            $individualPaths[] = $path;
        }

        if (empty($individualPaths)) {
            return response()->json(['message' => 'Tidak ada PDF yang dapat dibuat.'], 422);
        }

        $mergedPath = $tempPath.'/print_lampiran_merged_'.$timestamp.'.pdf';
        $filename = 'Print_Lampiran_'.$periode->bulan.'_'.$periode->tahun.'.pdf';

        $merged = PdfMergerService::mergePdfFiles($individualPaths, $mergedPath, $filename);

        foreach ($individualPaths as $path) {
            @unlink($path);
        }

        if (! $merged || ! file_exists($mergedPath)) {
            return response()->json(['message' => 'Gagal menggabungkan PDF.'], 500);
        }

        if (($validated['response_mode'] ?? 'binary') === 'url') {
            $previewUrl = $this->buildPublicPreviewSignedFileUrl($mergedPath, $filename, 'inline');

            if ($previewUrl === null) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        return response()->file($mergedPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Decode and sort preview items from JSON by petugas_nama, falling back to nomor_spk.
     *
     * @return Collection<int, array{petugas_hashed_id:string,nomor_spk:string,petugas_nama?:string}>
     */


    /**
     * Build SPK main (Pasal-based) PDF binary for a single petugas.
     */


    /**
     * Build SPK lampiran PDF binary for a single petugas.
     */


    /**
     * Preview SPK for a petugas in a periode
     */
    public function previewSpk(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);
        $periodeBulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds->all())
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        // Auto-calculate sampai_tanggal from this petugas' activity end dates
        $latestEndDate = null;
        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

            // For pengolahan roles, use processing schedules; otherwise use regular schedules
            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        // Fallback to end of month if no specific dates found
        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $validated['sampai_tanggal'] = $calculatedSampaiTanggal;

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds->all())
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = []; // Store per-kegiatan data including COA

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));

            // Store kegiatan data with its COA
            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];
        }

        // Get first kegiatan's COA as fallback for main document
        $bebanAnggaran = $allAlokasi->isNotEmpty() ? $this->getBebanAnggaran($allAlokasi->first()->periodeAlokasi->kegiatan) : '';

        // Sanitize filename untuk menghindari masalah karakter khusus
        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_'.$sanitizedName.'.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi, // Pass all alokasi for lampiran
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData, // Pass kegiatan data with COA
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => Carbon::parse($validated['sampai_tanggal']),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'pdfTitle' => $filename,
            'workType' => $this->detectWorkType($allAlokasi),
        ];
        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        // Generate 2 separate PDFs and merge them (SPK Main + Lampiran only)
        $pdfMain = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');

        // Set PDF title metadata untuk main
        $pdfMain->getDomPDF()->set_option('pdfTitle', $filename);

        $mainOutput = $pdfMain->output();
        $mainPageCount = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());
        $data['pageNumberOffset'] = $mainPageCount;

        $pdfLampiran = Pdf::loadView($lampiranView, $data)
            ->setPaper('a4', $lampiranPaper);

        // Set PDF title metadata untuk lampiran
        $pdfLampiran->getDomPDF()->set_option('pdfTitle', $filename);

        // Save temporary PDFs
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

        file_put_contents($mainPath, $mainOutput);
        file_put_contents($lampiranPath, $pdfLampiran->output());

        // Try to merge PDFs with title metadata
        $merged = PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath],
            $mergedPath,
            $filename
        );

        if ($merged && file_exists($mergedPath)) {
            // Cleanup non-merged temp files
            @unlink($mainPath);
            @unlink($lampiranPath);

            if (($validated['response_mode'] ?? 'binary') === 'url') {
                $previewUrl = $this->buildPublicPreviewSignedFileUrl($mergedPath, $filename, 'inline');
                if (! $previewUrl) {
                    return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
                }

                return response()->json([
                    'preview_url' => $previewUrl,
                    'filename' => $filename,
                ]);
            }

            // Stream merged PDF directly from disk — avoids loading entire file into memory
            return response()->file($mergedPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ])->deleteFileAfterSend(true);
        }

        // Cleanup temporary files
        @unlink($mainPath);
        @unlink($lampiranPath);

        // Fallback: Use combined template if merge failed
        $pdf = Pdf::loadView('spk-petugas', $data)
            ->setPaper('a4', 'portrait');

        // Sanitize filename untuk menghindari masalah karakter khusus
        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_'.$sanitizedName.'.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        if (($validated['response_mode'] ?? 'binary') === 'url') {
            $fallbackTemp = $this->storePublicPreviewTemporaryPdf($pdf->output());
            if (! $fallbackTemp) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            $previewUrl = $this->buildPublicPreviewSignedFileUrl($fallbackTemp, $filename, 'inline');
            if (! $previewUrl) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        return $pdf->stream($filename);
    }

    /**
     * @return array{filename:string,content:string}|null
     */


    /**
     * Preview SPK Main only
     */
    public function previewSpkMain(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        // Auto-calculate sampai_tanggal from this petugas' activity end dates
        $latestEndDate = null;
        foreach ($allAlokasi as $alokasi) {
            $periodeItem = $alokasi->periodeAlokasi;

            // Check if this is pengolahan/pengawas_pengolahan role
            $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan']);

            // Use appropriate schedules based on role
            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        // Fallback to end of month if no specific dates found
        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $validated['sampai_tanggal'] = $calculatedSampaiTanggal;

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
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

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => Carbon::parse($validated['sampai_tanggal']),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'workType' => $this->detectWorkType($allAlokasi),
        ];

        $pdf = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');

        // Sanitize filename untuk menghindari masalah karakter khusus
        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_Main_'.$sanitizedName.'.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        // Stream from temp file to avoid loading entire PDF into memory
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath.'/spk_main_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($tempFile, $pdf->output());

        if (($validated['response_mode'] ?? 'binary') === 'url') {
            $previewUrl = $this->buildPublicPreviewSignedFileUrl($tempFile, $filename, 'inline');
            if (! $previewUrl) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        return response()->file($tempFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview SPK Lampiran only
     */
    public function previewSpkLampiran(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:255'],
            'tanggal_spk' => ['required', 'date'],
            'response_mode' => ['nullable', 'in:binary,url'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        // Auto-calculate sampai_tanggal from this petugas' activity end dates
        $latestEndDate = null;
        foreach ($allAlokasi as $alokasi) {
            $periodeItem = $alokasi->periodeAlokasi;
            $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan']);

            // For pengolahan roles, use processing schedules; otherwise use regular schedules
            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        // Fallback to end of month if no specific dates found
        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $validated['sampai_tanggal'] = $calculatedSampaiTanggal;

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use 'dikirim' and 'direvisi' status
        $scopePeriodeIdsRevisi = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'direvisi']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIdsRevisi)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            abort(404, 'Tidak ada alokasi untuk petugas ini');
        }

        $petugas = $allAlokasi->first()->petugas;

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
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

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $periode->kegiatan,
            'nomorSpk' => $validated['nomor_spk'],
            'tanggalSpk' => Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => Carbon::parse($validated['sampai_tanggal']),
            'tanggalPerpanjangan' => null,
            'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
            'peran' => $allAlokasi->first()->peran,
            'peranLabel' => $this->getPeranLabel($allAlokasi->first()->peran),
            'totalHonor' => $totalHonor,
            'uraianTugas' => $uraianTugas,
            'bebanAnggaran' => $bebanAnggaran,
            'workType' => $this->detectWorkType($allAlokasi),
        ];

        $mainPreviewPdf = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');
        $mainPreviewPdf->output();
        $data['pageNumberOffset'] = max(0, (int) $mainPreviewPdf->getDomPDF()->getCanvas()->get_page_count());

        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        $pdf = Pdf::loadView($lampiranView, $data)
            ->setPaper('a4', $lampiranPaper);

        // Sanitize filename untuk menghindari masalah karakter khusus
        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_Lampiran_'.$sanitizedName.'.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        // Stream from temp file to avoid loading entire PDF into memory
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath.'/spk_lampiran_preview_'.time().'_'.uniqid().'.pdf';
        file_put_contents($tempFile, $pdf->output());

        if (($validated['response_mode'] ?? 'binary') === 'url') {
            $previewUrl = $this->buildPublicPreviewSignedFileUrl($tempFile, $filename, 'inline');
            if (! $previewUrl) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        return response()->file($tempFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate SPK PDF and save to database
     */
    public function generateSpk(Request $request, string $periodeHashedId, string $petugasHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        $petugasId = Hashids::decode($petugasHashedId)[0] ?? null;

        if (! $periodeId || ! $petugasId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
        ]);

        $periode = PeriodeAlokasi::with(['kegiatan', 'alokasiPetugas.petugas'])->findOrFail($periodeId);

        // Generate nomor_spk using next available urut for this year
        $tahun = $periode->tahun;
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);

        $nomorSpk = $this->formatNomorSpkForPeriode($periode, $nextNomorUrut);

        // Get all alokasi for this petugas in the same month (excluding Sensus Ekonomi in regular flow)
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'disetujui', 'perubahan']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        // Calculate sampai_tanggal automatically from petugas-specific activity end dates
        $latestEndDate = null;
        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

            // For pengolahan roles, use processing schedules; otherwise use regular schedules
            $endDates = $isPengolahanRole
                ? array_filter([
                    $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                    $periodeItem->jadwal_pengolahan_listing_selesai,
                ])
                : array_filter([
                    $periodeItem->tanggal_selesai,
                    $periodeItem->tanggal_selesai_listing,
                ]);

            if (! empty($endDates)) {
                $maxEndDate = max($endDates);
                if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                    $latestEndDate = $maxEndDate;
                }
            }
        }

        // Fallback to end of month if no specific dates found
        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $validated['sampai_tanggal'] = $calculatedSampaiTanggal;

        if ($allAlokasi->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada petugas yang dapat dibuatkan SPK untuk periode ini');
        }

        $petugas = Petugas::findOrFail($petugasId);

        // Get active Kepala BPS
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        // Get total honor for this petugas across all kegiatan
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

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($validated['tanggal_spk']),
            'sampaiTanggal' => Carbon::parse($validated['sampai_tanggal']),
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

        DB::beginTransaction();
        try {
            // Generate 2 separate PDFs (SPK Main + Lampiran only)
            $pdfMain = Pdf::loadView('spk-main', $data)
                ->setPaper('a4', 'portrait');

            $mainOutput = $pdfMain->output();
            $mainPageCount = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());
            $data['pageNumberOffset'] = $mainPageCount;

            $pdfLampiran = Pdf::loadView($lampiranView, $data)
                ->setPaper('a4', $lampiranPaper);

            // Save temporary PDFs
            $tempPath = storage_path('app/temp');
            if (! file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            $timestamp = time().'_'.uniqid();
            $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
            $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
            $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

            file_put_contents($mainPath, $mainOutput);
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
                // Fallback to single PDF if merge failed
                $pdf = Pdf::loadView('spk-petugas', $data)
                    ->setPaper('a4', 'portrait');
                $pdfOutput = $pdf->output();
            }

            // Cleanup temporary files
            @unlink($mainPath);
            @unlink($lampiranPath);
            @unlink($mergedPath);

            // Save PDF file to public/spk-export
            // Use the actual nominal segment from nomor_spk so the exported filename
            // matches the document number exactly, without inventing a suffix.
            $nomorUrut = $this->resolveDisplayNomorUrutSegment((string) $data['nomorSpk'], (int) $this->extractNomorUrut((string) $data['nomorSpk']));

            // Clean filename - remove special characters that are invalid for filenames
            $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
            $bulanLabel = $this->getBulanLabel($periode->bulan);

            $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
            $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;

            // Create directory if not exists
            $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
            if (! file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Save to public directory
            file_put_contents(public_path($filePath), $pdfOutput);

            // Save to database
            $spk = Spk::create([
                'nomor_spk' => $nomorSpk,
                'petugas_id' => $petugas->id,
                'alokasi_petugas_id' => $allAlokasi->first()->id,
                'alokasi_petugas_ids' => $allAlokasi->pluck('id')->toArray(),
                'addendum_number' => 0,
                'tanggal_spk' => $validated['tanggal_spk'],
                'tanggal_mulai_kerja' => Carbon::create($periode->tahun, $periode->bulan, 1),
                'tanggal_selesai_kerja' => Carbon::parse($calculatedSampaiTanggal),
                'nilai_kontrak' => $totalHonor,
                'lampiran_template' => $data['lampiranTemplate'],
                'lampiran_payload' => $data['lampiranPayload'],
                'nama_ppk' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'nip_ppk' => $penandatangan->nip ?? null,
                'file_path' => $filePath,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            $bulanName = Carbon::create()->month($periode->bulan)->translatedFormat('F');

            ActivityLog::log(
                'Generate SPK',
                'spk',
                "Berhasil generate SPK untuk {$petugas->nama}: {$nomorSpk} ({$bulanName} {$periode->tahun})",
                'success',
                [
                    'spk_id' => $spk->id,
                    'nomor_spk' => $nomorSpk,
                    'petugas_id' => $petugas->id,
                    'petugas_nama' => $petugas->nama,
                    'bulan' => $periode->bulan,
                    'tahun' => $periode->tahun,
                    'nilai_kontrak' => $totalHonor,
                ]
            );

            return redirect()->route('spk.index')->with('success', 'SPK berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Helper: Get bulan label
     */


    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiGroup
     * @return array{alokasi_id:int,alokasi_ids:array<int,int>,alokasi_details:array<int,array{alokasi_id:int,kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,alokasi_hashed_id:string,petugas:array{id:int,hashed_id:string,nama:string,nik:string,jenis_petugas:string},jumlah_kegiatan:int,kegiatan_list:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,perubahan:array<int,string>,total_honor:float}
     */
    private function buildGeneratePetugasListItem(Collection $alokasiGroup): array
    {
        $firstAlokasi = $alokasiGroup->first();

        $totalHonor = $alokasiGroup->sum(function (AlokasiPetugas $alokasi) {
            return $alokasi->getEffectiveCombinedHonor();
        });

        $kegiatanList = $alokasiGroup->map(function (AlokasiPetugas $alokasi): array {
            return [
                'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
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

        $alokasiDetails = $alokasiGroup->map(function (AlokasiPetugas $alokasi): array {
            return [
                'alokasi_id' => (int) $alokasi->id,
                'kegiatan_id' => (int) $alokasi->periodeAlokasi->kegiatan->id,
                'kegiatan_kode' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                'kegiatan_nama' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                'peran' => $alokasi->peran,
            ];
        })
            ->values()
            ->all();

        return [
            'alokasi_id' => $firstAlokasi->id,
            'alokasi_ids' => $alokasiGroup->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'alokasi_details' => $alokasiDetails,
            'alokasi_hashed_id' => $firstAlokasi->hashed_id,
            'petugas' => [
                'id' => $firstAlokasi->petugas->id,
                'hashed_id' => $firstAlokasi->petugas->hashed_id,
                'nama' => $firstAlokasi->petugas->nama,
                'nik' => $firstAlokasi->petugas->nik,
                'jenis_petugas' => $firstAlokasi->petugas->jenis_petugas,
            ],
            'jumlah_kegiatan' => $alokasiGroup->count(),
            'kegiatan_list' => $kegiatanList,
            'total_honor' => $totalHonor,
        ];
    }

    /**
     * @param  array{petugas:array{id:int},alokasi_ids:array<int,int>,alokasi_details:array<int,array{alokasi_id:int,kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,kegiatan_list:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,total_honor:float}  $currentItem
     * @param  array<int, array{nomor_spk:string,kegiatan_list?:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,total_honor?:float}>  $existingSpkRecords
     * @return array<int, string>
     */
    private function buildGenerateChangeSummaries(array $currentItem, array $existingSpkRecords): array
    {
        if ($existingSpkRecords === []) {
            return [];
        }

        $currentAllocationIds = collect($currentItem['alokasi_ids'] ?? [])
            ->map(static fn ($alokasiId) => (int) $alokasiId)
            ->unique()
            ->values();

        $baselineAllocationIds = collect($existingSpkRecords)
            ->flatMap(fn (array $record) => $record['alokasi_ids'] ?? [])
            ->map(static fn ($alokasiId) => (int) $alokasiId)
            ->unique()
            ->values();

        $missingAllocationIds = $currentAllocationIds->diff($baselineAllocationIds)->values();
        $removedAllocationIds = $baselineAllocationIds->diff($currentAllocationIds)->values();

        $summaries = [];

        if ($missingAllocationIds->isNotEmpty()) {
            $currentDetailsByAllocationId = collect($currentItem['alokasi_details'] ?? [])
                ->keyBy(static fn (array $detail): int => (int) ($detail['alokasi_id'] ?? 0));

            $missingActivityLabels = $missingAllocationIds
                ->map(function (int $alokasiId) use ($currentDetailsByAllocationId): ?string {
                    $detail = $currentDetailsByAllocationId->get($alokasiId);

                    if (! $detail) {
                        return null;
                    }

                    $label = trim((string) ($detail['kegiatan_nama'] ?? ''));
                    $peran = trim((string) ($detail['peran'] ?? ''));

                    if ($label === '') {
                        return null;
                    }

                    if ($peran !== '') {
                        return $label.' ('.$this->getPeranLabel($peran).')';
                    }

                    return $label;
                })
                ->filter()
                ->unique()
                ->values();

            if ($missingActivityLabels->isNotEmpty()) {
                $activityPreview = $missingActivityLabels->take(3)->implode(', ');
                $remainingCount = $missingActivityLabels->count() - $missingActivityLabels->take(3)->count();

                if ($remainingCount > 0) {
                    $activityPreview .= ' dan '.$remainingCount.' kegiatan lain';
                }

                $summaries[] = 'Kegiatan baru dialokasikan: '.$activityPreview;
            } else {
                $summaries[] = 'Ada '.$missingAllocationIds->count().' alokasi baru yang belum ada di SPK lama';
            }
        }

        if ($removedAllocationIds->isNotEmpty()) {
            $summaries[] = 'Ada '.$removedAllocationIds->count().' alokasi lama yang sudah tidak masuk SPK baru.';
        }

        $currentHonor = (float) ($currentItem['total_honor'] ?? 0);
        $baselineHonor = (float) collect($existingSpkRecords)->sum(function (array $record): float {
            return (float) ($record['total_honor'] ?? 0);
        });
        if (abs($currentHonor - $baselineHonor) > 0.01) {
            $summaries[] = 'Nilai kontrak berubah dari Rp '.number_format($baselineHonor, 0, ',', '.').' menjadi Rp '.number_format($currentHonor, 0, ',', '.');
        }

        return $summaries;
    }

    private function hasPositiveEffectiveHonor(AlokasiPetugas $alokasi): bool
    {
        return $alokasi->getEffectiveCombinedHonor() > 0;
    }

    /**
     * Calculate total honor for petugas
     */
    private function calculateTotalHonor(Kegiatan $kegiatan, AlokasiPetugas $alokasi): float
    {
        return $alokasi->getEffectiveCombinedHonor();
    }


    private function usesPeriodBasedSpkFlow(PeriodeAlokasi $periode): bool
    {
        return $this->isSensusEkonomi2026($periode->kegiatan);
    }

    private function resolveSpkScopePeriodeIds(PeriodeAlokasi $periode, array $statuses = []): Collection
    {
        $query = PeriodeAlokasi::query();
        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            $query->whereKey($periode->id);
        } else {
            $query->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                ->where('tahun', $periode->tahun)
                ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'));
        }

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        return $query->pluck('id');
    }


    private function hasDraftPeriodeInSpkScope(PeriodeAlokasi $periode): bool
    {
        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return false;
        }

        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        return PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
            ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->exists();
    }

    private function baseSpkScopeQuery(PeriodeAlokasi $periode)
    {
        $query = Spk::query()->where(function ($builder) {
            $builder->where('addendum_number', 0)
                ->orWhereNull('addendum_number');
        });

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return $query->whereHas('alokasiPetugas', function ($builder) use ($periode) {
                $builder->where('periode_alokasi_id', $periode->id);
            });
        }

        $bulanFormatted = str_pad((string) ((int) $periode->bulan), 2, '0', STR_PAD_LEFT);

        // Scope documents through their allocation period, not tanggal_spk.
        // The signing date may be outside the activity month.
        return $query->whereHas('alokasiPetugas.periodeAlokasi', function ($builder) use ($periode, $bulanFormatted) {
            $builder->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                ->where('tahun', $periode->tahun)
                ->whereHas('kegiatan', fn ($q) => $q->where('jenis_kegiatan', '!=', 'sensus'));
        });
    }

    private function resolveSpkIndexGroupKey(PeriodeAlokasi $periode): string
    {
        return sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
    }

    /**
     * @param  Collection<int, PeriodeAlokasi>  $monthPeriodes
     */
    private function resolveSpkIndexPrimaryPeriode(Collection $monthPeriodes): PeriodeAlokasi
    {
        return $monthPeriodes->sort(function (PeriodeAlokasi $left, PeriodeAlokasi $right): int {
            $leftPriority = $this->resolveSpkIndexStatusPriority($left->status);
            $rightPriority = $this->resolveSpkIndexStatusPriority($right->status);

            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            $leftCreatedAt = $left->created_at?->getTimestamp() ?? 0;
            $rightCreatedAt = $right->created_at?->getTimestamp() ?? 0;

            return $rightCreatedAt <=> $leftCreatedAt;
        })->first();
    }

    private function resolveSpkIndexStatusPriority(string $status): int
    {
        return match ($status) {
            'perubahan' => 4,
            'direvisi' => 3,
            'disetujui' => 2,
            'dikirim' => 1,
            default => 0,
        };
    }

    private function resolveSpkIndexDisplayLabel(PeriodeAlokasi $periode): string
    {
        if (! $this->usesPeriodBasedSpkFlow($periode)) {
            return $this->getBulanLabel((int) $periode->bulan).' '.$periode->tahun;
        }

        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
            $start = $periode->tanggal_mulai;
            $end = $periode->tanggal_selesai;

            if ($start->year === $end->year) {
                if ($start->month === $end->month) {
                    return $start->translatedFormat('d').'-'.$end->translatedFormat('d F Y');
                }

                return $start->translatedFormat('F').' - '.$end->translatedFormat('F Y');
            }

            return $start->translatedFormat('d F Y').' - '.$end->translatedFormat('d F Y');
        }

        return $this->getBulanLabel((int) $periode->bulan).' '.$periode->tahun;
    }

    private function isSensusEkonomi2026(Kegiatan $kegiatan): bool
    {
        return mb_strtolower((string) $kegiatan->jenis_kegiatan) === 'sensus'
            && str_contains(
                mb_strtolower(trim((string) $kegiatan->nama_kegiatan)),
                'sensus ekonomi'
            );
    }

    private function canAccessSensusMode(?User $user, ?int $tahunAnggaran = null): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($user->active_role, ['admin', 'operator', 'approver'], true)) {
            return true;
        }

        if ($user->active_role !== 'ketua_tim') {
            return false;
        }

        $activeYear = $tahunAnggaran ?? ActiveYearService::get();

        return Kegiatan::query()
            ->where('tahun_anggaran', $activeYear)
            ->where('nama_kegiatan', 'like', '%Sensus Ekonomi%')
            ->where(function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id)
                    ->orWhere('pj_lainnya_id', $user->id);
            })
            ->exists();
    }

    private function getRequestUser(Request $request): ?User
    {
        return effectiveUser($request) ?? $request->user();
    }

    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */
    private function buildSensusEkonomiLampiranPayload(
        mixed $periode,
        array $uraianTugas,
        float $totalHonor,
        Kegiatan $kegiatan,
        mixed $allAlokasi = null,
        mixed $alokasi = null,
    ): array {
        $positiveTask = collect($uraianTugas)->first(function ($task) {
            return (float) ($task['jumlah'] ?? 0) > 0;
        });

        $fallbackVolumeLabel = $this->formatLampiranVolumeLabel(
            $positiveTask['volume'] ?? null,
            $positiveTask['satuan'] ?? null
        );

        $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($allAlokasi, $alokasi);
        $selectedRows = $metrics['selected_rows'];
        $frameMuatanTotals = $metrics['frame_muatan_totals'];

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $terminSatuMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 40);
        $terminDuaMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 60);

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative($terminSatuMetrics['selected_rows']);
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative($terminDuaMetrics['selected_rows']);
        $totalVolumeLabel = $this->formatSensusEkonomiTotalSlsVolumeLabel($selectedRows);

        return [
            'groups' => [
                [
                    'items' => [
                        'Melakukan pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => $this->formatLampiranDate($periodeSelesai),
                    'persentase' => '60%',
                    'volume' => $terminDuaVolume,
                    'nilai_perjanjian' => $terminDuaAmount,
                ],
            ],
            'total' => [
                'waktu_penyelesaian' => $this->formatLampiranDateRange($periodeMulai, $periodeSelesai),
                'persentase' => '100%',
                'volume' => $totalVolumeLabel,
                'nilai_perjanjian' => $totalHonor,
            ],
            'wilayah_kerja' => $alokasi instanceof AlokasiPetugas
                ? $this->buildWilayahKerjaList($alokasi)
                : [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */
    private function buildPmlSensusEkonomiLampiranPayload(
        mixed $periode,
        array $uraianTugas,
        float $totalHonor,
        Kegiatan $kegiatan,
        mixed $allAlokasi = null,
        mixed $alokasi = null,
    ): array {
        $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($allAlokasi, $alokasi);
        $selectedRows = $metrics['selected_rows'];
        $frameMuatanTotals = $metrics['frame_muatan_totals'];

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $defaultTermOneSelectedRows = $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);
        $terminSatuSelectedRows = $this->resolvePmlSensusEkonomiTermOneSelectedRows(
            $allAlokasi,
            $alokasi,
            $selectedRows,
            $defaultTermOneSelectedRows,
        );

        $terminSatuMetrics = [
            'selected_rows' => $terminSatuSelectedRows,
        ];

        $terminDuaMetrics = [
            'selected_rows' => max(0, $selectedRows - $terminSatuSelectedRows),
        ];

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative($terminSatuMetrics['selected_rows']);
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative($terminDuaMetrics['selected_rows']);
        $totalVolumeLabel = $this->formatSensusEkonomiTotalSlsVolumeLabel($selectedRows);

        $wilayahKerja = $alokasi instanceof AlokasiPetugas
            ? $this->buildWilayahKerjaList($alokasi)
            : [];

        return [
            'groups' => [
                [
                    'items' => [
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door '.$kegiatan->nama_kegiatan.' 2026',
                    ],
                    'waktu_penyelesaian' => $this->formatLampiranDate($periodeSelesai),
                    'persentase' => '60%',
                    'volume' => $terminDuaVolume,
                    'nilai_perjanjian' => $terminDuaAmount,
                ],
            ],
            'total' => [
                'waktu_penyelesaian' => $this->formatLampiranDateRange($periodeMulai, $periodeSelesai),
                'persentase' => '100%',
                'volume' => $totalVolumeLabel,
                'nilai_perjanjian' => $totalHonor,
            ],
            'wilayah_kerja' => $wilayahKerja,
        ];
    }

    private function resolvePmlSensusEkonomiTermOneSelectedRows(
        mixed $allAlokasi,
        mixed $alokasi,
        int $pmlSelectedRows,
        int $defaultTermOneSelectedRows,
    ): int {
        $pmlSelectedRows = max(0, $pmlSelectedRows);
        $defaultTermOneSelectedRows = max(0, min($pmlSelectedRows, $defaultTermOneSelectedRows));

        $alokasiCollection = collect();

        if ($allAlokasi instanceof Collection) {
            $alokasiCollection = $allAlokasi
                ->filter(fn (mixed $item): bool => $item instanceof AlokasiPetugas)
                ->values();
        }

        if ($alokasiCollection->isEmpty() && $alokasi instanceof AlokasiPetugas) {
            $alokasiCollection = collect([$alokasi]);
        }

        if ($alokasiCollection->isEmpty()) {
            return $defaultTermOneSelectedRows;
        }

        /** @var AlokasiPetugas|null $pmlAlokasi */
        $pmlAlokasi = $alokasiCollection->first(function (AlokasiPetugas $item): bool {
            return mb_strtolower((string) $item->peran) === 'pml';
        });

        if (! $pmlAlokasi instanceof AlokasiPetugas && $alokasi instanceof AlokasiPetugas && mb_strtolower((string) $alokasi->peran) === 'pml') {
            $pmlAlokasi = $alokasi;
        }

        if (! $pmlAlokasi instanceof AlokasiPetugas) {
            return $defaultTermOneSelectedRows;
        }

        $pmlAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');

        $pmlFrameIds = $pmlAlokasi->frameSampelAllocations
            ->pluck('kegiatan_frame_sampel_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($pmlFrameIds)) {
            return $defaultTermOneSelectedRows;
        }

        $pendataanRoles = ['pcl_ppl', 'pcl', 'ppl'];
        $periodeAlokasiId = (int) ($pmlAlokasi->periode_alokasi_id ?? 0);

        $linkedPplAlokasi = $alokasiCollection
            ->filter(function (AlokasiPetugas $item) use ($pendataanRoles, $periodeAlokasiId): bool {
                if (! in_array(mb_strtolower((string) $item->peran), $pendataanRoles, true)) {
                    return false;
                }

                if ($periodeAlokasiId > 0 && (int) ($item->periode_alokasi_id ?? 0) !== $periodeAlokasiId) {
                    return false;
                }

                return true;
            })
            ->values();

        if ($linkedPplAlokasi->isEmpty() && $periodeAlokasiId > 0) {
            $linkedPplAlokasi = AlokasiPetugas::query()
                ->where('periode_alokasi_id', $periodeAlokasiId)
                ->whereIn('peran', $pendataanRoles)
                ->with('frameSampelAllocations.kegiatanFrameSampel')
                ->get();
        }

        if ($linkedPplAlokasi->isEmpty()) {
            return $defaultTermOneSelectedRows;
        }

        $aggregatedTermOneSelectedRows = $linkedPplAlokasi->sum(function (AlokasiPetugas $pplAlokasi) use ($pmlFrameIds): int {
            $pplAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');

            $matchingFrames = $pplAlokasi->frameSampelAllocations
                ->filter(function ($frameAllocation) use ($pmlFrameIds): bool {
                    return in_array((int) ($frameAllocation?->kegiatan_frame_sampel_id ?? 0), $pmlFrameIds, true);
                })
                ->unique('kegiatan_frame_sampel_id')
                ->values();

            $selectedRows = $matchingFrames->count();

            if ($selectedRows <= 0) {
                return 0;
            }

            $frameMuatanTotals = $matchingFrames
                ->map(function ($frameAllocation): int {
                    $targetUnitSampel = $frameAllocation?->kegiatanFrameSampel?->target_unit_sampel;

                    if (is_array($targetUnitSampel)) {
                        return (int) collect($targetUnitSampel)
                            ->map(fn ($value): int => max(0, (int) $value))
                            ->sum();
                    }

                    if (is_numeric($targetUnitSampel)) {
                        return max(0, (int) $targetUnitSampel);
                    }

                    return 0;
                })
                ->all();

            return $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);
        });

        if ($aggregatedTermOneSelectedRows <= 0) {
            return $defaultTermOneSelectedRows;
        }

        return min($pmlSelectedRows, $aggregatedTermOneSelectedRows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWilayahKerjaList(AlokasiPetugas $pmlAlokasi): array
    {
        $pmlAlokasi->loadMissing('frameSampelAllocations.kegiatanFrameSampel');
        $frames = $pmlAlokasi->frameSampelAllocations;

        if ($frames->isEmpty()) {
            return [];
        }

        $unitIds = $frames
            ->map(function ($frame): array {
                $targetUnitSampel = $frame->kegiatanFrameSampel?->target_unit_sampel;

                return is_array($targetUnitSampel)
                    ? array_values(array_map('intval', array_keys($targetUnitSampel)))
                    : [];
            })
            ->flatten()
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $unitNameById = ! empty($unitIds)
            ? MasterUnitSampel::query()
                ->whereIn('id', $unitIds)
                ->pluck('nama', 'id')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->toArray()
            : [];

        /** @var array<string, array{kdkec:string,kdkec_label:string,kddes:string,kddes_label:string,count:int,prelist_usaha:int,prelist_keluarga:int}> $grouped */
        $grouped = [];

        foreach ($frames as $frame) {
            $kfs = $frame->kegiatanFrameSampel;

            if (! $kfs) {
                continue;
            }

            $identitas = is_array($kfs->identitas_tambahan) ? $kfs->identitas_tambahan : [];
            $kdkec = $identitas['kdkec'] ?? $kfs->kode_kecamatan ?? '';
            $kdkecLabel = $identitas['kdkec_label'] ?? $kdkec;
            $kddes = $identitas['kddes'] ?? $kfs->kode_desa ?? '';
            $kddesLabel = $identitas['kddes_label'] ?? $kddes;

            $key = $kdkec.'_'.$kddes;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'kdkec' => $kdkec,
                    'kdkec_label' => $kdkecLabel,
                    'kddes' => $kddes,
                    'kddes_label' => $kddesLabel,
                    'count' => 0,
                    'prelist_usaha' => 0,
                    'prelist_keluarga' => 0,
                ];
            }

            $grouped[$key]['count']++;

            $targetUnitSampel = is_array($kfs->target_unit_sampel) ? $kfs->target_unit_sampel : [];

            foreach ($targetUnitSampel as $unitId => $total) {
                $totalValue = max(0, (int) $total);

                if ($totalValue === 0) {
                    continue;
                }

                $normalizedUnitName = '';

                if (is_numeric($unitId) && (int) $unitId > 0) {
                    $normalizedUnitName = $unitNameById[(int) $unitId] ?? '';
                } else {
                    $normalizedUnitName = mb_strtolower(trim((string) $unitId));
                }

                if (str_contains($normalizedUnitName, 'usaha')) {
                    $grouped[$key]['prelist_usaha'] += $totalValue;
                }

                if (str_contains($normalizedUnitName, 'keluarga') || str_contains($normalizedUnitName, 'rumah tangga')) {
                    $grouped[$key]['prelist_keluarga'] += $totalValue;
                }
            }
        }

        $result = [];
        $no = 1;

        foreach ($grouped as $entry) {
            $result[] = [
                'no' => $no++,
                'kecamatan' => '['.$entry['kdkec'].'] '.$entry['kdkec_label'],
                'desa' => '['.$entry['kddes'].'] '.$entry['kddes_label'],
                'jumlah_sls' => $entry['count'],
                'muatan_prelist' => $entry['prelist_usaha'].' usaha dan '.$entry['prelist_keluarga'].' keluarga',
            ];
        }

        return $result;
    }

    /**
     * @return array{selected_rows:int,prelist_total:int}
     */
    /**
     * @param  array<int, int>  $frameMuatanTotals
     * @return array{selected_rows: int}
     */
    private function calculateSensusEkonomiMilestoneMetrics(int $selectedRows, array $frameMuatanTotals, int $percentage): array
    {
        $selectedRows = max(0, $selectedRows);

        $terminSatuSelectedRows = $this->calculateSensusEkonomiTermSelectedRows($selectedRows, $frameMuatanTotals);

        if ($percentage === 40) {
            return [
                'selected_rows' => $terminSatuSelectedRows,
            ];
        }

        return [
            'selected_rows' => max(0, $selectedRows - $terminSatuSelectedRows),
        ];
    }

    /**
     * @param  array<int, int>  $frameMuatanTotals
     */
    private function calculateSensusEkonomiTermSelectedRows(int $selectedRows, array $frameMuatanTotals): int
    {
        $selectedRows = max(0, $selectedRows);
        $frameMuatanTotals = array_values(array_filter(
            array_map(static fn ($value): int => max(0, (int) $value), $frameMuatanTotals),
            static fn (int $value): bool => $value > 0,
        ));

        if ($selectedRows === 0) {
            return 0;
        }

        if (empty($frameMuatanTotals)) {
            return (int) ceil($selectedRows * 0.4);
        }

        $totalMuatan = array_sum($frameMuatanTotals);

        if ($totalMuatan <= 0) {
            return (int) ceil($selectedRows * 0.4);
        }

        $threshold = (int) ceil($totalMuatan * 0.4);
        rsort($frameMuatanTotals, SORT_NUMERIC);

        $accumulatedMuatan = 0;
        $count = 0;

        foreach ($frameMuatanTotals as $frameMuatan) {
            $accumulatedMuatan += $frameMuatan;
            $count++;

            if ($accumulatedMuatan >= $threshold) {
                break;
            }
        }

        return max(1, min($selectedRows, $count));
    }

    /**
     * @return array{selected_rows:int,prelist_total:int,total_volume:int,narrative:string}
     */
    private function resolveSensusEkonomiFrameVolumeMetrics(mixed $allAlokasi, mixed $alokasi): array
    {
        $alokasiCollection = collect();

        if ($allAlokasi instanceof Collection) {
            $alokasiCollection = $allAlokasi->filter(fn (mixed $item): bool => $item instanceof AlokasiPetugas)->values();
        }

        if ($alokasiCollection->isEmpty() && $alokasi instanceof AlokasiPetugas) {
            $alokasiCollection = collect([$alokasi]);
        }

        if ($alokasiCollection->isEmpty()) {
            return [
                'selected_rows' => 0,
                'prelist_total' => 0,
                'total_volume' => 0,
                'frame_muatan_totals' => [],
                'narrative' => '-',
            ];
        }

        $alokasiCollection->each(function (AlokasiPetugas $alokasiPetugas): void {
            $alokasiPetugas->loadMissing('frameSampelAllocations.kegiatanFrameSampel');
        });

        $frameAllocations = $alokasiCollection
            ->flatMap(function (AlokasiPetugas $alokasiPetugas): array {
                return $alokasiPetugas->frameSampelAllocations->all();
            })
            ->filter(fn (mixed $allocation): bool => $allocation !== null)
            ->unique('kegiatan_frame_sampel_id')
            ->values();

        $selectedRows = $frameAllocations->count();

        $perUnitSampelTotals = [];
        $frameMuatanTotals = [];
        foreach ($frameAllocations as $frameAllocation) {
            $targetUnitSampel = $frameAllocation?->kegiatanFrameSampel?->target_unit_sampel;
            $frameMuatanTotal = 0;

            if (is_array($targetUnitSampel)) {
                foreach ($targetUnitSampel as $unitSampelId => $count) {
                    $uid = (int) $unitSampelId;
                    $countValue = max(0, (int) $count);
                    $perUnitSampelTotals[$uid] = ($perUnitSampelTotals[$uid] ?? 0) + $countValue;
                    $frameMuatanTotal += $countValue;
                }
            } elseif (is_numeric($targetUnitSampel) && (int) $targetUnitSampel > 0) {
                $targetValue = (int) $targetUnitSampel;
                $perUnitSampelTotals[0] = ($perUnitSampelTotals[0] ?? 0) + $targetValue;
                $frameMuatanTotal += $targetValue;
            }

            $frameMuatanTotals[] = $frameMuatanTotal;
        }

        $unitSampelIds = array_values(array_filter(array_keys($perUnitSampelTotals), fn ($id) => $id > 0));
        $unitSampelNames = ! empty($unitSampelIds)
            ? MasterUnitSampel::query()->whereIn('id', $unitSampelIds)->pluck('nama', 'id')->toArray()
            : [];

        $prelistTotal = array_sum($perUnitSampelTotals);
        $totalVolume = $selectedRows + $prelistTotal;

        return [
            'selected_rows' => $selectedRows,
            'prelist_total' => $prelistTotal,
            'per_unit_sampel_totals' => $perUnitSampelTotals,
            'unit_sampel_names' => $unitSampelNames,
            'frame_muatan_totals' => $frameMuatanTotals,
            'total_volume' => $totalVolume,
            'narrative' => $this->formatSensusEkonomiVolumeNarrative($selectedRows),
        ];
    }

    private function formatSensusEkonomiVolumeNarrative(int $selectedRows): string
    {
        if ($selectedRows > 0) {
            return number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
        }

        return '-';
    }

    private function formatSensusEkonomiTotalSlsVolumeLabel(int $selectedRows): string
    {
        if ($selectedRows <= 0) {
            return '-';
        }

        return 'Seluruh Muatan '.number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
    }


    /**
     * Get uraian tugas details
     */


    /**
     * Get peran kegiatan label based on role and phase
     */


    /**
     * Get beban anggaran (MAK)
     */


    /**
     * Get peran label
     */


    /**
     * Detect work type from all allocations
     * Returns: 'lapangan', 'pengolahan', or 'lapangan_pengolahan'
     */


    /**
     * Generate addendum PDF content
     */


    /**
     * Bulk generate SPK for all non-organik petugas in a periode
     */
    public function generateAllSpk(Request $request, string $periodeHashedId)
    {
        $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
        if (! $periodeId) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_spk' => ['required', 'date'],
            'petugas_ids' => ['nullable', 'array'], // Array of petugas hashed IDs
        ]);

        // Decode petugas_ids if provided
        $selectedPetugasIds = [];
        if (! empty($validated['petugas_ids'])) {
            foreach ($validated['petugas_ids'] as $hashedId) {
                $decoded = Hashids::decode($hashedId);
                if (! empty($decoded)) {
                    $selectedPetugasIds[] = $decoded[0];
                }
            }
        }

        $periode = PeriodeAlokasi::with(['kegiatan'])->findOrFail($periodeId);
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'disetujui', 'direvisi', 'perubahan']);

        // Get all unique non-organik petugas from the SPK scope.
        // Only include those with honor > 0
        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->with([
                'petugas:id,nama,nik,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,status',
                'periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan',
            ])
            ->get();

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')->sortKeys();

        // Sort by petugas name for consistent nomor urut
        $sortedPetugas = $petugasList->sortBy(function ($group) {
            return $group->first()->petugas->nama;
        });

        // Filter by selected petugas if provided
        if (! empty($selectedPetugasIds)) {
            $sortedPetugas = $sortedPetugas->filter(function ($group, $petugasId) use ($selectedPetugasIds) {
                return in_array($petugasId, $selectedPetugasIds);
            })->sortBy(function ($group) {
                return mb_strtolower((string) $group->first()->petugas->nama);
            });
        }

        $tahun = $periode->tahun;
        $bulan = $periode->bulan;

        $petugasIdsInMonth = AlokasiPetugas::whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->pluck('petugas_id')
            ->unique();

        $existingSpkScopeQuery = $this->baseSpkScopeQuery($periode);

        $existingSpkGroups = (clone $existingSpkScopeQuery)
            ->whereIn('petugas_id', $petugasIdsInMonth)
            ->with(['petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->get()
            ->groupBy('petugas_id');

        $allExistingSpks = $existingSpkGroups->flatten(1)->values();
        $usesPeriodBasedNumbering = $this->usesPeriodBasedSpkFlow($periode);
        $existingSpksForFlow = $allExistingSpks->filter(function (Spk $spk) use ($usesPeriodBasedNumbering) {
            $jenisKegiatan = mb_strtolower((string) $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->jenis_kegiatan);

            return $usesPeriodBasedNumbering
                ? $jenisKegiatan === 'sensus'
                : $jenisKegiatan !== 'sensus';
        })->values();

        // Check if this is a regenerate (existing SPKs present for the current flow) or first time generate
        $isRegenerate = $existingSpksForFlow->isNotEmpty();

        // Determine the last nomor_urut_base from existing SPKs in this month
        $lastNomorUrutBase = null;
        if ($isRegenerate) {
            // Get the highest nomor_urut_base from existing SPKs in THIS MONTH only for the current flow
            foreach ($existingSpksForFlow as $spk) {
                $baseNumber = $spk->nomor_urut_base ?? $this->extractNomorUrut($spk->nomor_spk);
                if ($lastNomorUrutBase === null || $baseNumber > $lastNomorUrutBase) {
                    $lastNomorUrutBase = $baseNumber;
                }
            }
        }

        // For first time generation, get next sequential nomor
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);
        $nomorUrutCounter = 0;

        $nextSuffix = 'A';
        $results = [];

        $usedNomorUrutInCurrentBatch = collect();

        foreach ($sortedPetugas as $petugasId => $alokasiGroup) {
            $petugas = Petugas::findOrFail($petugasId);
            $petugasHashedId = $petugas->hashed_id;

            // Check if this petugas already has an SPK for the current flow
            $existingSpkGroup = $existingSpkGroups->get($petugasId, collect());
            $existingSpk = null;

            if ($existingSpkGroup->isNotEmpty()) {
                if ($usesPeriodBasedNumbering) {
                    $existingSpk = $existingSpkGroup->first(function (Spk $spk) use ($periode) {
                        return $spk->alokasiPetugas?->periodeAlokasi?->id === $periode->id;
                    }) ?: $existingSpkGroup->first(function (Spk $spk) {
                        return mb_strtolower((string) $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'sensus';
                    });
                } else {
                    $existingSpk = $existingSpkGroup->first(function (Spk $spk) {
                        return mb_strtolower((string) $spk->alokasiPetugas?->periodeAlokasi?->kegiatan?->jenis_kegiatan) !== 'sensus';
                    });
                }
            }

            if ($existingSpk) {
                // Use existing nomor for updates
                $nomorSpk = $existingSpk->nomor_spk;
                $noUrut = $existingSpk->nomor_urut_base ?? $this->extractNomorUrut($nomorSpk);
            } else {
                // New petugas
                if ($usesPeriodBasedNumbering) {
                    if ($isRegenerate) {
                        $noUrut = $this->getNextNomorUrutForPeriode($periode);
                        while ($usedNomorUrutInCurrentBatch->contains($noUrut)) {
                            $noUrut++;
                        }
                        $usedNomorUrutInCurrentBatch->push($noUrut);
                    } else {
                        $noUrut = $nextNomorUrut + $nomorUrutCounter;
                        $nomorUrutCounter++;
                    }

                    $nomorSpk = $this->formatNomorSpkForPeriode($periode, $noUrut);
                } elseif ($isRegenerate) {
                    $noUrut = $this->getNextNomorUrut((int) $tahun);
                    while ($usedNomorUrutInCurrentBatch->contains($noUrut)) {
                        $noUrut++;
                    }
                    $usedNomorUrutInCurrentBatch->push($noUrut);
                    $nomorSpk = $this->formatNomorSpkForPeriode($periode, $noUrut);
                } else {
                    // First time generation: use sequential numbering
                    $noUrut = $nextNomorUrut + $nomorUrutCounter;
                    $nomorSpk = $this->formatNomorSpkForPeriode($periode, $noUrut);
                    $nomorUrutCounter++;
                }
            }

            // Call the same logic as generateSpk, but inline to avoid HTTP call
            // IMPORTANT: Only get alokasi from current effective periode statuses
            $allAlokasiPetugas = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
                ->whereIn('periode_alokasi_id', $scopePeriodeIds)
                ->where('petugas_id', $petugasId)
                ->get();

            // Persist only the current effective snapshot. Historical
            // `direvisi` rows must not remain beside their `perubahan`
            // replacements in alokasi_petugas_ids.
            $allAlokasiPetugas = $this->spkActionDecisionService
                ->getEffectiveAlokasiByKegiatan($allAlokasiPetugas)
                ->values();

            if ($allAlokasiPetugas->isEmpty()) {
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => 'Tidak ada alokasi untuk petugas ini',
                ];

                continue;
            }

            $penandatangan = Penandatangan::active()->ppk()->first();
            if (! $penandatangan) {
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => 'Penandatangan (PPK) tidak ditemukan',
                ];

                continue;
            }

            $totalHonor = 0;
            $uraianTugas = [];
            $bebanAnggaran = '';
            foreach ($allAlokasiPetugas as $alokasi) {
                $kegiatan = $alokasi->periodeAlokasi->kegiatan;
                $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
                $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
                if (empty($bebanAnggaran)) {
                    $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
                }
            }

            // Calculate sampai_tanggal from activity end dates
            $latestEndDate = null;
            foreach ($allAlokasiPetugas as $alokasi) {
                $periodeItem = $alokasi->periodeAlokasi;
                $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan']);

                // For pengolahan roles, use processing schedules; otherwise use regular schedules
                $endDates = $isPengolahanRole
                    ? array_filter([
                        $periodeItem->jadwal_pengolahan_pencacahan_selesai,
                        $periodeItem->jadwal_pengolahan_listing_selesai,
                    ])
                    : array_filter([
                        $periodeItem->tanggal_selesai,
                        $periodeItem->tanggal_selesai_listing,
                    ]);

                if (! empty($endDates)) {
                    $maxEndDate = max($endDates);
                    if ($latestEndDate === null || $maxEndDate > $latestEndDate) {
                        $latestEndDate = $maxEndDate;
                    }
                }
            }

            // Fallback to end of month if no activity end dates found
            if ($latestEndDate === null) {
                $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
            }

            $calculatedSampaiTanggal = Carbon::parse($latestEndDate);

            $data = [
                'periode' => $periode,
                'alokasi' => $allAlokasiPetugas->first(),
                'allAlokasi' => $allAlokasiPetugas,
                'petugas' => $petugas,
                'kegiatan' => $allAlokasiPetugas->first()->periodeAlokasi->kegiatan,
                'nomorSpk' => $nomorSpk,
                'tanggalSpk' => Carbon::parse($validated['tanggal_spk']),
                'sampaiTanggal' => $calculatedSampaiTanggal,
                'tanggalPerpanjangan' => null,
                'penandatangan' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'kepalaBps' => preg_replace('/,.*$/', '', $penandatangan->nama),
                'peran' => $allAlokasiPetugas->first()->peran,
                'peranLabel' => $this->getPeranLabel($allAlokasiPetugas->first()->peran),
                'totalHonor' => $totalHonor,
                'uraianTugas' => $uraianTugas,
                'bebanAnggaran' => $bebanAnggaran,
                'workType' => $this->detectWorkType($allAlokasiPetugas),
            ];
            $data = $this->withLampiranContext($data);

            $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
            $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

            // Use the same PDF/database logic as generateSpk
            DB::beginTransaction();
            try {
                $pdfMain = Pdf::loadView('spk-main', $data)
                    ->setPaper('a4', 'portrait');
                $pdfLampiran = Pdf::loadView($lampiranView, $data)
                    ->setPaper('a4', $lampiranPaper);
                $tempPath = storage_path('app/temp');
                if (! file_exists($tempPath)) {
                    mkdir($tempPath, 0777, true);
                }
                $timestamp = time().'_'.uniqid();
                $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
                $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
                $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';
                file_put_contents($mainPath, $pdfMain->output());
                file_put_contents($lampiranPath, $pdfLampiran->output());
                $merged = PdfMergerService::mergePdfFiles(
                    [$mainPath, $lampiranPath],
                    $mergedPath
                );
                $pdfOutput = null;
                if ($merged && file_exists($mergedPath)) {
                    $pdfOutput = file_get_contents($mergedPath);
                } else {
                    $pdf = Pdf::loadView('spk-petugas', $data)
                        ->setPaper('a4', 'portrait');
                    $pdfOutput = $pdf->output();
                }
                @unlink($mainPath);
                @unlink($lampiranPath);
                @unlink($mergedPath);
                $nomorUrut = $this->resolveDisplayNomorUrutSegment(
                    (string) $data['nomorSpk'],
                    (int) $noUrut,
                    ($isRegenerate && ! $existingSpk) ? $nextSuffix : null,
                );

                // Check if SPK already exists for this petugas (use existing SPK from map)
                $existingSpkRecord = $existingSpk;
                $bulanLabel = $this->getBulanLabel($periode->bulan);
                $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
                $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
                $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;
                $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
                if (! file_exists($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }
                file_put_contents(public_path($filePath), $pdfOutput);

                if ($existingSpkRecord) {
                    // Update existing SPK with new data
                    // Save previous file_path and signed_file_path before updating
                    $previousDocumentPath = $existingSpkRecord->signed_file_path ?: $existingSpkRecord->file_path;

                    $updateData = [
                        'nomor_urut_base' => $noUrut, // Populate base number if NULL
                        'alokasi_petugas_ids' => $allAlokasiPetugas->pluck('id')->toArray(),
                        'tanggal_spk' => $validated['tanggal_spk'],
                        'tanggal_mulai_kerja' => Carbon::create($periode->tahun, $periode->bulan, 1),
                        'tanggal_selesai_kerja' => $calculatedSampaiTanggal,
                        'nilai_kontrak' => $totalHonor,
                        'lampiran_template' => $data['lampiranTemplate'],
                        'lampiran_payload' => $data['lampiranPayload'],
                        'nama_ppk' => preg_replace('/,.*$/', '', $penandatangan->nama),
                        'nip_ppk' => $penandatangan->nip ?? null,
                        'file_path' => $filePath, // Update file_path with new regenerated SPK
                        'regeneration_count' => ($existingSpkRecord->regeneration_count ?? 0) + 1, // Increment count
                    ];

                    if ($previousDocumentPath) {
                        $updateData['previous_file_path'] = $previousDocumentPath;
                    }

                    // If there was a signed file, move it to previous_file_path and reset signed_file_path
                    if ($existingSpkRecord->signed_file_path) {
                        $updateData['signed_file_path'] = null; // Reset signed file for new regenerated SPK
                    }

                    $existingSpkRecord->update($updateData);

                    DB::commit();
                    $results[] = [
                        'petugas_id' => $petugasId,
                        'status' => 'updated',
                        'spk_id' => $existingSpkRecord->id,
                    ];
                } else {
                    // Create new SPK
                    $spk = Spk::create([
                        'nomor_spk' => $nomorSpk,
                        'nomor_urut_suffix' => (strpos($nomorSpk, $noUrut) !== false && preg_match('/[A-Z]/', $nomorSpk)) ? $nextSuffix : null,
                        'nomor_urut_base' => $noUrut,
                        'petugas_id' => $petugasId,
                        'alokasi_petugas_id' => $allAlokasiPetugas->first()->id,
                        'alokasi_petugas_ids' => $allAlokasiPetugas->pluck('id')->toArray(),
                        'addendum_number' => 0,
                        'tanggal_spk' => $validated['tanggal_spk'],
                        'tanggal_mulai_kerja' => Carbon::create($periode->tahun, $periode->bulan, 1),
                        'tanggal_selesai_kerja' => $calculatedSampaiTanggal,
                        'nilai_kontrak' => $totalHonor,
                        'lampiran_template' => $data['lampiranTemplate'],
                        'lampiran_payload' => $data['lampiranPayload'],
                        'nama_ppk' => preg_replace('/,.*$/', '', $penandatangan->nama),
                        'nip_ppk' => $penandatangan->nip ?? null,
                        'file_path' => $filePath,
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                    ]);

                    DB::commit();
                    $results[] = [
                        'petugas_id' => $petugasId,
                        'status' => 'created',
                        'spk_id' => $spk->id,
                    ];

                    // Increment suffix for next new petugas (only in suffix mode)
                    if (strpos($nomorSpk, $nextSuffix) !== false) {
                        $nextSuffix++;
                    }
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $results[] = [
                    'petugas_id' => $petugasId,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('status', 'created')->count();
        $updatedCount = collect($results)->where('status', 'updated')->count();
        $failedCount = collect($results)->where('status', 'failed')->count();

        // Adjust message based on regenerate mode
        if ($isRegenerate) {
            $message = 'SPK berhasil ';
            if ($successCount > 0) {
                $message .= "ditambahkan: {$successCount} baru";
                if ($updatedCount > 0) {
                    $message .= ", {$updatedCount} diperbarui";
                }
            } elseif ($updatedCount > 0) {
                $message .= "diperbarui: {$updatedCount} SPK";
            } else {
                $message .= 'diproses';
            }
            $message .= '.';
        } else {
            $message = "SPK berhasil dibuat: {$successCount} baru, {$updatedCount} diperbarui.";
        }

        if ($failedCount > 0) {
            // Get failure details
            $failedMessages = collect($results)
                ->where('status', 'failed')
                ->pluck('message')
                ->filter()
                ->unique()
                ->join('; ');

            $message .= " {$failedCount} gagal";
            if ($failedMessages) {
                $message .= ": {$failedMessages}";
            } else {
                $message .= '.';
            }
        }

        $flashType = $failedCount > 0 ? 'error' : 'success';

        return redirect()->route('spk.index')->with($flashType, $message);
    }

    /**
     * Check if there are new kegiatan/petugas added after SPK was generated
     */
    private function hasNewKegiatanAfterSpk(int $tahun, int $bulan, $monthPeriodes): bool
    {
        return $this->spkActionDecisionService->resolveRegenerateCandidatesForMonth($tahun, $bulan)->isNotEmpty();
    }

    private function hasNewRevisionAfterAddendum(int $tahun, int $bulan, iterable $monthPeriodes): bool
    {

        $latestAddendumCreatedAt = null;

        foreach ($monthPeriodes as $periode) {
            $latestAddendum = $periode->spk()
                ->where('addendum_number', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestAddendum && (! $latestAddendumCreatedAt || $latestAddendum->created_at > $latestAddendumCreatedAt)) {
                $latestAddendumCreatedAt = $latestAddendum->created_at;
            }
        }

        if (! $latestAddendumCreatedAt) {
            return false;
        }

        foreach ($monthPeriodes as $periode) {
            if (! in_array($periode->status, ['perubahan', 'direvisi'])) {
                continue;
            }

            $nonOrganikAlokasi = $periode->alokasiPetugas()
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->where(function ($query) {
                    $query->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->get();

            foreach ($nonOrganikAlokasi as $alokasi) {
                $hasAddendum = Spk::where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', '>', 0)
                    ->exists();

                $isLaterThanAddendum = $periode->updated_at && $periode->updated_at > $latestAddendumCreatedAt;

                if (! $hasAddendum || $isLaterThanAddendum) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if there are petugas with revisions who don't have addendum yet
     */
    private function hasIncompleteAddendum(int $tahun, int $bulan, $monthPeriodes): bool
    {
        $candidateSummary = $this->spkActionDecisionService->resolveAddendumCandidatesForMonth($tahun, $bulan);

        return $candidateSummary->contains(fn (array $item): bool => ! (bool) ($item['has_addendum'] ?? false));
    }

    /**
     * Check if there are allocation changes to petugas who already have addendum
     */
    private function hasAddendumChanges(int $tahun, int $bulan, $monthPeriodes): bool
    {
        $candidateSummary = $this->spkActionDecisionService->resolveAddendumCandidatesForMonth($tahun, $bulan);

        if ($candidateSummary->isEmpty()) {
            return false;
        }

        if ($candidateSummary->contains(fn (array $item): bool => (bool) ($item['has_addendum'] ?? false))) {
            return true;
        }

        return false;
    }

    /**
     * Analyze allocation delta for a petugas in a month.
     *
     * @return array{has_new_kegiatan_added:bool,has_allocation_change:bool,has_perubahan_status:bool,is_allocation_incomplete:bool,has_honor_mismatch:bool}
     */
    private function analyzeAllocationDeltaForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        // Scope reference document to this specific month/year
        $baseQuery = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', (int) $bulanFormatted);

        if ($referenceType === 'latest_addendum') {
            // Find original SPK for this month first, then find latest addendum via parent_spk_id.
            // This correctly handles addendums whose tanggal_spk is outside the contract month.
            $originalSpkForMonth = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', '>', 0)
                ->where(function ($q) use ($originalSpkForMonth, $tahun, $bulanFormatted) {
                    $q->where(function ($q2) use ($tahun, $bulanFormatted) {
                        $q2->whereYear('tanggal_spk', $tahun)
                            ->whereMonth('tanggal_spk', (int) $bulanFormatted);
                    });
                    if ($originalSpkForMonth) {
                        $q->orWhere('parent_spk_id', $originalSpkForMonth->id);
                    }
                })
                ->orderBy('addendum_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        } elseif ($referenceType === 'same_month_original_spk') {
            $referenceDocument = (clone $baseQuery)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        } else {
            // Original SPK is NOT month-scoped: cross-month revisions reference a prior month's SPK.
            $referenceDocument = Spk::query()
                ->where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        }

        if (! $referenceDocument) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
                'has_perubahan_status' => false,
                'is_allocation_incomplete' => false,
                'has_honor_mismatch' => false,
            ];
        }

        $referenceSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
            $petugasId,
            $referenceDocument,
            $bulanFormatted,
            $tahun,
        );

        $currentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if (empty($currentSnapshot)) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
                'has_perubahan_status' => false,
                'is_allocation_incomplete' => false,
                'has_honor_mismatch' => false,
            ];
        }

        $currentTotalHonor = collect($currentSnapshot)->sum(function (array $item): float {
            return (float) ($item['total_honor'] ?? 0) + (float) ($item['total_honor_listing'] ?? 0);
        });
        $hasHonorMismatch = abs($currentTotalHonor - (float) $referenceDocument->nilai_kontrak) > 0.01;
        $referenceKeys = array_keys($referenceSnapshot);
        $currentKeys = array_keys($currentSnapshot);

        $newKegiatanKeys = array_values(array_diff($currentKeys, $referenceKeys));
        $hasNewKegiatanAdded = ! empty($newKegiatanKeys);

        $hasAllocationChange = false;
        $hasPerubahanStatus = false;

        foreach (array_intersect($referenceKeys, $currentKeys) as $kegiatanId) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $reference || ! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey((int) ($current['periode_alokasi_id'] ?? 0))
                ->value('status');

            if ($currentStatus === 'perubahan') {
                $hasPerubahanStatus = true;
            }

            if (
                $current['alokasi_id'] !== $reference['alokasi_id'] &&
                $currentStatus !== 'perubahan'
            ) {
                $hasNewKegiatanAdded = true;
            }

            if (
                $currentStatus === 'perubahan' &&
                (
                    $current['peran'] !== $reference['peran'] ||
                    $current['jumlah_satuan'] !== $reference['jumlah_satuan'] ||
                    $current['jumlah_satuan_listing'] !== $reference['jumlah_satuan_listing'] ||
                    abs($current['total_honor'] - $reference['total_honor']) > 0.01 ||
                    abs($current['total_honor_listing'] - $reference['total_honor_listing']) > 0.01
                )
            ) {
                $hasAllocationChange = true;
            }
        }

        foreach (array_diff($currentKeys, $referenceKeys) as $kegiatanId) {
            $current = $currentSnapshot[$kegiatanId] ?? null;

            if (! $current) {
                continue;
            }

            $currentStatus = PeriodeAlokasi::query()
                ->whereKey((int) ($current['periode_alokasi_id'] ?? 0))
                ->value('status');

            if ($currentStatus === 'perubahan') {
                $hasPerubahanStatus = true;
            }

            if ($currentStatus !== 'perubahan') {
                $hasNewKegiatanAdded = true;
            }
        }

        return [
            'has_new_kegiatan_added' => $hasNewKegiatanAdded,
            'has_allocation_change' => $hasAllocationChange,
            'has_perubahan_status' => $hasPerubahanStatus,
            'is_allocation_incomplete' => $hasNewKegiatanAdded,
            'has_honor_mismatch' => $hasHonorMismatch,
        ];
    }

    /**
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */
    private function buildEffectiveAllocationSnapshotForPetugasFromDocument(
        int $petugasId,
        Spk $document,
        string $bulanFormatted,
        int $tahun,
    ): array {
        $alokasiIds = $document->alokasi_petugas_ids ?? [];
        if (empty($alokasiIds)) {
            $alokasiIds = [$document->alokasi_petugas_id];
        }

        $alokasi = AlokasiPetugas::query()
            ->whereIn('id', $alokasiIds)
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($tahun) {
                $q->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($alokasi->isEmpty()) {
            return [];
        }

        return $alokasi
            ->groupBy(function ($item) {
                return $item->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Apply same priority as buildEffectiveAllocationSnapshotForPetugas:
                // perubahan > disetujui > dikirim
                // This prevents a perpetual delta loop when a document stores both
                // dikirim and perubahan alokasi IDs for the same kegiatan.
                $effective = $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'perubahan')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'disetujui')
                    ?? $kegiatanGroup->first(fn ($a) => ($a->periodeAlokasi->status ?? '') === 'dikirim')
                    ?? $kegiatanGroup->first();

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
                    'alokasi_id' => (int) ($effective->id ?? 0),
                    'periode_alokasi_id' => (int) ($effective->periode_alokasi_id ?? 0),
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
            ->filter()
            ->sortKeys()
            ->all();
    }

    /**
     * Detect if there's a meaningful change between perubahan and direvisi allocations.
     *
     * A meaningful change means the perubahan allocation has different values
     * (peran, jumlah_satuan, total_honor) compared to the corresponding direvisi
     * allocation for the same kegiatan.
     *
     * This is more accurate than comparing document snapshots because the document
     * may already contain both direvisi and perubahan allocations for the same kegiatan.
     */
    private function detectMeaningfulPerubahanChange(Collection $alokasiGroup): bool
    {
        // Group allocations by kegiatan_id
        $byKegiatan = $alokasiGroup->groupBy(function ($alokasi) {
            return $alokasi->periodeAlokasi?->kegiatan_id;
        });

        foreach ($byKegiatan as $kegiatanAlokasi) {
            // Find perubahan allocation for this kegiatan
            $perubahan = $kegiatanAlokasi->first(function ($alokasi) {
                return ($alokasi->periodeAlokasi?->status ?? '') === 'perubahan';
            });

            if (! $perubahan) {
                continue;
            }

            // Use PK reference only from dikirim, then perubahan
            $reference = $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim')
                ?? $kegiatanAlokasi->first(fn ($a) => ($a->periodeAlokasi?->status ?? '') === 'perubahan');

            if (! $reference) {
                // perubahan exists but no reference - this is a new kegiatan via perubahan
                // which should also trigger addendum
                continue;
            }

            // Compare perubahan vs reference (dikirim/perubahan only)
            if (
                $perubahan->peran !== $reference->peran ||
                (int) ($perubahan->jumlah_satuan ?? 0) !== (int) ($reference->jumlah_satuan ?? 0) ||
                (int) ($perubahan->jumlah_satuan_listing ?? 0) !== (int) ($reference->jumlah_satuan_listing ?? 0) ||
                abs((float) ($perubahan->total_honor ?? 0) - (float) ($reference->total_honor ?? 0)) > 0.01 ||
                abs((float) ($perubahan->total_honor_listing ?? 0) - (float) ($reference->total_honor_listing ?? 0)) > 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two allocation snapshots match (have same effective values).
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot1
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot2
     */
    private function snapshotsMatch(array $snapshot1, array $snapshot2): bool
    {
        // Different kegiatan sets = not matching (compare sorted keys to ignore order)
        $keys1 = array_keys($snapshot1);
        $keys2 = array_keys($snapshot2);
        sort($keys1);
        sort($keys2);
        if ($keys1 !== $keys2) {
            return false;
        }

        foreach ($snapshot1 as $kegiatanId => $data1) {
            $data2 = $snapshot2[$kegiatanId] ?? null;
            if (! $data2) {
                return false;
            }

            // Compare effective values (ignore alokasi_id and periode_alokasi_id which may differ)
            if (
                $data1['peran'] !== $data2['peran'] ||
                $data1['jumlah_satuan'] !== $data2['jumlah_satuan'] ||
                $data1['jumlah_satuan_listing'] !== $data2['jumlah_satuan_listing'] ||
                abs($data1['total_honor'] - $data2['total_honor']) > 0.01 ||
                abs($data1['total_honor_listing'] - $data2['total_honor_listing']) > 0.01
            ) {
                return false;
            }
        }

        return true;
    }

    private function hasAllocationDeltaAfterReferenceForPetugas(int $petugasId, string $bulanFormatted, int $tahun, DateTimeInterface|string|null $referenceCreatedAt): bool
    {
        $referenceSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            $referenceCreatedAt,
        );

        $currentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            null,
        );

        if (empty($currentSnapshot)) {
            return false;
        }

        if (array_keys($referenceSnapshot) !== array_keys($currentSnapshot)) {
            return true;
        }

        foreach ($currentSnapshot as $kegiatanId => $current) {
            $reference = $referenceSnapshot[$kegiatanId] ?? null;

            if (! $reference) {
                return true;
            }

            if (
                $current['alokasi_id'] !== $reference['alokasi_id'] ||
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

    /**
     * Build latest effective allocation snapshot keyed by kegiatan_id.
     *
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */
    private function buildEffectiveAllocationSnapshotForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        DateTimeInterface|string|null $upToCreatedAt,
    ): array {
        // Get all allocations for this petugas in this month (reference PK statuses only)
        $alokasiQuery = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun, $upToCreatedAt) {
                $q->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->whereHas('kegiatan', fn ($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));

                // When checking reference state, only get allocations that existed before
                if ($upToCreatedAt) {
                    $q->where('created_at', '<=', $upToCreatedAt);
                }
            })
            ->with('periodeAlokasi:id,kegiatan_id,status,created_at')
            ->get();

        if ($alokasiQuery->isEmpty()) {
            return [];
        }

        // Group by kegiatan and get effective allocation per kegiatan.
        // Priority: dikirim > perubahan.
        $snapshot = $alokasiQuery
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Apply priority: dikirim > perubahan
                $effective = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim')
                    ?? $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
                    'alokasi_id' => (int) ($effective->id ?? 0),
                    'periode_alokasi_id' => (int) ($effective->periode_alokasi_id ?? 0),
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
            ->filter()
            ->sortKeys()
            ->all();

        return $snapshot;
    }

    /**
     * Get list of petugas names for a specific month (sorted alphabetically)
     */
    public function getPetugasNames(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $periodeHashedId = $request->input('periode_hashed_id');

        $periode = null;
        if ($periodeHashedId) {
            $periodeId = Hashids::decode($periodeHashedId)[0] ?? null;
            if ($periodeId) {
                $periode = PeriodeAlokasi::with('kegiatan')->find($periodeId);
            }
        }

        if (! $periode && (! $bulan || ! $tahun)) {
            return response()->json(['error' => 'Bulan dan tahun harus diisi'], 400);
        }

        if ($periode) {
            $allPeriodeInMonth = $this->resolveSpkScopePeriodeIds(
                $periode,
                ['dikirim', 'disetujui', 'direvisi', 'perubahan']
            );
        } else {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

            $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
                ->where('tahun', $tahun)
                ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                ->whereHas('kegiatan', function ($q) use ($tahun) {
                    $q->where('tahun_anggaran', $tahun);
                })
                ->pluck('id');
        }

        // Get unique petugas IDs that will get SPK (non-organik with honor > 0)
        $petugasIds = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->distinct()
            ->pluck('petugas_id');

        // Get petugas names and sort alphabetically
        $petugasNames = Petugas::whereIn('id', $petugasIds)
            ->orderBy('nama')
            ->pluck('nama')
            ->toArray();

        return response()->json([
            'names' => $petugasNames,
            'count' => count($petugasNames),
        ]);
    }
}
