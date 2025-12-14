<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KegiatanController extends Controller
{
    use AuthorizesRequests;

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
        // Authorization via policy
        $this->authorize('view', $kegiatan);

        $kegiatan->load([
            'ketuaTim',
            'rateHonors.satuan',
            'alokasi.petugas',
        ]);

        return Inertia::render('Kegiatan/Show', [
            'kegiatan' => $kegiatan,
            'can' => [
                'update' => $request->user()->can('update', $kegiatan),
                'approve' => $request->user()->can('approve', $kegiatan),
                'reject' => $request->user()->can('reject', $kegiatan),
                'delete' => $request->user()->can('delete', $kegiatan),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Kegiatan $kegiatan): Response
    {
        // Authorization via policy
        $this->authorize('update', $kegiatan);

        // Additional check: only allow editing draft or divalidasi status
        if (! in_array($kegiatan->status, ['draft', 'divalidasi'])) {
            abort(403, 'Kegiatan hanya bisa diedit jika statusnya draft atau divalidasi.');
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
        // Authorization via policy
        $this->authorize('update', $kegiatan);

        // Additional check: only allow updating draft or divalidasi status
        if (! in_array($kegiatan->status, ['draft', 'divalidasi'])) {
            return back()->with('error', 'Kegiatan hanya bisa diedit jika statusnya draft atau divalidasi.');
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
        // Authorization via policy
        $this->authorize('delete', $kegiatan);

        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    /**
     * Show form to manage rate honors for a kegiatan
     */
    public function manageRateHonor(Kegiatan $kegiatan): Response|RedirectResponse
    {
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
        }

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
        // Check if kegiatan is approved
        if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
            return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
        }
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

    /**
     * Approve kegiatan (change status from draft/diajukan to divalidasi)
     */
    public function approve(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization check via policy
        $this->authorize('approve', $kegiatan);

        // Validate that kegiatan is in correct status
        if (! in_array($kegiatan->status, ['draft', 'diajukan'])) {
            return redirect()->back()
                ->with('error', 'Kegiatan dengan status '.$kegiatan->status.' tidak dapat disetujui.');
        }

        // Update status to divalidasi
        $kegiatan->update([
            'status' => 'divalidasi',
            'tanggal_validasi' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Kegiatan berhasil disetujui dan divalidasi.');
    }

    /**
     * Reject kegiatan (change status back to draft with notes)
     */
    public function reject(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Authorization check via policy
        $this->authorize('reject', $kegiatan);

        // Validate request
        $request->validate([
            'catatan' => 'required|string|max:1000',
        ]);

        // Validate that kegiatan is in correct status
        if (! in_array($kegiatan->status, ['draft', 'diajukan'])) {
            return redirect()->back()
                ->with('error', 'Kegiatan dengan status '.$kegiatan->status.' tidak dapat ditolak.');
        }

        // Update status back to draft
        $kegiatan->update([
            'status' => 'draft',
            'catatan' => $request->catatan,
        ]);

        return redirect()->back()
            ->with('success', 'Kegiatan ditolak dan dikembalikan ke status draft.');
    }

    /**
     * Submit kegiatan for approval (change status from draft to diajukan)
     */
    public function submit(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Only the ketua tim or admin/operator can submit
        if (! in_array($request->user()->active_role, ['admin', 'operator']) &&
            $kegiatan->ketua_tim_user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengajukan kegiatan ini.');
        }

        // Validate that kegiatan is draft
        if ($kegiatan->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Hanya kegiatan dengan status draft yang dapat diajukan.');
        }

        // Update status to diajukan
        $kegiatan->update([
            'status' => 'diajukan',
        ]);

        return redirect()->back()
            ->with('success', 'Kegiatan berhasil diajukan untuk persetujuan.');
    }
}
