<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlokasiPetugasRequest;
use App\Http\Requests\UpdateAlokasiPetugasRequest;
use App\Models\AlokasiPetugas;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\Petugas;
use App\Models\RateHonor;
use App\Models\Sbml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class AlokasiPetugasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = PeriodeAlokasi::query()
            ->with(['kegiatan:id,kode_kegiatan,nama_kegiatan,ketua_tim_user_id', 'alokasiPetugas'])
            ->withCount('alokasiPetugas as jumlah_petugas');

        // Search by kegiatan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('kegiatan', function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kode_kegiatan', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tahun
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter by bulan
        if ($request->filled('bulan')) {
            $query->where('bulan', $request->bulan);
        }

        // Filter for Ketua Tim - only their kegiatan
        if ($request->user()->isKetuaTim()) {
            $query->whereHas('kegiatan', function ($q) use ($request) {
                $q->where('ketua_tim_user_id', $request->user()->id);
            });
        }

        $alokasi = $query->latest('created_at')->paginate(15)->withQueryString();

        // Transform the result to include necessary data
        $alokasi->getCollection()->transform(function ($periode) {
            return [
                'kegiatan_id' => $periode->kegiatan_id,
                'bulan' => str_pad($periode->bulan, 2, '0', STR_PAD_LEFT),
                'tahun' => $periode->tahun,
                'jenis_kegiatan' => $periode->jenis_kegiatan,
                'status' => $periode->status,
                'jumlah_petugas' => $periode->jumlah_petugas,
                'total_honor' => $periode->total_honor,
                'latest_created_at' => $periode->created_at,
                'kegiatan' => [
                    'id' => $periode->kegiatan->id,
                    'hashed_id' => $periode->kegiatan->hashed_id,
                    'nama_kegiatan' => $periode->kegiatan->nama_kegiatan,
                    'kode_kegiatan' => $periode->kegiatan->kode_kegiatan,
                ],
            ];
        });

        return Inertia::render('Alokasi/Index', [
            'alokasi' => $alokasi,
            'filters' => $request->only(['search', 'status', 'tahun', 'bulan']),
        ]);
    }

    /**
     * Show the form for managing mitra for a kegiatan.
     */
    public function manage(Request $request, Kegiatan $kegiatan): Response|RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim can only manage alokasi for their own kegiatan
        if ($request->user()->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola alokasi kegiatan ini.');
        }

        $kegiatan->load(['ketuaTim', 'rateHonors.satuan', 'alokasi.petugas']);

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas')
            ->get();

        return Inertia::render('Alokasi/Manage', [
            'kegiatan' => $kegiatan,
            'petugas' => $petugas,
        ]);
    }

    /**
     * Store multiple alokasi for a kegiatan.
     */
    public function storeMultiple(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Alokasi petugas hanya bisa ditambahkan untuk kegiatan yang sudah divalidasi.');
        }

        // Ketua Tim can only add alokasi for their own kegiatan
        if ($request->user()->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk menambahkan alokasi pada kegiatan ini.');
        }
        // Validate that kegiatan has rate honors
        if ($kegiatan->rateHonors()->count() === 0) {
            return back()->withErrors([
                'rate_honor' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.petugas_id' => 'required|exists:petugas,id',
            'alokasi.*.peran' => 'required|string|in:PCL,PML,Pengolahan,Pengawas Pengolahan',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|integer|min:1',
            'alokasi.*.jenis_kegiatan' => 'required|in:sensus,survei',
            'alokasi.*.catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        $created = 0;
        $errors = [];

        // Group by periode (bulan+tahun+jenis_kegiatan) to create PeriodeAlokasi first
        $periodeGroups = [];

        foreach ($validated['alokasi'] as $index => $alokasiData) {
            // Get petugas to determine jenis_petugas
            $petugas = Petugas::find($alokasiData['petugas_id']);
            if (! $petugas) {
                $errors[] = 'Alokasi #'.($index + 1).': Petugas tidak ditemukan.';

                continue;
            }

            // Map peran to jenis_penugasan
            $jenisPenugasan = match ($alokasiData['peran']) {
                'PCL' => 'pcl_ppl',
                'PML' => 'pml',
                'Pengolahan' => 'pengolahan',
                'Pengawas Pengolahan' => 'pengawas_pengolahan',
                default => null,
            };

            if (! $jenisPenugasan) {
                $errors[] = 'Alokasi #'.($index + 1).': Peran tidak valid.';

                continue;
            }

            // Find matching rate honor based on petugas type, jenis_kegiatan, and jenis_penugasan
            $statusKepegawaian = $petugas->jenis_petugas === 'organik' ? 'organik' : 'non_organik';
            $rateHonor = $kegiatan->rateHonors()
                ->where('status_kepegawaian', $statusKepegawaian)
                ->where('jenis_kegiatan', $alokasiData['jenis_kegiatan'])
                ->where('jenis_penugasan', $jenisPenugasan)
                ->where('status', 'aktif')
                ->where('tahun_berlaku', $alokasiData['tahun'])
                ->first();

            if (! $rateHonor) {
                $errors[] = 'Alokasi #'.($index + 1).': Rate honor untuk '.$alokasiData['peran'].' ('.$statusKepegawaian.', '.$alokasiData['jenis_kegiatan'].') tidak ditemukan.';

                continue;
            }

            $totalHonor = $rateHonor->rate * $alokasiData['jumlah_satuan'];

            // Check SBML constraint
            $constraintError = $this->checkSbmlConstraint(
                $alokasiData['tahun'],
                $alokasiData['jenis_kegiatan'],
                $rateHonor->status_kepegawaian,
                $rateHonor->jenis_penugasan,
                $totalHonor
            );

            if ($constraintError) {
                $errors[] = 'Alokasi #'.($index + 1).': '.$constraintError;

                continue;
            }

            // Store data grouped by periode
            $periodeKey = $alokasiData['bulan'].'_'.$alokasiData['tahun'].'_'.$alokasiData['jenis_kegiatan'];
            if (! isset($periodeGroups[$periodeKey])) {
                $periodeGroups[$periodeKey] = [
                    'bulan' => str_pad($alokasiData['bulan'], 2, '0', STR_PAD_LEFT),
                    'tahun' => $alokasiData['tahun'],
                    'jenis_kegiatan' => $alokasiData['jenis_kegiatan'],
                    'alokasi' => [],
                ];
            }

            $periodeGroups[$periodeKey]['alokasi'][] = [
                'petugas_id' => $alokasiData['petugas_id'],
                'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                'total_honor' => $totalHonor,
                'peran' => $jenisPenugasan,
                'status_kepegawaian' => $rateHonor->status_kepegawaian,
                'catatan' => $alokasiData['catatan'] ?? null,
            ];

            $created++;
        }

        // Create PeriodeAlokasi and AlokasiPetugas
        foreach ($periodeGroups as $periodeData) {
            // Use firstOrCreate with deleted_at null to handle soft-deleted records
            $periode = PeriodeAlokasi::withTrashed()
                ->where('kegiatan_id', $kegiatan->id)
                ->where('bulan', $periodeData['bulan'])
                ->where('tahun', $periodeData['tahun'])
                ->first();

            if ($periode && $periode->trashed()) {
                // Restore soft-deleted periode
                $periode->restore();
                $periode->update([
                    'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                    'status' => 'draft',
                ]);
            } elseif (! $periode) {
                // Create new periode
                $periode = PeriodeAlokasi::create([
                    'kegiatan_id' => $kegiatan->id,
                    'bulan' => $periodeData['bulan'],
                    'tahun' => $periodeData['tahun'],
                    'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
                    'status' => 'draft',
                ]);
            }

            // Create AlokasiPetugas for this periode
            foreach ($periodeData['alokasi'] as $alokasiItem) {
                AlokasiPetugas::create([
                    'periode_alokasi_id' => $periode->id,
                    'petugas_id' => $alokasiItem['petugas_id'],
                    'jumlah_satuan' => $alokasiItem['jumlah_satuan'],
                    'total_honor' => $alokasiItem['total_honor'],
                    'peran' => $alokasiItem['peran'],
                    'status_kepegawaian' => $alokasiItem['status_kepegawaian'],
                    'catatan' => $alokasiItem['catatan'],
                ]);
            }
        }

        DB::commit();

        if (count($errors) > 0) {
            return back()->withErrors(['sbml_constraint' => $errors])
                ->with('warning', "{$created} alokasi berhasil ditambahkan. ".count($errors).' alokasi ditolak karena melebihi batas SBML.');
        }

        return redirect()->route('alokasi.manage', $kegiatan)
            ->with('success', "{$created} alokasi petugas berhasil ditambahkan.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $activeYear = \App\Services\ActiveYearService::get();

        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->with([
                'rateHonors' => function ($query) use ($activeYear) {
                    $query->where('status', 'aktif')
                        ->where('tahun_berlaku', $activeYear)
                        ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'satuan_id')
                        ->with('satuan:id,kode,nama');
                },
            ])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'jenis_kegiatan')
            ->orderBy('created_at', 'desc')
            ->get();

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas')
            ->get();

        // Handle pre-selected kegiatan from query string
        $selectedKegiatan = null;
        if ($request->filled('kegiatan_id')) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $selectedKegiatan = Kegiatan::where('id', $decodedId)
                        ->whereIn('status', ['divalidasi', 'aktif'])
                        ->with([
                            'rateHonors' => function ($query) use ($activeYear) {
                                $query->where('status', 'aktif')
                                    ->where('tahun_berlaku', $activeYear)
                                    ->select('id', 'kegiatan_id', 'posisi', 'jenis_kegiatan', 'status_kepegawaian', 'jenis_penugasan', 'rate', 'satuan_id')
                                    ->with('satuan:id,kode,nama');
                            },
                        ])
                        ->select('id', 'kode_kegiatan', 'nama_kegiatan', 'jenis_kegiatan')
                        ->first();
                }
            } catch (\Exception $e) {
                // Invalid hashed_id, just ignore
            }
        }

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'selectedKegiatan' => $selectedKegiatan,
            'active_year' => $activeYear,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlokasiPetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Calculate total honor
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $totalHonor = $rateHonor->rate * $data['jumlah_satuan'];

        // Check SBML constraint
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $rateHonor->status_kepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor
        );

        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        $data['total_honor'] = $totalHonor;
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;
        $data['submitted_by'] = $request->user()->id;

        AlokasiPetugas::create($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AlokasiPetugas $alokasi): Response
    {
        $alokasi->load([
            'kegiatan.ketuaTim',
            'kegiatan.rateHonor.satuan',
            'petugas',
            'submittedBy',
            'approvedBy',
        ]);

        return Inertia::render('Alokasi/Show', [
            'alokasi' => $alokasi,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AlokasiPetugas $alokasi): Response
    {
        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan')
            ->get();

        $petugas = Petugas::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email', 'jenis_petugas')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        return Inertia::render('Alokasi/Edit', [
            'alokasi' => $alokasi,
            'kegiatans' => $kegiatans,
            'petugas' => $petugas,
            'rateHonors' => $rateHonors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlokasiPetugasRequest $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        $data = $request->validated();

        // Calculate total honor
        $rateHonor = RateHonor::findOrFail($data['rate_honor_id']);
        $totalHonor = $rateHonor->rate * $data['jumlah_satuan'];

        // Check SBML constraint
        $constraintError = $this->checkSbmlConstraint(
            $data['tahun'],
            $data['jenis_kegiatan'],
            $rateHonor->status_kepegawaian,
            $rateHonor->jenis_penugasan,
            $totalHonor
        );

        if ($constraintError) {
            return back()->withErrors(['sbml_constraint' => $constraintError])->withInput();
        }

        $data['total_honor'] = $totalHonor;
        $data['peran'] = $rateHonor->posisi;
        $data['status_kepegawaian'] = $rateHonor->status_kepegawaian;

        $alokasi->update($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlokasiPetugas $alokasi): RedirectResponse
    {
        $alokasi->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'alokasi petugas berhasil dihapus.');
    }

    /**
     * Submit alokasi for approval.
     */
    public function submit(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if ($alokasi->status !== 'draft') {
            return back()->with('error', 'Hanya alokasi dengan status draft yang dapat diajukan.');
        }

        $alokasi->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Alokasi berhasil diajukan untuk persetujuan.');
    }

    /**
     * Approve alokasi.
     */
    public function approve(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if (! $request->user()->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui.');
    }

    /**
     * Reject alokasi.
     */
    public function reject(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if (! $request->user()->hasActiveRole('approver')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak alokasi.');
        }

        if (! in_array($alokasi->status, ['diajukan', 'disetujui_pj'])) {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'required|string',
        ]);

        $alokasi->update([
            'status' => 'ditolak',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'],
        ]);

        return back()->with('success', 'Alokasi ditolak.');
    }

    /**
     * Approve alokasi by Ketua Tim.
     */
    public function approvePj(Request $request, AlokasiPetugas $alokasi): RedirectResponse
    {
        if (! $request->user()->hasActiveRole('ketua_tim')) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        // Check if user is the Ketua Tim of the kegiatan
        if ($alokasi->kegiatan->ketua_tim_user_id !== $request->user()->id) {
            return back()->with('error', 'Anda bukan ketua tim kegiatan ini.');
        }

        if ($alokasi->status !== 'diajukan') {
            return back()->with('error', 'Hanya alokasi yang diajukan yang dapat disetujui.');
        }

        $validated = $request->validate([
            'catatan_approval' => 'nullable|string',
        ]);

        $alokasi->update([
            'status' => 'disetujui_pj',
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Alokasi berhasil disetujui. Menunggu persetujuan final dari Approver.');
    }

    /**
     * Submit all alokasi in a periode (kegiatan + bulan)
     */
    public function submitPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status', 'draft')
            ->firstOrFail();

        $periode->update([
            'status' => 'diajukan',
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
        ]);

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dikirim untuk pembuatan SK KPA dan SPK.');
    }

    /**
     * Edit all alokasi in a periode
     */
    public function editPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): Response
    {
        // Load periode
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status', 'draft')
            ->with(['alokasiPetugas.petugas'])
            ->firstOrFail();

        if ($periode->alokasiPetugas->isEmpty()) {
            return redirect()->route('alokasi.index')
                ->with('error', 'Tidak ada alokasi untuk periode ini.');
        }

        // Load kegiatan with rate honors
        $kegiatanWithRates = Kegiatan::with(['rateHonors'])->findOrFail($kegiatan->id);

        // Load all petugas
        $petugas = Petugas::select('id', 'nama', 'jenis_petugas')
            ->orderBy('nama')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'status_kepegawaian' => $p->jenis_petugas,
                ];
            });

        return Inertia::render('Alokasi/EditPeriode', [
            'kegiatan' => $kegiatanWithRates,
            'existingAlokasi' => $periode->alokasiPetugas,
            'petugas' => $petugas,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    /**
     * Update alokasi periode
     */
    public function updatePeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        $validated = $request->validate([
            'alokasi' => 'required|array',
            'alokasi.*.id' => 'required|exists:alokasi_petugas,id',
            'alokasi.*.jumlah_satuan' => 'required|numeric|min:0',
            'alokasi.*.total_honor' => 'required|numeric|min:0',
            'alokasi.*.catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Find periode
            $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->where('status', 'draft')
                ->firstOrFail();

            foreach ($validated['alokasi'] as $alokasiData) {
                AlokasiPetugas::where('id', $alokasiData['id'])
                    ->where('periode_alokasi_id', $periode->id)
                    ->update([
                        'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                        'total_honor' => $alokasiData['total_honor'],
                        'catatan' => $alokasiData['catatan'] ?? null,
                    ]);
            }

            DB::commit();

            return redirect()->route('alokasi.index')
                ->with('success', 'Alokasi periode berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui alokasi: '.$e->getMessage());
        }
    }

    /**
     * Soft delete periode and all its alokasi
     */
    public function destroyPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        $periode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status', 'draft')
            ->first();

        if (! $periode) {
            return back()->with('error', 'Tidak ada alokasi draft yang dapat dibatalkan.');
        }

        // Soft delete will cascade to alokasi_petugas
        $periode->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi periode berhasil dibatalkan.');
    }

    /**
     * Revisi: Soft delete old periode and create new draft
     */
    public function revisiPeriode(Request $request, Kegiatan $kegiatan, int $tahun, string $bulan): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Get existing periode
            $oldPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->where('status', 'diajukan')
                ->with('alokasiPetugas')
                ->first();

            if (! $oldPeriode) {
                return back()->with('error', 'Tidak ada alokasi terkirim untuk direvisi.');
            }

            // Soft delete old periode (will cascade to alokasi_petugas)
            $oldPeriode->delete();

            // Create new draft periode
            $newPeriode = PeriodeAlokasi::create([
                'kegiatan_id' => $oldPeriode->kegiatan_id,
                'bulan' => $oldPeriode->bulan,
                'tahun' => $oldPeriode->tahun,
                'jenis_kegiatan' => $oldPeriode->jenis_kegiatan,
                'status' => 'draft',
            ]);

            // Create new draft alokasi from old data
            foreach ($oldPeriode->alokasiPetugas as $old) {
                AlokasiPetugas::create([
                    'periode_alokasi_id' => $newPeriode->id,
                    'petugas_id' => $old->petugas_id,
                    'jumlah_satuan' => $old->jumlah_satuan,
                    'total_honor' => $old->total_honor,
                    'peran' => $old->peran,
                    'status_kepegawaian' => $old->status_kepegawaian,
                    'catatan' => $old->catatan,
                ]);
            }

            DB::commit();

            // Redirect to edit page
            return redirect('/alokasi/periode/'.$kegiatan->hashed_id.'/'.$tahun.'/'.$bulan.'/edit')
                ->with('success', 'Revisi berhasil dibuat. Silakan edit data sesuai kebutuhan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membuat revisi: '.$e->getMessage());
        }
    }

    /**
     * Check if total honor exceeds SBML maximum constraint
     */
    private function checkSbmlConstraint(
        int $tahun,
        string $jenisKegiatan,
        string $statusKepegawaian,
        string $jenisPenugasan,
        float $totalHonor
    ): ?string {
        $sbml = Sbml::where('tahun_anggaran', $tahun)
            ->where('jenis_kegiatan', $jenisKegiatan)
            ->where('status_kepegawaian', $statusKepegawaian)
            ->where('jenis_penugasan', $jenisPenugasan)
            ->where('status', 'aktif')
            ->first();

        if (! $sbml) {
            return 'SBML untuk kombinasi ini belum tersedia. Silakan hubungi admin untuk mengatur SBML terlebih dahulu.';
        }

        if ($totalHonor > $sbml->honor_max) {
            return 'Total honor (Rp '.number_format($totalHonor, 0, ',', '.').') melebihi batas maksimal SBML (Rp '.number_format($sbml->honor_max, 0, ',', '.').") untuk tahun {$tahun}.";
        }

        return null;
    }
}
