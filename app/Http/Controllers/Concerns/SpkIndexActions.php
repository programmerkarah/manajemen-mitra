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

trait SpkIndexActions
{
    public function index(FilterRequest $request): Response|RedirectResponse
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();
        $canAccessSensusMode = $this->canAccessSensusMode($this->getRequestUser($request), $activeYear);

        // Mode halaman disimpan di session. URL tetap /spk sehingga refresh browser
        // tidak mengulang POST dan tidak mengekspos parameter mode.
        $session = $request->hasSession() ? $request->session() : null;
        $requestedMode = (string) ($session?->get('spk_index_mode', 'regular') ?? 'regular');
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';

        if ($mode !== $requestedMode && $session) {
            $session->put('spk_index_mode', $mode);
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

            // Addendum indicators follow the same decision service used by
            // Generate/Addendum actions. They are intentionally shown only
            // for the active month; historical months remain informational.
            $isCurrentMonth = (int) now()->year === $tahun
                && (int) now()->month === $bulan;

            $hasIncompleteAddendum = ! $isPeriodBased
                && $isCurrentMonth
                && (int) ($actionCounts['generate_addendum'] ?? 0) > 0;

            $hasAddendumChanges = ! $isPeriodBased
                && $isCurrentMonth
                && (int) ($actionCounts['regenerate_addendum'] ?? 0) > 0;

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
