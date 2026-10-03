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
use App\Http\Controllers\Concerns\SpkDownloadSupport;
use App\Http\Controllers\Concerns\SpkScopeSupport;
use App\Http\Controllers\Concerns\SpkSensusSupport;
use App\Http\Controllers\Concerns\SpkChangeDetectionSupport;
use App\Http\Controllers\Concerns\SpkIndexActions;

class SpkController extends Controller
{
    use SpkIndexActions;
    use SpkChangeDetectionSupport;
    use SpkSensusSupport;
    use SpkScopeSupport;
    use SpkDownloadSupport;
    use SpkPdfSupport;
    use SpkAddendumSupport;
    use SpkPublicPreviewSupport;
    public function __construct(private readonly SpkActionDecisionService $spkActionDecisionService) {}

    /**
     * Display a listing of the resource.
     */


    /**
     * Display list of SPKs for a specific month
     */


    /**
     * Show SPK for a specific month with petugas list (GET version)
     */


    /**
     * Show SPK for a specific month with petugas list (POST version)
     */


    /**
     * Internal method to render ShowByMonth view
     */


    /**
     * Download all SPK files in a month as ZIP
     */


    /**
     * Download all SPK files for a specific kegiatan in a periode as ZIP
     */


    /**
     * Download all SPK files for a specific kegiatan in a specific month as ZIP
     * Used by ketua tim to download all SPK for their activity
     */


    /**
     * Upload signed SPK document
     */


    /**
     * Regenerate the same SPK document in place after a technical application error.
     * This updates only the current record and does not create a new database row.
     * If a signed PDF was already uploaded, the document must be re-scanned and re-uploaded.
     */


    /**
     * Get next nomor urut for SPK based on year
     */


    /**
     * Extract nomor urut from SPK number.
     */


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


    /**
     * @param  Collection<int, PeriodeAlokasi>  $monthPeriodes
     */


    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */


    /**
     * @param  array<int, array<string, mixed>>  $uraianTugas
     * @return array<string, mixed>
     */


    /**
     * @return array<int, array<string, mixed>>
     */


    /**
     * @return array{selected_rows:int,prelist_total:int}
     */
    /**
     * @param  array<int, int>  $frameMuatanTotals
     * @return array{selected_rows: int}
     */


    /**
     * @param  array<int, int>  $frameMuatanTotals
     */


    /**
     * @return array{selected_rows:int,prelist_total:int,total_volume:int,narrative:string}
     */


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


    /**
     * Check if there are petugas with revisions who don't have addendum yet
     */


    /**
     * Check if there are allocation changes to petugas who already have addendum
     */


    /**
     * Analyze allocation delta for a petugas in a month.
     *
     * @return array{has_new_kegiatan_added:bool,has_allocation_change:bool,has_perubahan_status:bool,is_allocation_incomplete:bool,has_honor_mismatch:bool}
     */


    /**
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */


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


    /**
     * Check if two allocation snapshots match (have same effective values).
     *
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot1
     * @param  array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>  $snapshot2
     */


    /**
     * Build latest effective allocation snapshot keyed by kegiatan_id.
     *
     * @return array<int, array{alokasi_id:int,periode_alokasi_id:int,peran:?string,jumlah_satuan:int,jumlah_satuan_listing:int,total_honor:float,total_honor_listing:float}>
     */


    /**
     * Get list of petugas names for a specific month (sorted alphabetically)
     */

}
