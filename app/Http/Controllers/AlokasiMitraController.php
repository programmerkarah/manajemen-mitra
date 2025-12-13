<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlokasiMitraRequest;
use App\Http\Requests\UpdateAlokasiMitraRequest;
use App\Models\AlokasiMitra;
use App\Models\Kegiatan;
use App\Models\Mitra;
use App\Models\RateHonor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        // Filter for PJ - only their kegiatan
        if ($request->user()->isPJ()) {
            $query->where('pj_user_id', $request->user()->id);
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
        $kegiatan->load(['penanggungJawab', 'alokasi.mitra', 'alokasi.rateHonor.satuan']);

        $mitras = Mitra::where('status', 'aktif')
            ->select('id', 'nama', 'nik', 'email')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        return Inertia::render('Alokasi/Manage', [
            'kegiatan' => $kegiatan,
            'mitras' => $mitras,
            'rateHonors' => $rateHonors,
        ]);
    }

    /**
     * Store multiple alokasi for a kegiatan.
     */
    public function storeMultiple(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validate([
            'alokasi' => 'required|array|min:1',
            'alokasi.*.mitra_id' => 'required|exists:mitras,id',
            'alokasi.*.rate_honor_id' => 'required|exists:rate_honors,id',
            'alokasi.*.bulan' => 'required|integer|min:1|max:12',
            'alokasi.*.tahun' => 'required|integer|min:2020|max:2099',
            'alokasi.*.jumlah_satuan' => 'required|integer|min:1',
            'alokasi.*.catatan' => 'nullable|string',
        ]);

        $created = 0;
        foreach ($validated['alokasi'] as $alokasiData) {
            $rateHonor = RateHonor::findOrFail($alokasiData['rate_honor_id']);

            AlokasiMitra::create([
                'kegiatan_id' => $kegiatan->id,
                'mitra_id' => $alokasiData['mitra_id'],
                'rate_honor_id' => $alokasiData['rate_honor_id'],
                'bulan' => $alokasiData['bulan'],
                'tahun' => $alokasiData['tahun'],
                'jumlah_satuan' => $alokasiData['jumlah_satuan'],
                'total_honor' => $rateHonor->honor_satuan * $alokasiData['jumlah_satuan'],
                'catatan' => $alokasiData['catatan'] ?? null,
                'submitted_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $created++;
        }

        return redirect()->route('alokasi.manage', $kegiatan)
            ->with('success', "{$created} alokasi mitra berhasil ditambahkan.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
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

        return Inertia::render('Alokasi/Create', [
            'kegiatans' => $kegiatans,
            'mitras' => $mitras,
            'rateHonors' => $rateHonors,
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
        $data['total_honor'] = $rateHonor->rate * $data['jumlah_satuan'];
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
            'mitra',
            'rateHonor.satuan',
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
        $data['total_honor'] = $rateHonor->rate * $data['jumlah_satuan'];

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
        if (! $request->user()->isApprover()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui alokasi.');
        }

        if ($alokasi->status !== 'diajukan') {
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
        if (! $request->user()->isApprover()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak alokasi.');
        }

        if ($alokasi->status !== 'diajukan') {
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
}
