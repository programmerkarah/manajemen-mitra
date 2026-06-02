<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
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
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $activeYear = ActiveYearService::get();

        // Get periode alokasi yang sudah validated grouped by month
        $query = PeriodeAlokasi::query()
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan,jenis_kegiatan,tahun_anggaran',
                'alokasiPetugas:id,periode_alokasi_id,petugas_id,total_honor,total_honor_listing',
                'alokasiPetugas.petugas:id,nama,nik,jenis_petugas',
                'spk:spk.id,alokasi_petugas_id,addendum_number,regeneration_count,spk.created_at',
            ])
            ->select('periode_alokasi.*') // Select all columns from periode_alokasi
            ->whereHas('kegiatan', function ($q) use ($activeYear) {
                $q->where('tahun_anggaran', $activeYear);
            })
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->where('tahun', $activeYear);

        $periodes = $query->latest()->get();

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
                        $perubahan = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                        if ($perubahan) {
                            return $perubahan;
                        }

                        $direvisi = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                        if ($direvisi) {
                            return $direvisi;
                        }

                        $disetujui = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                        if ($disetujui) {
                            return $disetujui;
                        }

                        return $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
                    })->filter();

                    // Only include petugas if they have positive honor
                    $hasPositiveHonor = $effectiveAlokasi->contains(function ($alokasi) {
                        return ($alokasi->total_honor ?? 0) > 0 || ($alokasi->total_honor_listing ?? 0) > 0;
                    });

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
                        $perubahan = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                        if ($perubahan) {
                            return $perubahan;
                        }

                        $direvisi = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                        if ($direvisi) {
                            return $direvisi;
                        }

                        $disetujui = $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                        if ($disetujui) {
                            return $disetujui;
                        }

                        return $kegiatanAlokasi->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
                    });
                })
                ->filter(function ($alokasi) {
                    // Only include allocations with positive honor
                    return ($alokasi->total_honor ?? 0) > 0 || ($alokasi->total_honor_listing ?? 0) > 0;
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
                'kegiatan_list' => $kegiatanList,
            ];
        })->sortByDesc(function ($item) {
            return $item['tahun'].str_pad($item['bulan'], 2, '0', STR_PAD_LEFT);
        })->values();

        // Paginate manually
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedItems = $groupedByMonth->slice($offset, $perPage)->values();
        $total = $groupedByMonth->count();

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Encrypt sensitive data
        $encryptedData = encryptData($paginatedItems);

        return Inertia::render('Spk/Index', [
            'periodeList' => [
                'encrypted' => $encryptedData,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'links' => $paginator->linkCollection()->toArray(),
            ],
            'filters' => [
                'encrypted' => encryptFilters($validated),
                'decrypted' => $validated,
            ],
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

        return $this->renderShowByMonth($bulan, $tahun, $spkHashedId);
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

        return $this->renderShowByMonth($bulan, $tahun, $spkHashedId);
    }

    /**
     * Internal method to render ShowByMonth view
     */
    private function renderShowByMonth(?string $bulan, ?string $tahun, ?string $spkHashedId): Response|RedirectResponse
    {

        if (! $bulan || ! $tahun) {
            return redirect()->route('spk.index');
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month (include 'direvisi' and 'perubahan')
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', function ($q) {
                $q->where('jenis_kegiatan', 'survei'); // Only survei activities
            })
            ->pluck('id');

        // Get all SPKs in this month
        $allSpks = Spk::with(['alokasiPetugas.petugas'])
            ->whereIn('alokasi_petugas_id', function ($query) use ($allPeriodeInMonth) {
                $query->select('id')
                    ->from('alokasi_petugas')
                    ->whereIn('periode_alokasi_id', $allPeriodeInMonth);
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
                'petugas_nama' => $s->alokasiPetugas->petugas->nama,
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
            'nama' => $petugas->nama,
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
                $zipFileNameInArchive = preg_replace('/\.pdf$/i', '', $fileName).'_ADDENDUM.pdf';
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

        // Check if all petugas have signed files that exist physically
        $missingSignedFiles = $allSpks->filter(function ($spk) {
            // For main SPKs (addendum 0), check file_path; for addendum, check signed_file_path
            if ($spk->addendum_number == 0) {
                return empty($spk->file_path) || ! file_exists(public_path($spk->file_path));
            } else {
                return empty($spk->signed_file_path) || ! file_exists(public_path($spk->signed_file_path));
            }
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
        // Masukkan SPK utama
        foreach ($mainSpks as $spk) {
            $fileToUse = $spk->signed_file_path ?? $spk->file_path;
            $filePath = public_path($fileToUse);
            if (file_exists($filePath)) {
                $petugasName = preg_replace('/[\/\\:*?"<>|]/', '_', $spk->alokasiPetugas->petugas->nama);
                $fileName = basename($fileToUse);
                $zipFileNameInArchive = "{$petugasName}_{$fileName}";
                $zip->addFile($filePath, $zipFileNameInArchive);
                $filesAdded++;
            }
        }

        // Masukkan addendum (gunakan signed_file_path jika ada, fallback ke file_path)
        foreach ($addendumSpks as $spk) {
            $fileToUse = $spk->signed_file_path ?? $spk->file_path;
            if ($fileToUse) {
                $filePath = public_path($fileToUse);
                if (file_exists($filePath)) {
                    $petugasName = preg_replace('/[\/\\:*?"<>|]/', '_', $spk->alokasiPetugas->petugas->nama);
                    $fileName = basename($fileToUse);
                    // Add ADDENDUM suffix to filename for clarity
                    $baseFileName = preg_replace('/\.pdf$/i', '', $fileName);
                    $zipFileNameInArchive = "{$petugasName}_{$baseFileName}_ADDENDUM_{$spk->addendum_number}.pdf";
                    $zip->addFile($filePath, $zipFileNameInArchive);
                    $filesAdded++;
                }
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
        // Add each SPK file to ZIP with organized folder structure
        foreach ($allSpks as $spk) {
            // Use signed file if available, otherwise use regular file
            $fileToUse = $spk->signed_file_path ?? $spk->file_path;
            if (! $fileToUse) {
                continue;
            }

            $filePath = public_path($fileToUse);

            if (file_exists($filePath)) {
                $fileName = basename($fileToUse);
                // Add file with petugas name in the filename for better organization
                $petugasName = preg_replace('/[\/\\\:*?"<>|]/', '_', $spk->alokasiPetugas->petugas->nama);

                // Add ADDENDUM suffix for addendum SPKs
                if ($spk->addendum_number > 0) {
                    $baseFileName = preg_replace('/\.pdf$/i', '', $fileName);
                    $zipFileNameInArchive = "{$petugasName}_{$baseFileName}_ADDENDUM_{$spk->addendum_number}.pdf";
                } else {
                    $zipFileNameInArchive = "{$petugasName}_{$fileName}";
                }

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
        $bulanLabel = $this->getBulanLabel($periode->bulan);

        $fileName = "SPK_{$nomorUrut}_{$namaPetugas}_{$bulanLabel}_signed.pdf";
        $filePath = 'spk-export/'.date('Y').'/'.date('m').'/'.$fileName;

        // Create directory if not exists
        $publicPath = public_path('spk-export/'.date('Y').'/'.date('m'));
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

        return 'PPIS/13730/'.$nomorUrut.'/K/'.$periode->tahun;
    }

    private function generateSignedDownloadUrl(string $filename): string
    {
        // Return direct static URL untuk better CDN caching
        // File di-serve langsung oleh web server (Nginx/Apache), bukan PHP
        return '/downloads/'.rawurlencode($filename);
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
            'Content-Disposition' => $disposition.'; filename="'.$responseFilename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function buildPublicPreviewSessionSignature(string $nama, string $nik, string $telepon4Digit): string
    {
        return hash('sha256', mb_strtolower(trim($nama)).'|'.trim($nik).'|'.trim($telepon4Digit));
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
            ->map(fn (Spk $spk): string => public_path((string) $spk->signed_file_path))
            ->filter(fn (string $path): bool => is_file($path))
            ->values()
            ->all();

        if (empty($signedPaths)) {
            return null;
        }

        $cacheKey = $this->buildPublicPreviewProtectedCacheKey($signedPaths);

        $baseName = pathinfo((string) $finalSpk->signed_file_path, PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $baseName) ?: 'spk_final';

        $downloadFilename = count($signedPaths) === 1
            ? 'Preview_'.$safeBaseName.'.pdf'
            : 'Preview_'.$safeBaseName.'_with_addendum.pdf';

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

        $token = time().'_'.uniqid();
        $mergedPath = $tempPath.'/spk_public_preview_signed_merge_'.$token.'.pdf';

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

                return $realPath.'|'.$modifiedTime.'|'.$fileSize;
            })
            ->implode('||');

        return hash('sha256', 'public-preview-v2|'.$fingerprint);
    }

    private function getCachedProtectedPublicPreviewPdfPath(string $cacheKey): ?string
    {
        $cachePath = storage_path('app/temp/public_preview_protected_'.$cacheKey.'.pdf');

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

        @file_put_contents($tempPath.'/public_preview_protected_'.$cacheKey.'.pdf', $protectedPdfContent);
    }

    private function storePublicPreviewTemporaryPdf(string $pdfContent): ?string
    {
        $tempPath = storage_path('app/temp');
        if (! $this->ensureDirectoryExists($tempPath)) {
            return null;
        }

        try {
            $filename = 'public_preview_runtime_'.bin2hex(random_bytes(16)).'.pdf';
        } catch (\Throwable) {
            $filename = 'public_preview_runtime_'.uniqid('', true).'.pdf';
        }

        $filePath = $tempPath.'/'.$filename;
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

        $filePath = storage_path('app/temp/'.$file);
        if (! is_file($filePath)) {
            abort(404);
        }

        $filename = (string) $request->query('filename', 'Preview_SPK.pdf');
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename) ?: 'Preview_SPK.pdf';
        $disposition = (string) $request->query('disposition', 'inline');
        $safeDisposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return $this->buildPublicPreviewFileResponse($filePath, $safeFilename, $safeDisposition);
    }

    private function ensureDirectoryExists(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, 0777, true) || is_dir($path);
    }

    private function buildPublicPreviewFileResponse(string $filePath, string $responseFilename, string $disposition, string $downloadToken = '')
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($disposition === 'attachment') {
            $response = response()->download($filePath, $responseFilename, $headers);

            return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
        }

        $response = response()->file($filePath, $headers + [
            'Content-Disposition' => 'inline; filename="'.$responseFilename.'"',
        ]);

        return $this->appendPublicPreviewDownloadCookie($response, $disposition, $downloadToken);
    }

    private function appendPublicPreviewDownloadCookie($response, string $disposition, string $downloadToken)
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

        $penugasanList = $alokasiCollection
            ->map(function (AlokasiPetugas $alokasi) use ($documentStatusMap): ?array {
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

                return [
                    'id' => $alokasi->id,
                    'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                    'kegiatan_hashed_id' => $kegiatan->hashed_id,
                    'periode_key' => $periodKey,
                    'periode_label' => $this->getBulanLabel((int) $periode->bulan).' '.(int) $periode->tahun,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'target_pekerjaan' => $this->resolvePublicPreviewTargetPekerjaan($alokasi),
                    'honor' => (float) $alokasi->getEffectiveCombinedHonor(),
                    'honor_label' => 'Rp '.number_format((float) $alokasi->getEffectiveCombinedHonor(), 0, ',', '.'),
                    'document_status' => $documentStatus,
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
                    'label' => $this->getBulanLabel((int) $bulan).' '.(int) $tahun,
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

            $hasMainSigned = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
            $hasAddendumDraft = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
            $hasAddendumSigned = collect($groupDocuments)->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

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
        return $periodKey.'|'.mb_strtolower($jenisKegiatan);
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

        return number_format($targetValue, 0, ',', '.').' '.$rateHonor->satuan->nama;
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
            return $rateHonor->status_kepegawaian.'|'.$rateHonor->jenis_penugasan;
        });

        $statusKepegawaian = $alokasi->status_kepegawaian
            ?? (($alokasi->petugas->jenis_petugas ?? 'non-organik') === 'organik' ? 'organik' : 'non_organik');

        return $rateHonorByKey->get($statusKepegawaian.'|'.$alokasi->peran)
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

        $hasMainSigned = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number === 0 && ! empty($spk->signed_file_path));
        $hasAddendumDraft = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && empty($spk->signed_file_path));
        $hasAddendumSigned = $documents->contains(fn (Spk $spk): bool => (int) $spk->addendum_number > 0 && ! empty($spk->signed_file_path));

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

        $token = time().'_'.uniqid();
        $inputPath = $tempPath.'/spk_public_preview_input_'.$token.'.pdf';
        $outputPath = $tempPath.'/spk_public_preview_output_'.$token.'.pdf';

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

        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);
        $hasDraftPeriode = $this->hasDraftPeriodeInSpkScope($periode);

        // Get all unique non-organik petugas from the SPK scope.
        // Only include alokasi with total_honor > 0 (either pencacahan or listing)
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
                'periodeAlokasi:id,kegiatan_id,jenis_kegiatan,status',
                'periodeAlokasi.kegiatan:id,kode_kegiatan,nama_kegiatan',
            ])
            ->get();

        // Group by petugas_id and aggregate their data
        $petugasList = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) {
                $firstAlokasi = $alokasiGroup->first();

                // Calculate total honor across all kegiatan
                $totalHonor = $alokasiGroup->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                // Get all kegiatan with their peran
                $kegiatanList = $alokasiGroup->map(function ($alokasi) {
                    return [
                        'kegiatan_id' => $alokasi->periodeAlokasi->kegiatan->id,
                        'kegiatan_kode' => $alokasi->periodeAlokasi->kegiatan->kode_kegiatan,
                        'kegiatan_nama' => $alokasi->periodeAlokasi->kegiatan->nama_kegiatan,
                        'peran' => $alokasi->peran,
                    ];
                })->values()->all();

                return [
                    'alokasi_id' => $firstAlokasi->id,
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
            })
            ->sortBy(function ($item) {
                return $item['petugas']['nama'];
            })
            ->values();

        // Get next nomor urut for this year
        $nextNomorUrut = $this->getNextNomorUrutForPeriode($periode);

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

            // Filter petugas list for regenerate mode
            // Only show: 1) New petugas (not in existingSpkMap), 2) Petugas with kegiatan additions
            $petugasList = $petugasList->filter(function ($item) use ($existingKegiatanPerPetugas) {
                $petugasId = $item['petugas']['id'];

                // For existing petugas, check if they have NEW kegiatan (additions only)
                $currentKegiatanIds = collect($item['kegiatan_list'])
                    ->pluck('kegiatan_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                $oldKegiatanIds = $existingKegiatanPerPetugas[$petugasId] ?? [];

                // Include only if there are NEW kegiatan (current has kegiatan that old doesn't have)
                $hasNewKegiatan = count(array_diff($currentKegiatanIds, $oldKegiatanIds)) > 0;

                return $hasNewKegiatan;
            })->values();

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
        } else {
            // Generate mode: Filter out petugas who already have SPK in this month
            // Only show petugas who DON'T have SPK yet
            $petugasIds = $petugasList->pluck('petugas.id')->unique();
            $existingSpkPetugasIds = (clone $existingSpkQuery)
                ->whereIn('petugas_id', $petugasIds)
                ->pluck('petugas_id')
                ->toArray();

            // Filter: only show petugas who don't have SPK yet
            $petugasList = $petugasList->filter(function ($item) use ($existingSpkPetugasIds) {
                $petugasId = $item['petugas']['id'];
                $notHaveSpk = ! in_array($petugasId, $existingSpkPetugasIds);

                return $notHaveSpk;
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
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $requestedMode = $request->input('mode');

        if (! $periodeId || ! $bulan || ! $tahun) {
            abort(404);
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return redirect()->route('spk.index')->with('error', 'Tidak ada periode valid untuk bulan ini.');
        }

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

        // Determine if this is regenerate mode or generate mode
        // Check if any petugas in this month already have addendum
        // Use proper query with whereHas for accurate checking
        $petugasWithAddendum = Spk::where('addendum_number', '>', 0)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun);
            })
            ->distinct()
            ->pluck('petugas_id')
            ->toArray();

        $resolvedMode = in_array($requestedMode, ['addendum', 'regenerate'], true)
            ? $requestedMode
            : (! empty($petugasWithAddendum) ? 'regenerate' : 'addendum');
        $isRegenerateAddendum = $resolvedMode === 'regenerate';

        // Group by petugas_id and aggregate their data
        $petugasListRaw = $allAlokasi->groupBy('petugas_id')
            ->map(function ($alokasiGroup) use ($bulanFormatted, $tahun, $petugasWithAddendum) {
                $firstAlokasi = $alokasiGroup->first();

                // Get existing SPK for this petugas in this month
                $existingSpk = Spk::whereHas('alokasiPetugas', function ($q) use ($firstAlokasi, $bulanFormatted, $tahun) {
                    $q->where('petugas_id', $firstAlokasi->petugas_id)
                        ->whereHas('periodeAlokasi', function ($q2) use ($bulanFormatted, $tahun) {
                            $q2->where('bulan', $bulanFormatted)
                                ->where('tahun', $tahun);
                        });
                })
                    ->where('addendum_number', 0) // Get original SPK only
                    ->first();

                if (! $existingSpk) {
                    return null; // Skip petugas without original SPK
                }

                // Get latest document (original SPK or latest addendum) to compare with current state
                $latestDocument = Spk::where('petugas_id', $firstAlokasi->petugas_id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                        $q->where('bulan', $bulanFormatted)
                            ->where('tahun', $tahun);
                    })
                    ->orderBy('addendum_number', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (! $latestDocument) {
                    return null;
                }

                // Get alokasi_petugas_ids from latest document
                $latestAlokasIds = $latestDocument->alokasi_petugas_ids ?? [$latestDocument->alokasi_petugas_id];

                // Build reference snapshot from latest document using meaningful allocations only.
                $latestSnapshot = $this->buildEffectiveAllocationSnapshotForPetugasFromDocument(
                    (int) $firstAlokasi->petugas_id,
                    $latestDocument,
                    $bulanFormatted,
                    (int) $tahun,
                );
                $latestPeriodeIds = collect(array_keys($latestSnapshot))
                    ->map(static fn ($kegiatanId) => (int) $kegiatanId)
                    ->sort()
                    ->values()
                    ->toArray();

                // Get current effective allocations (latest status for each kegiatan)
                $effectiveAlokasiByKegiatan = $this->getEffectiveAlokasiByKegiatan($alokasiGroup);

                // Get current periode_alokasi_ids
                $currentPeriodeIds = $effectiveAlokasiByKegiatan
                    ->pluck('periodeAlokasi.kegiatan_id')
                    ->map(static fn ($kegiatanId) => (int) $kegiatanId)
                    ->sort()
                    ->values()
                    ->toArray();

                // Calculate current total honor
                $currentTotalHonor = $effectiveAlokasiByKegiatan->sum(function ($alokasi) {
                    return ($alokasi->total_honor ?? 0) + ($alokasi->total_honor_listing ?? 0);
                });

                // Calculate nilai_kontrak from latest document
                $latestNilaiKontrak = (float) $latestDocument->nilai_kontrak;

                // Check if addendum is needed:
                // 1. periode_alokasi_ids changed (new kegiatan added or removed)
                // 2. OR nilai_kontrak changed
                $periodeIdsChanged = $latestPeriodeIds !== $currentPeriodeIds;
                $nilaiKontrakChanged = abs($latestNilaiKontrak - $currentTotalHonor) > 0.01;

                $needsAddendum = $periodeIdsChanged || $nilaiKontrakChanged;

                // Only show petugas who need addendum
                if (! $needsAddendum) {
                    return null;
                }

                // Check if there's any change after latest document was created
                $hasChangeAfterLatestDocument = $this->hasAllocationDeltaAfterReferenceForPetugas(
                    $firstAlokasi->petugas_id,
                    $bulanFormatted,
                    (int) $tahun,
                    $latestDocument->created_at,
                );

                if (! $hasChangeAfterLatestDocument) {
                    return null;
                }

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
                    })->values()->all();

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

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        // If no eligible petugas for addendum, block access
        if ($petugasListRaw->isEmpty()) {
            abort(404, 'Tidak ada petugas yang dapat dibuatkan addendum SPK.');
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
            ->filter(function ($alokasi) {
                return $alokasi && $this->isMeaningfulAllocation($alokasi);
            });
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
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        // Get all alokasi for this petugas in the same month (from 'dikirim' and 'perubahan')
        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->where('petugas_id', $petugasId)
            ->with(['periodeAlokasi.kegiatan.rateHonors.satuan'])
            ->get();

        $allAlokasi = $allAlokasi->filter(function ($alokasi) {
            return $alokasi->getEffectiveCombinedHonor() > 0;
        })->values();

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

            $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
                ->where('tahun', $tahun)
                ->whereIn('status', ['dikirim', 'perubahan'])
                ->pluck('id');

            $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->where('petugas_id', $petugasId)
                ->with(['periodeAlokasi.kegiatan.rateHonors.satuan'])
                ->get();

            $allAlokasi = $allAlokasi->filter(function ($alokasi) {
                return $alokasi->getEffectiveCombinedHonor() > 0;
            })->values();

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
            ->filter(fn ($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
            ->unique('petugas_hashed_id')
            ->values();

        if ($previewItems->isEmpty()) {
            return response()->json([
                'message' => 'Daftar petugas preview tidak valid.',
            ], 422);
        }

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
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
        ]);

        $periode = PeriodeAlokasi::with('kegiatan')->findOrFail($periodeId);

        $previewItems = $this->decodeAndSortPreviewItems((string) $validated['preview_items_json']);

        if ($previewItems->isEmpty()) {
            return response()->json(['message' => 'Daftar petugas tidak valid.'], 422);
        }

        $tempPath = storage_path('app/temp');
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
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
            file_put_contents($path, $pdfBinary);
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
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
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
            file_put_contents($path, $pdfBinary);
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
    private function decodeAndSortPreviewItems(string $json): Collection
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return collect();
        }

        return collect($decoded)
            ->filter(fn ($item) => ! empty($item['petugas_hashed_id']) && ! empty($item['nomor_spk']))
            ->unique('petugas_hashed_id')
            ->sortBy(fn ($item) => mb_strtolower((string) ($item['petugas_nama'] ?? $item['nomor_spk'])))
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
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode): void {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
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
        $filename = 'Print_PK_Main_'.$sanitizedName.'.pdf';

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
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode): void {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
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
        $filename = 'Print_Lampiran_'.$sanitizedName.'.pdf';

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

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
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
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
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
        $filename = 'Preview_SPK_'.$sanitizedName.'.pdf';

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

        $timestamp = time().'_'.uniqid();
        $mainPath = $tempPath.'/spk_main_'.$timestamp.'.pdf';
        $lampiranPath = $tempPath.'/spk_lampiran_'.$timestamp.'.pdf';
        $mergedPath = $tempPath.'/spk_merged_'.$timestamp.'.pdf';

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

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
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
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
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

        // Get all alokasi for this petugas in the same month
        // For regular SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'perubahan']);
            })
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
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'direvisi']);
            })
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

        // Get all alokasi for this petugas in the same month
        $allAlokasi = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
            ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                $q->where('bulan', $periode->bulan)
                    ->where('tahun', $periode->tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'perubahan']);
            })
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
            // Extract nomor urut from nomor_spk format: PPIS/13730/4/K/2025 -> get "4"
            $nomorUrut = (string) $this->extractNomorUrut((string) $data['nomorSpk']);

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
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulanLabels[$bulan] ?? '';
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

        if ($this->usesPeriodBasedSpkFlow($periode)) {
            $query->whereKey($periode->id);
        } else {
            $query->where('bulan', $periode->bulan)
                ->where('tahun', $periode->tahun);
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

        return PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
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
            return 'periode-'.$periode->id;
        }

        return $periode->tahun.'-'.$periode->bulan;
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
            && mb_strtolower(trim((string) $kegiatan->nama_kegiatan)) === 'sensus ekonomi';
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
        $perUnitSampelTotals = $metrics['per_unit_sampel_totals'];
        $unitSampelNames = $metrics['unit_sampel_names'];
        $baseVolumeLabel = ($selectedRows > 0 || array_sum($perUnitSampelTotals) > 0)
            ? $this->formatSensusEkonomiVolumeNarrative($selectedRows, $perUnitSampelTotals, $unitSampelNames)
            : $fallbackVolumeLabel;

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $terminSatuMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $perUnitSampelTotals, 40);
        $terminDuaMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $perUnitSampelTotals, 60);

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative(
            $terminSatuMetrics['selected_rows'],
            $terminSatuMetrics['per_unit_sampel_totals'],
            $unitSampelNames
        );
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative(
            $terminDuaMetrics['selected_rows'],
            $terminDuaMetrics['per_unit_sampel_totals'],
            $unitSampelNames
        );
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
        $perUnitSampelTotals = $metrics['per_unit_sampel_totals'];
        $unitSampelNames = $metrics['unit_sampel_names'];

        $periodeMulai = $periode?->tanggal_mulai;
        $periodeSelesai = $periode?->tanggal_selesai;

        $terminSatuAmount = $this->calculateLampiranMilestoneAmount($totalHonor, 0.40);
        $terminDuaAmount = round($totalHonor - $terminSatuAmount, 2);

        $terminSatuMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $perUnitSampelTotals, 40);
        $terminDuaMetrics = $this->calculateSensusEkonomiMilestoneMetrics($selectedRows, $perUnitSampelTotals, 60);

        $terminSatuVolume = $this->formatSensusEkonomiVolumeNarrative(
            $terminSatuMetrics['selected_rows'],
            $terminSatuMetrics['per_unit_sampel_totals'],
            $unitSampelNames
        );
        $terminDuaVolume = $this->formatSensusEkonomiVolumeNarrative(
            $terminDuaMetrics['selected_rows'],
            $terminDuaMetrics['per_unit_sampel_totals'],
            $unitSampelNames
        );
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
     * @param  array<int, int>  $perUnitSampelTotals
     * @return array{selected_rows: int, per_unit_sampel_totals: array<int, int>}
     */
    private function calculateSensusEkonomiMilestoneMetrics(int $selectedRows, array $perUnitSampelTotals, int $percentage): array
    {
        $selectedRows = max(0, $selectedRows);

        $terminSatuSelectedRows = (int) round($selectedRows * 0.4, 0, PHP_ROUND_HALF_UP);

        $terminSatuPerUnit = [];
        foreach ($perUnitSampelTotals as $unitId => $total) {
            $terminSatuPerUnit[$unitId] = (int) round(max(0, $total) * 0.4, 0, PHP_ROUND_HALF_UP);
        }

        if ($percentage === 40) {
            return [
                'selected_rows' => $terminSatuSelectedRows,
                'per_unit_sampel_totals' => $terminSatuPerUnit,
            ];
        }

        $terminDuaPerUnit = [];
        foreach ($perUnitSampelTotals as $unitId => $total) {
            $terminDuaPerUnit[$unitId] = max(0, max(0, $total) - ($terminSatuPerUnit[$unitId] ?? 0));
        }

        return [
            'selected_rows' => max(0, $selectedRows - $terminSatuSelectedRows),
            'per_unit_sampel_totals' => $terminDuaPerUnit,
        ];
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
        foreach ($frameAllocations as $frameAllocation) {
            $targetUnitSampel = $frameAllocation?->kegiatanFrameSampel?->target_unit_sampel;
            if (is_array($targetUnitSampel)) {
                foreach ($targetUnitSampel as $unitSampelId => $count) {
                    $uid = (int) $unitSampelId;
                    $perUnitSampelTotals[$uid] = ($perUnitSampelTotals[$uid] ?? 0) + max(0, (int) $count);
                }
            } elseif (is_numeric($targetUnitSampel) && (int) $targetUnitSampel > 0) {
                $perUnitSampelTotals[0] = ($perUnitSampelTotals[0] ?? 0) + (int) $targetUnitSampel;
            }
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
            'total_volume' => $totalVolume,
            'narrative' => $this->formatSensusEkonomiVolumeNarrative($selectedRows, $perUnitSampelTotals, $unitSampelNames),
        ];
    }

    /**
     * @param  array<int, int>  $perUnitSampelTotals
     * @param  array<int, string>  $unitSampelNames
     */
    private function formatSensusEkonomiVolumeNarrative(int $selectedRows, array $perUnitSampelTotals, array $unitSampelNames): string
    {
        $parts = [];
        $unitParts = [];

        if ($selectedRows > 0) {
            $parts[] = number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
        }

        foreach ($perUnitSampelTotals as $unitId => $total) {
            if ($total > 0) {
                $name = $unitSampelNames[(int) $unitId] ?? 'usaha/keluarga';
                $unitParts[] = number_format($total, 0, ',', '.').' '.mb_strtolower(trim((string) $name));
            }
        }

        if (! empty($unitParts)) {
            $parts[] = implode('/', $unitParts);
        }

        if (empty($parts)) {
            return '-';
        }

        return implode(' dan/atau ', $parts);
    }

    private function formatSensusEkonomiTotalSlsVolumeLabel(int $selectedRows): string
    {
        if ($selectedRows <= 0) {
            return '-';
        }

        return 'Seluruh Muatan '.number_format($selectedRows, 0, ',', '.').' SLS/sub-SLS';
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
                return $start->translatedFormat('d').'-'.$end->translatedFormat('d F Y');
            }

            return $start->translatedFormat('d F').'-'.$end->translatedFormat('d F Y');
        }

        return $start->translatedFormat('d F Y').'-'.$end->translatedFormat('d F Y');
    }

    private function formatLampiranVolumeLabel(mixed $volume, ?string $unit): string
    {
        if ($volume === null || $volume === '' || (float) $volume <= 0) {
            return '-';
        }

        $formattedVolume = $this->formatLampiranVolumeNumber((float) $volume);

        return trim($formattedVolume.' '.($unit ?? ''));
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
    private function detectWorkType($allAlokasi): string
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
        $scopePeriodeIds = $this->resolveSpkScopePeriodeIds($periode, ['dikirim', 'perubahan']);

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
                        $nomorSpk = 'PPIS/13730/'.$noUrut.$nextSuffix.'/K/'.$tahun;
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
                $nomorParts = explode('/', $data['nomorSpk']);
                $nomorUrut = $nomorParts[2] ?? '0';

                // Check if SPK already exists for this petugas (use existing SPK from map)
                $existingSpkRecord = $existingSpk;
                $bulanLabel = $this->getBulanLabel($periode->bulan);
                $namaPetugas = preg_replace('/[\/\\\\:*?"<>|]/', '', $petugas->nama);
                $nomorUrut = $noUrut.(($isRegenerate && ! $existingSpk) ? $nextSuffix : '');
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
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Check if any SPK exists in this month
        $hasAnySPK = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->exists();

        // If no SPK exists, return false (should use normal "Generate SPK" button)
        if (! $hasAnySPK) {
            return false;
        }

        // Get all existing SPKs in this month to build a map of petugas who already have SPK
        $existingSpkPetugasIds = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->pluck('petugas_id')
            ->unique()
            ->toArray();

        // Petugas yang sudah pernah addendum harus diproses lewat addendum,
        // bukan lewat re-generate SPK awal.
        $petugasWithAddendum = Spk::where('addendum_number', '>', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
            ->pluck('petugas_id')
            ->unique()
            ->toArray();

        $eligibleRegeneratePetugasIds = array_values(array_diff(
            $existingSpkPetugasIds,
            $petugasWithAddendum,
        ));

        // Get all periode alokasi in this month with validated status
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        // Check if there are any non-organik petugas who don't have SPK yet
        $petugasWithoutSpk = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->whereNotIn('petugas_id', $existingSpkPetugasIds)
            ->exists();

        // Also check for petugas with new kegiatan (kegiatan additions)
        $hasNewKegiatan = false;
        if (! $petugasWithoutSpk) {
            // Get all petugas who have SPK in this month
            $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
                ->whereIn('petugas_id', $eligibleRegeneratePetugasIds)
                ->whereHas('petugas', function ($q) {
                    $q->where('jenis_petugas', 'non-organik');
                })
                ->where(function ($query) {
                    $query->where('total_honor', '>', 0)
                        ->orWhere('total_honor_listing', '>', 0);
                })
                ->with('periodeAlokasi.kegiatan')
                ->get();

            // Group by petugas for current effective snapshot
            $petugasKegiatanIds = $allAlokasi->groupBy('petugas_id')
                ->map(function ($alokasiGroup) {
                    return $alokasiGroup->pluck('periodeAlokasi.kegiatan.id')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                });

            foreach ($eligibleRegeneratePetugasIds as $petugasId) {
                $delta = $this->analyzeAllocationDeltaForPetugas(
                    $petugasId,
                    $bulanFormatted,
                    $tahun,
                    'original_spk',
                );

                if ($delta['has_new_kegiatan_added']) {
                    $hasNewKegiatan = true;
                    break;
                }
            }
        }

        return $petugasWithoutSpk || $hasNewKegiatan;
    }

    /**
     * Check if there are new revisions after addendum was generated
     */
    private function hasNewRevisionAfterAddendum(int $tahun, int $bulan, $monthPeriodes): bool
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

        // If no Addendum exists, return false (should use normal "Addendum SPK" button)
        if (! $latestAddendumCreatedAt) {
            return false;
        }

        // Check if there are any revision periodes (status: perubahan or direvisi)
        // that were updated after the latest addendum generation
        $hasNewRevision = false;
        foreach ($monthPeriodes as $periode) {
            // Only check perubahan or direvisi status
            if (! in_array($periode->status, ['perubahan', 'direvisi'])) {
                continue;
            }

            // Get non-organik petugas from this revision periode with honor > 0
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
                // Check if this revision alokasi has addendum
                $hasAddendum = Spk::where('alokasi_petugas_id', $alokasi->id)
                    ->where('addendum_number', '>', 0)
                    ->exists();

                // If no addendum or periode was updated after latest addendum
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
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all validated periode in the same month.
        // Addendum can be needed not only by revisions, but also by newly added kegiatan.
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return false;
        }

        $allAlokasi = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeInMonth)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->where(function ($query) {
                $query->where('total_honor', '>', 0)
                    ->orWhere('total_honor_listing', '>', 0);
            })
            ->with(['petugas'])
            ->get();

        // Get petugas who already have addendum
        $petugasWithAddendum = Spk::where('addendum_number', '>', 0)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun);
            })
            ->distinct()
            ->pluck('petugas_id')
            ->toArray();

        $petugasIds = $allAlokasi->pluck('petugas_id')->unique();

        foreach ($petugasIds as $petugasId) {
            if (in_array($petugasId, $petugasWithAddendum)) {
                continue;
            }

            $originalSpk = Spk::where('petugas_id', $petugasId)
                ->where('addendum_number', 0)
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun);
                })
                ->orderBy('created_at', 'asc')
                ->first();

            if (! $originalSpk) {
                continue;
            }

            $delta = $this->analyzeAllocationDeltaForPetugas(
                $petugasId,
                $bulanFormatted,
                $tahun,
                'original_spk',
            );

            // Rule: perubahan alokasi => addendum.
            // Kegiatan baru murni tanpa perubahan alokasi diproses lewat re-generate SPK.
            if ($delta['has_allocation_change']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there are allocation changes to petugas who already have addendum
     */
    private function hasAddendumChanges(int $tahun, int $bulan, $monthPeriodes): bool
    {
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periode in month with any status
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->pluck('id');

        if ($allPeriodeInMonth->isEmpty()) {
            return false;
        }

        // Get petugas who already have addendum
        $petugasWithAddendum = Spk::where('addendum_number', '>', 0)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun);
            })
            ->distinct()
            ->pluck('petugas_id')
            ->toArray();

        if (empty($petugasWithAddendum)) {
            return false;
        }

        // For each petugas with addendum, check if there are changes
        foreach ($petugasWithAddendum as $petugasId) {
            // Get latest addendum for this petugas in this month
            $latestAddendum = Spk::where('petugas_id', $petugasId)
                ->where('addendum_number', '>', 0)
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                    $q->where('bulan', $bulanFormatted)
                        ->where('tahun', $tahun);
                })
                ->orderBy('addendum_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $latestAddendum) {
                continue;
            }

            $delta = $this->analyzeAllocationDeltaForPetugas(
                $petugasId,
                $bulanFormatted,
                $tahun,
                'latest_addendum',
            );

            // Rule: jika sudah pernah addendum, kegiatan baru maupun perubahan alokasi
            // tetap diproses sebagai addendum (re-generate addendum).
            if ($delta['has_new_kegiatan_added'] || $delta['has_allocation_change']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze allocation delta for a petugas in a month.
     *
     * @return array{has_new_kegiatan_added:bool,has_allocation_change:bool}
     */
    private function analyzeAllocationDeltaForPetugas(
        int $petugasId,
        string $bulanFormatted,
        int $tahun,
        string $referenceType = 'original_spk',
    ): array {
        $referenceDocument = Spk::query()
            ->where('petugas_id', $petugasId)
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun);
            });

        if ($referenceType === 'latest_addendum') {
            $referenceDocument = $referenceDocument
                ->where('addendum_number', '>', 0)
                ->orderBy('addendum_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            $referenceDocument = $referenceDocument
                ->where('addendum_number', 0)
                ->orderBy('created_at', 'asc')
                ->first();
        }

        if (! $referenceDocument) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
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
            ];
        }

        $hasChangeAfterReference = $this->hasAllocationDeltaAfterReferenceForPetugas(
            $petugasId,
            $bulanFormatted,
            $tahun,
            $referenceDocument->created_at,
        );

        if (! $hasChangeAfterReference) {
            return [
                'has_new_kegiatan_added' => false,
                'has_allocation_change' => false,
            ];
        }

        $referenceKeys = array_keys($referenceSnapshot);
        $currentKeys = array_keys($currentSnapshot);

        $newKegiatanKeys = array_values(array_diff($currentKeys, $referenceKeys));
        $removedKegiatanKeys = array_values(array_diff($referenceKeys, $currentKeys));

        $hasNewKegiatanAdded = ! empty($newKegiatanKeys);
        $hasAllocationChange = ! empty($removedKegiatanKeys);

        $commonKeys = array_intersect($referenceKeys, $currentKeys);
        foreach ($commonKeys as $kegiatanId) {
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
                $hasAllocationChange = true;
                break;
            }
        }

        return [
            'has_new_kegiatan_added' => $hasNewKegiatanAdded,
            'has_allocation_change' => $hasAllocationChange,
        ];
    }

    /**
     * @return array<int, array{peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
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
            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun)
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
                $effective = $kegiatanGroup->first();

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
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
     * @return array<int, array{peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
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
                $q->where('bulan', $bulanFormatted)
                    ->where('tahun', $tahun)
                    ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);

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

        // Group by kegiatan and get effective allocation per kegiatan
        // Priority: perubahan > direvisi > disetujui > dikirim
        $snapshot = $alokasiQuery
            ->groupBy(function ($alokasi) {
                return $alokasi->periodeAlokasi?->kegiatan_id;
            })
            ->map(function ($kegiatanGroup) {
                // Apply priority: perubahan > direvisi > disetujui > dikirim
                $perubahan = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'perubahan');
                if ($perubahan) {
                    $effective = $perubahan;
                } else {
                    $direvisi = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'direvisi');
                    if ($direvisi) {
                        $effective = $direvisi;
                    } else {
                        $disetujui = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'disetujui');
                        if ($disetujui) {
                            $effective = $disetujui;
                        } else {
                            $effective = $kegiatanGroup->first(fn ($a) => $a->periodeAlokasi->status === 'dikirim');
                        }
                    }
                }

                if (! $effective || ! $this->isMeaningfulAllocation($effective)) {
                    return null;
                }

                return [
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
