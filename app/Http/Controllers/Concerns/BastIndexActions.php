<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\BappSeTermin;
use App\Models\Bast;
use App\Models\BastKegiatan;
use App\Models\BastNumberAllocation;
use App\Models\BastPetugas;
use App\Models\Kegiatan;
use App\Models\MasterUnitSampel;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\Spk;
use App\Models\User;
use App\Services\ActiveYearService;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

trait BastIndexActions
{
    public function openDetailByPetugas(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $decrypted = [];
            if ($request->has('encrypted_filters')) {
                $decrypted = decryptFilters($request->input('encrypted_filters'));
            }

            $request->merge($decrypted);

            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
                'mode' => 'nullable|string',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $filters['mode'] = (string) $validated['mode'];
            }

            $request->session()->put('bast_open_detail_filters', $filters);

            $routeParams = [];

            if (filled($validated['petugas_id'] ?? null)) {
                $routeParams['petugas_id'] = (int) $validated['petugas_id'];
            }

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $routeParams['mode'] = (string) $validated['mode'];
            }

            return redirect()->route('bast.open-detail-by-petugas', $routeParams);
        }

        if ($request->filled('state')) {
            $request->merge(decryptFilters((string) $request->query('state')));
        }

        $filters = null;

        if ($request->hasAny(['bulan', 'tahun'])) {
            $validated = $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'petugas_id' => 'nullable|integer|exists:petugas,id',
                'mode' => 'nullable|string',
            ]);

            $filters = [
                'bulan' => (int) $validated['bulan'],
                'tahun' => (int) $validated['tahun'],
            ];

            if (filled($validated['mode'] ?? null) && (string) $validated['mode'] !== 'regular') {
                $filters['mode'] = (string) $validated['mode'];
            }

            $request->session()->put('bast_open_detail_filters', $filters);
        }

        if (! is_array($filters)) {
            $filters = $request->session()->get('bast_open_detail_filters');
            if (! is_array($filters) || ! isset($filters['bulan'], $filters['tahun'])) {
                return redirect()->route('bast.index')
                    ->with('error', 'Pilih periode terlebih dahulu untuk membuka detail BAST.');
            }
        }

        $selectedPetugasId = $request->input('petugas_id');
        if (filled($selectedPetugasId) && is_numeric((string) $selectedPetugasId)) {
            $filters['petugas_id'] = (int) $selectedPetugasId;
        }

        if (filled($request->input('mode')) && (string) $request->input('mode') !== 'regular') {
            $filters['mode'] = (string) $request->input('mode');
        }

        $request->merge($filters);

        return $this->listByMonth($request);
    }

    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $activeYear = ActiveYearService::get();
        $user = $this->getRequestUser($request);
        $canAccessSensusMode = $this->canAccessSensusMode($user, $activeYear);
        $session = $request->hasSession() ? $request->session() : null;
        $requestedMode = (string) ($session?->get('bast_index_mode', 'regular') ?? 'regular');
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';

        if ($mode !== $requestedMode && $session) {
            $session->put('bast_index_mode', $mode);
        }
        $isSensusEkonomiMode = $mode === 'sensus-ekonomi';
        $sensusPetugasByMonth = collect();

        if ($isSensusEkonomiMode) {
            $excludedOldPetugasIds = Schema::hasTable('sensus_ekonomi_petugas_replacements')
                ? DB::table('sensus_ekonomi_petugas_replacements')
                    ->where('termin_i_paid', 0)
                    ->where('status', '!=', 'dibatalkan')
                    ->pluck('petugas_berhenti_id')
                : collect();

            $sensusPetugasIds = Spk::query()
                ->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($activeYear) {
                    $q->where('tahun', $activeYear);
                })
                ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($q) {
                    $q->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                })
                ->whereHas('alokasiPetugas', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('jumlah_satuan', '>', 0)
                            ->orWhere('jumlah_satuan_listing', '>', 0)
                            ->orWhere('total_honor', '>', 0)
                            ->orWhere('total_honor_listing', '>', 0);
                    });
                })
                ->pluck('petugas_id')
                ->filter()
                ->unique()
                ->values()
                ->diff($excludedOldPetugasIds)
                ->values();

            // Business rule: all Sensus Ekonomi PK are processed in August BAST batch.
            $sensusPetugasByMonth = collect([
                8 => $sensusPetugasIds,
            ]);
        }

        // Ambil semua SPK yang punya alokasi > 0 pada periode status 'perubahan' (final allocation state) di tahun berjalan
        // Konsisten dengan filtering di create() method
        $eligibleSpks = DB::table('spk')
            ->join('alokasi_petugas as ap', 'ap.petugas_id', '=', 'spk.petugas_id')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->join('kegiatan as k', 'pa.kegiatan_id', '=', 'k.id')
            ->where('spk.addendum_number', 0)
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->when($isSensusEkonomiMode, function ($query) {
                $query->where('k.nama_kegiatan', 'like', '%Sensus Ekonomi%');
            }, function ($query) {
                $query->where('k.nama_kegiatan', 'not like', '%Sensus Ekonomi%');
            })
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->distinct('spk.petugas_id')
            ->select('spk.*')
            ->get();

        // Untuk setiap SPK, tentukan bulan periode alokasi pertamanya di tahun berjalan (alokasi > 0, status perubahan)
        // Ambil seluruh alokasi_petugas yang join ke periode_alokasi (tahun aktif, status perubahan, jumlah > 0)
        $alokasiRows = DB::table('alokasi_petugas as ap')
            ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
            ->join('kegiatan as k', 'pa.kegiatan_id', '=', 'k.id')
            ->where('pa.tahun', $activeYear)
            ->where('pa.status', 'perubahan')
            ->when($isSensusEkonomiMode, function ($query) {
                $query->where('k.nama_kegiatan', 'like', '%Sensus Ekonomi%');
            }, function ($query) {
                $query->where('k.nama_kegiatan', 'not like', '%Sensus Ekonomi%');
            })
            ->where(function ($q) {
                $q->where('ap.jumlah_satuan', '>', 0)
                    ->orWhere('ap.jumlah_satuan_listing', '>', 0)
                    ->orWhere('ap.total_honor', '>', 0)
                    ->orWhere('ap.total_honor_listing', '>', 0);
            })
            ->select('ap.petugas_id', 'pa.bulan')
            ->get();

        // Untuk setiap bulan, kumpulkan petugas unik
        $spkByBulan = [];
        foreach (range(1, 12) as $bulan) {
            $petugasIds = $alokasiRows->filter(function ($row) use ($bulan) {
                return (int) ltrim($row->bulan, '0') === $bulan;
            })->pluck('petugas_id')->unique()->values();
            $spkByBulan[$bulan] = $petugasIds->all();
        }

        $data = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $bulanFormatted = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $activeYear);

            if ($isSensusEkonomiMode) {
                $eligiblePetugasCount = $bulan === 8
                    ? $sensusPetugasByMonth->get($bulan, collect())->count()
                    : 0;
            } else {
                // Get all unique petugas who have SPK (original or addendum) in this month
                $allPetugasIds = Spk::whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($activeYear, $bulanFormatted) {
                    $q->where('tahun', $activeYear)
                        ->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                })
                    ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($relationQuery) {
                        $relationQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                    })
                    ->distinct()
                    ->pluck('petugas_id')
                    ->filter()
                    ->unique()
                    ->values();

                // Filter petugas using the same logic as create() method
                $eligiblePetugasIds = $allPetugasIds->filter(function ($petugasId) use ($bulanFormatted, $activeYear, $isLegacyBastMode) {
                    if ($isLegacyBastMode) {
                        return $this->hasPositiveBastAttachmentPayloadForPetugas(
                            (int) $petugasId,
                            $bulanFormatted,
                            (int) $activeYear
                        );
                    }

                    return $this->hasPositiveEffectiveAlokasiForPetugasInMonth(
                        (int) $petugasId,
                        $bulanFormatted,
                        (int) $activeYear
                    );
                });

                $eligiblePetugasCount = $eligiblePetugasIds->count();
            }

            // Samakan metrik "BAST dibuat" dengan detail bulan (jumlah petugas pada dokumen BAST bulan tersebut)
            $petugasWithBast = DB::table('bast_petugas as bp')
                ->join('bast as b', 'bp.bast_id', '=', 'b.id')
                ->whereYear('b.tanggal_bast', $activeYear)
                ->whereMonth('b.tanggal_bast', $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true, 'b.nomor_bast');
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false, 'b.nomor_bast');
                })
                ->distinct('bp.petugas_id')
                ->count('bp.petugas_id');

            if ($isSensusEkonomiMode && $bulan !== 8) {
                $petugasWithBast = 0;
            }

            $totalPetugas = max($eligiblePetugasCount, $petugasWithBast);
            $petugasWithoutBast = max(0, $totalPetugas - $petugasWithBast);

            $data[] = [
                'bulan' => $bulan,
                'bulan_label' => $this->getBulanLabel($bulan),
                'tahun' => $activeYear,
                'total_spk' => $totalPetugas,
                'spk_with_bast' => $petugasWithBast,
                'spk_without_bast' => $petugasWithoutBast,
                'visible_petugas_count' => $eligiblePetugasCount,
                'has_spk' => $totalPetugas > 0,
                'all_completed' => $totalPetugas > 0 && $petugasWithoutBast === 0,
            ];
        }

        // Encrypt sensitive data
        $encryptedData = encryptData($data);

        return Inertia::render('Bast/Index', [
            'data' => [
                'encrypted' => $encryptedData,
            ],
            'filters' => [
                'search' => $search,
                'mode' => $mode,
            ],
            'active_year' => $activeYear,
            'mode' => $mode,
            'can_access_sensus_mode' => $canAccessSensusMode,
        ]);
    }

    public function switchIndexMode(Request $request): RedirectResponse
    {
        $state = decryptFilters((string) $request->input('encrypted_filters'));
        $mode = (string) ($state['mode'] ?? 'regular');

        if (! in_array($mode, ['regular', 'sensus-ekonomi'], true)) {
            $mode = 'regular';
        }

        $request->session()->put('bast_index_mode', $mode);

        return redirect()->route('bast.index');
    }

    public function openCreate(Request $request): RedirectResponse
    {
        $state = decryptFilters((string) $request->input('encrypted_filters'));
        $bulan = (int) ($state['bulan'] ?? 0);
        $tahun = (int) ($state['tahun'] ?? 0);
        $mode = (string) ($state['mode'] ?? 'regular');

        if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
            return back()->with('error', 'Periode BAST tidak valid.');
        }

        if (! in_array($mode, ['regular', 'sensus-ekonomi'], true)) {
            $mode = 'regular';
        }

        if ($mode === 'sensus-ekonomi'
            && ! $this->canAccessSensusMode($this->getRequestUser($request), $tahun)) {
            return back()->with('error', 'Anda tidak memiliki akses ke BAST Sensus Ekonomi.');
        }

        $request->session()->put('bast_create_filters', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'mode' => $mode,
        ]);

        return redirect()->route('bast.create');
    }

    public function listByMonth(Request $request): Response|RedirectResponse
    {
        $decrypted = [];
        if ($request->has('encrypted_filters')) {
            $decrypted = decryptFilters($request->input('encrypted_filters'));
        }

        $request->merge($decrypted);

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $selectedPetugasId = (int) $request->input('petugas_id', 0);
        $requestedMode = (string) $request->input('mode', 'regular');
        $user = $this->getRequestUser($request);
        $canAccessSensusMode = $this->canAccessSensusMode($user, (int) $tahun);
        $mode = $requestedMode === 'sensus-ekonomi' && $canAccessSensusMode
            ? 'sensus-ekonomi'
            : 'regular';
        $isSensusEkonomiMode = $mode === 'sensus-ekonomi';
        $activeYear = ActiveYearService::get();

        // Default to current year if no filter
        if (! $tahun) {
            $tahun = $activeYear;
        }

        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $isKetuaTim = $user?->active_role === 'ketua_tim';

        // Get first BAST for this month, filtered by kegiatan managed by the current ketua tim so
        // they land on a BAST that actually contains their lampiran (not an unrelated BAST).
        $firstBast = Bast::query()
            ->whereYear('tanggal_bast', (int) $tahun)
            ->when($bulan, function ($query) use ($bulan) {
                $query->whereMonth('tanggal_bast', (int) $bulan);
            })
            ->when($isSensusEkonomiMode, function ($query) {
                $this->applyBastNomorModeFilter($query, true);
            }, function ($query) {
                $this->applyBastNomorModeFilter($query, false);
            })
            ->when($isKetuaTim, function ($query) use ($user) {
                $query->whereHas('bastKegiatan.kegiatan', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('ketua_tim_user_id', $user?->id)
                            ->orWhere('pj_lainnya_id', $user?->id);
                    });
                });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($selectedPetugasId > 0) {
            $selectedPetugasBast = Bast::query()
                ->whereYear('tanggal_bast', (int) $tahun)
                ->whereMonth('tanggal_bast', (int) $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true);
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false);
                })
                ->where(function ($query) use ($selectedPetugasId) {
                    $query->whereHas('spk.alokasiPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    })->orWhereHas('bastPetugas', function ($relationQuery) use ($selectedPetugasId) {
                        $relationQuery->where('petugas_id', $selectedPetugasId);
                    });
                })
                ->latest('created_at')
                ->first();

            if ($selectedPetugasBast) {
                return $this->show($request, $selectedPetugasBast);
            }
        }

        // For April 2026+, when selecting a petugas without BAST (or no BAST exists yet),
        // show same detail layout without generating BAST document.
        if ($selectedPetugasId > 0 || ! $firstBast) {
            $canManageMain = $this->userCanManageBastMain($request);

            $periodeReference = PeriodeAlokasi::query()
                ->where('tahun', $tahun)
                ->when($bulan, function ($query) use ($bulanFormatted) {
                    $query->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                })
                ->latest('id')
                ->first();

            $bastList = $periodeReference
                ? $this->buildBastListForPeriod($periodeReference, $canManageMain, $isKetuaTim, $request, null, $isSensusEkonomiMode)
                : collect();

            $existingBastPetugasIds = Bast::query()
                ->with('spk:id,petugas_id')
                ->whereYear('tanggal_bast', (int) $tahun)
                ->whereMonth('tanggal_bast', (int) $bulan)
                ->when($isSensusEkonomiMode, function ($query) {
                    $this->applyBastNomorModeFilter($query, true);
                }, function ($query) {
                    $this->applyBastNomorModeFilter($query, false);
                })
                ->get()
                ->pluck('spk.petugas_id')
                ->filter()
                ->unique();

            $isLegacyBastMode = $this->isLegacyBastAttachmentMode($bulanFormatted, (int) $tahun);

            $eligibleWithoutBast = Spk::with('alokasiPetugas.petugas')
                ->whereNotIn('petugas_id', $existingBastPetugasIds)
                ->when($isSensusEkonomiMode, function ($query) use ($tahun) {
                    $query->whereHas('alokasiPetugas.periodeAlokasi', function ($periodeQuery) use ($tahun) {
                        $periodeQuery->where('tahun', (int) $tahun);
                    })
                        ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                            $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                        });
                }, function ($query) use ($bulanFormatted, $tahun) {
                    $query->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                        $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                            ->where('tahun', $tahun)
                            ->whereIn('status', ['dikirim', 'disetujui', 'direvisi', 'perubahan']);
                    })->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                        $kegiatanQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                    });
                })
                ->when($isKetuaTim, function ($query) use ($user, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                    $alokasiIds = AlokasiPetugas::whereHas('periodeAlokasi', function ($q) use ($user, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                        $q->where('tahun', $tahun)
                            ->when(! $isSensusEkonomiMode, function ($subQuery) use ($bulanFormatted) {
                                $subQuery->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted));
                            })
                            ->whereHas('kegiatan', function ($qk) use ($user, $isSensusEkonomiMode) {
                                if ($isSensusEkonomiMode) {
                                    $qk->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                                }
                                $qk->where(function ($sub) use ($user) {
                                    $sub->where('ketua_tim_user_id', $user?->id)
                                        ->orWhere('pj_lainnya_id', $user?->id);
                                });
                            });
                    })
                        ->pluck('id')
                        ->toArray();

                    if (empty($alokasiIds)) {
                        $query->whereRaw('0 = 1');

                        return;
                    }

                    $query->where(function ($inner) use ($alokasiIds) {
                        foreach ($alokasiIds as $id) {
                            $inner->orWhereJsonContains('alokasi_petugas_ids', $id);
                        }
                    });
                })
                ->get()
                ->map(function ($spk) {
                    $petugas = $spk->alokasiPetugas?->petugas;

                    return [
                        'petugas_nama' => $petugas?->nama ?? 'Petugas tidak diketahui',
                        'petugas_id' => $petugas?->id,
                    ];
                })
                ->filter(function (array $item) use ($isLegacyBastMode, $bulanFormatted, $tahun, $isSensusEkonomiMode) {
                    if ($isSensusEkonomiMode) {
                        return true;
                    }

                    $petugasId = (int) ($item['petugas_id'] ?? 0);
                    if ($petugasId === 0) {
                        return false;
                    }

                    if ($isLegacyBastMode) {
                        return $this->hasPositiveBastAttachmentPayloadForPetugas($petugasId, $bulanFormatted, (int) $tahun);
                    }

                    return $this->hasPositiveEffectiveAlokasiForPetugasInMonth($petugasId, $bulanFormatted, (int) $tahun);
                })
                ->unique('petugas_id')
                ->sortBy('petugas_nama')
                ->values();

            if ($selectedPetugasId === 0 && $eligibleWithoutBast->isNotEmpty()) {
                $selectedPetugasId = (int) ($eligibleWithoutBast->first()['petugas_id'] ?? 0);
            }

            $selectedPetugas = null;
            $lampiranPreview = collect();
            $selectedSpk = null;
            $previewSensusReference = null;

            if ($selectedPetugasId > 0) {
                $selectedSpk = Spk::where('petugas_id', $selectedPetugasId)
                    ->when($isSensusEkonomiMode, function ($query) use ($tahun) {
                        $query->whereHas('alokasiPetugas.periodeAlokasi', function ($periodeQuery) use ($tahun) {
                            $periodeQuery->where('tahun', (int) $tahun);
                        })
                            ->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                                $kegiatanQuery->where('nama_kegiatan', 'like', '%Sensus Ekonomi%');
                            });
                    }, function ($query) use ($bulanFormatted, $tahun) {
                        $query->whereHas('alokasiPetugas.periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                ->where('tahun', $tahun);
                        })->whereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                            $kegiatanQuery->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%');
                        });
                    })
                    ->orderByDesc('addendum_number')
                    ->orderByDesc('created_at')
                    ->first();

                $alokasiIdsFromLatestSpk = collect($selectedSpk?->alokasi_petugas_ids ?? [])
                    ->filter()
                    ->values();

                if ($selectedSpk?->alokasi_petugas_id) {
                    $alokasiIdsFromLatestSpk->push($selectedSpk->alokasi_petugas_id);
                }

                $alokasiIdsFromLatestSpk = $alokasiIdsFromLatestSpk->unique()->values();

                if ($isSensusEkonomiMode) {
                    $alokasiPreview = $this->getSensusEkonomiAlokasiForPetugasInYear($selectedPetugasId, (int) $tahun)
                        ->when($isKetuaTim, function ($collection) use ($user) {
                            return $collection->filter(function ($alokasi) use ($user) {
                                $kegiatan = $alokasi->periodeAlokasi?->kegiatan;

                                return (int) ($kegiatan?->ketua_tim_user_id ?? 0) === (int) ($user?->id ?? 0)
                                    || (int) ($kegiatan?->pj_lainnya_id ?? 0) === (int) ($user?->id ?? 0);
                            })->values();
                        });
                } else {
                    $alokasiPreviewQuery = AlokasiPetugas::query();

                    if ($alokasiIdsFromLatestSpk->isNotEmpty()) {
                        $alokasiPreviewQuery->whereIn('id', $alokasiIdsFromLatestSpk->all());
                    } else {
                        $alokasiPreviewQuery->where('petugas_id', $selectedPetugasId)
                            ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                                $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                    ->where('tahun', $tahun)
                                    ->whereIn('status', ['dikirim', 'perubahan']);
                            });
                    }

                    $alokasiPreview = $alokasiPreviewQuery
                        ->whereHas('periodeAlokasi', function ($q) use ($bulanFormatted, $tahun) {
                            $q->whereIn('bulan', $this->resolveBulanCandidates($bulanFormatted))
                                ->where('tahun', $tahun)
                                ->whereIn('status', ['dikirim', 'perubahan']);
                        })
                        ->whereHas('petugas', function ($q) {
                            $q->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->when($isKetuaTim, function ($query) use ($user) {
                            $query->whereHas('periodeAlokasi.kegiatan', function ($q) use ($user) {
                                $q->where(function ($sub) use ($user) {
                                    $sub->where('ketua_tim_user_id', $user?->id)
                                        ->orWhere('pj_lainnya_id', $user?->id);
                                });
                            });
                        })
                        ->with([
                            'petugas',
                            'periodeAlokasi.kegiatan.ketuaTim',
                        ])
                        ->get();
                }

                $selectedPetugas = $alokasiPreview->first()?->petugas;

                $previewSensusReference = $selectedSpk && $this->isSensusEkonomiSpk($selectedSpk)
                    ? $this->buildSensusReferencePayload($selectedSpk, (int) $bulan, (int) $tahun)
                    : null;
                $sharedPreviewScreenshotPath = $previewSensusReference['fasih_screenshot_path'] ?? null;

                $lampiranPreview = $this->getEffectiveAlokasiByKegiatan($alokasiPreview)
                    ->values()
                    ->map(function (AlokasiPetugas $alokasi, int $index) use ($selectedSpk, $sharedPreviewScreenshotPath) {
                        $kegiatan = $alokasi->periodeAlokasi?->kegiatan;
                        $tanggalSelesai = $this->getAlokasiLatestTanggalSelesai($alokasi);
                        $formatted = '-';

                        if ($tanggalSelesai) {
                            try {
                                $formatted = Carbon::parse($tanggalSelesai)->locale('id')->isoFormat('D MMMM YYYY');
                            } catch (\Exception $e) {
                                $formatted = '-';
                            }
                        }

                        $previewDocumentState = $this->getPreviewLampiranDocumentState(
                            $selectedSpk,
                            (int) ($kegiatan?->id ?? 0),
                            (int) ($alokasi->periode_alokasi_id ?? 0),
                            (string) ($kegiatan?->kode_kegiatan ?? '-'),
                            $sharedPreviewScreenshotPath,
                        );
                        $usesFasihScreenshot = $this->shouldUseLampiranFasihScreenshot($kegiatan?->nama_kegiatan, $alokasi->peran);
                        $readyToGenerate = $this->isLampiranGenerationAllowed([
                            'tanggal_selesai' => $tanggalSelesai,
                            'nama_kegiatan' => $kegiatan?->nama_kegiatan,
                            'peran' => $alokasi->peran,
                            'fasih_screenshot_path' => $sharedPreviewScreenshotPath,
                            'bapp_termin_ii_complete' => $previewSensusReference['bapp_termin_ii_complete'] ?? null,
                        ]);

                        return [
                            'id' => $index + 1,
                            'kegiatan_id' => (int) ($kegiatan?->id ?? 0),
                            'periode_alokasi_id' => (int) ($alokasi->periode_alokasi_id ?? 0),
                            'kode_kegiatan' => $kegiatan?->kode_kegiatan ?? '-',
                            'nama_kegiatan' => $kegiatan?->nama_kegiatan ?? '-',
                            'jenis_kegiatan' => $kegiatan?->jenis_kegiatan ?? 'survei',
                            'peran' => $alokasi->peran,
                            'tanggal_selesai' => $tanggalSelesai,
                            'tanggal_selesai_formatted' => $formatted,
                            'ketua_tim_nama' => $kegiatan?->ketuaTim?->name,
                            'file_path' => $previewDocumentState['file_path'],
                            'signed_file_path' => $previewDocumentState['signed_file_path'],
                            'fasih_screenshot_path' => $previewDocumentState['fasih_screenshot_path'],
                            'generated_at' => $previewDocumentState['generated_at'],
                            'signed_uploaded_at' => $previewDocumentState['signed_uploaded_at'],
                            'status' => $previewDocumentState['status'],
                            'can_download' => $readyToGenerate,
                            'can_generate' => $readyToGenerate,
                            'can_upload_signed' => $previewDocumentState['can_upload_signed'],
                            'can_upload_fasih_screenshot' => false,
                            'can_preview' => $readyToGenerate,
                            'ready_to_generate' => $readyToGenerate,
                            'uses_fasih_screenshot' => $usesFasihScreenshot,
                            'preview_spk_id' => $selectedSpk?->id,
                        ];
                    });
            }

            $generatedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['file_path']))->count();
            $signedLampiranPreviewCount = $lampiranPreview->filter(fn (array $item) => filled($item['signed_file_path']))->count();
            $allLampiranPreviewGenerated = $lampiranPreview->isNotEmpty() && $generatedLampiranPreviewCount === $lampiranPreview->count();
            $allLampiranPreviewSigned = $lampiranPreview->isNotEmpty() && $signedLampiranPreviewCount === $lampiranPreview->count();
            $previewSensusReferenceForView = $previewSensusReference ?? null;

            return Inertia::render('Bast/Show', [
                'bast' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'nomor_bast' => '-',
                    'tanggal_bast' => '-',
                    'tanggal_serah_terima' => '-',
                    'menggunakan_fasih' => false,
                    'uraian_pekerjaan' => '-',
                    'nama_ketua_tim' => '-',
                    'nip_ketua_tim' => null,
                    'nama_ppk' => '-',
                    'nip_ppk' => null,
                    'hasil_pekerjaan' => null,
                    'file_path' => null,
                    'compiled_file_path' => null,
                    'main_signed_file_path' => null,
                    'signed_file_path' => null,
                    'lokasi_kegiatan' => null,
                    'status' => 'draft',
                    'catatan' => null,
                    'is_sensus_ekonomi' => $selectedSpk ? $this->isSensusEkonomiSpk($selectedSpk) : false,
                    'muatan_input' => $previewSensusReferenceForView['muatan_input'] ?? null,
                    'muatan_prelist' => $previewSensusReferenceForView['muatan_prelist'] ?? null,
                    'realisasi_unit_sampel' => $previewSensusReferenceForView['realisasi_unit_sampel'] ?? null,
                    'fasih_screenshot_path' => $previewSensusReferenceForView['fasih_screenshot_path'] ?? null,
                    'created_by' => '-',
                    'created_at' => '-',
                    'is_legacy_mode' => false,
                ],
                'spk' => $selectedSpk ? [
                    'id' => $selectedSpk->id,
                    'hashed_id' => $selectedSpk->hashed_id,
                    'nomor_spk' => $selectedSpk->nomor_spk,
                    'tanggal_spk' => $selectedSpk->tanggal_spk?->format('Y-m-d') ?? '-',
                    'nilai_kontrak' => (float) ($selectedSpk->nilai_kontrak ?? 0),
                ] : null,
                'petugas' => $selectedPetugas ? [
                    'id' => $selectedPetugas->id,
                    'hashed_id' => $selectedPetugas->hashed_id ?? '',
                    'nama' => $selectedPetugas->nama,
                    'nik' => $selectedPetugas->nik,
                    'alamat' => $selectedPetugas->alamat,
                    'no_hp' => $selectedPetugas->no_hp,
                ] : null,
                'kegiatan' => [
                    'id' => 0,
                    'hashed_id' => '',
                    'kode_kegiatan' => '-',
                    'nama_kegiatan' => 'Belum ada BAST',
                    'jenis_kegiatan' => 'survei',
                    'tahun_anggaran' => (int) $tahun,
                ],
                'lampiran' => $lampiranPreview->values()->toArray(),
                'bast_list' => $bastList->values()->toArray(),
                'eligible_without_bast' => $eligibleWithoutBast
                    ->filter(function ($item) use ($bastList) {
                        $idsWithBast = $bastList->pluck('petugas_id')->filter()->unique()->toArray();

                        return ! in_array($item['petugas_id'] ?? null, $idsWithBast, true);
                    })
                    ->values()
                    ->toArray(),
                'permissions' => [
                    'can_manage_main' => $canManageMain,
                    'is_ketua_tim' => $isKetuaTim,
                    'can_upload_main' => in_array($user?->active_role, ['admin', 'operator'], true),
                ],
                'summary' => [
                    'total_lampiran' => $lampiranPreview->count(),
                    'generated_lampiran' => $generatedLampiranPreviewCount,
                    'signed_lampiran' => $signedLampiranPreviewCount,
                    'all_lampiran_generated' => $allLampiranPreviewGenerated,
                    'all_lampiran_signed' => $allLampiranPreviewSigned,
                    'main_signed_uploaded' => false,
                    'final_signed_ready' => false,
                ],
                'sensus_reference' => $previewSensusReferenceForView,
                'mode' => $mode,
                'bulan' => (int) $bulan,
                'tahun' => (int) $tahun,
                'bulan_label' => $this->getBulanLabel((int) $bulan),
            ]);
        }

        if (! $firstBast) {
            return redirect()->route('bast.index')
                ->with('error', 'Tidak ada BAST untuk periode '.$this->getBulanLabel((int) $bulan).' '.$tahun);
        }

        // Redirect to canonical open-detail page (no hash URL)
        $this->rememberOpenDetailFiltersFromBast($request, $firstBast);

        $routeParams = $mode !== 'regular' ? ['mode' => $mode] : [];

        return redirect()->route('bast.open-detail-by-petugas', $routeParams);
    }
}
