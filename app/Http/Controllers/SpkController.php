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

class SpkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response|RedirectResponse
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();
        $requestedMode = (string) $request->input('mode', 'regular');
        $canAccessSensusMode = $this->canAccessSensusMode($this->getRequestUser($request), $activeYear);

        if ($requestedMode === 'sensus-ekonomi' && ! $canAccessSensusMode) {
            return redirect()->route('spk.index', ['mode' => 'regular']);
        }

        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';

        // Get periode alokasi yang sudah validated grouped by month
        $query = PeriodeAlokasi::query()
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tahun_anggaran',
                'alokasiPetugas:id,periode_alokasi_id,petugas_id,total_honor,total_honor_listing,is_partial_payment,estimasi_honor_partial,is_partial_payment_listing,estimasi_honor_partial_listing',
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
                $q->whereHas('kegiatan', fn($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));
            })
            ->when($mode === 'sensus-ekonomi', function ($q) {
                $q->whereHas('kegiatan', fn($qq) => $qq->where('jenis_kegiatan', 'sensus'));
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
            $primaryPeriode = $monthPeriodes->first();
            $tahun = (int) $primaryPeriode->tahun;
            $bulan = (int) $primaryPeriode->bulan;
            $isPeriodBased = $this->usesPeriodBasedSpkFlow($primaryPeriode);

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
                        // Priority: perubahan > direvisi > disetujui > dikirim
                        $perubahan = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'perubahan');
                        if ($perubahan) {
                            return $perubahan;
                        }

                        $direvisi = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'direvisi');
                        if ($direvisi) {
                            return $direvisi;
                        }

                        $disetujui = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'disetujui');
                        if ($disetujui) {
                            return $disetujui;
                        }

                        return $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'dikirim');
                    })->filter();

                    // Only include petugas if they have positive effective honor (respects partial payment)
                    $hasPositiveHonor = $effectiveAlokasi->contains(
                        fn($alokasi) => $this->hasPositiveEffectiveHonor($alokasi)
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
                        // Priority: perubahan > direvisi > disetujui > dikirim
                        $perubahan = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'perubahan');
                        if ($perubahan) {
                            return $perubahan;
                        }

                        $direvisi = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'direvisi');
                        if ($direvisi) {
                            return $direvisi;
                        }

                        $disetujui = $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'disetujui');
                        if ($disetujui) {
                            return $disetujui;
                        }

                        return $kegiatanAlokasi->first(fn($a) => $a->periodeAlokasi->status === 'dikirim');
                    });
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
                'is_period_based' => $isPeriodBased,
                'has_new_revision_after_addendum' => $hasNewRevisionAfterAddendum,
                'has_been_regenerated' => $hasBeenRegenerated,
                'has_incomplete_addendum' => $hasIncompleteAddendum,
                'has_addendum_changes' => $hasAddendumChanges,
                'kegiatan_list' => $kegiatanList,
            ];
        })->sortByDesc(function ($item) {
            return $item['tahun'] . str_pad($item['bulan'], 2, '0', STR_PAD_LEFT);
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
        $decrypted = [];
        if ($request->filled('state')) {
            $decrypted = decryptFilters((string) $request->query('state'));
        }

        $bulan = $decrypted['bulan'] ?? $request->query('bulan');
        $tahun = $decrypted['tahun'] ?? $request->query('tahun');
        $spkHashedId = $decrypted['spk'] ?? $request->query('spk');
        $periodeHashedId = $decrypted['periode_hashed_id'] ?? $request->query('periode_hashed_id');

        return $this->renderShowByMonth($request, $bulan, $tahun, $spkHashedId, $periodeHashedId);
    }

    /**
     * Show SPK for a specific month with petugas list (POST version)
     */
    public function showByMonth(Request $request): Response|RedirectResponse
    {
        // Decrypt payload
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $spkHashedId = $request->input('spk');
        $periodeHashedId = $request->input('periode_hashed_id');

        return $this->renderShowByMonth($request, $bulan, $tahun, $spkHashedId, $periodeHashedId);
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
            ->map(fn($id) => (int) $id)
            ->values();

        // Get all SPKs in this month
        $allSpks = Spk::with(['alokasiPetugas.petugas'])
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
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor for original periode (only from that specific periode)
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
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

        // Build petugas list for sidebar
        $petugasList = $allSpks->map(function ($s) {
            // Get the latest SPK document (including addendums) for this petugas
            $originalSpkId = $s->parent_spk_id ?: $s->id;
            $latestSpkDoc = Spk::where(function ($q) use ($originalSpkId) {
                $q->where('id', $originalSpkId)
                    ->orWhere('parent_spk_id', $originalSpkId);
            })
                ->orderBy('addendum_number', 'desc')
                ->first();

            return [
                'id' => $s->id,
                'hashed_id' => $s->hashed_id,
                'nomor_spk' => $s->nomor_spk,
                'petugas_nama' => $this->formatDisplayName($s->alokasiPetugas->petugas->nama),
                'petugas_nik' => $s->alokasiPetugas->petugas->nik,
                'status' => $s->status,
                'file_path' => $latestSpkDoc?->file_path,
                'signed_file_path' => $latestSpkDoc?->signed_file_path,
            ];
        })->sortBy('petugas_nama')->values()->all();

        // Get all unique kegiatan in this month with SPK count
        $allKegiatanIds = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->distinct()
            ->pluck('periode_alokasi_id');

        $uniqueKegiatanList = PeriodeAlokasi::whereIn('id', $allKegiatanIds)
            ->with('kegiatan')
            ->get()
            ->groupBy('kegiatan_id')
            ->map(function ($periodeGroup) use ($allSpks, $bulanFormatted, $tahun) {
                $kegiatan = $periodeGroup->first()->kegiatan;

                // Get all petugas who are allocated to this kegiatan in this month/year
                // This matches the download logic
                $petugasIdsInKegiatan = DB::table('alokasi_petugas')
                    ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                    ->where('periode_alokasi.kegiatan_id', $kegiatan->id)
                    ->where('periode_alokasi.bulan', $bulanFormatted)
                    ->where('periode_alokasi.tahun', $tahun)
                    ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan', 'direvisi'])
                    ->distinct()
                    ->pluck('alokasi_petugas.petugas_id');

                // Get SPKs for these petugas in this month
                $spksForKegiatan = $allSpks->filter(function ($spk) use ($petugasIdsInKegiatan) {
                    return $petugasIdsInKegiatan->contains($spk->petugas_id);
                })->unique('petugas_id');

                $spkCount = $spksForKegiatan->count();

                // Check if ALL petugas have signed SPKs and the files exist physically
                $allSigned = $spksForKegiatan->every(function ($spk) {
                    return ! empty($spk->signed_file_path) && file_exists(public_path($spk->signed_file_path));
                });

                return [
                    'id' => $kegiatan->id,
                    'hashed_id' => $kegiatan->hashed_id,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'jumlah_spk' => $spkCount,
                    'all_signed' => $allSigned,
                ];
            })
            ->filter(function ($kegiatan) {
                // Only show kegiatan where jumlah_spk > 0 AND all SPKs are signed
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
                    'status' => $s->status,
                    'created_by' => $s->createdBy->name ?? 'System',
                    'created_at' => $s->created_at->format('d M Y H:i'),
                    'updated_at' => $s->updated_at->format('d M Y H:i'),
                ];
            });

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
            'addendum_number' => $spk->addendum_number,
            'parent_spk_id' => $spk->parent_spk_id,
            'created_by' => $spk->createdBy->name ?? 'System',
            'created_at' => $spk->created_at->format('d M Y H:i'),
            'updated_at' => $spk->updated_at->format('d M Y H:i'),
        ];
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
        ]);
    }

    /**
     * Download all SPK files in a month as ZIP
     */
    public function downloadAll(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index')->with('error', 'Bulan dan tahun harus diisi');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Ambil semua SPK utama (addendum_number = 0)
        $mainSpks = Spk::with(['alokasiPetugas.petugas'])
            ->where('addendum_number', 0)
            ->whereNotNull('file_path')
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get();

        // Ambil semua addendum yang sudah signed (addendum_number > 0 dan signed_file_path != null)
        $addendumSpks = Spk::with(['alokasiPetugas.petugas'])
            ->where('addendum_number', '>', 0)
            ->whereNotNull('signed_file_path')
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get();

        if ($mainSpks->isEmpty() && $addendumSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada SPK/addendum yang sudah ditandatangani untuk diunduh');
        }

        // Create ZIP file with deterministic name (no timestamp)
        $zip = new \ZipArchive;
        $bulanLabel = $this->getBulanLabel((int) $bulan);
        $zipFileName = "SPK_{$bulanLabel}_{$tahun}.zip";

        // Ensure downloads directory exists
        $downloadsDir = public_path('downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir . '/' . $zipFileName;

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
        // Masukkan SPK utama
        foreach ($mainSpks as $spk) {
            $fileToUse = $spk->signed_file_path ?? $spk->file_path;
            $filePath = public_path($fileToUse);
            if (file_exists($filePath)) {
                $fileName = basename($fileToUse);
                $zip->addFile($filePath, $fileName);
                $filesAdded++;
            }
        }

        // Masukkan addendum yang sudah signed
        foreach ($addendumSpks as $spk) {
            $filePath = public_path($spk->signed_file_path);
            if (file_exists($filePath)) {
                $fileName = basename($spk->signed_file_path);
                // Tambahkan keterangan addendum pada nama file
                $zipFileNameInArchive = preg_replace('/\.pdf$/i', '', $fileName) . '_ADDENDUM.pdf';
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
            ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan', 'direvisi'])
            ->distinct()
            ->pluck('alokasi_petugas.petugas_id');

        // Get all periodes in the same month and year (for any kegiatan)
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'perubahan', 'direvisi'])
            ->pluck('id');

        // Ambil semua SPK utama (addendum_number = 0)
        $mainSpks = Spk::with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where('addendum_number', 0)
            ->whereNotNull('file_path')
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->get();

        // Ambil semua addendum (addendum_number > 0) yang memiliki file
        $addendumSpks = Spk::with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where('addendum_number', '>', 0)
            ->where(function ($query) {
                // Ambil addendum yang memiliki signed_file_path ATAU file_path
                $query->whereNotNull('signed_file_path')
                    ->orWhereNotNull('file_path');
            })
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
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
        $downloadsDir = public_path('downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir . '/' . $zipFileName;

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
    public function downloadByKegiatanMonth(Request $request, string $kegiatanHashedId)
    {
        $kegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;
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

        // Get ALL SPKs for these petugas in this month/year, regardless of which kegiatan the SPK was created for
        $allSpks = Spk::with(['alokasiPetugas.petugas', 'alokasiPetugas.periodeAlokasi.kegiatan'])
            ->where(function ($q) {
                $q->whereNotNull('file_path')
                    ->orWhereNotNull('signed_file_path');
            })
            ->whereIn('petugas_id', $petugasIdsInKegiatan)
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
            })
            ->orderBy('nomor_spk')
            ->orderBy('addendum_number')
            ->get();

        if ($allSpks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada SPK untuk kegiatan ini di periode tersebut.');
        }

        // Check if all SPKs have files that exist physically
        $missingFiles = $allSpks->filter(function ($spk) {
            // Check either signed_file_path or file_path
            $fileToUse = $spk->signed_file_path ?? $spk->file_path;

            if (empty($fileToUse)) {
                return true; // Missing file
            }

            return ! file_exists(public_path($fileToUse)); // File path exists but file doesn't
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
        $downloadsDir = public_path('downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $zipPath = $downloadsDir . '/' . $zipFileName;

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
     * Get next nomor urut for SPK based on year
     */
    private function getNextNomorUrut(int $tahun): int
    {
        // Get last SPK number for the given year
        $lastSpk = Spk::where('nomor_spk', 'like', "PPIS/13730/%/K/{$tahun}")
            ->orderByRaw('CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(nomor_spk, "/", 3), "/", -1) AS UNSIGNED) DESC')
            ->first();

        if (! $lastSpk) {
            return 1;
        }

        // Extract nomor urut from format: PPIS/13730/4/K/2025
        $parts = explode('/', $lastSpk->nomor_spk);
        $lastUrut = isset($parts[2]) ? (int) $parts[2] : 0;

        return $lastUrut + 1;
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

        return 'PPIS/13730/' . $nomorUrut . '/K/' . $periode->tahun;
    }

    private function generateSignedDownloadUrl(string $filename): string
    {
        // Return direct static URL untuk better CDN caching
        // File di-serve langsung oleh web server (Nginx/Apache), bukan PHP
        return '/downloads/' . rawurlencode($filename);
    }

    private function resolvePreferredSpkFilePathForZip(Spk $spk): ?string
    {
        $candidates = collect([$spk->signed_file_path, $spk->file_path])
            ->filter(fn($path) => is_string($path) && trim($path) !== '')
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

        return preg_match('/add(?:endum)?[_\-]?(?:no[_\-]?)?' . $escapedNumber . '(?!\d)/i', $path) === 1
            || preg_match('/add-' . $escapedNumber . '(?!\d)/i', $path) === 1;
    }

    private function buildZipFilenameForSpk(Spk $spk, string $sourcePath): string
    {
        $petugasName = preg_replace('/[\/\\:*?"<>|]/', '_', $spk->alokasiPetugas->petugas->nama);
        $fileName = basename($sourcePath);

        if ((int) ($spk->addendum_number ?? 0) > 0) {
            $baseFileName = preg_replace('/\.pdf$/i', '', $fileName);
            $baseFileName = preg_replace('/(?:_ADDENDUM_\d+|_ADD-\d+)$/i', '', (string) $baseFileName);

            return "{$petugasName}_{$baseFileName}_ADDENDUM_{$spk->addendum_number}.pdf";
        }

        return "{$petugasName}_{$fileName}";
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

    /**
     * Public page for petugas to find and preview/download SPK draft document.
     */
    public function publicPreviewForm(Request $request): Response
    {
        $activeYear = ActiveYearService::get();

        return Inertia::render('Spk/PublicPreview', [
            'survei_periods' => [],
            'sensus_kegiatans' => [],
            'penugasan_list' => [],
            'active_year' => $activeYear,
            'recaptcha_site_key' => (string) config('services.recaptcha.site_key', ''),
        ]);
    }

    public function publicPreviewOptions(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:64'],
            'telepon_4_digit' => ['required', 'string', 'regex:/^\d{4}$/'],
            'recaptcha_token' => ['required', 'string', 'max:8192'],
        ]);

        if (! $this->isValidPublicPreviewRecaptcha((string) $validated['recaptcha_token'], $request->ip())) {
            return response()->json([
                'message' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
            ], 422);
        }

        $petugas = $this->resolvePublicPreviewPetugas(
            (string) $validated['nama'],
            (string) $validated['nik']
        );

        if (! $petugas) {
            return response()->json([
                'message' => 'Petugas dengan Nama dan NIK tersebut tidak ditemukan.',
            ], 404);
        }

        if (! $this->matchesPublicPreviewPhoneVerification($petugas, (string) $validated['telepon_4_digit'])) {
            return response()->json([
                'message' => 'Verifikasi 4 digit nomor HP tidak sesuai.',
            ], 422);
        }

        $request->session()->put('mitra_preview_verified', [
            'signature' => $this->buildPublicPreviewSessionSignature(
                (string) $validated['nama'],
                (string) $validated['nik'],
                (string) $validated['telepon_4_digit'],
            ),
            'verified_at' => now()->timestamp,
        ]);

        $options = $this->resolvePublicPreviewOptionsForPetugas($petugas, ActiveYearService::get());

        return response()->json([
            'petugas_nama' => $petugas->nama,
            'survei_periods' => $options['survei_periods'],
            'sensus_kegiatans' => $options['sensus_kegiatans'],
            'penugasan_list' => $options['penugasan_list'],
        ]);
    }

    /**
     * Public preview/download action for SPK.
     */
    public function publicPreviewDownload(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:64'],
            'telepon_4_digit' => ['required', 'string', 'regex:/^\d{4}$/'],
            'jenis_kegiatan' => ['required', 'in:survei,sensus'],
            'survei_periode' => ['nullable', 'string'],
            'sensus_kegiatan' => ['nullable', 'string'],
            'recaptcha_token' => ['nullable', 'string', 'max:8192'],
            'aksi' => ['nullable', 'in:preview,download'],
            'response_mode' => ['nullable', 'in:binary,url'],
            'download_token' => ['nullable', 'string', 'max:120'],
            'dokumen_tipe' => ['nullable', 'in:pk,bast,bapp'],
            'bapp_termin' => ['nullable', 'in:1,2'],
        ]);

        $hasRecentSessionVerification = $this->hasRecentPublicPreviewSessionVerification(
            $request,
            (string) $validated['nama'],
            (string) $validated['nik'],
            (string) $validated['telepon_4_digit'],
        );

        if (! $hasRecentSessionVerification) {
            if (! $this->isValidPublicPreviewRecaptcha((string) ($validated['recaptcha_token'] ?? ''), $request->ip())) {
                return response()->json([
                    'message' => 'Sesi verifikasi berakhir. Klik "Muat Data" kembali.',
                ], 422);
            }
        }

        $petugas = $this->resolvePublicPreviewPetugas(
            (string) $validated['nama'],
            (string) $validated['nik']
        );

        if (! $petugas) {
            return response()->json([
                'message' => 'Petugas dengan Nama dan NIK tersebut tidak ditemukan.',
            ], 404);
        }

        if (! $this->matchesPublicPreviewPhoneVerification($petugas, (string) $validated['telepon_4_digit'])) {
            return response()->json([
                'message' => 'Verifikasi 4 digit nomor HP tidak sesuai.',
            ], 422);
        }

        $jenisKegiatan = (string) $validated['jenis_kegiatan'];
        $periode = null;
        $selectedKegiatanId = null;

        if ($jenisKegiatan === 'survei') {
            $surveiPeriode = (string) ($validated['survei_periode'] ?? '');
            if (! preg_match('/^\d{4}-\d{2}$/', $surveiPeriode)) {
                return response()->json([
                    'message' => 'Periode survei tidak valid.',
                ], 422);
            }

            [$tahun, $bulan] = explode('-', $surveiPeriode);
            $bulanFormatted = str_pad((string) ((int) $bulan), 2, '0', STR_PAD_LEFT);

            $hasDraftSurvei = PeriodeAlokasi::query()
                ->where('tahun', (int) $tahun)
                ->where('bulan', $bulanFormatted)
                ->where('status', 'draft')
                ->whereHas('kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'survei');
                })
                ->exists();

            if ($hasDraftSurvei) {
                return response()->json([
                    'message' => 'Preview SPK survei belum dapat dilakukan karena masih ada kegiatan draft pada bulan tersebut.',
                ], 422);
            }

            $periode = PeriodeAlokasi::query()
                ->where('tahun', (int) $tahun)
                ->where('bulan', $bulanFormatted)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->whereHas('kegiatan', function ($query): void {
                    $query->where('jenis_kegiatan', 'survei');
                })
                ->first();

            if (! $periode) {
                return response()->json([
                    'message' => 'Periode survei yang dipilih belum siap untuk preview SPK.',
                ], 422);
            }
        }

        if ($jenisKegiatan === 'sensus') {
            $kegiatanHashedId = (string) ($validated['sensus_kegiatan'] ?? '');
            $selectedKegiatanId = Hashids::decode($kegiatanHashedId)[0] ?? null;

            if (! $selectedKegiatanId) {
                return response()->json([
                    'message' => 'Jenis kegiatan sensus tidak valid.',
                ], 422);
            }

            $periode = PeriodeAlokasi::query()
                ->where('kegiatan_id', (int) $selectedKegiatanId)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->orderByDesc('revision_number')
                ->orderByDesc('id')
                ->first();

            if (! $periode) {
                return response()->json([
                    'message' => 'Kegiatan sensus belum dikirim sehingga preview belum tersedia.',
                ], 422);
            }
        }

        if (! $periode) {
            return response()->json([
                'message' => 'Data periode tidak ditemukan.',
            ], 422);
        }

        $hasMatchingAlokasi = AlokasiPetugas::query()
            ->where('petugas_id', $petugas->id)
            ->whereHas('periodeAlokasi', function ($query) use ($periode, $jenisKegiatan, $selectedKegiatanId): void {
                $query->where('tahun', $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan, $selectedKegiatanId): void {
                        $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);

                        if ($selectedKegiatanId !== null) {
                            $kegiatanQuery->where('id', $selectedKegiatanId);
                        }
                    });
            })
            ->where(function ($query): void {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->exists();

        if (! $hasMatchingAlokasi) {
            return response()->json([
                'message' => 'Petugas tidak memiliki alokasi Perjanjian Kerja yang sesuai kriteria.',
            ], 422);
        }

        $dokumenTipe = (string) ($validated['dokumen_tipe'] ?? 'pk');
        $bappTermin = max(1, min(2, (int) ($validated['bapp_termin'] ?? 1)));

        if ($dokumenTipe === 'bast') {
            return $this->servePublicPreviewBast($petugas, $periode, $selectedKegiatanId, $jenisKegiatan, $validated);
        }

        if ($dokumenTipe === 'bapp') {
            if ($jenisKegiatan !== 'sensus') {
                return response()->json(['message' => 'BAPP hanya tersedia untuk kegiatan sensus.'], 422);
            }

            return $this->servePublicPreviewBapp($petugas, ActiveYearService::get(), $bappTermin, $validated);
        }

        $finalSignedPdf = $this->resolveFinalSignedSpkPdfBinaryForPublicPreview(
            $periode,
            (int) $petugas->id,
            $selectedKegiatanId,
            $jenisKegiatan,
        );

        $responseFilename = null;
        $sourcePdfContent = null;
        $protectedPdfContent = null;
        $protectedPdfPath = null;

        if ($finalSignedPdf !== null) {
            $responseFilename = $finalSignedPdf['filename'];

            if (($finalSignedPdf['is_protected'] ?? false) === true) {
                $protectedPdfPath = $finalSignedPdf['protected_path'] ?? null;
            } else {
                $sourcePdfContent = $finalSignedPdf['content'];
            }
        } else {
            $nomorSpkPreview = $this->formatPreviewNomorSpkForPeriode(
                $periode,
                $this->getNextNomorUrutForPeriode($periode)
            );

            $pdfPreview = $this->buildMergedSpkPreviewBinary(
                $periode,
                (int) $petugas->id,
                $nomorSpkPreview,
                now()->toDateString(),
                $selectedKegiatanId,
                $jenisKegiatan
            );

            if ($pdfPreview === null) {
                return response()->json([
                    'message' => 'Preview SPK tidak dapat dibuat untuk data ini.',
                ], 422);
            }

            $sourcePdfContent = $pdfPreview['content'];
            $responseFilename = $pdfPreview['filename'];
        }

        if ($protectedPdfContent === null && $protectedPdfPath === null) {
            if (! is_string($sourcePdfContent) || $sourcePdfContent === '') {
                return response()->json([
                    'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            $protectedPdfContent = $this->applyDraftWatermarkAndProtection($sourcePdfContent);

            if ($finalSignedPdf !== null && isset($finalSignedPdf['cache_key'])) {
                $this->storeCachedProtectedPublicPreviewPdf((string) $finalSignedPdf['cache_key'], $protectedPdfContent);
                $protectedPdfPath = $this->getCachedProtectedPublicPreviewPdfPath((string) $finalSignedPdf['cache_key']);
            }
        }

        $disposition = ($validated['aksi'] ?? 'preview') === 'download' ? 'attachment' : 'inline';
        $responseMode = (string) ($validated['response_mode'] ?? 'binary');
        $downloadToken = (string) ($validated['download_token'] ?? '');

        if ($responseMode === 'url' && $disposition === 'inline') {
            if (! is_string($protectedPdfPath) || ! is_file($protectedPdfPath)) {
                if (! is_string($protectedPdfContent) || $protectedPdfContent === '') {
                    return response()->json([
                        'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                    ], 422);
                }

                $protectedPdfPath = $this->storePublicPreviewTemporaryPdf($protectedPdfContent);
            }

            if (! is_string($protectedPdfPath) || ! is_file($protectedPdfPath)) {
                return response()->json([
                    'message' => 'File preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            $previewUrl = $this->buildPublicPreviewSignedFileUrl(
                $protectedPdfPath,
                (string) $responseFilename,
                'inline',
            );

            if ($previewUrl === null) {
                return response()->json([
                    'message' => 'URL preview tidak tersedia. Silakan coba beberapa saat lagi.',
                ], 422);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => (string) $responseFilename,
            ]);
        }

        if (is_string($protectedPdfPath) && is_file($protectedPdfPath)) {
            return $this->buildPublicPreviewFileResponse($protectedPdfPath, (string) $responseFilename, $disposition, $downloadToken);
        }

        $response = response($protectedPdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $responseFilename . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function buildPublicPreviewSessionSignature(string $nama, string $nik, string $telepon4Digit): string
    {
        return hash('sha256', mb_strtolower(trim($nama)) . '|' . trim($nik) . '|' . trim($telepon4Digit));
    }

    private function hasRecentPublicPreviewSessionVerification(
        Request $request,
        string $nama,
        string $nik,
        string $telepon4Digit,
    ): bool {
        $payload = $request->session()->get('mitra_preview_verified');

        if (! is_array($payload)) {
            return false;
        }

        $verifiedAt = (int) ($payload['verified_at'] ?? 0);
        if ($verifiedAt <= 0 || (now()->timestamp - $verifiedAt) > 900) {
            return false;
        }

        $signature = (string) ($payload['signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        return hash_equals($signature, $this->buildPublicPreviewSessionSignature($nama, $nik, $telepon4Digit));
    }

    /**
     * @return array{filename:string,content?:string,cache_key:string,is_protected:bool,protected_path?:string}|null
     */
    private function resolveFinalSignedSpkPdfBinaryForPublicPreview(
        PeriodeAlokasi $periode,
        int $petugasId,
        ?int $kegiatanId = null,
        ?string $jenisKegiatan = null,
    ): ?array {
        $finalSpk = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereNotNull('signed_file_path')
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($periode, $kegiatanId, $jenisKegiatan): void {
                $query->where('tahun', $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->orderByDesc('addendum_number')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $finalSpk || ! $finalSpk->signed_file_path) {
            return null;
        }

        $rootSpkId = $finalSpk->parent_spk_id ?: $finalSpk->id;

        $signedDocuments = Spk::query()
            ->where('petugas_id', $petugasId)
            ->where(function ($query) use ($rootSpkId): void {
                $query->where('id', $rootSpkId)
                    ->orWhere('parent_spk_id', $rootSpkId);
            })
            ->whereNotNull('signed_file_path')
            ->orderBy('addendum_number')
            ->orderBy('id')
            ->get(['id', 'signed_file_path', 'addendum_number']);

        $signedPaths = $signedDocuments
            ->map(fn(Spk $spk): string => public_path((string) $spk->signed_file_path))
            ->filter(fn(string $path): bool => is_file($path))
            ->values()
            ->all();

        if (empty($signedPaths)) {
            return null;
        }

        $cacheKey = $this->buildPublicPreviewProtectedCacheKey($signedPaths);

        $baseName = pathinfo((string) $finalSpk->signed_file_path, PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $baseName) ?: 'spk_final';

        $downloadFilename = count($signedPaths) === 1
            ? 'Preview_' . $safeBaseName . '.pdf'
            : 'Preview_' . $safeBaseName . '_with_addendum.pdf';

        $cachedProtectedPath = $this->getCachedProtectedPublicPreviewPdfPath($cacheKey);
        if ($cachedProtectedPath !== null) {
            return [
                'filename' => $downloadFilename,
                'cache_key' => $cacheKey,
                'is_protected' => true,
                'protected_path' => $cachedProtectedPath,
            ];
        }

        if (count($signedPaths) === 1) {
            $binaryContent = file_get_contents($signedPaths[0]);
            if (! is_string($binaryContent) || $binaryContent === '') {
                return null;
            }

            return [
                'filename' => $downloadFilename,
                'content' => $binaryContent,
                'cache_key' => $cacheKey,
                'is_protected' => false,
            ];
        }

        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return null;
        }

        $token = time() . '_' . uniqid();
        $mergedPath = $tempPath . '/spk_public_preview_signed_merge_' . $token . '.pdf';

        try {
            $merged = PdfMergerService::mergePdfFiles($signedPaths, $mergedPath);
            if (! $merged || ! is_file($mergedPath)) {
                return null;
            }

            $mergedContent = file_get_contents($mergedPath);
            if (! is_string($mergedContent) || $mergedContent === '') {
                return null;
            }

            return [
                'filename' => $downloadFilename,
                'content' => $mergedContent,
                'cache_key' => $cacheKey,
                'is_protected' => false,
            ];
        } finally {
            @unlink($mergedPath);
        }
    }

    /**
     * @param  array<int,string>  $absolutePdfPaths
     */
    private function buildPublicPreviewProtectedCacheKey(array $absolutePdfPaths): string
    {
        $fingerprint = collect($absolutePdfPaths)
            ->map(function (string $path): string {
                $realPath = realpath($path) ?: $path;
                $modifiedTime = (string) (@filemtime($path) ?: 0);
                $fileSize = (string) (@filesize($path) ?: 0);

                return $realPath . '|' . $modifiedTime . '|' . $fileSize;
            })
            ->implode('||');

        return hash('sha256', 'public-preview-v2|' . $fingerprint);
    }

    private function getCachedProtectedPublicPreviewPdfPath(string $cacheKey): ?string
    {
        $cachePath = storage_path('app/temp/public_preview_protected_' . $cacheKey . '.pdf');

        if (! is_file($cachePath)) {
            return null;
        }

        return $cachePath;
    }

    private function storeCachedProtectedPublicPreviewPdf(string $cacheKey, string $protectedPdfContent): void
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return;
        }

        @file_put_contents($tempPath . '/public_preview_protected_' . $cacheKey . '.pdf', $protectedPdfContent);
    }

    private function storePublicPreviewTemporaryPdf(string $pdfContent): ?string
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return null;
        }

        try {
            $filename = 'public_preview_runtime_' . bin2hex(random_bytes(16)) . '.pdf';
        } catch (\Throwable) {
            $filename = 'public_preview_runtime_' . uniqid('', true) . '.pdf';
        }

        $filePath = $tempPath . '/' . $filename;
        if (@file_put_contents($filePath, $pdfContent) === false) {
            return null;
        }

        return $filePath;
    }

    private function buildPublicPreviewSignedFileUrl(string $filePath, string $responseFilename, string $disposition = 'inline'): ?string
    {
        if (! is_file($filePath)) {
            return null;
        }

        $file = basename($filePath);
        if ($file === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            return null;
        }

        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $responseFilename) ?: 'Preview_SPK.pdf';
        $safeDisposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return URL::temporarySignedRoute(
            'spk.public-preview.file',
            now()->addMinutes(10),
            [
                'file' => $file,
                'filename' => $safeFilename,
                'disposition' => $safeDisposition,
            ],
        );
    }

    public function publicPreviewFile(Request $request, string $file)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            abort(404);
        }

        $filePath = storage_path('app/temp/' . $file);
        if (! is_file($filePath)) {
            abort(404);
        }

        $filename = (string) $request->query('filename', 'Preview_SPK.pdf');
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename) ?: 'Preview_SPK.pdf';
        $disposition = (string) $request->query('disposition', 'inline');
        $safeDisposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $this->buildPublicPreviewFileResponse($filePath, $safeFilename, $safeDisposition, '', 600);
    }

    private function ensureDirectoryExists(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, 0777, true) || is_dir($path);
    }

    private function buildPublicPreviewFileResponse(string $filePath, string $responseFilename, string $disposition, string $downloadToken = '', int $cacheSeconds = 0)
    {
        $cacheControl = $cacheSeconds > 0
            ? 'public, max-age=' . $cacheSeconds . ', immutable'
            : 'no-cache, must-revalidate';

        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => $cacheControl,
            'Expires' => $cacheSeconds > 0 ? gmdate('D, d M Y H:i:s', time() + $cacheSeconds) . ' GMT' : '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($disposition === 'attachment') {
            $response = response()->download($filePath, $responseFilename, $headers);

            return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
        }

        $response = response()->file($filePath, $headers + [
            'Content-Disposition' => 'inline; filename="' . $responseFilename . '"',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function appendPublicPreviewDownloadCookie(mixed $response, string $disposition, string $downloadToken): mixed
    {
        if ($disposition !== 'attachment') {
            return $response;
        }

        $token = trim($downloadToken);
        if ($token === '') {
            return $response;
        }

        return $response->cookie(cookie('mitra_download_token', $token, 2, '/', null, false, false, false, 'Lax'));
    }

    private function isValidPublicPreviewRecaptcha(string $token, ?string $ipAddress = null): bool
    {
        if (! (bool) config('services.recaptcha.enabled', false)) {
            return true;
        }

        $secretKey = trim((string) config('services.recaptcha.secret_key', ''));

        if ($secretKey === '' || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            if (! $response->ok()) {
                return false;
            }

            $payload = $response->json();

            return (bool) ($payload['success'] ?? false);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @return array{survei_periods:array<int,array{value:string,label:string}>,sensus_kegiatans:array<int,array{value:string,label:string}>}
     */
    private function resolvePublicPreviewOptionsForPetugas(Petugas $petugas, int $activeYear): array
    {
        $alokasiCollection = AlokasiPetugas::query()
            ->with([
                'periodeAlokasi.kegiatan.rateHonors.satuan',
                'periodeAlokasi.kegiatan.rateHonors.satuanListing',
                'frameSampelAllocations.kegiatanFrameSampel.frameSampel',
            ])
            ->where('petugas_id', $petugas->id)
            ->where(function ($query): void {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->whereHas('periodeAlokasi', function ($query) use ($activeYear): void {
                $query->where('tahun', $activeYear)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
            ->get();

        $documentStatusMap = $this->resolvePublicPreviewDocumentStatusMap(
            (int) $petugas->id,
            $alokasiCollection,
        );

        // Batch-query BAST status grouped by periodKey|jenisKegiatan.
        // BAST is 1 per petugas per period — look up directly via spk.petugas_id
        // to avoid any dependency on alokasi status (dikirim/perubahan/direvisi).
        $allBasts = Bast::query()
            ->whereHas('spk', function ($q) use ($petugas, $activeYear): void {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($pq) use ($activeYear): void {
                        $pq->where('tahun', $activeYear);
                    });
            })
            ->with([
                'spk.alokasiPetugas.periodeAlokasi.kegiatan',
            ])
            ->whereNull('deleted_at')
            ->get(['id', 'spk_id', 'file_path', 'signed_file_path', 'main_signed_file_path']);

        /** @var array<string, Bast|null> $bastStatusByKey */
        $bastStatusByKey = [];

        foreach ($allBasts as $bast) {
            $spkPeriode = $bast->spk?->alokasiPetugas?->periodeAlokasi;
            $spkKegiatan = $spkPeriode?->kegiatan;

            if (! $spkPeriode || ! $spkKegiatan) {
                continue;
            }

            $spkPeriodKey = sprintf('%d-%02d', (int) $spkPeriode->tahun, (int) $spkPeriode->bulan);
            $spkStatusKey = $this->buildPublicPreviewDocumentStatusKey($spkPeriodKey, (string) $spkKegiatan->jenis_kegiatan);

            $existing = $bastStatusByKey[$spkStatusKey] ?? null;

            // Prefer signed BAST over draft
            if (! $existing || (($bast->signed_file_path || $bast->main_signed_file_path) && ! $existing->signed_file_path && ! $existing->main_signed_file_path)) {
                $bastStatusByKey[$spkStatusKey] = $bast;
            }
        }

        // Batch-query BAPP status (only needed for sensus)
        $bapps = BappSeTermin::query()
            ->where('petugas_id', $petugas->id)
            ->where('tahun', $activeYear)
            ->get(['id', 'termin', 'file_path', 'signed_file_path']);

        $bappByTermin = $bapps->keyBy('termin');

        $penugasanList = $alokasiCollection
            ->map(function (AlokasiPetugas $alokasi) use ($documentStatusMap, $bastStatusByKey, $bappByTermin): ?array {
                $periode = $alokasi->periodeAlokasi;
                $kegiatan = $periode?->kegiatan;

                if (! $periode || ! $kegiatan) {
                    return null;
                }

                $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
                $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                    $periodKey,
                    (string) $kegiatan->jenis_kegiatan,
                );
                $documentStatus = $documentStatusMap[$statusKey] ?? 'Belum ada PK';

                $bast = $bastStatusByKey[$statusKey] ?? null;

                $isSensus = mb_strtolower((string) $kegiatan->jenis_kegiatan) === 'sensus';

                return [
                    'id' => $alokasi->id,
                    'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                    'kegiatan_hashed_id' => $kegiatan->hashed_id,
                    'periode_key' => $periodKey,
                    'periode_label' => $this->getBulanLabel((int) $periode->bulan) . ' ' . (int) $periode->tahun,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'target_pekerjaan' => $this->resolvePublicPreviewTargetPekerjaan($alokasi),
                    'honor' => (float) $alokasi->getEffectiveCombinedHonor(),
                    'honor_label' => 'Rp ' . number_format((float) $alokasi->getEffectiveCombinedHonor(), 0, ',', '.'),
                    'document_status' => $documentStatus,
                    'bast_status' => $this->getBastStatusLabel($bast),
                    'bapp_termin_i_status' => $isSensus ? $this->getBappStatusLabel($bappByTermin->get(1)) : null,
                    'bapp_termin_ii_status' => $isSensus ? $this->getBappStatusLabel($bappByTermin->get(2)) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $surveiPeriods = $alokasiCollection
            ->filter(function (AlokasiPetugas $alokasi): bool {
                return mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'survei';
            })
            ->map(function (AlokasiPetugas $alokasi): ?string {
                $periode = $alokasi->periodeAlokasi;

                if (! $periode) {
                    return null;
                }

                $hasDraft = PeriodeAlokasi::query()
                    ->where('tahun', (int) $periode->tahun)
                    ->where('bulan', $periode->bulan)
                    ->where('status', 'draft')
                    ->whereHas('kegiatan', function ($query): void {
                        $query->where('jenis_kegiatan', 'survei');
                    })
                    ->exists();

                if ($hasDraft) {
                    return null;
                }

                return sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $periodKey): array {
                [$tahun, $bulan] = explode('-', $periodKey);

                return [
                    'value' => $periodKey,
                    'label' => $this->getBulanLabel((int) $bulan) . ' ' . (int) $tahun,
                ];
            })
            ->all();

        $sensusKegiatans = $alokasiCollection
            ->filter(function (AlokasiPetugas $alokasi): bool {
                return mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'sensus';
            })
            ->map(function (AlokasiPetugas $alokasi): ?array {
                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

                if (! $kegiatan) {
                    return null;
                }

                return [
                    'value' => $kegiatan->hashed_id,
                    'label' => $kegiatan->nama_kegiatan,
                ];
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'survei_periods' => $surveiPeriods,
            'sensus_kegiatans' => $sensusKegiatans,
            'penugasan_list' => $penugasanList,
        ];
    }

    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiCollection
     * @return array<string,string>
     */
    private function resolvePublicPreviewDocumentStatusMap(int $petugasId, Collection $alokasiCollection): array
    {
        $keys = [];
        $months = [];
        $years = [];
        $kegiatanIds = [];

        foreach ($alokasiCollection as $alokasi) {
            $periode = $alokasi->periodeAlokasi;
            $kegiatan = $periode?->kegiatan;

            if (! $periode || ! $kegiatan) {
                continue;
            }

            $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                $periodKey,
                (string) $kegiatan->jenis_kegiatan,
            );

            $keys[$statusKey] = [
                'period_key' => $periodKey,
                'jenis_kegiatan' => (string) $kegiatan->jenis_kegiatan,
                'kegiatan_id' => (int) $kegiatan->id,
            ];

            $months[(int) $periode->bulan] = (int) $periode->bulan;
            $years[(int) $periode->tahun] = (int) $periode->tahun;
            $kegiatanIds[(int) $kegiatan->id] = (int) $kegiatan->id;
        }

        if (empty($keys)) {
            return [];
        }

        $documents = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($months, $years, $kegiatanIds): void {
                $query->whereIn('bulan', array_values($months))
                    ->whereIn('tahun', array_values($years))
                    ->whereIn('kegiatan_id', array_values($kegiatanIds));
            })
            ->with([
                'alokasiPetugas.periodeAlokasi:id,kegiatan_id,bulan,tahun',
                'alokasiPetugas.periodeAlokasi.kegiatan:id,jenis_kegiatan',
            ])
            ->get(['id', 'alokasi_petugas_id', 'signed_file_path', 'addendum_number']);

        $groups = [];
        foreach ($documents as $document) {
            $periode = $document->alokasiPetugas?->periodeAlokasi;
            $kegiatan = $periode?->kegiatan;

            if (! $periode || ! $kegiatan) {
                continue;
            }

            $periodKey = sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
            $statusKey = $this->buildPublicPreviewDocumentStatusKey(
                $periodKey,
                (string) $kegiatan->jenis_kegiatan,
            );

            $groups[$statusKey][] = $document;
        }

        $result = [];
        foreach ($keys as $statusKey => $meta) {
            $groupDocuments = $groups[$statusKey] ?? [];

            if (empty($groupDocuments)) {
                $result[$statusKey] = 'Belum ada PK';

                continue;
            }

            $hasMainSigned = collect($groupDocuments)->contains(fn(Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
            $hasAddendumDraft = collect($groupDocuments)->contains(fn(Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
            $hasAddendumSigned = collect($groupDocuments)->contains(fn(Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

            if ($hasMainSigned && $hasAddendumSigned) {
                $result[$statusKey] = 'PK Final + Addendum';

                continue;
            }

            if ($hasMainSigned && $hasAddendumDraft) {
                $result[$statusKey] = 'PK Final + Addendum(draft)';

                continue;
            }

            if ($hasAddendumSigned) {
                $result[$statusKey] = 'Addendum Final';

                continue;
            }

            if ($hasMainSigned) {
                $result[$statusKey] = 'PK Final';

                continue;
            }

            $result[$statusKey] = 'PK Draft';
        }

        return $result;
    }

    private function buildPublicPreviewDocumentStatusKey(string $periodKey, string $jenisKegiatan): string
    {
        return $periodKey . '|' . mb_strtolower($jenisKegiatan);
    }

    /**
     * @param  array<string,mixed>  $validated
     */
    private function servePublicPreviewBast(Petugas $petugas, PeriodeAlokasi $periode, ?int $kegiatanId, string $jenisKegiatan, array $validated): mixed
    {
        // BAST is 1 per petugas per period — look up directly via spk.petugas_id.
        $bast = Bast::query()
            ->whereHas('spk', function ($q) use ($petugas, $periode, $jenisKegiatan): void {
                $q->where('petugas_id', $petugas->id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($pq) use ($periode, $jenisKegiatan): void {
                        $pq->where('tahun', $periode->tahun)
                            ->where('bulan', $periode->bulan)
                            ->whereHas('kegiatan', function ($kq) use ($jenisKegiatan): void {
                                $kq->where('jenis_kegiatan', $jenisKegiatan);
                            });
                    });
            })
            ->whereNull('deleted_at')
            ->orderByRaw('(signed_file_path IS NOT NULL OR main_signed_file_path IS NOT NULL) DESC')
            ->first();

        if (! $bast) {
            return response()->json(['message' => 'BAST belum tersedia untuk penugasan ini.'], 422);
        }

        $filePath = $bast->signed_file_path ?: ($bast->main_signed_file_path ?: $bast->file_path);

        if (! $filePath) {
            return response()->json(['message' => 'File BAST belum dibuat.'], 422);
        }

        $absolutePath = public_path(ltrim(str_replace('\\', '/', $filePath), '/'));

        if (! file_exists($absolutePath)) {
            return response()->json(['message' => 'File BAST tidak dapat diakses.'], 422);
        }

        $content = (string) file_get_contents($absolutePath);
        $nomor = preg_replace('/[^A-Za-z0-9_\-]/', '-', (string) ($bast->nomor_bast ?? 'BAST'));

        return $this->serveProtectedPublicPreviewContent($content, 'BAST_' . $nomor . '.pdf', $validated);
    }

    /**
     * @param  array<string,mixed>  $validated
     */
    private function servePublicPreviewBapp(Petugas $petugas, int $tahun, int $termin, array $validated): mixed
    {
        $bapp = BappSeTermin::query()
            ->where('petugas_id', $petugas->id)
            ->where('tahun', $tahun)
            ->where('termin', $termin)
            ->first();

        $terminLabel = $termin === 1 ? 'I' : 'II';

        if (! $bapp) {
            return response()->json(['message' => "BAPP Termin {$terminLabel} belum tersedia."], 422);
        }

        $filePath = $bapp->signed_file_path ?: $bapp->file_path;

        if (! $filePath) {
            return response()->json(['message' => "File BAPP Termin {$terminLabel} belum dibuat."], 422);
        }

        $absolutePath = Storage::disk('public')->path($filePath);

        if (! file_exists($absolutePath)) {
            return response()->json(['message' => 'File BAPP tidak dapat diakses.'], 422);
        }

        $content = (string) file_get_contents($absolutePath);
        $nomor = preg_replace('/[^A-Za-z0-9_\-]/', '-', (string) ($bapp->nomor_bapp ?? 'BAPP'));

        return $this->serveProtectedPublicPreviewContent($content, 'BAPP_Termin_' . $terminLabel . '_' . $nomor . '.pdf', $validated);
    }

    /**
     * Common PDF protection and serving logic for public preview.
     *
     * @param  array<string,mixed>  $validated
     */
    private function serveProtectedPublicPreviewContent(string $content, string $filename, array $validated): mixed
    {
        $protectedContent = $this->applyDraftWatermarkAndProtection($content);
        $disposition = ($validated['aksi'] ?? 'preview') === 'download' ? 'attachment' : 'inline';
        $responseMode = (string) ($validated['response_mode'] ?? 'binary');
        $downloadToken = (string) ($validated['download_token'] ?? '');

        if ($responseMode === 'url' && $disposition === 'inline') {
            $tempPath = $this->storePublicPreviewTemporaryPdf($protectedContent);

            if (! is_string($tempPath) || ! is_file($tempPath)) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            $previewUrl = $this->buildPublicPreviewSignedFileUrl($tempPath, $filename, 'inline');

            if (! $previewUrl) {
                return response()->json(['message' => 'URL preview tidak tersedia.'], 500);
            }

            return response()->json([
                'preview_url' => $previewUrl,
                'filename' => $filename,
            ]);
        }

        $response = response($protectedContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function getBastStatusLabel(?Bast $bast): string
    {
        if (! $bast) {
            return 'Belum ada BAST';
        }

        if ($bast->signed_file_path || $bast->main_signed_file_path) {
            return 'BAST Final';
        }

        if ($bast->file_path) {
            return 'Draft BAST';
        }

        return 'Belum ada BAST';
    }

    private function getBappStatusLabel(?BappSeTermin $bapp): string
    {
        if (! $bapp) {
            return 'Belum ada BAPP';
        }

        if ($bapp->signed_file_path) {
            return 'BAPP Final';
        }

        if ($bapp->file_path) {
            return 'Draft BAPP';
        }

        return 'Belum ada BAPP';
    }

    private function resolvePublicPreviewTargetPekerjaan(AlokasiPetugas $alokasi): string
    {
        if (mb_strtolower((string) $alokasi->periodeAlokasi?->kegiatan?->jenis_kegiatan) === 'sensus') {
            $metrics = $this->resolveSensusEkonomiFrameVolumeMetrics($alokasi, $alokasi);
            if (($metrics['narrative'] ?? '-') !== '-') {
                return (string) $metrics['narrative'];
            }
        }

        $rateHonor = $this->resolvePublicPreviewRateHonorForAlokasi($alokasi);

        if (! $rateHonor || ! $rateHonor->satuan) {
            if ($alokasi->getEffectiveCombinedHonor() > 0) {
                return '1 paket';
            }

            return '-';
        }

        $targetValue = $alokasi->getEffectiveJumlahSatuan();
        if ($targetValue <= 0 && $alokasi->getEffectiveCombinedHonor() > 0) {
            $targetValue = 1;
        }

        if ($targetValue <= 0) {
            return '-';
        }

        return number_format($targetValue, 0, ',', '.') . ' ' . $rateHonor->satuan->nama;
    }

    private function resolvePublicPreviewRateHonorForAlokasi(AlokasiPetugas $alokasi): ?RateHonor
    {
        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

        if (! $kegiatan) {
            return null;
        }

        $kegiatan->loadMissing([
            'rateHonors.satuan',
            'rateHonors.satuanListing',
        ]);

        $rateHonorByKey = $kegiatan->rateHonors->keyBy(function (RateHonor $rateHonor): string {
            return $rateHonor->status_kepegawaian . '|' . $rateHonor->jenis_penugasan;
        });

        $statusKepegawaian = $alokasi->status_kepegawaian
            ?? (($alokasi->petugas->jenis_petugas ?? 'non-organik') === 'organik' ? 'organik' : 'non_organik');

        return $rateHonorByKey->get($statusKepegawaian . '|' . $alokasi->peran)
            ?? $kegiatan->rateHonors->firstWhere('status', 'aktif');
    }

    private function resolvePublicPreviewDocumentStatusForPeriod(
        int $petugasId,
        string $periodKey,
        ?string $jenisKegiatan = null,
        ?int $kegiatanId = null,
    ): string {
        [$tahun, $bulan] = explode('-', $periodKey);

        $documents = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($tahun, $bulan, $jenisKegiatan, $kegiatanId): void {
                $query->where('tahun', (int) $tahun)
                    ->where('bulan', (int) $bulan)
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->orderBy('addendum_number')
            ->get(['id', 'signed_file_path', 'addendum_number']);

        if ($documents->isEmpty()) {
            return 'Belum ada PK';
        }

        $hasMainSigned = $documents->contains(fn(Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
        $hasAddendumDraft = $documents->contains(fn(Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
        $hasAddendumSigned = $documents->contains(fn(Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

        if ($hasMainSigned && $hasAddendumSigned) {
            return 'PK Final + Addendum';
        }

        if ($hasMainSigned && $hasAddendumDraft) {
            return 'PK Final + Addendum(draft)';
        }

        if ($hasAddendumSigned) {
            return 'Addendum Final';
        }

        if ($hasMainSigned) {
            return 'PK Final';
        }

        return 'PK Draft';
    }

    private function applyDraftWatermarkAndProtection(string $pdfBinary): string
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return $pdfBinary;
        }

        $token = time() . '_' . uniqid();
        $inputPath = $tempPath . '/spk_public_preview_input_' . $token . '.pdf';
        $outputPath = $tempPath . '/spk_public_preview_output_' . $token . '.pdf';

        try {
            file_put_contents($inputPath, $pdfBinary);

            $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->setProtection(['modify', 'annot-forms', 'fill-forms', 'assemble'], '', '@dm1n_SIMANTIK');

            $pageCount = $pdf->setSourceFile($inputPath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = (($size['width'] ?? 210) > ($size['height'] ?? 297)) ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pageWidth = (float) ($size['width'] ?? 210);
                $pageHeight = (float) ($size['height'] ?? 297);
                $centerX = $pageWidth / 2;
                $centerY = $pageHeight / 2;
                $watermarkText = 'BPS KOTA SAWAHLUNTO';
                $rotationAngle = 28.0;
                $fontSize = min(44.0, max(24.0, $pageWidth * 0.18));

                $pdf->SetAlpha(0.12);
                $pdf->SetTextColor(95, 95, 95);
                $pdf->SetFont('helvetica', 'B', (float) $fontSize);

                $textWidth = (float) $pdf->GetStringWidth($watermarkText);

                // Keep reducing the font until rotated watermark fits safely inside the page.
                while ($fontSize > 16.0) {
                    $theta = deg2rad($rotationAngle);
                    $halfRotatedWidth = (abs($textWidth * cos($theta)) + abs($fontSize * sin($theta))) / 2;
                    $halfRotatedHeight = (abs($textWidth * sin($theta)) + abs($fontSize * cos($theta))) / 2;

                    $fitsHorizontally = $halfRotatedWidth <= ($pageWidth / 2) - 8;
                    $fitsVertically = $halfRotatedHeight <= ($pageHeight / 2) - 8;

                    if ($fitsHorizontally && $fitsVertically) {
                        break;
                    }

                    $fontSize -= 1.0;
                    $pdf->SetFont('helvetica', 'B', (float) $fontSize);
                    $textWidth = (float) $pdf->GetStringWidth($watermarkText);
                }

                $textX = $centerX - ($textWidth / 2);
                $textY = $centerY - ($fontSize * 0.35);

                $pdf->StartTransform();
                $pdf->Rotate($rotationAngle, $centerX, $centerY);
                $pdf->Text($textX, $textY, $watermarkText);
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
            }

            $pdf->Output($outputPath, 'F');
            $securedBinary = file_get_contents($outputPath);

            return is_string($securedBinary) && $securedBinary !== '' ? $securedBinary : $pdfBinary;
        } catch (\Throwable $exception) {
            return $pdfBinary;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    private function formatPreviewNomorSpkForPeriode(PeriodeAlokasi $periode, int $nomorUrut): string
    {
        $nomorSpkAsli = $this->formatNomorSpkForPeriode($periode, $nomorUrut);

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return (string) preg_replace('/^B-(\d+)/', 'PREVIEW-$1', $nomorSpkAsli, 1);
        }

        $parts = explode('/', $nomorSpkAsli);
        if (isset($parts[3]) && mb_strtoupper((string) $parts[3]) === 'K') {
            $parts[3] = 'PREVIEW-K';

            return implode('/', $parts);
        }

        return str_replace('/K/', '/PREVIEW-K/', $nomorSpkAsli);
    }

    private function resolvePublicPreviewPetugas(string $nama, string $nik): ?Petugas
    {
        $normalizedNama = mb_strtolower(trim($nama));
        $normalizedNik = trim($nik);

        return Petugas::query()
            ->where('status', 'aktif')
            ->get()
            ->first(function (Petugas $petugas) use ($normalizedNama, $normalizedNik): bool {
                $petugasNik = trim((string) $petugas->getAttribute('nik'));
                $petugasNama = mb_strtolower(trim((string) $petugas->nama));

                return $petugasNik === $normalizedNik && $petugasNama === $normalizedNama;
            });
    }

    private function matchesPublicPreviewPhoneVerification(Petugas $petugas, string $telepon4Digit): bool
    {
        $normalizedPhoneVerification = preg_replace('/\D+/', '', trim($telepon4Digit)) ?? '';

        if (strlen($normalizedPhoneVerification) !== 4) {
            return false;
        }

        $petugasPhoneDigits = preg_replace('/\D+/', '', (string) $petugas->telepon) ?? '';

        if (strlen($petugasPhoneDigits) < 4) {
            return false;
        }

        return substr($petugasPhoneDigits, -4) === $normalizedPhoneVerification;
    }

    /**
     * Show the form to generate SPKs for a periode
     */
    public function create(string $periodeHashedId): Response|RedirectResponse
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
            ->filter(fn(AlokasiPetugas $alokasi) => $this->hasPositiveEffectiveHonor($alokasi));

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')
            ->map(fn(Collection $alokasiGroup) => $this->buildGeneratePetugasListItem($alokasiGroup))
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        // Get next nomor urut for this year
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);
        dd($nextNomorUrut);

        // Check if there are existing SPKs in this month (for regenerate mode)
        $existingSpkQuery = $this->baseSpkScopeQuery($periode);
        $existingSpk = (clone $existingSpkQuery)->first();

        // If existing SPK found, use its dates and set readonly mode
        $isRegenerate = $existingSpk !== null;
        $defaultTanggalSpk = $isRegenerate ? $existingSpk->tanggal_spk->format('Y-m-d') : null;

        // Get all existing SPKs for petugas in this month (map petugas_id => nomor_spk)
        $existingSpkMap = [];
        $existingKegiatanPerPetugas = [];
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

                $kegiatanIds = AlokasiPetugas::whereIn('id', $baselineAlokasiIds)
                    ->where('petugas_id', $spk->petugas_id)
                    ->whereIn('periode_alokasi_id', $scopePeriodeIds)
                    ->where(function ($query) {
                        $query->where('total_honor', '>', 0)
                            ->orWhere('total_honor_listing', '>', 0);
                    })
                    ->with('periodeAlokasi.kegiatan')
                    ->get()
                    ->pluck('periodeAlokasi.kegiatan.id')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                $existingKegiatanPerPetugas[$spk->petugas_id] = $kegiatanIds;

                $existingSpkMap[$spk->petugas_id] = [
                    'nomor_spk' => $spk->nomor_spk,
                    'nomor_urut' => $spk->nomor_urut_base,
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

        if ($isRegenerate && ! $this->usesPeriodBasedSpkFlow($periode)) {
            $eligibleRegeneratePetugasIds = $this->resolveSpkActionDecisionsForMonth((int) $periode->tahun, (int) $periode->bulan)
                ->filter(fn(array $item): bool => (bool) ($item['should_regenerate'] ?? false))
                ->pluck('petugas_id')
                ->map(static fn($petugasId) => (int) $petugasId)
                ->unique()
                ->all();

            $petugasList = $petugasList->filter(function (array $item) use ($eligibleRegeneratePetugasIds) {
                $petugasId = (int) ($item['petugas']['id'] ?? 0);

                return in_array($petugasId, $eligibleRegeneratePetugasIds, true);
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
            ->with('spk')
            ->get();

        if ($monthPeriodes->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada periode valid untuk bulan ini.');
        }

        if ($this->hasNewKegiatanAfterSpk((int) $tahun, (int) $bulan, $monthPeriodes)) {
            return redirect()->route('spk.index')
                ->with('warning', 'Silakan selesaikan re-generate SPK terlebih dahulu sebelum membuat addendum.');
        }

        $allPeriodeInMonth = $monthPeriodes->pluck('id');

        $petugasWithAllocation = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->pluck('petugas_id')
            ->unique();

        // Get all petugas with allocation in this month.
        // Load ALL alokasi in month for those petugas (including honor 0) for comparison and total display
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereIn('petugas_id', $petugasWithAllocation)
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        $candidateSummary = $this->resolveAddendumCandidatesForMonth((int) $tahun, (int) $bulan);

        $petugasWithAddendum = $candidateSummary
            ->filter(fn(array $item): bool => (bool) ($item['has_addendum'] ?? false))
            ->pluck('petugas_id')
            ->values()
            ->all();

        $eligiblePetugasIds = $candidateSummary
            ->pluck('petugas_id')
            ->map(static fn($petugasId) => (int) $petugasId)
            ->unique()
            ->values()
            ->all();

        $resolvedMode = in_array($requestedMode, ['addendum', 'regenerate'], true)
            ? $requestedMode
            : (! empty($petugasWithAddendum) ? 'regenerate' : 'addendum');
        $isRegenerateAddendum = $resolvedMode === 'regenerate';

        // Group by petugas_id and aggregate their data
        $petugasListRaw = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) use ($bulanFormatted, $tahun, $petugasWithAddendum, $eligiblePetugasIds) {
                $firstAlokasi = $alokasiGroup->first();

                if (! in_array((int) $firstAlokasi->petugas_id, $eligiblePetugasIds, true)) {
                    return null;
                }

                // Get existing SPK for this petugas in this month
                $existingSpk = Spk::where('petugas_id', $firstAlokasi->petugas_id)
                    ->where('addendum_number', 0) // Get original SPK only
                    ->whereYear('tanggal_spk', (int) $tahun)
                    ->whereMonth('tanggal_spk', (int) $bulanFormatted)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if (! $existingSpk) {
                    return null; // Skip petugas without original SPK
                }

                // Get current effective allocations (latest status for each kegiatan)
                $effectiveAlokasiByKegiatan = $this->getEffectiveAlokasiByKegiatan($alokasiGroup);

                // Calculate current total honor
                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                $totalHonor = $currentTotalHonor;

                // Skip if total honor from perubahan is 0
                if ($totalHonor <= 0) {
                    return null;
                }

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
                        return $item['kegiatan_kode'] . '|' . $item['peran'];
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
                    'has_addendum' => in_array($firstAlokasi->petugas_id, $petugasWithAddendum),
                ];
            })
            ->filter() // Remove nulls
            ->filter(function ($item) use ($isRegenerateAddendum) {
                // Generate addendum biasa hanya untuk petugas tanpa addendum.
                // Re-generate addendum hanya untuk petugas yang sudah punya addendum.
                return $isRegenerateAddendum
                    ? $item['has_addendum']
                    : ! $item['has_addendum'];
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

    private function getEffectiveAlokasiByKegiatan(Collection $alokasiGroup): Collection
    {
        return $alokasiGroup
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Priority: perubahan > direvisi > disetujui > dikirim
                $perubahan = $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'perubahan');
                if ($perubahan) {
                    return $perubahan;
                }

                $direvisi = $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'direvisi');
                if ($direvisi) {
                    return $direvisi;
                }

                $disetujui = $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'disetujui');
                if ($disetujui) {
                    return $disetujui;
                }

                return $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'dikirim');
            })
            ->filter(function ($alokasi) {
                return $alokasi && $this->isMeaningfulAllocation($alokasi);
            });
    }

    /**
     * Build the effective addendum allocation set for a petugas in one month.
     *
     * This uses the same month scope for preview and final generation, then reduces
     * each kegiatan to a single effective allocation so the output stays consistent.
     */
    private function getEffectiveAddendumAlokasiForPetugas(int $petugasId, int $tahun, int $bulan): Collection
    {
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $allPeriodeInMonth = PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return collect();
        }

        $allAlokasi = AlokasiPetugas::query()
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q): void {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->with(['petugas', 'periodeAlokasi.kegiatan.rateHonors.satuan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->getEffectiveCombinedHonor() > 0;
            });

        if ($allAlokasi->isEmpty()) {
            return collect();
        }

        return $this->getEffectiveAlokasiByKegiatan($allAlokasi)->values();
    }

    /**
     * Build addendum candidate summary for a month using the same rules as createAddendum petugas_list.
     *
     * @return Collection<int, array{petugas_id:int,has_addendum:bool}>
     */
    private function resolveAddendumCandidatesForMonth(int $tahun, int $bulan): Collection
    {
        return $this->resolveSpkActionDecisionsForMonth($tahun, $bulan)
            ->filter(fn(array $item): bool => (bool) ($item['should_addendum'] ?? false))
            ->map(function (array $item) {
                return [
                    'petugas_id' => (int) ($item['petugas_id'] ?? 0),
                    'has_addendum' => (bool) ($item['has_addendum'] ?? false),
                ];
            })
            ->filter()
            ->values();
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

    /**
     * Determine whether two allocation snapshots differ on any kegiatan that exists in both snapshots.
     *
     * New kegiatan are intentionally ignored here so they can be handled by regenerate logic.
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $referenceSnapshot
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $currentSnapshot
     */
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

    /**
     * Preview addendum SPK PDF
     */
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

        $allAlokasi = $this->getEffectiveAddendumAlokasiForPetugas($petugasId, $tahun, (int) $bulan);

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
        $nomorSpkParts[2] = $nomorSpkParts[2] . '/ADD-' . $validated['addendum_number'];
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
            $filename = 'preview-addendum-spk-' . $sanitizedName . '.pdf';

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
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent))
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', 'public, must-revalidate, max-age=0')
                ->header('Pragma', 'public')
                ->header('X-Content-Type-Options', 'nosniff');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal generate preview addendum SPK: ' . $e->getMessage());
        }
    }

    /**
     * Generate and save addendum SPK
     */
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
                'message' => 'Gagal generate addendum SPK: ' . $e->getMessage(),
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

            $allAlokasi = $this->getEffectiveAddendumAlokasiForPetugas($petugasId, $tahun, (int) $bulan);

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
            $nomorSpkParts[2] = $baseNomorUrut . '/ADD-' . $addendumNumber;
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
            $fileName = 'SPK-ADDENDUM-' . $addendumNumber . '-' . $sanitizedNamaPetugas . '-' . $bulanFormatted . '-' . $tahun . '.pdf';
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
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
            }) ?? $alokasiGroup->first();
            $latest = $alokasiGroup->sortByDesc(function ($a) {
                return $a->periodeAlokasi->id;
            })->first();

            // Calculate total honor across all peran for this kegiatan
            $originalTotalHonor = $alokasiGroup->filter(function ($a) {
                return in_array($a->periodeAlokasi->status, ['dikirim', 'disetujui', 'direvisi']);
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
            ->filter(fn($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
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

        $zipFileName = 'Preview_SPK_' . $this->getBulanLabel((int) $periode->bulan) . '_' . $periode->tahun . '.zip';
        $zipPath = $tempPath . '/preview_spk_' . $periode->id . '_' . time() . '_' . uniqid() . '.zip';

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
                $archiveFilename = preg_replace('/\.pdf$/i', '', $pdfPreview['filename']) . '_' . ($suffixCounter++) . '.pdf';
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
        $timestamp = time() . '_' . uniqid();

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

            $path = $tempPath . '/print_main_' . $timestamp . '_' . $index . '.pdf';

            if (@file_put_contents($path, $pdfBinary) === false) {
                continue;
            }

            $individualPaths[] = $path;
        }

        if (empty($individualPaths)) {
            return response()->json(['message' => 'Tidak ada PDF yang dapat dibuat.'], 422);
        }

        $mergedPath = $tempPath . '/print_main_merged_' . $timestamp . '.pdf';
        $filename = 'Print_PK_Main_' . $periode->bulan . '_' . $periode->tahun . '.pdf';

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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
        $timestamp = time() . '_' . uniqid();

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

            $path = $tempPath . '/print_lampiran_' . $timestamp . '_' . $index . '.pdf';

            if (@file_put_contents($path, $pdfBinary) === false) {
                continue;
            }

            $individualPaths[] = $path;
        }

        if (empty($individualPaths)) {
            return response()->json(['message' => 'Tidak ada PDF yang dapat dibuat.'], 422);
        }

        $mergedPath = $tempPath . '/print_lampiran_merged_' . $timestamp . '.pdf';
        $filename = 'Print_Lampiran_' . $periode->bulan . '_' . $periode->tahun . '.pdf';

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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Decode and sort preview items from JSON by petugas_nama, falling back to nomor_spk.
     *
     * @return Collection<int, array{petugas_hashed_id:string,nomor_spk:string,petugas_nama?:string}>
     */
    private function decodeAndSortPreviewItems(string $json): Collection
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return collect();
        }

        return collect($decoded)
            ->filter(fn($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
            ->unique('petugas_hashed_id')
            ->sortBy(fn($item) => mb_strtolower((string) ($item['petugas_nama'] ?? $item['nomor_spk'])))
            ->values();
    }

    /**
     * Build SPK main (Pasal-based) PDF binary for a single petugas.
     */
    private function buildSpkMainPdfBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
    ): ?string {
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;

        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

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

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];

            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Print_PK_Main_' . $sanitizedName . '.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse($calculatedSampaiTanggal),
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

        $pdf = Pdf::loadView('spk-main', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        return $pdf->output() ?: null;
    }

    /**
     * Build SPK lampiran PDF binary for a single petugas.
     */
    private function buildSpkLampiranPdfBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
    ): ?string {
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereIn('periode_alokasi_id', $scopePeriodeIds)
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;

        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

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

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];
        $bebanAnggaran = '';

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));
            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];

            if (empty($bebanAnggaran)) {
                $bebanAnggaran = $this->getBebanAnggaran($kegiatan);
            }
        }

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Print_Lampiran_' . $sanitizedName . '.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse(Carbon::parse($latestEndDate)->format('Y-m-d')),
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

        // Render main first just for page count offset
        $pdfMain = Pdf::loadView('spk-main', $data)->setPaper('a4', 'portrait');
        $pdfMain->output();
        $data['pageNumberOffset'] = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());

        $data = $this->withLampiranContext($data);

        $lampiranView = $this->resolveLampiranView($data['kegiatan'], $data['peran']);
        $lampiranPaper = $this->resolveLampiranPaperOrientation($data['kegiatan'], $data['peran']);

        $pdf = Pdf::loadView($lampiranView, $data)->setPaper('a4', $lampiranPaper);
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        return $pdf->output() ?: null;
    }

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
        $filename = 'Preview_SPK_' . $sanitizedName . '.pdf';

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

        $timestamp = time() . '_' . uniqid();
        $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
        $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
        $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';

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
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
        $filename = 'Preview_SPK_' . $sanitizedName . '.pdf';

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
    private function buildMergedSpkPreviewBinary(
        PeriodeAlokasi $periode,
        int $petugasId,
        string $nomorSpk,
        string $tanggalSpk,
        ?int $kegiatanId = null,
        ?string $jenisKegiatan = null,
    ): ?array {
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode, $kegiatanId, $jenisKegiatan) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan'])
                    ->when($kegiatanId !== null, function ($periodeQuery) use ($kegiatanId): void {
                        $periodeQuery->where('kegiatan_id', $kegiatanId);
                    })
                    ->when($jenisKegiatan !== null, function ($periodeQuery) use ($jenisKegiatan): void {
                        $periodeQuery->whereHas('kegiatan', function ($kegiatanQuery) use ($jenisKegiatan): void {
                            $kegiatanQuery->where('jenis_kegiatan', $jenisKegiatan);
                        });
                    });
            })
            ->where('petugas_id', $petugasId)
            ->get();

        if ($allAlokasi->isEmpty()) {
            return null;
        }

        $latestEndDate = null;
        foreach ($allAlokasi as $alokasiItem) {
            $periodeItem = $alokasiItem->periodeAlokasi;
            $isPengolahanRole = in_array($alokasiItem->peran, ['pengolahan', 'pengawas_pengolahan']);

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

        if ($latestEndDate === null) {
            $latestEndDate = Carbon::create($periode->tahun, $periode->bulan, 1)->endOfMonth();
        }

        $calculatedSampaiTanggal = Carbon::parse($latestEndDate)->format('Y-m-d');
        $petugas = $allAlokasi->first()->petugas;
        $penandatangan = Penandatangan::active()->ppk()->firstOrFail();

        $totalHonor = 0;
        $uraianTugas = [];
        $kegiatanData = [];

        foreach ($allAlokasi as $alokasi) {
            $kegiatan = $alokasi->periodeAlokasi->kegiatan;
            $totalHonor += $this->calculateTotalHonor($kegiatan, $alokasi);
            $uraianTugas = array_merge($uraianTugas, $this->getUraianTugas($kegiatan, $alokasi));

            $kegiatanData[] = [
                'kegiatan_id' => $kegiatan->id,
                'kode_kegiatan' => $kegiatan->kode_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'kode_coa' => $kegiatan->kode_coa,
                'alokasi_id' => $alokasi->id,
            ];
        }

        $bebanAnggaran = $allAlokasi->isNotEmpty()
            ? $this->getBebanAnggaran($allAlokasi->first()->periodeAlokasi->kegiatan)
            : '';

        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $petugas->nama);
        $filename = 'Preview_SPK_' . $sanitizedName . '.pdf';

        $data = [
            'periode' => $periode,
            'alokasi' => $allAlokasi->first(),
            'allAlokasi' => $allAlokasi,
            'petugas' => $petugas,
            'kegiatan' => $allAlokasi->first()->periodeAlokasi->kegiatan,
            'kegiatanData' => $kegiatanData,
            'nomorSpk' => $nomorSpk,
            'tanggalSpk' => Carbon::parse($tanggalSpk),
            'sampaiTanggal' => Carbon::parse($calculatedSampaiTanggal),
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

        $pdfMain = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');
        $pdfMain->getDomPDF()->set_option('pdfTitle', $filename);

        $mainOutput = $pdfMain->output();
        $mainPageCount = max(0, (int) $pdfMain->getDomPDF()->getCanvas()->get_page_count());
        $data['pageNumberOffset'] = $mainPageCount;

        $pdfLampiran = Pdf::loadView($lampiranView, $data)
            ->setPaper('a4', $lampiranPaper);
        $pdfLampiran->getDomPDF()->set_option('pdfTitle', $filename);

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $timestamp = time() . '_' . uniqid();
        $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
        $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
        $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';

        file_put_contents($mainPath, $mainOutput);
        file_put_contents($lampiranPath, $pdfLampiran->output());

        $merged = PdfMergerService::mergePdfFiles(
            [$mainPath, $lampiranPath],
            $mergedPath,
            $filename
        );

        $pdfOutput = null;
        if ($merged && file_exists($mergedPath)) {
            $pdfOutput = file_get_contents($mergedPath) ?: null;
        }

        @unlink($mainPath);
        @unlink($lampiranPath);
        @unlink($mergedPath);

        if ($pdfOutput === null) {
            $pdf = Pdf::loadView('spk-petugas', $data)
                ->setPaper('a4', 'portrait');
            $pdf->getDomPDF()->set_option('pdfTitle', $filename);
            $pdfOutput = $pdf->output();
        }

        return [
            'filename' => $filename,
            'content' => $pdfOutput,
        ];
    }

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
        $filename = 'Preview_SPK_Main_' . $sanitizedName . '.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        // Stream from temp file to avoid loading entire PDF into memory
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath . '/spk_main_preview_' . time() . '_' . uniqid() . '.pdf';
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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
        $filename = 'Preview_SPK_Lampiran_' . $sanitizedName . '.pdf';

        // Set PDF title metadata
        $pdf->getDomPDF()->set_option('pdfTitle', $filename);

        // Stream from temp file to avoid loading entire PDF into memory
        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath . '/spk_lampiran_preview_' . time() . '_' . uniqid() . '.pdf';
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
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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

            $timestamp = time() . '_' . uniqid();
            $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
            $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
            $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';

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
            // Extract nomor urut from nomor_spk format: PPIS/13730/4/K/2025 -> get "4"
            $nomorUrut = (string) $this->extractNomorUrut((string) $data['nomorSpk']);

            // Clean filename - remove special characters that are invalid for filenames
            $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
            $bulanLabel = $this->getBulanLabel($periode->bulan);

            $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
            $filePath = 'spk-export/' . date('Y') . '/' . date('m') . '/' . $fileName;

            // Create directory if not exists
            $publicPath = public_path('spk-export/' . date('Y') . '/' . date('m'));
            if (! file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Save to public directory
            file_put_contents(public_path($filePath), $pdfOutput);

            // Save to database
            $spk = Spk::create([
                'nomor_spk' => $nomorSpk,
                'alokasi_petugas_id' => $allAlokasi->first()->id,
                'alokasi_petugas_ids' => $allAlokasi->pluck('id')->toArray(),
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

    /**
     * @param  Collection<int,AlokasiPetugas>  $alokasiGroup
     * @return array{alokasi_id:int,alokasi_ids:array<int,int>,alokasi_hashed_id:string,petugas:array{id:int,hashed_id:string,nama:string,nik:string,jenis_petugas:string},jumlah_kegiatan:int,kegiatan_list:array<int,array{kegiatan_id:int,kegiatan_kode:string,kegiatan_nama:string,peran:string}>,total_honor:float}
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
                return $item['kegiatan_kode'] . '|' . $item['peran'];
            })
            ->values()
            ->all();

        return [
            'alokasi_id' => $firstAlokasi->id,
            'alokasi_ids' => $alokasiGroup->pluck('id')->map(fn($id) => (int) $id)->values()->all(),
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

    private function withLampiranContext(array $data): array
    {
        $kegiatan = $data['kegiatan'] ?? null;
        $peran = mb_strtolower((string) ($data['peran'] ?? ''));

        if (! $kegiatan instanceof Kegiatan) {
            $data['lampiranTemplate'] = 'default';
            $data['lampiranPayload'] = null;

            return $data;
        }

        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            $data['lampiranTemplate'] = 'sensus_ekonomi';
            $data['lampiranPayload'] = $this->buildSensusEkonomiLampiranPayload(
                $data['periode'] ?? null,
                $data['uraianTugas'] ?? [],
                (float) ($data['totalHonor'] ?? 0),
                $kegiatan,
                $data['allAlokasi'] ?? null,
                $data['alokasi'] ?? null,
            );

            return $data;
        }

        if ($this->isSensusEkonomi2026($kegiatan) && $peran === 'pml') {
            $data['lampiranTemplate'] = 'pml_sensus_ekonomi';
            $data['lampiranPayload'] = $this->buildPmlSensusEkonomiLampiranPayload(
                $data['periode'] ?? null,
                $data['uraianTugas'] ?? [],
                (float) ($data['totalHonor'] ?? 0),
                $kegiatan,
                $data['allAlokasi'] ?? null,
                $data['alokasi'] ?? null,
            );

            return $data;
        }

        $data['lampiranTemplate'] = 'default';
        $data['lampiranPayload'] = null;

        return $data;
    }

    private function resolveLampiranView(Kegiatan $kegiatan, string $peran): string
    {
        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            return 'spk-lampiran-sensus-ekonomi';
        }

        if ($this->isSensusEkonomi2026($kegiatan) && mb_strtolower($peran) === 'pml') {
            return 'spk-lampiran-pml-sensus-ekonomi';
        }

        return 'spk-lampiran';
    }

    private function resolveLampiranPaperOrientation(Kegiatan $kegiatan, string $peran): string
    {
        if ($this->usesSensusEkonomiLampiranTemplate($kegiatan, $peran)) {
            return 'landscape';
        }

        return 'landscape';
    }

    private function usesSensusEkonomiLampiranTemplate(Kegiatan $kegiatan, string $peran): bool
    {
        return $this->isSensusEkonomi2026($kegiatan)
            && in_array(mb_strtolower($peran), ['pcl_ppl', 'pcl', 'ppl'], true);
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
                ->whereHas('kegiatan', fn($q) => $q->where('jenis_kegiatan', '!=', 'sensus'));
        }

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        return $query->pluck('id');
    }

    private function formatDisplayName(string $name): string
    {
        return ucwords(mb_strtolower(trim($name)));
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
            ->whereHas('kegiatan', fn($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->exists();
    }

    private function baseSpkScopeQuery(PeriodeAlokasi $periode)
    {
        $query = Spk::query()->where('addendum_number', 0);

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return $query->whereHas('alokasiPetugas', function ($builder) use ($periode) {
                $builder->where('periode_alokasi_id', $periode->id);
            });
        }

        return $query->whereYear('tanggal_spk', $periode->tahun)
            ->whereMonth('tanggal_spk', (int) $periode->bulan);
    }

    private function resolveSpkIndexGroupKey(PeriodeAlokasi $periode): string
    {
        if ($this->usesPeriodBasedSpkFlow($periode)) {
            return 'periode-' . $periode->id;
        }

        return sprintf('%d-%02d', (int) $periode->tahun, (int) $periode->bulan);
    }

    private function resolveSpkIndexDisplayLabel(PeriodeAlokasi $periode): string
    {
        if (! $this->usesPeriodBasedSpkFlow($periode)) {
            return $this->getBulanLabel((int) $periode->bulan) . ' ' . $periode->tahun;
        }

        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
            $start = $periode->tanggal_mulai;
            $end = $periode->tanggal_selesai;

            if ($start->year === $end->year) {
                if ($start->month === $end->month) {
                    return $start->translatedFormat('d') . '-' . $end->translatedFormat('d F Y');
                }

                return $start->translatedFormat('F') . ' - ' . $end->translatedFormat('F Y');
            }

            return $start->translatedFormat('d F Y') . ' - ' . $end->translatedFormat('d F Y');
        }

        return $this->getBulanLabel((int) $periode->bulan) . ' ' . $periode->tahun;
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
                        'Melakukan pendataan lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pendataan lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026',
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

        $terminSatuMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 40);
        $terminDuaMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $frameMuatanTotals, 60);

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
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026 termin I',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026',
                    ],
                    'waktu_penyelesaian' => 'Minimal 1 bulan',
                    'persentase' => '40%',
                    'volume' => $terminSatuVolume,
                    'nilai_perjanjian' => $terminSatuAmount,
                ],
                [
                    'items' => [
                        'Melakukan pemeriksaan hasil pendataan Petugas Lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026 termin II',
                        'Memastikan seluruh kelengkapan dokumen hasil pendataan Petugas Lapangan door to door ' . $kegiatan->nama_kegiatan . ' 2026',
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
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $unitNameById = ! empty($unitIds)
            ? MasterUnitSampel::query()
            ->whereIn('id', $unitIds)
            ->pluck('nama', 'id')
            ->map(fn($name) => mb_strtolower(trim((string) $name)))
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

            $key = $kdkec . '_' . $kddes;

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
                'kecamatan' => '[' . $entry['kdkec'] . '] ' . $entry['kdkec_label'],
                'desa' => '[' . $entry['kddes'] . '] ' . $entry['kddes_label'],
                'jumlah_sls' => $entry['count'],
                'muatan_prelist' => $entry['prelist_usaha'] . ' usaha dan ' . $entry['prelist_keluarga'] . ' keluarga',
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
            array_map(static fn($value): int => max(0, (int) $value), $frameMuatanTotals),
            static fn(int $value): bool => $value > 0,
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
            $alokasiCollection = $allAlokasi->filter(fn(mixed $item): bool => $item instanceof AlokasiPetugas)->values();
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
            ->filter(fn(mixed $allocation): bool => $allocation !== null)
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

        $unitSampelIds = array_values(array_filter(array_keys($perUnitSampelTotals), fn($id) => $id > 0));
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
            return number_format($selectedRows, 0, ',', '.') . ' SLS/sub-SLS';
        }

        return '-';
    }

    private function formatSensusEkonomiTotalSlsVolumeLabel(int $selectedRows): string
    {
        if ($selectedRows <= 0) {
            return '-';
        }

        return 'Seluruh Muatan ' . number_format($selectedRows, 0, ',', '.') . ' SLS/sub-SLS';
    }

    private function formatLampiranVolumeNumber(float|int $volume): string
    {
        return floor((float) $volume) === (float) $volume
            ? number_format((float) $volume, 0, ',', '.')
            : rtrim(rtrim(number_format((float) $volume, 2, ',', '.'), '0'), ',');
    }

    private function calculateLampiranMilestoneAmount(float $totalHonor, float $ratio): float
    {
        return round($totalHonor * $ratio, 2);
    }

    private function formatLampiranDate(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }

    private function formatLampiranDateRange(mixed $startDate, mixed $endDate): string
    {
        if (! $startDate && ! $endDate) {
            return '-';
        }

        if (! $startDate) {
            return $this->formatLampiranDate($endDate);
        }

        if (! $endDate) {
            return $this->formatLampiranDate($startDate);
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->year === $end->year) {
            if ($start->month === $end->month) {
                return $start->translatedFormat('d') . '-' . $end->translatedFormat('d F Y');
            }

            return $start->translatedFormat('d F') . '-' . $end->translatedFormat('d F Y');
        }

        return $start->translatedFormat('d F Y') . '-' . $end->translatedFormat('d F Y');
    }

    private function formatLampiranVolumeLabel(mixed $volume, ?string $unit): string
    {
        if ($volume === null || $volume === '' || (float) $volume <= 0) {
            return '-';
        }

        $formattedVolume = $this->formatLampiranVolumeNumber((float) $volume);

        return trim($formattedVolume . ' ' . ($unit ?? ''));
    }

    /**
     * Get uraian tugas details
     */
    private function getUraianTugas(Kegiatan $kegiatan, AlokasiPetugas $alokasi): array
    {
        $rateHonor = RateHonor::where('kegiatan_id', $kegiatan->id)
            ->where('jenis_penugasan', $alokasi->peran)
            ->where('status_kepegawaian', $alokasi->status_kepegawaian ?? ($alokasi->petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik'))
            ->with(['satuan', 'satuanListing'])
            ->first();

        $uraian = [];
        $periode = $alokasi->periodeAlokasi;

        if ($rateHonor) {
            // Check if this is a pengolahan role
            $isPengolahanRole = in_array($alokasi->peran, ['pengolahan', 'pengawas_pengolahan']);
            $effectiveListingVolume = $alokasi->getEffectiveJumlahSatuanListing();
            $effectiveListingHonor = $alokasi->getEffectiveTotalHonorListing();
            $effectivePencacahanVolume = $alokasi->getEffectiveJumlahSatuan();
            $effectivePencacahanHonor = $alokasi->getEffectiveTotalHonor();
            $unitSampelVolume = (int) ($alokasi->jumlah_unit_sampel ?? 0);

            if ($unitSampelVolume > 0) {
                if ($effectiveListingVolume > 0) {
                    $effectiveListingVolume = $unitSampelVolume;
                }

                if ($effectivePencacahanVolume > 0) {
                    $effectivePencacahanVolume = $unitSampelVolume;
                }
            }

            // Add listing task if exists
            if ($effectiveListingVolume > 0 && $effectiveListingHonor > 0) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'listing');

                // Use processing schedule for pengolahan roles, otherwise use regular schedule
                $tanggalMulai = $isPengolahanRole && $periode->jadwal_pengolahan_listing_mulai
                    ? $periode->jadwal_pengolahan_listing_mulai->format('Y-m-d')
                    : $periode->tanggal_mulai_listing?->format('Y-m-d');
                $tanggalSelesai = $isPengolahanRole && $periode->jadwal_pengolahan_listing_selesai
                    ? $periode->jadwal_pengolahan_listing_selesai->format('Y-m-d')
                    : $periode->tanggal_selesai_listing?->format('Y-m-d');

                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} Tahun {$kegiatan->tahun_anggaran} (Listing)",
                    'volume' => $effectiveListingVolume,
                    'satuan' => $rateHonor->satuanListing->kode ?? 'DOK',
                    'harga_satuan' => $effectiveListingVolume > 0 ? ($effectiveListingHonor / $effectiveListingVolume) : (float) ($rateHonor->rate_listing ?? 0),
                    'jumlah' => $effectiveListingHonor,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'phase' => 'listing',
                    'kode_coa' => $kegiatan->kode_coa, // Add COA per kegiatan
                ];
            }

            // Add regular task (pencacahan)
            if ($effectivePencacahanVolume > 0 && $effectivePencacahanHonor > 0) {
                $peranKegiatan = $this->getPeranKegiatan($alokasi->peran, 'pencacahan');

                // Use processing schedule for pengolahan roles, otherwise use regular schedule
                $tanggalMulai = $isPengolahanRole && $periode->jadwal_pengolahan_pencacahan_mulai
                    ? $periode->jadwal_pengolahan_pencacahan_mulai->format('Y-m-d')
                    : $periode->tanggal_mulai?->format('Y-m-d');
                $tanggalSelesai = $isPengolahanRole && $periode->jadwal_pengolahan_pencacahan_selesai
                    ? $periode->jadwal_pengolahan_pencacahan_selesai->format('Y-m-d')
                    : $periode->tanggal_selesai?->format('Y-m-d');

                $uraian[] = [
                    'uraian' => "Melakukan {$peranKegiatan} {$kegiatan->nama_kegiatan} bulan {$this->getBulanLabel($periode->bulan)} Tahun {$kegiatan->tahun_anggaran}",
                    'volume' => $effectivePencacahanVolume,
                    'satuan' => $rateHonor->satuan->kode ?? 'DOK',
                    'harga_satuan' => $effectivePencacahanVolume > 0 ? ($effectivePencacahanHonor / $effectivePencacahanVolume) : (float) ($rateHonor->rate ?? 0),
                    'jumlah' => $effectivePencacahanHonor,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'phase' => 'pencacahan',
                    'kode_coa' => $kegiatan->kode_coa, // Add COA per kegiatan
                ];
            }
        }

        return $uraian;
    }

    /**
     * Get peran kegiatan label based on role and phase
     */
    private function getPeranKegiatan(string $peran, string $phase): string
    {
        if ($phase === 'listing') {
            return match ($peran) {
                'pcl_ppl' => 'Pemutakhiran Lapangan',
                'pml' => 'Pemeriksaan Pemutakhiran Lapangan',
                'pengolahan' => 'Pengolahan Dokumen Pemutakhiran Lapangan',
                'pengawas_pengolahan' => 'Pemeriksaan Pengolahan Pemutakhiran Lapangan',
                default => 'Pemutakhiran Lapangan',
            };
        }

        // pencacahan
        return match ($peran) {
            'pcl_ppl' => 'Pendataan Lapangan',
            'pml' => 'Pemeriksaan Lapangan',
            'pengolahan' => 'Pengolahan Dokumen Lapangan',
            'pengawas_pengolahan' => 'Pemeriksaan Pengolahan Lapangan',
            default => 'Pendataan Lapangan',
        };
    }

    /**
     * Get beban anggaran (MAK)
     */
    private function getBebanAnggaran(Kegiatan $kegiatan): string
    {
        // Prioritaskan kode_coa dari kegiatan jika ada
        if (! empty($kegiatan->kode_coa)) {
            return $kegiatan->kode_coa;
        }

        // Fallback ke MAK dari DIPA
        $dipa = Dipa::active()->first();

        return $dipa->mak ?? '2904.BMA.006.005.A.521213';
    }

    /**
     * Get peran label
     */
    private function getPeranLabel(string $peran): string
    {
        return match ($peran) {
            'pcl_ppl' => 'Petugas Pencacahan',
            'pml' => 'Pemeriksa Lapangan/PML',
            'pengolahan' => 'Petugas Pengolahan',
            'pengawas_pengolahan' => 'Pemeriksa Pengolahan',
            default => $peran,
        };
    }

    /**
     * Detect work type from all allocations
     * Returns: 'lapangan', 'pengolahan', or 'lapangan_pengolahan'
     */
    private function detectWorkType(iterable $allAlokasi): string
    {
        $hasPengolahan = false;
        $hasLapangan = false;

        foreach ($allAlokasi as $alokasi) {
            $peran = $alokasi->peran ?? '';
            if (in_array($peran, ['pengolahan', 'pengawas_pengolahan'])) {
                $hasPengolahan = true;
            } else {
                $hasLapangan = true;
            }
        }

        if ($hasPengolahan && $hasLapangan) {
            return 'lapangan_pengolahan';
        } elseif ($hasPengolahan) {
            return 'pengolahan';
        } else {
            return 'lapangan';
        }
    }

    /**
     * Generate addendum PDF content
     */
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

        $timestamp = time() . '_' . uniqid();
        $mainPath = $tempPath . '/spk_addendum_main_' . $timestamp . '.pdf';
        $lampiranPath = $tempPath . '/spk_addendum_lampiran_' . $timestamp . '.pdf';
        $mergedPath = $tempPath . '/spk_addendum_merged_' . $timestamp . '.pdf';

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

        $existingSpks = (clone $existingSpkScopeQuery)
            ->whereIn('petugas_id', $petugasIdsInMonth)
            ->with('petugas')
            ->get()
            ->keyBy('petugas_id');

        // Check if this is a regenerate (existing SPKs present) or first time generate
        $isRegenerate = $existingSpks->isNotEmpty();

        // Determine the last nomor_urut_base from existing SPKs in this month
        $lastNomorUrutBase = null;
        if ($isRegenerate) {
            // Get the highest nomor_urut_base from existing SPKs in THIS MONTH only
            foreach ($existingSpks as $spk) {
                $baseNumber = $spk->nomor_urut_base ?? $this->extractNomorUrut($spk->nomor_spk);
                if ($lastNomorUrutBase === null || $baseNumber > $lastNomorUrutBase) {
                    $lastNomorUrutBase = $baseNumber;
                }
            }
        }

        // For first time generation, get next sequential nomor
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);
        $nomorUrutCounter = 0;
        $usesPeriodBasedNumbering = $this->usesPeriodBasedSpkFlow($periode);

        $nextSuffix = 'A';
        $results = [];

        foreach ($sortedPetugas as $petugasId => $alokasiGroup) {
            $petugas = Petugas::findOrFail($petugasId);
            $petugasHashedId = $petugas->hashed_id;

            // Check if this petugas already has an SPK
            $existingSpk = $existingSpks->get($petugasId);

            if ($existingSpk) {
                // Use existing nomor for updates
                $nomorSpk = $existingSpk->nomor_spk;
                $noUrut = $existingSpk->nomor_urut_base ?? $this->extractNomorUrut($nomorSpk);
            } else {
                // New petugas
                if ($usesPeriodBasedNumbering) {
                    if ($isRegenerate) {
                        $noUrut = ($lastNomorUrutBase ?? $nextNomorUrut) + 1;
                        $lastNomorUrutBase = $noUrut;
                    } else {
                        $noUrut = $nextNomorUrut + $nomorUrutCounter;
                        $nomorUrutCounter++;
                    }

                    $nomorSpk = $this->formatNomorSpkForPeriode($periode, $noUrut);
                } elseif ($isRegenerate) {
                    // Regenerate mode: Check if next sequential number is available
                    $nextSequential = ($lastNomorUrutBase ?? $nextNomorUrut) + 1;

                    // Check if this number is already used in ANY month this year
                    $numberUsed = Spk::where('nomor_urut_base', $nextSequential)
                        ->where('addendum_number', 0)
                        ->whereYear('tanggal_spk', $tahun)
                        ->exists();

                    if ($numberUsed) {
                        // Use suffix mode
                        $noUrut = $lastNomorUrutBase ?? $nextNomorUrut;
                        $nomorSpk = 'PPIS/13730/' . $noUrut . $nextSuffix . '/K/' . $tahun;
                    } else {
                        // Use sequential mode
                        $noUrut = $nextSequential;
                        $nomorSpk = $this->formatNomorSpkForPeriode($periode, $noUrut);
                        // Update lastNomorUrutBase for next iteration
                        $lastNomorUrutBase = $noUrut;
                    }
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
                $timestamp = time() . '_' . uniqid();
                $mainPath = $tempPath . '/spk_main_' . $timestamp . '.pdf';
                $lampiranPath = $tempPath . '/spk_lampiran_' . $timestamp . '.pdf';
                $mergedPath = $tempPath . '/spk_merged_' . $timestamp . '.pdf';
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
                $nomorParts = explode('/', $data['nomorSpk']);
                $nomorUrut = $nomorParts[2] ?? '0';

                // Check if SPK already exists for this petugas (use existing SPK from map)
                $existingSpkRecord = $existingSpk;
                $bulanLabel = $this->getBulanLabel($periode->bulan);
                $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
                $nomorUrut = $noUrut . (($isRegenerate && ! $existingSpk) ? $nextSuffix : '');
                $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}.pdf";
                $filePath = 'spk-export/' . date('Y') . '/' . date('m') . '/' . $fileName;
                $publicPath = public_path('spk-export/' . date('Y') . '/' . date('m'));
                if (! file_exists($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }
                file_put_contents(public_path($filePath), $pdfOutput);

                if ($existingSpkRecord) {
                    // Update existing SPK with new data
                    // Save previous file_path and signed_file_path before updating
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

                    // If there was a signed file, move it to previous_file_path and reset signed_file_path
                    if ($existingSpkRecord->signed_file_path) {
                        $updateData['previous_file_path'] = $existingSpkRecord->signed_file_path;
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

        return redirect()->route('spk.index')->with('success', $message);
    }

    /**
     * Check if there are new kegiatan/petugas added after SPK was generated
     */
    private function hasNewKegiatanAfterSpk(int $tahun, int $bulan, $monthPeriodes): bool
    {
        return $this->resolveRegenerateCandidatesForMonth($tahun, $bulan)->isNotEmpty();
    }

    /**
     * Check if there are new revisions after addendum was generated
     */
    private function hasNewRevisionAfterAddendum(int $tahun, int $bulan, iterable $monthPeriodes): bool
    {
        // Get the latest Addendum (SPK with addendum_number > 0) creation timestamp in this month
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

        $hasNewRevision = false;
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

                if (! $hasAddendum || $periode->updated_at > $latestAddendumCreatedAt) {
                    $hasNewRevision = true;
                    break 2;
                }
            }
        }

        return $hasNewRevision;
    }

    /**
     * Check if there are petugas with revisions who don't have addendum yet
     */
    private function hasIncompleteAddendum(int $tahun, int $bulan, $monthPeriodes): bool
    {
        return $this->resolveAddendumCandidatesForMonth($tahun, $bulan)
            ->contains(fn(array $item): bool => ! (bool) ($item['has_addendum'] ?? false));
    }

    /**
     * Check if there are allocation changes to petugas who already have addendum
     */
    private function hasAddendumChanges(int $tahun, int $bulan, $monthPeriodes): bool
    {
        return $this->resolveAddendumCandidatesForMonth($tahun, $bulan)
            ->contains(fn(array $item): bool => (bool) ($item['has_addendum'] ?? false));
    }

    /**
     * Build shared SPK action decisions for each petugas in a month.
     *
     * @return Collection<int, array{petugas_id:int,has_addendum:bool,should_regenerate:bool,should_addendum:bool}>
     */
    private function resolveSpkActionDecisionsForMonth(int $tahun, int $bulan): Collection
    {
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $allPeriodeInMonth = PeriodeAlokasi::whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', fn($q) => $q->where('jenis_kegiatan', '!=', 'sensus'))
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return collect();
        }

        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->with(['petugas', 'periodeAlokasi.kegiatan'])
            ->get()
            ->filter(function ($alokasi) {
                return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
            });

        if ($allAlokasi->isEmpty()) {
            return collect();
        }

        return $allAlokasi->groupBy('petugas_id')
            ->map(function (Collection $alokasiGroup) use ($tahun, $bulanFormatted) {
                $firstAlokasi = $alokasiGroup->first();

                if (! $firstAlokasi) {
                    return null;
                }

                $petugasId = (int) $firstAlokasi->petugas_id;

                $existingSpk = Spk::query()
                    ->where('petugas_id', $petugasId)
                    ->where('addendum_number', 0)
                    ->whereYear('tanggal_spk', $tahun)
                    ->whereMonth('tanggal_spk', (int) $bulanFormatted)
                    ->orderBy('created_at', 'asc')
                    ->first();

                // Scope hasExistingAddendum to the existingSpk chain only.
                // A petugas with multiple original SPKs in the same month (e.g. one for
                // January allocations and another for February allocations but dated Jan-30)
                // must not have addendums from the wrong chain counted here.
                $hasExistingAddendum = $existingSpk
                    ? Spk::where('parent_spk_id', $existingSpk->id)
                    ->where('addendum_number', '>', 0)
                    ->exists()
                    : false;

                if (! $existingSpk) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => false,
                        'should_regenerate' => true,
                        'should_addendum' => false,
                    ];
                }

                $delta = $this->analyzeAllocationDeltaForPetugas(
                    $petugasId,
                    $bulanFormatted,
                    $tahun,
                    'same_month_original_spk',
                );

                $shouldRegenerate = ! $hasExistingAddendum
                    && $delta['is_allocation_incomplete']
                    && $delta['has_new_kegiatan_added']
                    && ! $delta['has_allocation_change'];

                // Scope latestDocument to the existingSpk chain only (original + its addendums).
                // Do NOT include other original SPKs that happen to share the same calendar month,
                // as those may contain allocations from a different month scope.
                $latestDocument = Spk::query()
                    ->where('petugas_id', $petugasId)
                    ->where(function ($q) use ($existingSpk) {
                        $q->where('id', $existingSpk->id)
                            ->orWhere('parent_spk_id', $existingSpk->id);
                    })
                    ->orderBy('addendum_number', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (! $latestDocument) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => $hasExistingAddendum,
                        'should_regenerate' => $shouldRegenerate,
                        'should_addendum' => false,
                    ];
                }

                $latestSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
                    $petugasId,
                    $latestDocument,
                    $bulanFormatted,
                    $tahun,
                );

                $effectiveAlokasiByKegiatan = $this->getEffectiveAlokasiByKegiatan($alokasiGroup);
                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                if ($currentTotalHonor <= 0) {
                    return [
                        'petugas_id' => $petugasId,
                        'has_addendum' => $hasExistingAddendum,
                        'should_regenerate' => false,
                        'should_addendum' => false,
                    ];
                }

                $currentSnapshot = $effectiveAlokasiByKegiatan
                    ->mapWithKeys(function ($alokasi, $kegiatanId) {
                        return [
                            (int) $kegiatanId => [
                                'alokasi_id' => (int) ($alokasi->id ?? 0),
                                'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                                'peran' => $alokasi?->peran,
                                'jumlah_satuan' => (int) ($alokasi->jumlah_satuan ?? 0),
                                'jumlah_satuan_listing' => (int) ($alokasi->jumlah_satuan_listing ?? 0),
                                'total_honor' => (float) ($alokasi->total_honor ?? 0),
                                'total_honor_listing' => (float) ($alokasi->total_honor_listing ?? 0),
                            ],
                        ];
                    })
                    ->all();

                // Detect meaningful perubahan change by comparing perubahan vs direvisi allocations
                // for the same kegiatan. This is more accurate than comparing document snapshots
                // because document may already contain both direvisi and perubahan allocations.
                $hasMeaningfulPerubahanChange = $this->detectMeaningfulPerubahanChange($alokasiGroup);

                // Build current snapshot for comparison
                $currentSnapshot = $effectiveAlokasiByKegiatan
                    ->mapWithKeys(function ($alokasi, $kegiatanId) {
                        return [
                            (int) $kegiatanId => [
                                'alokasi_id' => (int) ($alokasi->id ?? 0),
                                'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                                'peran' => $alokasi?->peran,
                                'jumlah_satuan' => (int) ($alokasi->jumlah_satuan ?? 0),
                                'jumlah_satuan_listing' => (int) ($alokasi->jumlah_satuan_listing ?? 0),
                                'total_honor' => (float) ($alokasi->total_honor ?? 0),
                                'total_honor_listing' => (float) ($alokasi->total_honor_listing ?? 0),
                            ],
                        ];
                    })
                    ->all();

                // Check if the change is already covered by an existing ADDENDUM (not original SPK).
                // Original SPK may incorrectly include perubahan allocations due to timing,
                // but we still need an addendum to formally document the change.
                // Only suppress addendum button if there's already an addendum that covers the change.
                $isChangeAlreadyCovered = false;
                if ($hasExistingAddendum && $latestDocument && $latestDocument->addendum_number > 0) {
                    $documentSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
                        $petugasId,
                        $latestDocument,
                        $bulanFormatted,
                        $tahun,
                    );

                    // Check if snapshots match (kegiatan-wise comparison)
                    $isChangeAlreadyCovered = $this->snapshotsMatch($documentSnapshot, $currentSnapshot);
                }

                // Addendum is only needed when there's a meaningful change in perubahan allocations
                // AND the change is not yet covered by an existing document (addendum or regenerated SPK).
                return [
                    'petugas_id' => $petugasId,
                    'has_addendum' => $hasExistingAddendum,
                    'should_regenerate' => $shouldRegenerate,
                    'should_addendum' => $hasMeaningfulPerubahanChange && ! $isChangeAlreadyCovered,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Resolve regenerate candidates for a month using the same filtering logic as create() regenerate petugas_list.
     *
     * @return Collection<int, int>
     */
    private function resolveRegenerateCandidatesForMonth(int $tahun, int $bulan): Collection
    {
        $existingSpks = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->get();

        if ($existingSpks->isEmpty()) {
            return collect();
        }

        // Only consider regenerate candidates among petugas who already have an ORIGINAL SPK
        // in this calendar month. This prevents "re-generate" being triggered for
        // petugas who simply don't yet have an SPK (initial generation).
        $existingPetugasIds = $existingSpks->pluck('petugas_id')->map(fn($id) => (int) $id)->unique();

        return $this->resolveSpkActionDecisionsForMonth($tahun, $bulan)
            ->filter(fn(array $item): bool => (bool) ($item['should_regenerate'] ?? false))
            ->pluck('petugas_id')
            ->map(static fn($petugasId) => (int) $petugasId)
            ->unique()
            ->intersect($existingPetugasIds)
            ->values();
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
                $effective = $kegiatanGroup->first(fn($a) => ($a->periodeAlokasi->status ?? '') === 'perubahan')
                    ?? $kegiatanGroup->first(fn($a) => ($a->periodeAlokasi->status ?? '') === 'disetujui')
                    ?? $kegiatanGroup->first(fn($a) => ($a->periodeAlokasi->status ?? '') === 'dikirim')
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

            // Find corresponding direvisi allocation for the same kegiatan
            $direvisi = $kegiatanAlokasi->first(function ($alokasi) {
                return ($alokasi->periodeAlokasi?->status ?? '') === 'direvisi';
            });

            // If no direvisi exists, check disetujui or dikirim as reference
            $reference = $direvisi
                ?? $kegiatanAlokasi->first(fn($a) => ($a->periodeAlokasi?->status ?? '') === 'disetujui')
                ?? $kegiatanAlokasi->first(fn($a) => ($a->periodeAlokasi?->status ?? '') === 'dikirim');

            if (! $reference) {
                // perubahan exists but no reference - this is a new kegiatan via perubahan
                // which should also trigger addendum
                continue;
            }

            // Compare perubahan vs reference (direvisi/disetujui/dikirim)
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
        // Get all allocations for this petugas in this month (all statuses)
        $alokasiQuery = AlokasiPetugas::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun, $upToCreatedAt) {
                $q->whereRaw("LPAD(CAST(bulan AS UNSIGNED), 2, '0') = ?", [$bulanFormatted])
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'perubahan'])
                    ->whereHas('kegiatan', fn($qq) => $qq->where('jenis_kegiatan', '!=', 'sensus'));

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
        // Priority: perubahan > disetujui > dikirim (direvisi and draft are not valid for SPK).
        $snapshot = $alokasiQuery
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Apply priority: perubahan > disetujui > dikirim
                $effective = $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'perubahan')
                    ?? $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'disetujui')
                    ?? $kegiatanGroup->first(fn($a) => $a->periodeAlokasi->status === 'dikirim');

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
