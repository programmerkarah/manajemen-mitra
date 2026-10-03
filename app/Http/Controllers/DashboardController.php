<?php

namespace App\Http\Controllers;

use App\Models\AlokasiPetugas;
use App\Models\Bast;
use App\Models\DasarHukum;
use App\Models\Dipa;
use App\Models\Kegiatan;
use App\Models\Penandatangan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\ReviewPetugas;
use App\Models\Sbml;
use App\Models\SkKpa;
use App\Models\Spk;
use App\Services\SpkActionDecisionService;
use App\Services\DashboardInsightService;
use App\Services\SensusEkonomiReplacementReadService;
use App\Traits\EffectivePeriodeScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use EffectivePeriodeScope;

    public function __construct(
        private readonly DashboardInsightService $insightService,
    ) {}

    public function index(Request $request): Response
    {
        $user = effectiveUser($request);
        $activeRole = $user->getActiveRole()?->name;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $currentMonthFormatted = str_pad((string) $currentMonth, 2, '0', STR_PAD_LEFT);
        $replacementAssignments = app(SensusEkonomiReplacementReadService::class)
            ->assignments($currentYear);

        // Basic stats
        $stats = [
            'total_petugas' => Petugas::where('status', 'aktif')->count(),
            'total_kegiatan' => Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
            'draft_kegiatan' => Kegiatan::where('status', 'draft')->where('tahun_anggaran', $currentYear)->count(),
            'bast_pending' => Bast::where('status', 'draft')->whereYear('created_at', $currentYear)->count(),
        ];

        // Additional comprehensive stats
        $additionalStats = [
            // SBML Stats
            'sbml' => [
                'total' => Sbml::count(),
                'aktif' => Sbml::where('status', 'aktif')->count(),
                'nonaktif' => Sbml::where('status', 'nonaktif')->count(),
            ],
            // DIPA Stats
            'dipa' => [
                'total' => Dipa::count(),
                'aktif' => Dipa::where('is_active', true)->count(),
                'nonaktif' => Dipa::where('is_active', false)->count(),
            ],
            // Penandatangan Stats
            'penandatangan' => [
                'total' => Penandatangan::count(),
                'kepala' => Penandatangan::where('jenis_penandatangan', 'kepala')->count(),
                'ppk' => Penandatangan::where('jenis_penandatangan', 'ppk')->count(),
                'aktif' => Penandatangan::where('is_active', true)->count(),
            ],
            // Dasar Hukum Stats
            'dasar_hukum' => [
                'total' => DasarHukum::count(),
                'aktif' => DasarHukum::where('status', 'aktif')->count(),
            ],
            // SK Stats
            'sk' => [
                'total' => SkKpa::where('tahun', $currentYear)->count(),
                'draft' => SkKpa::where('status', 'draft')->where('tahun', $currentYear)->count(),
                'diterbitkan' => SkKpa::where('status', 'diterbitkan')->where('tahun', $currentYear)->count(),
                'dibatalkan' => SkKpa::where('status', 'dibatalkan')->where('tahun', $currentYear)->count(),
            ],
            // SPK Stats
            'spk' => [
                'total' => Spk::whereYear('tanggal_spk', $currentYear)->count(),
            ],
            // Petugas by Type
            'petugas_detail' => [
                'organik' => Petugas::where('jenis_petugas', 'organik')->where('status', 'aktif')->count(),
                'non_organik' => Petugas::where('jenis_petugas', 'non-organik')->where('status', 'aktif')->count(),
            ],
            // Kegiatan by Type
            'kegiatan_detail' => [
                'sensus' => Kegiatan::where('jenis_kegiatan', 'sensus')->whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
                'survei' => Kegiatan::where('jenis_kegiatan', 'survei')->whereIn('status', ['aktif', 'divalidasi'])->where('tahun_anggaran', $currentYear)->count(),
            ],
            // Alokasi by Status
            'alokasi_detail' => [
                'draft' => PeriodeAlokasi::where('status', 'draft')->where('tahun', $currentYear)->count(),
                'dikirim' => PeriodeAlokasi::where('status', 'dikirim')->where('tahun', $currentYear)->count(),
                'direvisi' => PeriodeAlokasi::where('status', 'direvisi')->where('tahun', $currentYear)->count(),
            ],
        ];

        // Get recent activities based on user role
        $recentAlokasi = PeriodeAlokasi::query()
            ->select('periode_alokasi.*')
            ->with([
                'kegiatan:id,kode_kegiatan,nama_kegiatan',
                'alokasiPetugas:id,periode_alokasi_id,petugas_id',
                'alokasiPetugas.petugas:id,jenis_petugas',
            ])
            ->where('tahun', $currentYear)
            ->when($user->isOperator(), function ($query) use ($user) {
                $query->where('submitted_by', $user->id);
            })
            ->when($user->isApprover(), function ($query) {
                $query->where('status', 'dikirim');
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($periode) {
                // Count organik and non-organik petugas
                $organikCount = $periode->alokasiPetugas->filter(function ($alokasi) {
                    return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'organik';
                })->count();

                $nonOrganikCount = $periode->alokasiPetugas->filter(function ($alokasi) {
                    return $alokasi->petugas && $alokasi->petugas->jenis_petugas === 'non-organik';
                })->count();

                return [
                    'id' => $periode->id,
                    'status' => $periode->status,
                    'bulan' => $periode->bulan,
                    'tahun' => $periode->tahun,
                    'kegiatan' => [
                        'nama_kegiatan' => $periode->kegiatan->nama_kegiatan ?? '',
                        'kode_kegiatan' => $periode->kegiatan->kode_kegiatan ?? '',
                    ],
                    'jumlah_organik' => $organikCount,
                    'jumlah_non_organik' => $nonOrganikCount,
                    'total_petugas' => $organikCount + $nonOrganikCount,
                ];
            });

        $spkPetugasIdsCurrentMonth = Spk::query()
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($currentMonthFormatted, $currentYear) {
                $query->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear);
            })
            ->pluck('petugas_id')
            ->unique();

        $bastPetugasIdsCurrentMonth = Spk::query()
            ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($currentMonthFormatted, $currentYear) {
                $query->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear);
            })
            ->whereHas('bast')
            ->pluck('petugas_id')
            ->unique();

        // Get kegiatan bulan ini with details
        $kegiatanBulanIni = Kegiatan::query()
            ->with(['ketuaTim'])
            ->whereIn('status', ['aktif', 'divalidasi'])
            ->where('tahun_anggaran', $currentYear)
            ->where(function ($query) use ($currentMonth, $currentYear) {
                $query->whereYear('tanggal_mulai', '<=', $currentYear)
                    ->whereMonth('tanggal_mulai', '<=', $currentMonth)
                    ->whereYear('tanggal_selesai', '>=', $currentYear)
                    ->whereMonth('tanggal_selesai', '>=', $currentMonth);
            })
            ->when($activeRole === 'ketua_tim', function ($query) use ($user) {
                $query->where('ketua_tim_user_id', $user->id);
            })
            ->get()
            ->map(function ($kegiatan) use ($bastPetugasIdsCurrentMonth, $currentMonth, $currentYear, $currentMonthFormatted, $spkPetugasIdsCurrentMonth) {
                // Get periode alokasi for current month
                $periodeAlokasi = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonthFormatted)
                    ->where('tahun', $currentYear)
                    ->latest('updated_at')
                    ->first();

                $hasCurrentMonthChangeIndicator = false;
                if ($periodeAlokasi) {
                    $hasCurrentMonthChangeIndicator = in_array($periodeAlokasi->status, ['draft', 'direvisi', 'perubahan'], true)
                        || (int) ($periodeAlokasi->revision_number ?? 0) > 0
                        || ! empty($periodeAlokasi->parent_periode_id);
                }

                // Get SK for current month
                $skCurrentMonth = SkKpa::where('kegiatan_id', $kegiatan->id)
                    ->where('bulan', $currentMonth)
                    ->where('tahun', $currentYear)
                    ->first();

                // Get latest SK up to current month (fallback when no change indication this month)
                $skLatest = SkKpa::where('kegiatan_id', $kegiatan->id)
                    ->where('tahun', $currentYear)
                    ->where('bulan', '<=', $currentMonth)
                    ->orderByDesc('bulan')
                    ->latest('updated_at')
                    ->first();

                $skForDisplay = $skCurrentMonth;
                $skSource = 'bulan_berjalan';

                if (! $hasCurrentMonthChangeIndicator && $skLatest) {
                    $skForDisplay = $skLatest;
                    $skSource = $skCurrentMonth && $skLatest->id === $skCurrentMonth->id
                        ? 'bulan_berjalan'
                        : 'periode_terakhir';
                }

                $showMissingSk = ! $skForDisplay
                    || ($hasCurrentMonthChangeIndicator && ! $skCurrentMonth);

                $allocatedNonOrganikPetugasIds = $periodeAlokasi
                    ? $periodeAlokasi->alokasiPetugas()
                        ->whereHas('petugas', function ($query) {
                            $query->where('jenis_petugas', 'non-organik');
                        })
                        ->where(function ($query) {
                            $query->where('jumlah_satuan', '>', 0)
                                ->orWhere('jumlah_satuan_listing', '>', 0)
                                ->orWhere('total_honor', '>', 0)
                                ->orWhere('total_honor_listing', '>', 0);
                        })
                        ->distinct()
                        ->pluck('petugas_id')
                    : collect();

                $requiredDocumentCount = $allocatedNonOrganikPetugasIds->count();
                $spkCount = $allocatedNonOrganikPetugasIds->intersect($spkPetugasIdsCurrentMonth)->count();
                $bastCount = $allocatedNonOrganikPetugasIds->intersect($bastPetugasIdsCurrentMonth)->count();
                $totalPetugasAlokasi = $periodeAlokasi ? $periodeAlokasi->alokasiPetugas()->count() : 0;

                $spkRequiresDocument = $requiredDocumentCount > 0;
                $bastRequiresDocument = $requiredDocumentCount > 0;

                return [
                    'id' => $kegiatan->id,
                    'hashed_id' => $kegiatan->hashed_id,
                    'kode_kegiatan' => $kegiatan->kode_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'status' => $kegiatan->status,
                    'periode_alokasi' => $periodeAlokasi ? [
                        'id' => $periodeAlokasi->id,
                        'hashed_id' => $periodeAlokasi->hashed_id,
                        'status' => $periodeAlokasi->status,
                        'jumlah_petugas' => $totalPetugasAlokasi,
                        'has_alokasi' => $totalPetugasAlokasi > 0,
                    ] : null,
                    'sk' => $skForDisplay ? [
                        'id' => $skForDisplay->id,
                        'hashed_id' => $skForDisplay->hashed_id,
                        'nomor_sk' => $skForDisplay->nomor_sk,
                        'status' => $skForDisplay->status,
                        'is_signed' => $skForDisplay->is_signed,
                    ] : null,
                    'sk_meta' => [
                        'show_missing' => $showMissingSk,
                        'source' => $skSource,
                        'source_bulan' => $skForDisplay?->bulan,
                        'source_tahun' => $skForDisplay?->tahun,
                    ],
                    'spk' => [
                        'count' => $spkCount,
                        'has_spk' => $spkCount > 0,
                        'required_count' => $requiredDocumentCount,
                        'requires_document' => $spkRequiresDocument,
                        'is_complete' => $spkRequiresDocument && $spkCount >= $requiredDocumentCount,
                        'detail_url' => $periodeAlokasi
                            ? route('spk.show-by-month-get', ['bulan' => $periodeAlokasi->bulan, 'tahun' => $periodeAlokasi->tahun])
                            : null,
                    ],
                    'bast' => [
                        'count' => $bastCount,
                        'has_bast' => $bastCount > 0,
                        'required_count' => $requiredDocumentCount,
                        'requires_document' => $bastRequiresDocument,
                        'is_complete' => $bastRequiresDocument && $bastCount >= $requiredDocumentCount,
                        'detail_url' => $periodeAlokasi
                            ? route('bast.list', ['bulan' => $periodeAlokasi->bulan, 'tahun' => $periodeAlokasi->tahun])
                            : null,
                    ],
                ];
            });

        $today = Carbon::today();
        $attentionItems = collect();

        if (in_array($activeRole, ['admin', 'operator', 'ketua_tim'], true)) {
            $draftKegiatanQuery = Kegiatan::query()
                ->where('status', 'draft')
                ->where('tahun_anggaran', $currentYear);

            if ($activeRole === 'ketua_tim') {
                $draftKegiatanQuery->where('ketua_tim_user_id', $user->id);
            }

            $draftKegiatanCount = $draftKegiatanQuery->count();

            if ($draftKegiatanCount > 0) {
                $attentionItems->push([
                    'key' => 'kegiatan_draft',
                    'label' => 'kegiatan draft',
                    'count' => $draftKegiatanCount,
                    'url' => route('kegiatan.index'),
                    'description' => 'Perlu ditindaklanjuti ke alokasi',
                    'severity' => 'warning',
                ]);
            }
        }

        if (in_array($activeRole, ['admin', 'approver'], true)) {
            // Gunakan sumber keputusan yang sama dengan halaman PK. Dashboard tidak
            // boleh gagal total apabila evaluasi kandidat dokumen menemui data lama
            // yang tidak konsisten; kasus tersebut dicatat dan kartu PK dilewati.
            try {
                $spkActionCounts = app(SpkActionDecisionService::class)
                    ->resolveForMonth($currentYear, $currentMonth)
                    ->countBy('final_action');

                $generatePkCount = (int) ($spkActionCounts->get('generate_pk', 0));
                $generateAddendumCount = (int) ($spkActionCounts->get('generate_addendum', 0));
                $spkAttentionCount = $generatePkCount + $generateAddendumCount;

                if ($spkAttentionCount > 0) {
                    $attentionItems->push([
                        'key' => 'spk_missing',
                        'label' => 'PK / addendum perlu dibuat',
                        'count' => $spkAttentionCount,
                        'url' => route('spk.index'),
                        'description' => sprintf(
                            '%d PK baru dan %d addendum perlu dibuat',
                            $generatePkCount,
                            $generateAddendumCount,
                        ),
                        'severity' => 'warning',
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Dashboard gagal menghitung tindak lanjut PK.', [
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (in_array($activeRole, ['admin', 'pj', 'operator'], true)) {
            // SK KPA belum dibuat: kegiatan aktif tahun ini dengan alokasi tapi belum punya SK
            $skBelumDibuatCount = Kegiatan::query()
                ->where('tahun_anggaran', $currentYear)
                ->withCount('skKpa')
                ->whereHas('periodeAlokasi', function ($q) use ($currentYear) {
                    $q->where('tahun', $currentYear)
                        ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
                        ->whereHas('alokasiPetugas');
                })
                ->having('sk_kpa_count', '=', 0)
                ->count();

            if ($skBelumDibuatCount > 0) {
                $attentionItems->push([
                    'key' => 'sk_kpa_missing',
                    'label' => 'SK KPA belum dibuat',
                    'count' => $skBelumDibuatCount,
                    'url' => route('sk-kpa.index').'#filter=not_created',
                    'description' => 'Kegiatan sudah ada alokasi tapi belum ada SK KPA',
                    'severity' => 'warning',
                ]);
            }

            // SK KPA perlu perubahan: ada perubahan personel nyata setelah SK terakhir dibuat
            // Load candidates first, then check if personnel actually changed (mirrors SkKpaController logic)
            $skPerluPerubahanCandidates = Kegiatan::query()
                ->where('tahun_anggaran', $currentYear)
                ->whereHas('skKpa')
                ->whereHas('periodeAlokasi', function ($q) use ($currentYear) {
                    $q->where('tahun', $currentYear)
                        ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
                        ->whereHas('alokasiPetugas')
                        ->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('sk_kpa')
                                ->whereColumn('sk_kpa.kegiatan_id', 'periode_alokasi.kegiatan_id')
                                ->whereColumn('periode_alokasi.created_at', '>', 'sk_kpa.created_at');
                        });
                })
                ->with([
                    'skKpa' => fn ($q) => $q->latest('created_at')->select(['id', 'kegiatan_id', 'created_at', 'revision_acknowledged_at']),
                    // Load all periods (no year filter) so reference period can be found correctly
                    'periodeAlokasi' => fn ($q) => $q
                        ->whereIn('status', ['dikirim', 'perubahan', 'direvisi'])
                        ->with('alokasiPetugas:id,periode_alokasi_id,petugas_id,jumlah_satuan,jumlah_satuan_listing')
                        ->orderBy('tahun')
                        ->orderByRaw('CAST(bulan AS UNSIGNED)')
                        ->orderBy('created_at')
                        ->orderBy('id'),
                ])
                ->get();

            $skPerluPerubahanCount = $skPerluPerubahanCandidates->filter(function ($keg) use ($currentYear) {
                $latestSk = $keg->skKpa->first();
                if (! $latestSk) {
                    return false;
                }

                // If the SK has already been acknowledged as not needing revision, skip
                if ($latestSk->revision_acknowledged_at !== null) {
                    return false;
                }

                // Mirror resolveReferencePeriodeForStoredSk:
                // first SK → use earliest (asc) period ≤ SK; revision SK → use latest (desc) period ≤ SK
                $hasPreviousSk = $keg->skKpa->count() > 1;
                $periodsBeforeSk = $keg->periodeAlokasi
                    ->filter(fn ($p) => $p->created_at <= $latestSk->created_at);

                $referencePeriode = $hasPreviousSk
                    ? $periodsBeforeSk->last()
                    : $periodsBeforeSk->first();

                if (! $referencePeriode) {
                    return false;
                }

                // Only check periods in the active year (mirrors SkKpaController::checkPersonnelChanges)
                $periodsAfterSk = $keg->periodeAlokasi->filter(
                    fn ($p) => $p->created_at->gt($latestSk->created_at) && (int) $p->tahun === $currentYear
                );
                if ($periodsAfterSk->isEmpty()) {
                    return false;
                }

                // Reference team = all petugas listed on the SK (regardless of satuan)
                $referenceTeam = $referencePeriode->alokasiPetugas->pluck('petugas_id')->flip();

                // Flag only if a post-SK period adds a NEW active petugas not on the original SK
                // Petugas with jumlah_satuan = 0 are "not needed this period" — not a team change
                foreach ($periodsAfterSk as $period) {
                    $activePostSkPetugas = $period->alokasiPetugas
                        ->filter(fn ($ap) => ($ap->jumlah_satuan ?? 0) > 0 || ($ap->jumlah_satuan_listing ?? 0) > 0)
                        ->pluck('petugas_id');

                    foreach ($activePostSkPetugas as $petugasId) {
                        if (! $referenceTeam->has($petugasId)) {
                            return true;
                        }
                    }
                }

                return false;
            })->count();

            if ($skPerluPerubahanCount > 0) {
                $attentionItems->push([
                    'key' => 'sk_kpa_perlu_perubahan',
                    'label' => 'SK KPA perlu perubahan',
                    'count' => $skPerluPerubahanCount,
                    'url' => route('sk-kpa.index').'#filter=needs_revision',
                    'description' => 'Ada perubahan personel setelah SK terakhir diterbitkan',
                    'severity' => 'warning',
                ]);
            }
        }

        if (in_array($activeRole, ['admin', 'operator', 'pj', 'approver', 'ketua_tim'], true)) {
            $spkBastQuery = Spk::query()
                ->with(['petugas:id,nama', 'alokasiPetugas:id,periode_alokasi_id'])
                ->whereYear('tanggal_spk', $currentYear)
                ->whereDoesntHave('bast')
                // BAST SE2026 memakai upload manual. Gunakan whereHas + NOT EXISTS
                // yang eksplisit agar aman terhadap data SPK lama tanpa relasi alokasi.
                ->where(function ($query) {
                    $query->whereDoesntHave('alokasiPetugas.periodeAlokasi.kegiatan')
                        ->orWhereHas('alokasiPetugas.periodeAlokasi.kegiatan', function ($kegiatanQuery) {
                            $kegiatanQuery
                                ->where(function ($q) {
                                    $q->where('jenis_kegiatan', '!=', 'sensus')
                                        ->orWhereNull('jenis_kegiatan');
                                })
                                ->where(function ($q) {
                                    $q->where('nama_kegiatan', 'not like', '%Sensus Ekonomi%')
                                        ->orWhereNull('nama_kegiatan');
                                });
                        });
                });

            if ($activeRole === 'ketua_tim') {
                $kegiatanIds = Kegiatan::query()
                    ->where('ketua_tim_user_id', $user->id)
                    ->pluck('id');

                $spkBastQuery->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($kegiatanIds) {
                    $query->whereIn('kegiatan_id', $kegiatanIds);
                });
            }

            $spkWithoutBast = $spkBastQuery->get();

            // Petugas SE2026 yang sudah dicatat berhenti/mengundurkan diri tidak
            // memiliki kewajiban BAST pada SPK lama. Jangan biarkan SPK sumber
            // tersebut masuk notifikasi "BAST mendekati / melewati target".
            $stoppedSensusSpkIds = Schema::hasTable('sensus_ekonomi_petugas_replacements')
                ? DB::table('sensus_ekonomi_petugas_replacements')
                    ->whereNotNull('spk_lama_id')
                    ->where(function ($query): void {
                        $query->whereNull('status')
                            ->orWhere('status', '!=', 'dibatalkan');
                    })
                    ->pluck('spk_lama_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->flip()
                : collect();

            // Pre-load all alokasi_petugas satuan data to check BAST eligibility
            // (petugas with jumlah_satuan=0 and jumlah_satuan_listing=0 are not BAST candidates)
            $allAlokasiIds = $spkWithoutBast
                ->flatMap(fn ($spk) => $spk->alokasi_petugas_ids ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Load alokasi with status and periode_alokasi_id to apply effective alokasi priority:
            // perubahan > direvisi > disetujui > dikirim (same logic as BastController)
            $alokasiSatuanMap = AlokasiPetugas::whereIn('id', $allAlokasiIds)
                ->select('id', 'jumlah_satuan', 'jumlah_satuan_listing', 'periode_alokasi_id')
                ->with('periodeAlokasi:id,kegiatan_id,bulan,tahun,status')
                ->get()
                ->keyBy('id');

            // Pre-load perubahan periods kegiatan to detect removed petugas.
            // If a petugas is in a direvisi alokasi but NOT in the perubahan period for
            // the same kegiatan/month, they have been removed and are not BAST candidates.
            $kegiatanMonthCombos = $alokasiSatuanMap
                ->filter(fn ($a) => $a->periodeAlokasi !== null)
                ->map(fn ($a) => $a->periodeAlokasi->kegiatan_id.'_'.$a->periodeAlokasi->bulan.'_'.$a->periodeAlokasi->tahun)
                ->unique()
                ->values()
                ->all();

            // Build lookup: "kegiatan_id_bulan_tahun" => [petugas_id, ...]
            // Only for kegiatan that have a perubahan period
            $perubahanPetugasMap = [];

            if (! empty($kegiatanMonthCombos)) {
                $perubahanAlokasi = DB::table('alokasi_petugas as ap')
                    ->join('periode_alokasi as pa', 'ap.periode_alokasi_id', '=', 'pa.id')
                    ->where('pa.status', 'perubahan')
                    ->whereYear('pa.tahun', $currentYear)
                    ->select('pa.kegiatan_id', 'pa.bulan', 'pa.tahun', 'ap.petugas_id')
                    ->get();

                foreach ($perubahanAlokasi as $row) {
                    $key = $row->kegiatan_id.'_'.$row->bulan.'_'.$row->tahun;
                    $perubahanPetugasMap[$key][] = $row->petugas_id;
                }
            }

            // Pre-load which petugas already have a BAST for each year+month
            // (to avoid counting old/batch SPKs superseded by newer individual SPKs)
            $petugasMonthsWithBast = DB::table('bast_petugas as bp')
                ->join('bast as b', 'bp.bast_id', '=', 'b.id')
                ->whereYear('b.tanggal_bast', $currentYear)
                ->select(
                    'bp.petugas_id',
                    DB::raw('YEAR(b.tanggal_bast) as bast_year'),
                    DB::raw('MONTH(b.tanggal_bast) as bast_month'),
                )
                ->distinct()
                ->get()
                ->mapWithKeys(fn ($row) => [$row->petugas_id.'_'.$row->bast_year.'_'.$row->bast_month => true]);

            // BAST baru/manual tidak selalu memiliki baris bast_petugas. Gabungkan
            // relasi langsung bast -> spk -> petugas agar dashboard memakai sumber
            // kelengkapan yang sama dengan halaman Berita Acara.
            $directPetugasMonthsWithBast = DB::table('bast as b')
                ->join('spk as s', 'b.spk_id', '=', 's.id')
                ->whereNull('b.deleted_at')
                ->whereYear('b.tanggal_bast', $currentYear)
                ->select(
                    's.petugas_id',
                    DB::raw('YEAR(b.tanggal_bast) as bast_year'),
                    DB::raw('MONTH(b.tanggal_bast) as bast_month'),
                )
                ->distinct()
                ->get()
                ->mapWithKeys(fn ($row) => [$row->petugas_id.'_'.$row->bast_year.'_'.$row->bast_month => true]);

            $petugasMonthsWithBast = $petugasMonthsWithBast
                ->union($directPetugasMonthsWithBast)
                ->all();

            $bastDueSoonCount = 0;
            $bastOverdueCount = 0;
            $bastAttentionTargets = collect();

            foreach ($spkWithoutBast as $spk) {
                if ($stoppedSensusSpkIds->has((int) $spk->id)) {
                    continue;
                }

                $expectedBastDate = $spk->tanggal_selesai_kerja ?? $spk->tanggal_mulai_kerja;
                if (! $expectedBastDate) {
                    continue;
                }

                $bastDate = Carbon::parse($expectedBastDate);

                // Skip if petugas has no positive satuan after applying effective alokasi priority
                // (perubahan > direvisi > disetujui > dikirim — mirrors BastController logic)
                $statusPriority = ['perubahan' => 4, 'direvisi' => 3, 'disetujui' => 2, 'dikirim' => 1];
                $spkAlokasiIds = $spk->alokasi_petugas_ids ?? [];

                // Group alokasi by kegiatan, pick highest priority status per kegiatan.
                // If a perubahan period exists for a kegiatan but this petugas is NOT in it,
                // they were removed (replaced) and that kegiatan must be excluded entirely.
                $effectiveAlokasi = collect($spkAlokasiIds)
                    ->map(fn ($id) => $alokasiSatuanMap->get($id))
                    ->filter()
                    ->groupBy(fn ($alokasi) => $alokasi->periodeAlokasi?->kegiatan_id)
                    ->filter(function ($group, $kegiatanId) use ($spk, $bastDate, $perubahanPetugasMap) {
                        $bulanFormatted = str_pad((string) $bastDate->month, 2, '0', STR_PAD_LEFT);
                        $key = $kegiatanId.'_'.$bulanFormatted.'_'.$bastDate->year;
                        if (! array_key_exists($key, $perubahanPetugasMap)) {
                            return true; // no perubahan period — petugas not removed
                        }

                        return in_array($spk->petugas_id, $perubahanPetugasMap[$key], true);
                    })
                    ->map(function ($group) use ($statusPriority) {
                        return $group->sortByDesc(
                            fn ($alokasi) => $statusPriority[$alokasi->periodeAlokasi?->status] ?? 0
                        )->first();
                    });

                $hasPositiveSatuan = $effectiveAlokasi->contains(
                    fn ($alokasi) => (int) ($alokasi->jumlah_satuan ?? 0) > 0 || (int) ($alokasi->jumlah_satuan_listing ?? 0) > 0
                );

                if (! $hasPositiveSatuan) {
                    continue;
                }

                $effectivePeriode = $effectiveAlokasi
                    ->pluck('periodeAlokasi')
                    ->filter()
                    ->sortByDesc(fn ($periode) => $statusPriority[$periode->status] ?? 0)
                    ->first();

                $targetBulan = (int) ($effectivePeriode?->bulan ?? $bastDate->month);
                $targetTahun = (int) ($effectivePeriode?->tahun ?? $bastDate->year);

                // Gunakan periode yang sama dengan BastController::create(). SPK lama
                // atau tanggal selesai kontrak tidak boleh memunculkan notifikasi jika
                // SPK efektif/latest petugas pada periode tersebut sudah memiliki BAST.
                $latestSpkForPeriod = Spk::query()
                    ->where('petugas_id', $spk->petugas_id)
                    ->whereHas('alokasiPetugas.periodeAlokasi', function ($query) use ($targetBulan, $targetTahun): void {
                        $query->where('tahun', $targetTahun)
                            ->whereIn('bulan', [
                                (string) $targetBulan,
                                str_pad((string) $targetBulan, 2, '0', STR_PAD_LEFT),
                            ]);
                    })
                    ->with('bast')
                    ->orderByDesc('addendum_number')
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestSpkForPeriod?->bast->contains(function (Bast $bast) use ($targetBulan, $targetTahun): bool {
                    if (! $bast->tanggal_bast) {
                        return false;
                    }

                    return (int) $bast->tanggal_bast->format('Y') === $targetTahun
                        && (int) $bast->tanggal_bast->format('n') === $targetBulan;
                })) {
                    continue;
                }

                // Skip jika petugas sudah punya BAST di periode efektif lewat dokumen lain.
                $petugasMonthKey = $spk->petugas_id.'_'.$targetTahun.'_'.$targetBulan;
                if (array_key_exists($petugasMonthKey, $petugasMonthsWithBast)) {
                    continue;
                }

                $targetDate = clone $bastDate;
                while (in_array($targetDate->dayOfWeekIso, [6, 7], true)) {
                    $targetDate->subDay();
                }

                $attentionType = null;
                if ($targetDate->lt($today)) {
                    $bastOverdueCount++;
                    $attentionType = 'overdue';
                } elseif ($targetDate->betweenIncluded($today, $today->copy()->addDays(3))) {
                    $bastDueSoonCount++;
                    $attentionType = 'due_soon';
                }

                if ($attentionType !== null) {
                    $bastAttentionTargets->push([
                        'petugas' => $spk->petugas?->nama ?: 'Petugas',
                        'bulan' => $targetBulan,
                        'tahun' => $targetTahun,
                        'target_date' => $targetDate->toDateString(),
                        'type' => $attentionType,
                    ]);
                }
            }

            $bastAttentionCount = $bastDueSoonCount + $bastOverdueCount;

            if ($bastAttentionCount > 0) {
                $firstTarget = $bastAttentionTargets
                    ->sortBy(fn (array $item) => $item['target_date'])
                    ->first();

                // Dashboard membuka workspace BAST melalui POST terenkripsi
                // dari frontend. URL fallback selalu kanonik tanpa query string.
                $targetUrl = route('bast.index');

                $targetHint = $firstTarget
                    ? sprintf(
                        ' · %s (%s %d)',
                        $firstTarget['petugas'],
                        Carbon::create((int) $firstTarget['tahun'], (int) $firstTarget['bulan'], 1)->translatedFormat('F'),
                        (int) $firstTarget['tahun'],
                    )
                    : '';

                $attentionItems->push([
                    'key' => 'bast_due',
                    'label' => 'BAST mendekati / melewati target',
                    'count' => $bastAttentionCount,
                    'url' => $targetUrl,
                    'target_bulan' => $firstTarget['bulan'] ?? null,
                    'target_tahun' => $firstTarget['tahun'] ?? null,
                    'description' => sprintf(
                        '%d lewat target, %d mendekati target%s',
                        $bastOverdueCount,
                        $bastDueSoonCount,
                        $targetHint,
                    ),
                    'severity' => $bastOverdueCount > 0 ? 'danger' : 'warning',
                ]);
            }
        }

        // Chart data from January to current month
        $chartData = [];
        $petugasMonitoringData = [];
        $totalNonOrganikAktif = Petugas::where('status', 'aktif')->where('jenis_petugas', 'non-organik')->count();

        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthName = Carbon::create($currentYear, $month, 1)->format('M');
            $monthFormatted = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

            // Count total petugas allocated for this month (exclude honor=0)
            $totalPetugasAlokasi = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($totalPetugasAlokasi, $month, 'kegiatan');
            $totalPetugasAlokasiIds = $totalPetugasAlokasi
                ->distinct()
                ->pluck('alokasi_petugas.petugas_id')
                ->map(fn ($id) => (int) $id);

            $replacementPetugasBulan = $replacementAssignments
                ->filter(fn (array $assignment): bool =>
                    ((float) ($assignment['monthly_honor'][$month] ?? 0)) > 0
                )
                ->pluck('petugas_id')
                ->map(fn ($id) => (int) $id);

            $totalPetugasAlokasi = $totalPetugasAlokasiIds
                ->concat($replacementPetugasBulan)
                ->unique()
                ->count();

            // Count kegiatan for this month
            $kegiatanCount = DB::table('periode_alokasi')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear);
            $this->applySensusEkonomiMonthFilter($kegiatanCount, $month, 'kegiatan');
            $kegiatanCount = $kegiatanCount
                ->distinct('periode_alokasi.kegiatan_id')
                ->count('periode_alokasi.kegiatan_id');

            $chartData[] = [
                'month' => $monthName,
                'petugas_count' => $totalPetugasAlokasi,
                'kegiatan_count' => $kegiatanCount,
            ];

            // Get all alokasi for this month (non-organik only, exclude honor=0)
            $alokasiThisMonth = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($alokasiThisMonth, $month, 'kegiatan');
            $alokasiThisMonth = $alokasiThisMonth
                ->select('alokasi_petugas.petugas_id', DB::raw('COUNT(*) as jumlah_kegiatan'), DB::raw('SUM(COALESCE(alokasi_petugas.jumlah_satuan, 0) + COALESCE(alokasi_petugas.jumlah_satuan_listing, 0)) as total_satuan'))
                ->groupBy('alokasi_petugas.petugas_id')
                ->get();

            $replacementWorkloadRows = $replacementAssignments
                ->filter(fn (array $assignment): bool =>
                    ((float) ($assignment['monthly_honor'][$month] ?? 0)) > 0
                )
                ->map(fn (array $assignment) => (object) [
                    'petugas_id' => (int) $assignment['petugas_id'],
                    'jumlah_kegiatan' => 1,
                    'total_satuan' => (float) ($assignment['target_sisa'] ?? 0),
                ]);

            $alokasiThisMonth = $alokasiThisMonth
                ->concat($replacementWorkloadRows)
                ->groupBy('petugas_id')
                ->map(function ($rows) {
                    return (object) [
                        'petugas_id' => (int) $rows->first()->petugas_id,
                        'jumlah_kegiatan' => (int) $rows->sum(fn ($row) => (int) $row->jumlah_kegiatan),
                        'total_satuan' => (float) $rows->sum(fn ($row) => (float) $row->total_satuan),
                    ];
                })
                ->values();

            // Count by categories
            $petugasTidakDialokasikan = $totalNonOrganikAktif - $alokasiThisMonth->count();
            $petugas1_2Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan >= 1 && $p->jumlah_kegiatan <= 2)->count();
            $petugas3_5Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan >= 3 && $p->jumlah_kegiatan <= 5)->count();
            $petugasLebih5Kegiatan = $alokasiThisMonth->filter(fn ($p) => $p->jumlah_kegiatan > 5)->count();
            $totalKegiatanBulanIni = (int) $alokasiThisMonth->sum('jumlah_kegiatan');

            // Workload inequality stats per month
            $kegiatanCounts = $alokasiThisMonth->pluck('jumlah_kegiatan')->map(fn ($v) => (int) $v)->toArray();
            $avgKegiatan = 0;
            $maxKegiatan = 0;
            $minKegiatan = 0;
            $giniKegiatan = 0;

            if (count($kegiatanCounts) > 0) {
                $avgKegiatan = array_sum($kegiatanCounts) / count($kegiatanCounts);
                $maxKegiatan = max($kegiatanCounts);
                $minKegiatan = min($kegiatanCounts);

                $n = count($kegiatanCounts);

                if ($n > 1 && $avgKegiatan > 0) {
                    $sumAbsDiff = 0;
                    foreach ($kegiatanCounts as $xi) {
                        foreach ($kegiatanCounts as $xj) {
                            $sumAbsDiff += abs($xi - $xj);
                        }
                    }
                    $giniKegiatan = ($sumAbsDiff / (2 * $n * $n * $avgKegiatan)) * 100;
                }
            }

            // Satuan-based workload inequality (normalized metric, less biased than kegiatan count
            // since it accounts for actual work volume per kegiatan instead of treating all
            // kegiatan equally regardless of scope).
            // total_satuan combines jumlah_satuan + jumlah_satuan_listing so that listing-only
            // surveys (e.g. Susenas) are represented correctly instead of appearing as 0.
            // Petugas with combined satuan = 0 are still excluded to avoid noise from
            // allocations where satuan was never recorded.
            $satuanValues = $alokasiThisMonth
                ->pluck('total_satuan')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->toArray();
            $avgSatuan = 0;
            $cvSatuan = 0;

            if (count($satuanValues) > 0) {
                $avgSatuan = array_sum($satuanValues) / count($satuanValues);

                if (count($satuanValues) > 1 && $avgSatuan > 0) {
                    $sVariance = 0;
                    foreach ($satuanValues as $s) {
                        $sVariance += ($s - $avgSatuan) ** 2;
                    }
                    $sStdDev = sqrt($sVariance / count($satuanValues));
                    $cvSatuan = ($sStdDev / $avgSatuan) * 100;
                }
            }

            $petugasMonitoringData[] = [
                'month' => $monthName,
                'tidak_dialokasikan' => $petugasTidakDialokasikan,
                'kegiatan_1_2' => $petugas1_2Kegiatan,
                'kegiatan_3_5' => $petugas3_5Kegiatan,
                'kegiatan_lebih_5' => $petugasLebih5Kegiatan,
                'total_dialokasikan' => $alokasiThisMonth->count(),
                'total_kegiatan' => $totalKegiatanBulanIni,
                'kegiatan_count' => $kegiatanCount,
                'avg_kegiatan' => round($avgKegiatan, 2),
                'max_kegiatan' => $maxKegiatan,
                'min_kegiatan' => $minKegiatan,
                'gini_kegiatan' => round($giniKegiatan, 2),
                'avg_satuan' => round($avgSatuan, 1),
                'cv_satuan' => round($cvSatuan, 2),
            ];
        }

        // Honor inequality analysis data
        $honorInequalityData = [];
        $allPetugasHonorByMonth = []; // [petugas_id => [monthName => total_honor]]
        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthName = Carbon::create($currentYear, $month, 1)->format('M');

            // Get all honor data for this month, prefer 'perubahan' over 'dikirim' per (petugas, kegiatan)
            $rawAlokasi = DB::table('alokasi_petugas')
                ->join('periode_alokasi', 'alokasi_petugas.periode_alokasi_id', '=', 'periode_alokasi.id')
                ->join('petugas', 'alokasi_petugas.petugas_id', '=', 'petugas.id')
                ->join('kegiatan', 'periode_alokasi.kegiatan_id', '=', 'kegiatan.id')
                ->where('periode_alokasi.tahun', $currentYear)
                ->whereIn('periode_alokasi.status', ['dikirim', 'perubahan'])
                ->where('petugas.jenis_petugas', 'non-organik')
                ->whereRaw($this->allocationOrHonorExistsClause());
            $this->applySensusEkonomiMonthFilter($rawAlokasi, $month, 'kegiatan');
            $rawAlokasi = $rawAlokasi->select(
                'alokasi_petugas.petugas_id',
                'periode_alokasi.kegiatan_id',
                'periode_alokasi.status as periode_status',
                'kegiatan.jenis_kegiatan',
                'kegiatan.nama_kegiatan',
                'alokasi_petugas.total_honor',
                'alokasi_petugas.total_honor_listing',
                'alokasi_petugas.is_partial_payment',
                'alokasi_petugas.estimasi_honor_partial',
                'alokasi_petugas.is_partial_payment_listing',
                'alokasi_petugas.estimasi_honor_partial_listing'
            )
                ->get();

            // Group by petugas_id + kegiatan_id. Perubahan tetap paling prioritas.
            // Untuk Sensus Ekonomi, satu kegiatan dapat memiliki baris periode Juni–Agustus.
            // Pilih honor efektif terbesar pada status yang sama agar periode bernilai 0
            // tidak menutupi basis honor yang diproyeksikan ke bulan laporan.
            $grouped = [];
            foreach ($rawAlokasi as $row) {
                $key = $row->petugas_id.'-'.$row->kegiatan_id;
                if (! isset($grouped[$key])) {
                    $grouped[$key] = $row;
                    continue;
                }

                $existing = $grouped[$key];

                if ($row->periode_status === 'perubahan' && $existing->periode_status !== 'perubahan') {
                    $grouped[$key] = $row;
                    continue;
                }

                if ($row->periode_status !== $existing->periode_status) {
                    continue;
                }

                $rowEffectiveHonor = ($row->is_partial_payment && $row->estimasi_honor_partial !== null)
                    ? (float) $row->estimasi_honor_partial
                    : (float) ($row->total_honor ?? 0);
                $rowEffectiveHonor += ($row->is_partial_payment_listing && $row->estimasi_honor_partial_listing !== null)
                    ? (float) $row->estimasi_honor_partial_listing
                    : (float) ($row->total_honor_listing ?? 0);

                $existingEffectiveHonor = ($existing->is_partial_payment && $existing->estimasi_honor_partial !== null)
                    ? (float) $existing->estimasi_honor_partial
                    : (float) ($existing->total_honor ?? 0);
                $existingEffectiveHonor += ($existing->is_partial_payment_listing && $existing->estimasi_honor_partial_listing !== null)
                    ? (float) $existing->estimasi_honor_partial_listing
                    : (float) ($existing->total_honor_listing ?? 0);

                if ($rowEffectiveHonor > $existingEffectiveHonor) {
                    $grouped[$key] = $row;
                }
            }

            // Sum honor per petugas
            $petugasHonor = [];
            foreach ($grouped as $row) {
                $pid = $row->petugas_id;
                // Respect is_partial_payment: use estimasi_honor_partial when set,
                // mirroring AlokasiPetugas::getEffectiveCombinedHonor().
                $effectiveHonor = ($row->is_partial_payment && $row->estimasi_honor_partial !== null)
                    ? (float) $row->estimasi_honor_partial
                    : (float) ($row->total_honor ?? 0);
                $effectiveHonorListing = ($row->is_partial_payment_listing && $row->estimasi_honor_partial_listing !== null)
                    ? (float) $row->estimasi_honor_partial_listing
                    : (float) ($row->total_honor_listing ?? 0);
                $honor = $this->insightService->calculateDashboardHonor(
                    $month,
                    $effectiveHonor + $effectiveHonorListing,
                    $row->jenis_kegiatan ?? null,
                    $row->nama_kegiatan ?? null,
                );
                if (! isset($petugasHonor[$pid])) {
                    $petugasHonor[$pid] = 0;
                }
                $petugasHonor[$pid] += $honor;
            }

            foreach ($replacementAssignments as $assignment) {
                $replacementHonor = (float) ($assignment['monthly_honor'][$month] ?? 0);
                if ($replacementHonor <= 0) {
                    continue;
                }

                $pid = (int) $assignment['petugas_id'];
                if (! isset($petugasHonor[$pid])) {
                    $petugasHonor[$pid] = 0;
                }
                $petugasHonor[$pid] += $replacementHonor;
            }

            // Accumulate per-petugas monthly totals
            foreach ($petugasHonor as $pid => $total) {
                if ($total > 0) {
                    if (! isset($allPetugasHonorByMonth[$pid])) {
                        $allPetugasHonorByMonth[$pid] = [];
                    }
                    $allPetugasHonorByMonth[$pid][$monthName] = $total;
                }
            }

            // Build honorData as collection of objects for compatibility
            $honorData = collect();
            foreach ($petugasHonor as $pid => $total) {
                if ($total > 0) {
                    $honorData->push((object) [
                        'petugas_id' => $pid,
                        'total_honor_bulan' => $total,
                    ]);
                }
            }

            if ($honorData->count() > 0) {
                $honors = $honorData->pluck('total_honor_bulan')->toArray();
                $totalHonor = array_sum($honors);
                $avgHonor = $totalHonor / count($honors);
                $maxHonor = max($honors);
                $minHonor = min($honors);

                // Calculate standard deviation
                $variance = 0;
                foreach ($honors as $honor) {
                    $variance += pow($honor - $avgHonor, 2);
                }
                $stdDev = sqrt($variance / count($honors));

                // Calculate coefficient of variation (CV) as inequality measure
                $coefficientVariation = $avgHonor > 0 ? ($stdDev / $avgHonor) * 100 : 0;

                // Count distribution brackets
                $honor0_500rb = collect($honors)->filter(fn ($h) => $h >= 0 && $h <= 500000)->count();
                $honor501rb_1500rb = collect($honors)->filter(fn ($h) => $h >= 501000 && $h <= 1500000)->count();
                $honor1501rb_2500rb = collect($honors)->filter(fn ($h) => $h >= 1501000 && $h <= 2500000)->count();
                $honor2501rb_3500rb = collect($honors)->filter(fn ($h) => $h >= 2501000 && $h <= 3500000)->count();
                $honorLebih3501rb = collect($honors)->filter(fn ($h) => $h >= 3501000)->count();

                $honorInequalityData[] = [
                    'month' => $monthName,
                    'rata_rata_honor' => round($avgHonor, 0),
                    'honor_tertinggi' => round($maxHonor, 0),
                    'honor_terendah' => round($minHonor, 0),
                    'std_deviasi' => round($stdDev, 0),
                    'koefisien_variasi' => round($coefficientVariation, 2),
                    'honor_0_500rb' => $honor0_500rb,
                    'honor_501rb_1500rb' => $honor501rb_1500rb,
                    'honor_1501rb_2500rb' => $honor1501rb_2500rb,
                    'honor_2501rb_3500rb' => $honor2501rb_3500rb,
                    'honor_lebih_3501rb' => $honorLebih3501rb,
                    'total_petugas' => count($honors),
                    'total_honor_bulan' => round($totalHonor, 0),
                ];
            } else {
                $honorInequalityData[] = [
                    'month' => $monthName,
                    'rata_rata_honor' => 0,
                    'honor_tertinggi' => 0,
                    'honor_terendah' => 0,
                    'std_deviasi' => 0,
                    'koefisien_variasi' => 0,
                    'honor_0_500rb' => 0,
                    'honor_501rb_1500rb' => 0,
                    'honor_1501rb_2500rb' => 0,
                    'honor_2501rb_3500rb' => 0,
                    'honor_lebih_3501rb' => 0,
                    'total_petugas' => 0,
                    'total_honor_bulan' => 0,
                ];
            }
        }

        // Calculate summary statistics
        // Only average months that had at least one petugas allocated, to avoid
        // inflating 'tidak_dialokasikan' from months with no alokasi activity at all.
        $petugasMonitoringSummary = [
            'tidak_dialokasikan' => 0,
            'kegiatan_1_2' => 0,
            'kegiatan_3_5' => 0,
            'kegiatan_lebih_5' => 0,
        ];

        $monthsWithAlokasi = collect($petugasMonitoringData)
            ->filter(fn ($m) => ($m['kegiatan_1_2'] + $m['kegiatan_3_5'] + $m['kegiatan_lebih_5']) > 0);

        if ($monthsWithAlokasi->count() > 0) {
            $petugasMonitoringSummary = [
                'tidak_dialokasikan' => round($monthsWithAlokasi->avg('tidak_dialokasikan'), 0),
                'kegiatan_1_2' => round($monthsWithAlokasi->avg('kegiatan_1_2'), 0),
                'kegiatan_3_5' => round($monthsWithAlokasi->avg('kegiatan_3_5'), 0),
                'kegiatan_lebih_5' => round($monthsWithAlokasi->avg('kegiatan_lebih_5'), 0),
            ];
        }

        // Workload Inequality Summary
        $workloadInequalitySummary = ['has_data' => false];

        if ($monthsWithAlokasi->count() > 0) {
            // Use satuan-based CV as the primary inequality metric — it accounts for actual
            // work volume per kegiatan rather than treating all kegiatan with equal weight.
            $avgCvWorkload = $monthsWithAlokasi->avg('gini_kegiatan');
            $avgAvgKegiatan = $monthsWithAlokasi->avg('avg_kegiatan');
            $totalOverload = $monthsWithAlokasi->sum('kegiatan_lebih_5');
            $totalUnderutilized = $monthsWithAlokasi->sum('kegiatan_1_2');
            $totalAllocated = $monthsWithAlokasi->sum('total_dialokasikan');
            $pctOverload = $totalAllocated > 0 ? ($totalOverload / $totalAllocated) * 100 : 0;
            $pctUnderutilized = $totalAllocated > 0 ? ($totalUnderutilized / $totalAllocated) * 100 : 0;

            // Recommendation: how many petugas are needed per month to achieve 3-5 kegiatan/petugas
            $avgTotalKegiatan = $monthsWithAlokasi->avg('total_kegiatan');
            $avgKegiatanCount = $monthsWithAlokasi->avg('kegiatan_count');
            $avgAllocated = $monthsWithAlokasi->avg('total_dialokasikan');
            $maxAllocated = $monthsWithAlokasi->max('total_dialokasikan');
            $minAllocated = $monthsWithAlokasi->min('total_dialokasikan');
            $rekomendasiMin = $avgTotalKegiatan > 0 ? (int) ceil($avgTotalKegiatan / 5) : 0;
            $rekomendasiMax = $avgTotalKegiatan > 0 ? (int) ceil($avgTotalKegiatan / 3) : 0;
            $utilizationRate = $totalNonOrganikAktif > 0 ? ($avgAllocated / $totalNonOrganikAktif) * 100 : 0;

            $workloadInequalitySummary = [
                'has_data' => true,
                'avg_cv' => round((float) $avgCvWorkload, 2),
                'avg_kegiatan' => round((float) $avgAvgKegiatan, 2),
                'pct_overload' => round($pctOverload, 1),
                'pct_underutilized' => round($pctUnderutilized, 1),
                'total_non_organik_aktif' => $totalNonOrganikAktif,
                'avg_allocated' => round((float) $avgAllocated, 1),
                'max_allocated' => (int) $maxAllocated,
                'min_allocated' => (int) $minAllocated,
                'avg_total_kegiatan' => round((float) $avgTotalKegiatan, 1),
                'avg_kegiatan_count' => round((float) $avgKegiatanCount, 1),
                'rekomendasi_min' => $rekomendasiMin,
                'rekomendasi_max' => $rekomendasiMax,
                'utilization_rate' => round($utilizationRate, 1),
                'insights' => $this->insightService->buildWorkloadInsights($monthsWithAlokasi, $currentMonth, (float) $avgCvWorkload, (float) $avgAvgKegiatan, $totalNonOrganikAktif, $rekomendasiMin, $rekomendasiMax, round((float) $utilizationRate, 1)),
            ];
        }

        // Honor Inequality Summary - Average per month
        $honorInequalitySummary = ['has_data' => false];

        $honorMonthsWithData = collect($honorInequalityData)->filter(fn ($data) => $data['total_petugas'] > 0);

        if ($honorMonthsWithData->count() > 0) {
            $avgRataRataHonor = $honorMonthsWithData->avg('rata_rata_honor');
            $avgHonorTertinggi = $honorMonthsWithData->avg('honor_tertinggi');
            $avgHonorTerendah = $honorMonthsWithData->avg('honor_terendah');
            $avgStdDeviasi = $honorMonthsWithData->avg('std_deviasi');
            $avgKoefisienVariasi = $honorMonthsWithData->avg('koefisien_variasi');

            // Volume-weighted CV: weight each month by its total honor payout so that
            // months with more economic activity have proportionally more influence on
            // the annual inequality summary. Without weighting, a quiet month (few
            // petugas, possibly noisy CV) would count the same as a peak month.
            $totalHonorVolume = (float) $honorMonthsWithData->sum('total_honor_bulan');
            $weightedCvHonor = $totalHonorVolume > 0
                ? (float) $honorMonthsWithData->sum(fn ($m) => $m['koefisien_variasi'] * $m['total_honor_bulan']) / $totalHonorVolume
                : (float) $avgKoefisienVariasi;

            $avgGapHonor = $avgHonorTertinggi - $avgHonorTerendah;
            $avgTotalPetugas = $honorMonthsWithData->avg('total_petugas');

            $honorInequalitySummary = [
                'has_data' => true,
                'rata_rata_honor' => round($avgRataRataHonor, 0),
                'honor_tertinggi' => round($avgHonorTertinggi, 0),
                'honor_terendah' => round($avgHonorTerendah, 0),
                'std_deviasi' => round($avgStdDeviasi, 0),
                'koefisien_variasi' => round($weightedCvHonor, 2),
                'koefisien_variasi_simple' => round($avgKoefisienVariasi, 2),
                'gap_honor' => round($avgGapHonor, 0),
                'total_petugas' => round($avgTotalPetugas, 0),
                'insights' => $this->insightService->buildHonorInsights($honorMonthsWithData, $currentMonth, $avgRataRataHonor, $avgKoefisienVariasi, $weightedCvHonor),
            ];
        }

        // Build per-petugas honor per month table
        $petugasIds = array_keys($allPetugasHonorByMonth);
        $petugasNamaMap = Petugas::query()
            ->whereIn('id', $petugasIds)
            ->pluck('nama', 'id');

        $monthNames = [];
        for ($m = 1; $m <= $currentMonth; $m++) {
            $monthNames[] = Carbon::create($currentYear, $m, 1)->format('M');
        }

        $honorPerPetugas = collect($allPetugasHonorByMonth)
            ->map(function ($bulanData, $pid) use ($petugasNamaMap, $monthNames) {
                $total = array_sum($bulanData);
                $perBulan = [];
                foreach ($monthNames as $mn) {
                    $perBulan[$mn] = $bulanData[$mn] ?? 0;
                }

                return [
                    'petugas_id' => $pid,
                    'nama' => $petugasNamaMap[$pid] ?? '-',
                    'per_bulan' => $perBulan,
                    'total' => $total,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $reviewRows = ReviewPetugas::query()
            ->with([
                'petugas:id,nama',
                'periodeAlokasi:id,bulan,tahun',
            ])
            ->whereHas('periodeAlokasi', function ($query) use ($currentYear) {
                $query->where('tahun', $currentYear);
            })
            ->get();

        $reviewRowsCurrentMonth = $reviewRows->filter(function ($review) use ($currentMonthFormatted) {
            $reviewMonth = $review->reviewed_at?->format('m')
                ?? str_pad((string) ($review->periodeAlokasi?->bulan ?? ''), 2, '0', STR_PAD_LEFT);

            return $reviewMonth === $currentMonthFormatted;
        });

        $reviewRowsCurrentMonthGlobalAvg = $reviewRowsCurrentMonth->isNotEmpty()
            ? (float) $reviewRowsCurrentMonth->avg('rating')
            : 0.0;

        $bestMitraCurrentMonth = $reviewRowsCurrentMonth
            ->groupBy('petugas_id')
            ->map(function ($group) use ($reviewRowsCurrentMonthGlobalAvg) {
                $first = $group->first();
                $reviewCount = $group->count();
                $avgRating = (float) $group->avg('rating');
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($reviewRowsCurrentMonthGlobalAvg * 0.3))
                    * (0.6 + (0.4 * $confidence));

                return [
                    'petugas_id' => $first->petugas_id,
                    'petugas_nama' => $first->petugas?->nama ?? '-',
                    'avg_rating' => round($avgRating, 2),
                    'total_review' => $reviewCount,
                    'balanced_score' => round($balancedScore, 3),
                ];
            })
            ->sortByDesc(fn ($item) => ($item['balanced_score'] * 1000) + $item['total_review'])
            ->first();

        $reviewRowsGlobalAvg = $reviewRows->isNotEmpty()
            ? (float) $reviewRows->avg('rating')
            : 0.0;

        $topMitra = $reviewRows
            ->groupBy('petugas_id')
            ->map(function ($group) use ($reviewRowsGlobalAvg) {
                $first = $group->first();
                $reviewCount = $group->count();
                $avgRating = (float) $group->avg('rating');
                $confidence = min(1, $reviewCount / 5);
                $balancedScore = (($avgRating * 0.7) + ($reviewRowsGlobalAvg * 0.3))
                    * (0.6 + (0.4 * $confidence));

                return [
                    'petugas_id' => $first->petugas_id,
                    'petugas_nama' => $first->petugas?->nama ?? '-',
                    'avg_rating' => round($avgRating, 2),
                    'total_review' => $reviewCount,
                    'balanced_score' => round($balancedScore, 3),
                ];
            })
            ->sortByDesc(fn ($item) => ($item['balanced_score'] * 1000) + $item['total_review'])
            ->take(3)
            ->values();

        $mitraReviewSummary = [
            'year' => [
                'total_reviews' => $reviewRows->count(),
                'avg_rating' => $reviewRows->count() > 0 ? round((float) $reviewRows->avg('rating'), 2) : 0,
                'mitra_reviewed' => $reviewRows->pluck('petugas_id')->unique()->count(),
                'positive_percentage' => $reviewRows->count() > 0
                    ? round(($reviewRows->where('rating', '>=', 4)->count() / $reviewRows->count()) * 100, 1)
                    : 0,
            ],
            'current_month' => [
                'total_reviews' => $reviewRowsCurrentMonth->count(),
                'avg_rating' => $reviewRowsCurrentMonth->count() > 0 ? round((float) $reviewRowsCurrentMonth->avg('rating'), 2) : 0,
                'mitra_reviewed' => $reviewRowsCurrentMonth->pluck('petugas_id')->unique()->count(),
                'positive_percentage' => $reviewRowsCurrentMonth->count() > 0
                    ? round(($reviewRowsCurrentMonth->where('rating', '>=', 4)->count() / $reviewRowsCurrentMonth->count()) * 100, 1)
                    : 0,
            ],
            'top_mitra' => $topMitra,
            'best_mitra_current_month' => $bestMitraCurrentMonth,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'additionalStats' => $additionalStats,
            'recentAlokasi' => $recentAlokasi,
            'kegiatanBulanIni' => $kegiatanBulanIni,
            'chartData' => $chartData,
            'petugasMonitoringData' => $petugasMonitoringData,
            'honorInequalityData' => $honorInequalityData,
            'honorPerPetugas' => $honorPerPetugas,
            'honorMonths' => $monthNames,
            'petugasMonitoringSummary' => $petugasMonitoringSummary,
            'workloadInequalitySummary' => $workloadInequalitySummary,
            'honorInequalitySummary' => $honorInequalitySummary,
            'mitraReviewSummary' => $mitraReviewSummary,
            'attentionItems' => $attentionItems->values(),
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'userRole' => $user->role,
        ]);
    }
}
