<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlokasiMitraRequest;
use App\Http\Requests\UpdateAlokasiMitraRequest;
use App\Models\AlokasiMitra;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\RateHonor;
use App\Models\Sbml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class AlokasiMitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Kegiatan::query()
            ->with(['penanggungJawab'])
            ->withCount('alokasi');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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
            $query->where('tahun_anggaran', $request->tahun);
        }

        // Filter for Ketua Tim - only their kegiatan
        if ($request->user()->isKetuaTim()) {
            $query->where('ketua_tim_user_id', $request->user()->id);
        }

        $kegiatans = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Alokasi/Index', [
            'kegiatans' => $kegiatans,
            'filters' => $request->only(['search', 'status', 'tahun']),
        ]);
    }

    /**
     * Show the form for managing mitra for a kegiatan.
     */
    public function manage(Kegiatan $kegiatan): Response
    {
        $kegiatan->load(['penanggungJawab', 'rateHonor.satuan', 'alokasi.mitra']);

        $mitras = Mitra::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email')
            ->get();

        return Inertia::render('Alokasi/Manage', [
            'kegiatan' => $kegiatan,
            'mitras' => $mitras,
        ]);
    }

    /**
     * Store multiple alokasi for a kegiatan.
     */
    public function storeMultiple(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Validate that kegiatan has rate honor
        if (! $kegiatan->rate_honor_id) {
            return back()->withErrors([
                'rate_honor' => 'Kegiatan ini belum memiliki rate honor. Silakan set rate honor pada kegiatan terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.mitra_id' => 'required|exists:mitra,id',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|integer|min:1',
            'alokasi.*.jenis_kegiatan' => 'required|in:sensus,survei',
            'alokasi.*.catatan' => 'nullable|string',
        ]);

        $rateHonor = $kegiatan->rateHonor;
        $created = 0;
        $errors = [];

        foreach ($validated['alokasi'] as $index => $alokasiData) {
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

            AlokasiMitra::create([
                'kegiatan_id' => $kegiatan->id,
                'mitra_id' => $alokasiData['mitra_id'],
                'bulan' => $alokasiData['bulan'],
                'tahun' => $alokasiData['tahun'],
                'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                'total_honor' => $totalHonor,
                'peran' => $rateHonor->posisi,
                'jenis_kegiatan' => $alokasiData['jenis_kegiatan'],
                'status_kepegawaian' => $rateHonor->status_kepegawaian,
                'catatan' => $alokasiData['catatan'] ?? null,
                'submitted_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $created++;
        }

        if (count($errors) > 0) {
            return back()->withErrors(['sbml_constraint' => $errors])
                ->with('warning', "{$created} alokasi berhasil ditambahkan. ".count($errors).' alokasi ditolak karena melebihi batas SBML.');
        }

        return redirect()->route('alokasi.manage', $kegiatan)
            ->with('success', "{$created} alokasi mitra berhasil ditambahkan.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->select('id', 'hashed_id', 'kode_kegiatan', 'nama_kegiatan')
            ->get();

        $mitras = Mitra::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        // Handle pre-selected kegiatan from query string
        $selectedKegiatan = null;
        if ($request->filled('kegiatan_id')) {
            try {
                $decodedId = Hashids::decode($request->kegiatan_id)[0] ?? null;
                if ($decodedId) {
                    $selectedKegiatan = Kegiatan::where('id', $decodedId)
                        ->select('id', 'kode_kegiatan', 'nama_kegiatan')
                        ->first();
                }
            } catch (\Exception $e) {
                // Invalid hashed_id, just ignore
            }
        }

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => $kegiatans,
            'mitras' => $mitras,
            'rateHonors' => $rateHonors,
            'selectedKegiatan' => $selectedKegiatan,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlokasiMitraRequest $request): RedirectResponse
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

        AlokasiMitra::create($data);

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi mitra berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AlokasiMitra $alokasi): Response
    {
        $alokasi->load([
            'kegiatan.penanggungJawab',
            'kegiatan.rateHonor.satuan',
            'mitra',
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
    public function edit(AlokasiMitra $alokasi): Response
    {
        $kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
            ->select('id', 'kode_kegiatan', 'nama_kegiatan')
            ->get();

        $mitras = Mitra::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        return Inertia::render('Alokasi/Edit', [
            'alokasi' => $alokasi,
            'kegiatans' => $kegiatans,
            'mitras' => $mitras,
            'rateHonors' => $rateHonors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlokasiMitraRequest $request, AlokasiMitra $alokasi): RedirectResponse
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
            ->with('success', 'Alokasi mitra berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AlokasiMitra $alokasi): RedirectResponse
    {
        $alokasi->delete();

        return redirect()->route('alokasi.index')
            ->with('success', 'Alokasi mitra berhasil dihapus.');
    }

    /**
     * Submit alokasi for approval.
     */
    public function submit(Request $request, AlokasiMitra $alokasi): RedirectResponse
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
    public function approve(Request $request, AlokasiMitra $alokasi): RedirectResponse
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
    public function reject(Request $request, AlokasiMitra $alokasi): RedirectResponse
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
    public function approvePj(Request $request, AlokasiMitra $alokasi): RedirectResponse
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
