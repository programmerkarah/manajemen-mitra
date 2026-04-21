<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Spk;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
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

        // Group by month and year
        $groupedByMonth = $periodes->groupBy(function ($periode) {
            return $periode->tahun.'-'.$periode->bulan;
        })->map(function ($monthPeriodes, $key) {
            [$tahun, $bulan] = explode('-', $key);

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
            $hasNewKegiatanAfterSpk = $this->hasNewKegiatanAfterSpk($tahun, $bulan, $monthPeriodes);

            // Check for new revisions after addendum was generated
            $hasNewRevisionAfterAddendum = $this->hasNewRevisionAfterAddendum($tahun, $bulan, $monthPeriodes);

            // Check if SPK has been regenerated (regeneration_count > 0)
            $hasBeenRegenerated = $monthPeriodes->flatMap(function ($periode) {
                return $periode->spk;
            })->contains(function ($spk) {
                return ($spk->regeneration_count ?? 0) > 0;
            });

            // Check for incomplete addendum (some petugas with revision don't have addendum yet)
            $hasIncompleteAddendum = $this->hasIncompleteAddendum($tahun, $bulan, $monthPeriodes);

            // Check for addendum changes (petugas who already have addendum but have allocation changes)
            $hasAddendumChanges = $this->hasAddendumChanges($tahun, $bulan, $monthPeriodes);

            return [
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
    private function renderShowByMonth($bulan, $tahun, $spkHashedId): Response|RedirectResponse
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
        $nomorParts = explode('/', $spk->nomor_spk);
        $nomorUrut = $nomorParts[2] ?? '0';

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

    private function generateSignedDownloadUrl(string $filename): string
    {
        // Return direct static URL untuk better CDN caching
        // File di-serve langsung oleh web server (Nginx/Apache), bukan PHP
        return '/downloads/'.rawurlencode($filename);
    }

    /**
     * Extract nomor urut from SPK number (e.g., "PPIS/13730/4A/K/2025" -> 4)
     */
    private function extractNomorUrut(string $nomorSpk): int
    {
        $parts = explode('/', $nomorSpk);
        if (! isset($parts[2])) {
            return 0;
        }

        // Remove suffix letters (e.g., "4A" -> "4")
        $nomorWithSuffix = $parts[2];

        return (int) preg_replace('/[^0-9]/', '', $nomorWithSuffix);
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

        // Check if there are any draft periode in the same month
        $hasDraftPeriode = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->where('status', 'draft')
            ->exists();

        // Get all periode alokasi in the same month and year
        // For regenerate SPK (non-addendum): use effective status 'dikirim' and 'perubahan'
        // For addendum: use 'perubahan' status
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        // Get all unique non-organik petugas from all alokasi in this month
        // Only include alokasi with total_honor > 0 (either pencacahan or listing)
        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
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
        $nextNomorUrut = $this->getNextNomorUrut($periode->tahun);

        // Check if there are existing SPKs in this month (for regenerate mode)
        $existingSpk = Spk::where('addendum_number', 0)
            ->whereYear('tanggal_spk', $periode->tahun)
            ->whereMonth('tanggal_spk', $periode->bulan)
            ->first();

        // If existing SPK found, use its dates and set readonly mode
        $isRegenerate = $existingSpk !== null;
        $defaultTanggalSpk = $isRegenerate ? $existingSpk->tanggal_spk->format('Y-m-d') : null;

        // Get all existing SPKs for petugas in this month (map petugas_id => nomor_spk)
        $existingSpkMap = [];
        $lastNomorUrutInMonth = 0;
        $usesSuffixForNewPetugas = false;

        if ($isRegenerate) {
            // Get ALL existing SPKs in this month first (not limited to current petugasList)
            // This ensures we capture all petugas who already have SPK, even if they're not in current list
            $existingSpks = Spk::where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->whereMonth('tanggal_spk', $periode->bulan)
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
                    ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                        $q->where('bulan', str_pad((string) $periode->bulan, 2, '0', STR_PAD_LEFT))
                            ->where('tahun', $periode->tahun)
                            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
                    })
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
            $existingSpkPetugasIds = Spk::whereIn('petugas_id', $petugasIds)
                ->where('addendum_number', 0)
                ->whereYear('tanggal_spk', $periode->tahun)
                ->whereMonth('tanggal_spk', $periode->bulan)
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

                // Get periode_alokasi_ids from those alokasi
                $latestPeriodeIds = AlokasiPetugas::whereIn('id', $latestAlokasIds)
                    ->pluck('periode_alokasi_id')
                    ->sort()
                    ->values()
                    ->toArray();

                // Get current effective allocations (latest status for each kegiatan)
                $effectiveAlokasiByKegiatan = $this->getEffectiveAlokasiByKegiatan($alokasiGroup);

                // Get current periode_alokasi_ids
                $currentPeriodeIds = $effectiveAlokasiByKegiatan
                    ->pluck('periode_alokasi_id')
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
            ->filter();
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

        // Generate 2 separate PDFs and merge them (SPK Main + Lampiran only)
        $pdfMain = Pdf::loadView('spk-main', $data)
            ->setPaper('a4', 'portrait');

        // Set PDF title metadata untuk main
        $pdfMain->getDomPDF()->set_option('pdfTitle', $filename);

        $pdfLampiran = Pdf::loadView('spk-lampiran', $data)
            ->setPaper('a4', 'landscape');

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

        file_put_contents($mainPath, $pdfMain->output());
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

        return $pdf->stream($filename);
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

        $pdf = Pdf::loadView('spk-lampiran', $data)
            ->setPaper('a4', 'landscape');

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
        $nextNomorUrut = $this->getNextNomorUrut($tahun);
        // Format: PPIS/13730/{urut}/K/{tahun}
        $nomorSpk = "PPIS/13730/{$nextNomorUrut}/K/{$tahun}";

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

        DB::beginTransaction();
        try {
            // Generate 2 separate PDFs (SPK Main + Lampiran only)
            $pdfMain = Pdf::loadView('spk-main', $data)
                ->setPaper('a4', 'portrait');

            $pdfLampiran = Pdf::loadView('spk-lampiran', $data)
                ->setPaper('a4', 'landscape');

            // Save temporary PDFs
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
            $nomorParts = explode('/', $data['nomorSpk']);
            $nomorUrut = $nomorParts[2] ?? '0'; // Index 2 is the sequential number

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

        // Get all periode alokasi in the same month and year
        // For SPK generation (non-addendum): use effective status 'dikirim' and 'perubahan'
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $periode->bulan)
            ->where('tahun', $periode->tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->pluck('id');

        // Get all unique non-organik petugas from all alokasi in this month
        // Only include those with honor > 0
        $allAlokasi = AlokasiPetugas::select('alokasi_petugas.*')
            ->whereIn('periode_alokasi_id', $allPeriodeInMonth)
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

        // Get all existing SPKs for this month (one SPK per petugas)
        // Now we can query directly by petugas_id since SPK table has it
        $allPeriodeIds = PeriodeAlokasi::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('id');

        // Get unique petugas_ids that have alokasi in this month
        $petugasIdsInMonth = AlokasiPetugas::whereIn('periode_alokasi_id', $allPeriodeIds)
            ->pluck('petugas_id')
            ->unique();

        // Get existing SPKs for these petugas by checking tanggal_spk (the official date)
        $existingSpks = Spk::whereIn('petugas_id', $petugasIdsInMonth)
            ->where('addendum_number', 0)
            ->whereYear('tanggal_spk', $tahun)
            ->whereMonth('tanggal_spk', $bulan)
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
        $nextNomorUrut = $this->getNextNomorUrut($tahun);
        $nomorUrutCounter = 0;

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
                if ($isRegenerate) {
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
                        $nomorSpk = "PPIS/13730/{$noUrut}{$nextSuffix}/K/{$tahun}";
                    } else {
                        // Use sequential mode
                        $noUrut = $nextSequential;
                        $nomorSpk = "PPIS/13730/{$noUrut}/K/{$tahun}";
                        // Update lastNomorUrutBase for next iteration
                        $lastNomorUrutBase = $noUrut;
                    }
                } else {
                    // First time generation: use sequential numbering
                    $noUrut = $nextNomorUrut + $nomorUrutCounter;
                    $nomorSpk = "PPIS/13730/{$noUrut}/K/{$tahun}";
                    $nomorUrutCounter++;
                }
            }

            // Call the same logic as generateSpk, but inline to avoid HTTP call
            // IMPORTANT: Only get alokasi from current effective periode statuses
            $allAlokasiPetugas = AlokasiPetugas::with(['petugas', 'periodeAlokasi.kegiatan'])
                ->whereHas('periodeAlokasi', function ($q) use ($periode) {
                    $q->where('bulan', $periode->bulan)
                        ->where('tahun', $periode->tahun)
                        ->whereIn('status', ['dikirim', 'perubahan']);
                })
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

            // Use the same PDF/database logic as generateSpk
            DB::beginTransaction();
            try {
                $pdfMain = Pdf::loadView('spk-main', $data)
                    ->setPaper('a4', 'portrait');
                $pdfLampiran = Pdf::loadView('spk-lampiran', $data)
                    ->setPaper('a4', 'landscape');
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

                return [
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
            ->sortKeys()
            ->all();
    }

    private function hasAllocationDeltaAfterReferenceForPetugas(int $petugasId, string $bulanFormatted, int $tahun, $referenceCreatedAt): bool
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
        $upToCreatedAt,
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

                return [
                    'peran' => $effective?->peran,
                    'jumlah_satuan' => (int) ($effective->jumlah_satuan ?? 0),
                    'jumlah_satuan_listing' => (int) ($effective->jumlah_satuan_listing ?? 0),
                    'total_honor' => (float) ($effective->total_honor ?? 0),
                    'total_honor_listing' => (float) ($effective->total_honor_listing ?? 0),
                ];
            })
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

        if (! $bulan || ! $tahun) {
            return response()->json(['error' => 'Bulan dan tahun harus diisi'], 400);
        }

        // Format bulan with leading zero
        $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get all periodes in this month
        $allPeriodeInMonth = PeriodeAlokasi::where('bulan', $bulanFormatted)
            ->where('tahun', $tahun)
            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan'])
            ->whereHas('kegiatan', function ($q) use ($tahun) {
                $q->where('tahun_anggaran', $tahun);
            })
            ->pluck('id');

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
