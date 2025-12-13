<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KegiatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Kegiatan::query()->with('ketuaTim');

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

        // Filter by Ketua Tim for ketua_tim role
        if ($request->user()->isKetuaTim()) {
            $query->where('ketua_tim_user_id', $request->user()->id);
        }

        $kegiatans = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Kegiatan/Index', [
            'kegiatans' => $kegiatans,
            'filters' => $request->only(['search', 'status', 'tahun']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $ketuaTimUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        $rateHonors = RateHonor::with('satuan')
            ->where('status', 'aktif')
            ->where('tahun_berlaku', now()->year)
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        return Inertia::render('Kegiatan/Create', [
            'ketuaTimUsers' => $ketuaTimUsers,
            'rateHonors' => $rateHonors,
            'tahunOptions' => $tahunOptions,
        ]);
    }

    /**
     * Generate kode kegiatan otomatis.
     */
    private function generateKodeKegiatan(int $tahunAnggaran): string
    {
        // Format: KEG-{TAHUN}-{NOMOR_URUT}
        $prefix = "KEG-{$tahunAnggaran}-";

        // Get last kegiatan number for this year
        $lastKegiatan = Kegiatan::where('kode_kegiatan', 'like', $prefix.'%')
            ->orderBy('kode_kegiatan', 'desc')
            ->first();

        if ($lastKegiatan) {
            // Extract number from last code
            $lastNumber = (int) str_replace($prefix, '', $lastKegiatan->kode_kegiatan);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKegiatanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // If ketua_tim creates kegiatan, automatically assign themselves as ketua_tim
        if ($request->user()->isKetuaTim()) {
            $data['ketua_tim_user_id'] = $request->user()->id;
        }

        // Generate kode kegiatan otomatis
        $data['kode_kegiatan'] = $this->generateKodeKegiatan($data['tahun_anggaran']);

        if (! isset($data['status'])) {
            $data['status'] = 'draft';
        }

        Kegiatan::create($data);

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Kegiatan $kegiatan): Response
    {
        // Ketua Tim can only view their own kegiatan
        if ($request->user()->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk melihat kegiatan ini.');
        }

        $kegiatan->load([
            'ketuaTim',
            'rateHonors.satuan',
            'alokasi.petugas',
        ]);

        return Inertia::render('Kegiatan/Show', [
            'kegiatan' => $kegiatan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Kegiatan $kegiatan): Response
    {
        // Ketua Tim can only edit their own kegiatan
        if ($request->user()->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit kegiatan ini.');
        }
        $ketuaTimUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'ketua_tim'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        return Inertia::render('Kegiatan/Edit', [
            'kegiatan' => $kegiatan,
            'ketuaTimUsers' => $ketuaTimUsers,
            'tahunOptions' => $tahunOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Ketua Tim can only update their own kegiatan
        if ($request->user()->isKetuaTim() && $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengupdate kegiatan ini.');
        }

        $data = $request->validated();

        // Ketua Tim can validate kegiatan
        if ($request->user()->isKetuaTim() && $request->filled('validate')) {
            $data['status'] = 'divalidasi';
            $data['tanggal_validasi'] = now();
        }

        $kegiatan->update($data);

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Only admin can delete kegiatan
        if (! $request->user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus kegiatan ini.');
        }
        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    /**
     * Show form to manage rate honors for a kegiatan
     */
    public function manageRateHonor(Kegiatan $kegiatan): Response
    {
        // Load existing rate honors for this kegiatan
        $kegiatan->load(['rateHonors' => function ($query) {
            $query->orderBy('status_kepegawaian')
                ->orderBy('jenis_penugasan');
        }]);

        // Get all available satuan
        $satuans = Satuan::where('status', 'aktif')->get();

        return Inertia::render('Kegiatan/ManageRateHonor', [
            'kegiatan' => $kegiatan,
            'satuans' => $satuans,
        ]);
    }

    /**
     * Bulk update rate honors for a kegiatan
     */
    public function bulkUpdateRateHonor(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $request->validate([
            'rate_honors' => ['required', 'array'],
            'rate_honors.*.status_kepegawaian' => ['required', 'in:organik,non_organik'],
            'rate_honors.*.jenis_penugasan' => ['required', 'in:pcl_ppl,pml,pengolahan,pengawas_pengolahan'],
            'rate_honors.*.rate' => ['required', 'numeric', 'min:0'],
            'rate_honors.*.satuan_id' => ['required', 'exists:satuan,id'],
        ]);

        // Delete existing rate honors for this kegiatan
        RateHonor::where('kegiatan_id', $kegiatan->id)->delete();

        // Create new rate honors
        foreach ($request->rate_honors as $rateHonorData) {
            // Generate posisi label
            $statusLabel = $rateHonorData['status_kepegawaian'] === 'organik'
                ? 'Organik (PNS/PPPK)'
                : 'Non-Organik';

            $penugasanLabels = [
                'pcl_ppl' => 'PCL/PPL',
                'pml' => 'PML',
                'pengolahan' => 'Pengolahan',
                'pengawas_pengolahan' => 'Pengawas Pengolahan',
            ];
            $penugasanLabel = $penugasanLabels[$rateHonorData['jenis_penugasan']] ?? $rateHonorData['jenis_penugasan'];

            RateHonor::create([
                'kegiatan_id' => $kegiatan->id,
                'jenis_kegiatan' => $kegiatan->jenis_kegiatan,
                'posisi' => "{$kegiatan->nama_kegiatan} - {$statusLabel} - {$penugasanLabel}",
                'jenis_penugasan' => $rateHonorData['jenis_penugasan'],
                'status_kepegawaian' => $rateHonorData['status_kepegawaian'],
                'deskripsi' => "Rate honor untuk kegiatan {$kegiatan->kode_kegiatan}",
                'rate' => $rateHonorData['rate'],
                'satuan_id' => $rateHonorData['satuan_id'],
                'tahun_berlaku' => $kegiatan->tahun_anggaran,
                'status' => 'aktif',
            ]);
        }

        return redirect()->route('kegiatan.show', $kegiatan->hashed_id)
            ->with('success', 'Rate honor berhasil disimpan.');
    }
}
