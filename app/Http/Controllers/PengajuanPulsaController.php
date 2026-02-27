<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanPulsaRequest;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PengajuanPulsa;
use App\Models\PeriodeAlokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $isAdminOrOperator = $effectiveUser?->isAdmin() || $effectiveUser?->isOperator();

        /**
         * Hanya tampilkan kegiatan yang memiliki minimal satu petugas non-organik
         * (bukan pengolahan) yang dialokasikan di bulan yang dipilih.
         *
         * @var \Illuminate\Support\Collection<int, int> $kegiatanWithPeriod
         */
        $kegiatanWithPeriod = \App\Models\PeriodeAlokasi::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereHas('alokasiPetugas', function ($q) {
                $q->whereNotIn('peran', self::PENGOLAHAN_ROLES)
                    ->whereHas('petugas', fn ($q2) => $q2->where('jenis_petugas', 'non-organik'));
            })
            ->pluck('kegiatan_id');

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
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'metode_pendataan_pencacahan', 'metode_pendataan_listing', 'metode_pelatihan', 'has_listing_updating')
            ->where(function ($q) {
                // Hide kegiatan where both pulsa columns would be locked:
                // - pelatihan locked: metode_pelatihan NOT IN ['daring', 'hybrid']
                // - pendataan locked: metode_pendataan_pencacahan = 'PAPI'
                // Show if at least one column is available.
                $q->whereIn('metode_pelatihan', ['daring', 'hybrid'])
                    ->orWhere('metode_pendataan_pencacahan', 'CAPI')
                    ->orWhereNull('metode_pelatihan'); // legacy: show until metode_pelatihan is set
            })
            ->orderBy('kode_kegiatan')
            ->get();

        $kegiatanIds = $eligibleKegiatan->pluck('id');

        /**
         * Admin/operator: tampilkan semua petugas yang pernah dialokasikan ke kegiatan
         * tersebut dalam tahun aktif (tidak dibatasi bulan tertentu).
         * Ketua tim: hanya petugas yang ada alokasi di bulan yang dipilih.
         *
         * @var \Illuminate\Support\Collection<int, \App\Models\AlokasiPetugas> $allocations
         */
        $allocations = AlokasiPetugas::query()
            ->whereHas('periodeAlokasi', function ($q) use ($bulan, $tahun, $kegiatanIds, $isAdminOrOperator) {
                $q->where('tahun', $tahun)
                    ->whereIn('kegiatan_id', $kegiatanIds);
                if (! $isAdminOrOperator) {
                    $q->where('bulan', $bulan);
                }
            })
            ->whereNotIn('peran', self::PENGOLAHAN_ROLES)
            ->whereHas('petugas', function ($q) {
                $q->where('jenis_petugas', 'non-organik');
            })
            ->with([
                'petugas:id,nama,jenis_petugas',
                'periodeAlokasi:id,kegiatan_id,bulan,tahun',
            ])
            ->get();

        $petugasPerKegiatan = $allocations
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

        $existingSubmissions = PengajuanPulsa::query()
            ->whereIn('kegiatan_id', $kegiatanIds)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotIn('status', ['ditolak'])
            ->get(['kegiatan_id', 'petugas_id', 'jenis_pulsa', 'nominal']);

        $existingTotals = $existingSubmissions
            ->groupBy('petugas_id')
            ->map(fn ($rows) => $rows->sum('nominal'));

        /**
         * Key format: "${kegiatan_id}_${petugas_id}_${jenis_pulsa}" → nominal
         * Used on frontend to lock cells that already have a non-rejected submission.
         *
         * @var array<string, float>
         */
        $existingPerKegiatan = $existingSubmissions
            ->mapWithKeys(fn ($row) => [
                "{$row->kegiatan_id}_{$row->petugas_id}_{$row->jenis_pulsa}" => (float) $row->nominal,
            ]);

        return Inertia::render('PengajuanPulsa/Create', [
            'eligibleKegiatan' => $eligibleKegiatan,
            'petugasPerKegiatan' => $petugasPerKegiatan,
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
            ->get(['id', 'metode_pendataan_pencacahan', 'metode_pelatihan']);

        $validKegiatanById = $validKegiatanModels->keyBy('id');
        $validKegiatan = $validKegiatanModels->pluck('id');

        $invalidKegiatan = $kegiatanIds->diff($validKegiatan);

        if ($invalidKegiatan->isNotEmpty()) {
            return back()->withErrors([
                'items' => 'Beberapa kegiatan tidak ditemukan atau bukan kegiatan Anda.',
            ]);
        }

        // Validate per-item: pulsa pelatihan only allowed for daring/hybrid kegiatan
        foreach ($items as $item) {
            if ($item['jenis_pulsa'] === 'pelatihan') {
                /** @var \App\Models\Kegiatan|null $kegiatan */
                $kegiatan = $validKegiatanById->get($item['kegiatan_id']);
                $metodePelatihan = $kegiatan?->metode_pelatihan;
                if (! in_array($metodePelatihan, ['daring', 'hybrid'])) {
                    return back()->withErrors([
                        'items' => 'Pulsa pelatihan hanya dapat diajukan untuk kegiatan dengan metode pelatihan daring atau hybrid.',
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

        DB::transaction(function () use ($items, $bulan, $tahun, $periodeAlokasiMap, $validated, $effectiveUser) {
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
            }
        });

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
     * Review (approve or reject) a pengajuan pulsa.
     */
    public function review(Request $request, PengajuanPulsa $pengajuanPulsa): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:diterima,ditolak'],
            'catatan_penolakan' => ['required_if:action,ditolak', 'nullable', 'string', 'max:500'],
        ]);

        $pengajuanPulsa->update([
            'status' => $validated['action'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'catatan_penolakan' => $validated['catatan_penolakan'] ?? null,
        ]);

        $message = $validated['action'] === 'diterima'
            ? 'Pengajuan pulsa berhasil diterima.'
            : 'Pengajuan pulsa ditolak.';

        return back()->with('success', $message);
    }
}
