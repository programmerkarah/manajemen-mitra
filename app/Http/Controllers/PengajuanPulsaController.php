<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanPulsaRequest;
use App\Models\ActivityLog;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\PeriodeAlokasi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanPulsaController extends Controller
{
    private const PENGOLAHAN_ROLES = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];

    /**
     * Display a listing of pengajuan pulsa.
     */
    public function index(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = \App\Services\ActiveYearService::get();

        $query = PengajuanPulsa::query()
            ->with([
                'petugas:id,nama',
                'kegiatan:id,kode_kegiatan,nama_kegiatan',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($effectiveUser?->isKetuaTim() && ! $effectiveUser->isAdmin() && ! $effectiveUser->isOperator()) {
            $kegiatanIds = Kegiatan::where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            })->pluck('id');

            $query->whereIn('kegiatan_id', $kegiatanIds);
        }

        $pengajuanList = $query->orderBy('petugas_id')->orderBy('kegiatan_id')->get();

        $encryptedData = encryptData($pengajuanList);

        return Inertia::render('PengajuanPulsa/Index', [
            'pengajuanList' => [
                'encrypted' => $encryptedData,
            ],
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    /**
     * Show the form for creating a new pengajuan pulsa.
     */
    public function create(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = (string) \App\Services\ActiveYearService::get();
        $bulanInt = (int) $bulan;

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        // --- Step 1: Find pelatihan kegiatan with bulan_pelatihan == bulan ---
        // For each, determine the allocation bulan used to retrieve petugas:
        //   - If kegiatan starts in bulan_pelatihan month → use bulan_pelatihan (same as submission)
        //   - Otherwise → use bulan_pelatihan + 1 (petugas already allocated for the work month)
        $pelatihanKegiatanQuery = Kegiatan::query()
            ->where('tahun_anggaran', $tahun)
            ->where('bulan_pelatihan', $bulanInt)
            ->whereNotNull('metode_pelatihan')
            ->where('metode_pelatihan', '!=', 'tidak_ada_pelatihan')
            ->select('id', 'bulan_pelatihan', 'tanggal_mulai');

        if (! $isAdminOrOperator) {
            $pelatihanKegiatanQuery->where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $pelatihanKegiatanList = $pelatihanKegiatanQuery->get();

        /**
         * Map kegiatan_id → alokasi period info for pelatihan petugas lookup.
         *
         * @var \Illuminate\Support\Collection<int, array{kegiatan_id: int, alokasi_bulan: string, alokasi_tahun: string}>
         */
        $pelatihanAlokasiInfo = $pelatihanKegiatanList->map(function ($k) use ($bulanInt, $tahun) {
            $mulaiMonth = $k->tanggal_mulai?->month;
            $useSameBulan = ($mulaiMonth === $bulanInt);

            if ($useSameBulan) {
                return [
                    'kegiatan_id' => $k->id,
                    'alokasi_bulan' => str_pad($bulanInt, 2, '0', STR_PAD_LEFT),
                    'alokasi_tahun' => $tahun,
                ];
            }

            $nextBulan = $bulanInt + 1;
            $nextTahun = (int) $tahun;
            if ($nextBulan > 12) {
                $nextBulan = 1;
                $nextTahun++;
            }

            return [
                'kegiatan_id' => $k->id,
                'alokasi_bulan' => str_pad($nextBulan, 2, '0', STR_PAD_LEFT),
                'alokasi_tahun' => (string) $nextTahun,
            ];
        })->keyBy('kegiatan_id');

        // Find which pelatihan kegiatan have allocations in their computed period
        $kegiatanWithPelatihanPeriod = collect();
        foreach ($pelatihanAlokasiInfo as $kegiatanId => $info) {
            $hasPeriod = PeriodeAlokasi::query()
                ->where('kegiatan_id', $kegiatanId)
                ->where('bulan', $info['alokasi_bulan'])
                ->where('tahun', $info['alokasi_tahun'])
                ->whereHas('alokasiPetugas', function ($q) {
                    $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                        ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
                })
                ->exists();

            if ($hasPeriod) {
                $kegiatanWithPelatihanPeriod->push($kegiatanId);
            }
        }

        // --- Step 2: Find kegiatan with pendataan allocations in current bulan ---
        $kegiatanWithPendataanPeriod = PeriodeAlokasi::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereHas('alokasiPetugas', function ($q) {
                $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                    ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
            })
            ->pluck('kegiatan_id');

        $kegiatanWithPeriod = $kegiatanWithPendataanPeriod->merge($kegiatanWithPelatihanPeriod)->unique()->values();

        // --- Step 3: Load eligible kegiatan ---
        $kegiatanQuery = Kegiatan::query()
            ->whereIn('id', $kegiatanWithPeriod)
            ->where('tahun_anggaran', $tahun);

        if (! $isAdminOrOperator) {
            $kegiatanQuery->where(function ($q) use ($effectiveUser) {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $eligibleKegiatan = $kegiatanQuery
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'metode_pendataan_pencacahan', 'metode_pendataan_listing', 'metode_pelatihan', 'bulan_pelatihan', 'has_listing_updating', 'tanggal_mulai')
            ->where(function ($q) use ($kegiatanWithPelatihanPeriod) {
                // Show if at least one column is available:
                // - Pelatihan: kegiatan is in the pelatihan-eligible set (has allocation in configured bulan)
                // - Pendataan: CAPI method
                // - Legacy: metode_pelatihan not yet set
                $q->whereIn('id', $kegiatanWithPelatihanPeriod)
                    ->orWhere('metode_pendataan_pencacahan', 'CAPI')
                    ->orWhereNull('metode_pelatihan');
            })
            ->orderBy('kode_kegiatan')
            ->get();

        $kegiatanIds = $eligibleKegiatan->pluck('id');

        // --- Step 4: Build petugasPerKegiatanPendataan (from current bulan allocations) ---
        // Pendataan petugas: always filter by current bulan so the column is only
        // activated when there is an allocation in that specific month.
        $pendataanAllocations = AlokasiPetugas::query()
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun, $kegiatanIds) {
                $q->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->whereIn('kegiatan_id', $kegiatanIds);
            })
            ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
            ->whereHas('petugas', fn ($q) => $q->where('jenis_petugas', 'non-organik'))
            ->with([
                'petugas:id,nama,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,bulan,tahun',
            ])
            ->get();

        $petugasPerKegiatan = $pendataanAllocations
            ->groupBy(fn ($a) => $a->periodeAlokasi?->kegiatan_id)
            ->map(fn ($group) => $group
                ->unique('petugas_id')
                ->sortBy('petugas.nama')
                ->map(fn ($a) => [
                    'id' => $a->petugas?->id,
                    'nama' => $a->petugas?->nama,
                    'peran' => $a->peran,
                ])
                ->values()
            );

        // --- Step 5: Build petugasPerKegiatanPelatihan (from allocation bulan per kegiatan) ---
        // Group eligible pelatihan kegiatan by their alokasi period to minimize queries
        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array{kegiatan_id: int, alokasi_bulan: string, alokasi_tahun: string}>> $byAlokasiPeriod */
        $byAlokasiPeriod = $pelatihanAlokasiInfo
            ->filter(fn ($info) => $kegiatanWithPelatihanPeriod->contains($info['kegiatan_id']))
            ->groupBy(fn ($info) => $info['alokasi_bulan'].'_'.$info['alokasi_tahun']);

        $petugasPerKegiatanPelatihan = collect();

        foreach ($byAlokasiPeriod as $periodKey => $kegiatanGroup) {
            [$alokasiB, $alokasiT] = explode('_', $periodKey, 2);
            $pelatihanKegiatanIds = $kegiatanGroup->pluck('kegiatan_id');

            $pelatihanAllocations = AlokasiPetugas::query()
                ->whereHas('periodeAlokasi', function ($q) use ($alokasiB, $alokasiT, $pelatihanKegiatanIds) {
                    $q->where('bulan', $alokasiB)
                        ->where('tahun', $alokasiT)
                        ->whereIn('kegiatan_id', $pelatihanKegiatanIds);
                })
                ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                ->whereHas('petugas', fn ($q) => $q->where('jenis_petugas', 'non-organik'))
                ->with([
                    'petugas:id,nama,jenis_petugas',
                    'periodeAlokasi:id,kegiatan_id,bulan,tahun',
                ])
                ->get();

            $grouped = $pelatihanAllocations
                ->groupBy(fn ($a) => $a->periodeAlokasi?->kegiatan_id)
                ->map(fn ($group) => $group
                    ->unique('petugas_id')
                    ->sortBy('petugas.nama')
                    ->map(fn ($a) => [
                        'id' => $a->petugas?->id,
                        'nama' => $a->petugas?->nama,
                        'peran' => $a->peran,
                    ])
                    ->values()
                );

            foreach ($grouped as $kegiatanId => $petugasList) {
                $petugasPerKegiatanPelatihan->put($kegiatanId, $petugasList);
            }
        }

        // --- Step 6: Build existing submission data ---
        $existingSubmissions = PengajuanPulsa::query()
            ->whereIn('kegiatan_id', $kegiatanIds)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->get(['kegiatan_id', 'petugas_id', 'jenis_pulsa', 'nominal', 'nominal_disetujui', 'status']);

        $existingSubmissionsWithEffectiveNominal = $existingSubmissions->map(function ($row) {
            $effectiveNominal = $row->status === 'diterima'
                ? (float) ($row->nominal_disetujui ?? $row->nominal)
                : (float) $row->nominal;

            return [
                'kegiatan_id' => (int) $row->kegiatan_id,
                'petugas_id' => (int) $row->petugas_id,
                'jenis_pulsa' => (string) $row->jenis_pulsa,
                'nominal' => $effectiveNominal,
            ];
        });

        $existingTotals = $existingSubmissionsWithEffectiveNominal
            ->groupBy('petugas_id')
            ->map(fn ($rows) => $rows->sum('nominal'));

        /**
         * Key format: "${kegiatan_id}_${petugas_id}_${jenis_pulsa}" → nominal
         * Used on frontend to lock cells that already have a non-rejected submission.
         *
         * @var array<string, float>
         */
        $existingPerKegiatan = $existingSubmissionsWithEffectiveNominal
            ->mapWithKeys(fn ($row) => [
                "{$row['kegiatan_id']}_{$row['petugas_id']}_{$row['jenis_pulsa']}" => (float) $row['nominal'],
            ]);

        return Inertia::render('PengajuanPulsa/Create', [
            'eligibleKegiatan' => $eligibleKegiatan,
            'petugasPerKegiatan' => $petugasPerKegiatan,
            'petugasPerKegiatanPelatihan' => $petugasPerKegiatanPelatihan,
            'existingTotals' => $existingTotals,
            'existingPerKegiatan' => $existingPerKegiatan,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    /**
     * Store newly created pengajuan pulsa records.
     */
    public function store(StorePengajuanPulsaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $effectiveUser = effectiveUser($request);
        $bulan = $validated['bulan'];
        $tahun = \App\Services\ActiveYearService::get();
        $items = $validated['items'];

        /** @var array<int, array{kegiatan_id: int, petugas_id: int, jenis_pulsa: string, nominal: float}> $items */

        // Validate that all kegiatan are CAPI and belong to this ketua tim
        $kegiatanIds = collect($items)->pluck('kegiatan_id')->unique()->values();
        // Load kegiatan for validation
        $validKegiatanModels = Kegiatan::whereIn('id', $kegiatanIds)
            ->when(
                $effectiveUser?->isKetuaTim() && ! $effectiveUser->isAdmin(),
                fn ($q) => $q->where(function ($q2) use ($effectiveUser) {
                    $q2->where('ketua_tim_user_id', $effectiveUser->id)
                        ->orWhere('pj_lainnya_id', $effectiveUser->id);
                })
            )
            ->get(['id', 'metode_pendataan_pencacahan', 'metode_pelatihan', 'bulan_pelatihan']);

        $validKegiatanById = $validKegiatanModels->keyBy('id');
        $validKegiatan = $validKegiatanModels->pluck('id');

        $invalidKegiatan = $kegiatanIds->diff($validKegiatan);

        if ($invalidKegiatan->isNotEmpty()) {
            return back()->withErrors([
                'items' => 'Beberapa kegiatan tidak ditemukan atau bukan kegiatan Anda.',
            ]);
        }

        // Validate per-item: pulsa pelatihan only allowed for kegiatan with pelatihan month configured
        foreach ($items as $item) {
            if ($item['jenis_pulsa'] === 'pelatihan') {
                /** @var \App\Models\Kegiatan|null $kegiatan */
                $kegiatan = $validKegiatanById->get($item['kegiatan_id']);
                $metodePelatihan = $kegiatan?->metode_pelatihan;
                if ($metodePelatihan === null || $metodePelatihan === 'tidak_ada_pelatihan') {
                    return back()->withErrors([
                        'items' => 'Pulsa pelatihan hanya dapat diajukan untuk kegiatan yang memiliki pelatihan.',
                    ]);
                }

                if ((int) ($kegiatan?->bulan_pelatihan ?? 0) !== (int) $bulan) {
                    return back()->withErrors([
                        'items' => 'Pulsa pelatihan hanya dapat diajukan pada bulan penyelenggaraan pelatihan yang diatur pada kegiatan.',
                    ]);
                }
            }
            if ($item['jenis_pulsa'] === 'pendataan') {
                /** @var \App\Models\Kegiatan|null $kegiatan */
                $kegiatan = $validKegiatanById->get($item['kegiatan_id']);
                if ($kegiatan?->metode_pendataan_pencacahan !== 'CAPI') {
                    return back()->withErrors([
                        'items' => 'Pulsa pendataan hanya dapat diajukan untuk kegiatan dengan metode pendataan CAPI.',
                    ]);
                }
            }
        }

        // Validate per-petugas max 100k across all items for this bulan/tahun
        $newTotalsPerPetugas = collect($items)
            ->groupBy('petugas_id')
            ->map(fn ($group) => collect($group)->sum('nominal'));

        $existingTotals = PengajuanPulsa::whereIn('petugas_id', $newTotalsPerPetugas->keys())
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->select('petugas_id', DB::raw('SUM(nominal) as total_nominal'))
            ->groupBy('petugas_id')
            ->pluck('total_nominal', 'petugas_id');

        $errors = [];
        foreach ($newTotalsPerPetugas as $petugasId => $newTotal) {
            $existingTotal = (float) ($existingTotals[$petugasId] ?? 0);
            if ($existingTotal + $newTotal > 100000) {
                $errors["petugas_{$petugasId}"] = "Total pulsa petugas ID {$petugasId} melebihi batas Rp100.000 per bulan (saat ini: Rp{$existingTotal}, baru: Rp{$newTotal}).";
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors);
        }

        // Find periode_alokasi for each kegiatan+bulan+tahun
        $periodeAlokasiMap = PeriodeAlokasi::whereIn('kegiatan_id', $kegiatanIds)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('id', 'kegiatan_id');

        $storedCount = 0;
        $totalNominal = 0.0;

        DB::transaction(function () use ($items, $bulan, $tahun, $periodeAlokasiMap, $validated, $effectiveUser, &$storedCount, &$totalNominal) {
            foreach ($items as $item) {
                if ((float) $item['nominal'] <= 0) {
                    continue;
                }

                PengajuanPulsa::create([
                    'petugas_id' => $item['petugas_id'],
                    'kegiatan_id' => $item['kegiatan_id'],
                    'periode_alokasi_id' => $periodeAlokasiMap[$item['kegiatan_id']] ?? null,
                    'bulan' => $bulan,
                    'tahun' => (int) $tahun,
                    'jenis_pulsa' => $item['jenis_pulsa'],
                    'nominal' => $item['nominal'],
                    'status' => 'dikirim',
                    'submitted_by' => $effectiveUser?->id ?? Auth::id(),
                    'submitted_at' => now(),
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                $storedCount++;
                $totalNominal += (float) $item['nominal'];
            }
        });

        $bulanName = Carbon::create()->month((int) $bulan)->translatedFormat('F');
        $uniqueKegiatanCount = $kegiatanIds->count();

        try {
            ActivityLog::log(
                'Kirim Pengajuan Pulsa',
                'pengajuan_pulsa',
                "Berhasil mengirim {$storedCount} pengajuan pulsa untuk {$bulanName} {$tahun} ({$uniqueKegiatanCount} kegiatan, total Rp".number_format($totalNominal, 0, ',', '.').')',
                'success',
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'item_count' => $storedCount,
                    'total_nominal' => $totalNominal,
                    'kegiatan_ids' => $kegiatanIds->toArray(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan-pulsa.index', [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ])->with('success', 'Pengajuan pulsa berhasil disimpan.');
    }

    /**
     * Show the detail page for a specific kegiatan's pengajuan pulsa in a given month.
     */
    public function detail(Request $request): Response
    {
        $effectiveUser = effectiveUser($request);
        $kegiatanId = (int) $request->input('kegiatan_id');
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = \App\Services\ActiveYearService::get();

        $kegiatan = Kegiatan::findOrFail($kegiatanId);

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        if (! $isAdminOrOperator) {
            abort_unless(
                $kegiatan->ketua_tim_user_id === $effectiveUser?->id ||
                $kegiatan->pj_lainnya_id === $effectiveUser?->id,
                403
            );
        }

        $pengajuanList = PengajuanPulsa::query()
            ->with([
                'petugas:id,nama',
                'submittedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->where('kegiatan_id', $kegiatanId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('petugas_id')
            ->orderBy('jenis_pulsa')
            ->get();

        return Inertia::render('PengajuanPulsa/Detail', [
            'kegiatan' => $kegiatan->only(['id', 'kode_kegiatan', 'nama_kegiatan', 'metode_pendataan_pencacahan']),
            'pengajuanList' => [
                'encrypted' => encryptData($pengajuanList),
            ],
            'filters' => [
                'bulan' => $bulan,
                'tahun' => (string) $tahun,
            ],
            'canReview' => $isAdminOrOperator,
        ]);
    }

    /**
     * Review (approve or reject) a single pengajuan pulsa.
     */
    public function review(Request $request, PengajuanPulsa $pengajuanPulsa): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:diterima,ditolak'],
            'nominal_disetujui' => [
                'required_if:action,diterima',
                'nullable',
                'numeric',
                'min:1',
                'max:'.(float) $pengajuanPulsa->nominal,
            ],
            'catatan_penolakan' => ['required_if:action,ditolak', 'nullable', 'string', 'max:500'],
        ]);

        $pengajuanPulsa->load('petugas:id,nama', 'kegiatan:id,kode_kegiatan,nama_kegiatan');

        $isDiterima = $validated['action'] === 'diterima';
        $nominalDisetujui = $isDiterima ? (float) ($validated['nominal_disetujui'] ?? $pengajuanPulsa->nominal) : null;

        $pengajuanPulsa->update([
            'status' => $validated['action'],
            'nominal_disetujui' => $nominalDisetujui,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'catatan_penolakan' => $isDiterima ? null : ($validated['catatan_penolakan'] ?? null),
        ]);

        $petugasName = $pengajuanPulsa->petugas?->nama ?? 'Petugas #'.$pengajuanPulsa->petugas_id;
        $kodeKegiatan = $pengajuanPulsa->kegiatan?->kode_kegiatan ?? 'Kegiatan #'.$pengajuanPulsa->kegiatan_id;
        $bulanName = Carbon::create()->month((int) $pengajuanPulsa->bulan)->translatedFormat('F');
        $nominalFormatted = 'Rp'.number_format((float) $pengajuanPulsa->nominal, 0, ',', '.');
        $disetujuiFormatted = 'Rp'.number_format((float) $nominalDisetujui, 0, ',', '.');
        $jenisPulsa = $pengajuanPulsa->jenis_pulsa === 'pendataan' ? 'Pendataan' : 'Pelatihan';

        try {
            ActivityLog::log(
                $isDiterima ? 'Terima Pengajuan Pulsa' : 'Tolak Pengajuan Pulsa',
                'pengajuan_pulsa',
                $isDiterima
                    ? "Pengajuan pulsa {$jenisPulsa} {$petugasName} ({$kodeKegiatan}) {$bulanName} {$pengajuanPulsa->tahun} senilai {$nominalFormatted} diterima (disetujui {$disetujuiFormatted})"
                    : "Pengajuan pulsa {$jenisPulsa} {$petugasName} ({$kodeKegiatan}) {$bulanName} {$pengajuanPulsa->tahun} senilai {$nominalFormatted} ditolak",
                'success',
                [
                    'pengajuan_id' => $pengajuanPulsa->id,
                    'kegiatan_id' => $pengajuanPulsa->kegiatan_id,
                    'kode_kegiatan' => $kodeKegiatan,
                    'petugas_id' => $pengajuanPulsa->petugas_id,
                    'petugas_nama' => $petugasName,
                    'jenis_pulsa' => $pengajuanPulsa->jenis_pulsa,
                    'nominal' => (float) $pengajuanPulsa->nominal,
                    'nominal_disetujui' => $nominalDisetujui,
                    'bulan' => $pengajuanPulsa->bulan,
                    'tahun' => $pengajuanPulsa->tahun,
                    'action' => $validated['action'],
                    'catatan_penolakan' => $validated['catatan_penolakan'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log review pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        $message = $isDiterima
            ? 'Pengajuan pulsa berhasil diterima.'
            : 'Pengajuan pulsa ditolak.';

        return back()->with('success', $message);
    }

    /**
     * Bulk-review "dikirim" pengajuan pulsa for a kegiatan in a given period.
     * Accepts per-item decisions including per-item nominal_disetujui.
     *
     * @param  array<int, array{id: int, action: string, nominal_disetujui?: float, catatan_penolakan?: string}>  $items
     */
    public function reviewAll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'bulan' => ['required', 'string'],
            'tahun' => ['required', 'integer'],
            'catatan_penolakan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:pengajuan_pulsa,id'],
            'items.*.action' => ['required', 'in:diterima,ditolak'],
            'items.*.nominal_disetujui' => ['nullable', 'numeric', 'min:1'],
            'items.*.catatan_penolakan' => ['nullable', 'string', 'max:500'],
        ]);

        // Load all targeted items and verify they belong to the given kegiatan/period
        $itemIds = collect($validated['items'])->pluck('id');
        $targetItems = PengajuanPulsa::query()
            ->with('petugas:id,nama', 'kegiatan:id,kode_kegiatan,nama_kegiatan')
            ->whereIn('id', $itemIds)
            ->where('kegiatan_id', $validated['kegiatan_id'])
            ->where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->where('status', 'dikirim')
            ->get()
            ->keyBy('id');

        if ($targetItems->isEmpty()) {
            return back()->with('error', 'Tidak ada pengajuan pulsa dengan status "Diajukan" untuk periode ini.');
        }

        $reviewedAt = now();
        $reviewerId = Auth::id();
        $globalCatatan = $validated['catatan_penolakan'] ?? null;

        /** @var array<int, array{action: string, nominal_disetujui?: float|null, catatan_penolakan?: string|null}> $itemMap */
        $itemMap = collect($validated['items'])->keyBy('id');

        $countDiterima = 0;
        $countDitolak = 0;
        $totalDisetujui = 0.0;

        foreach ($targetItems as $item) {
            $itemData = $itemMap[$item->id] ?? null;
            if (! $itemData) {
                continue;
            }

            $isDiterima = $itemData['action'] === 'diterima';
            $nominalDisetujui = $isDiterima
                ? min((float) ($itemData['nominal_disetujui'] ?? $item->nominal), (float) $item->nominal)
                : null;

            $catatanPenolakan = $isDiterima
                ? null
                : ($itemData['catatan_penolakan'] ?? $globalCatatan);

            $item->update([
                'status' => $itemData['action'],
                'nominal_disetujui' => $nominalDisetujui,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $reviewedAt,
                'catatan_penolakan' => $catatanPenolakan,
            ]);

            if ($isDiterima) {
                $countDiterima++;
                $totalDisetujui += $nominalDisetujui ?? 0.0;
            } else {
                $countDitolak++;
            }
        }

        $firstItem = $targetItems->first();
        $kodeKegiatan = $firstItem->kegiatan?->kode_kegiatan ?? 'Kegiatan #'.$validated['kegiatan_id'];
        $bulanName = Carbon::create()->month((int) $validated['bulan'])->translatedFormat('F');
        $count = $countDiterima + $countDitolak;

        try {
            ActivityLog::log(
                'Proses Review Pengajuan Pulsa',
                'pengajuan_pulsa',
                "Review {$count} pengajuan pulsa ({$kodeKegiatan}) {$bulanName} {$validated['tahun']}: {$countDiterima} diterima, {$countDitolak} ditolak",
                'success',
                [
                    'kegiatan_id' => $validated['kegiatan_id'],
                    'kode_kegiatan' => $kodeKegiatan,
                    'bulan' => $validated['bulan'],
                    'tahun' => $validated['tahun'],
                    'count_diterima' => $countDiterima,
                    'count_ditolak' => $countDitolak,
                    'total_disetujui' => $totalDisetujui,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log review-all pengajuan pulsa activity', ['error' => $e->getMessage()]);
        }

        $parts = [];
        if ($countDiterima > 0) {
            $parts[] = "{$countDiterima} diterima";
        }
        if ($countDitolak > 0) {
            $parts[] = "{$countDitolak} ditolak";
        }
        $message = 'Review selesai: '.implode(', ', $parts).'.';

        return back()->with('success', $message);
    }
}
