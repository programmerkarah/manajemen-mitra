<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\PeriodeAlokasi;
use App\Models\RateHonor;
use App\Models\Satuan;
use App\Models\User;
use App\Services\ActiveYearService;
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
        $activeYear = ActiveYearService::get();
        $query = Kegiatan::query()
            ->with('ketuaTim')
            ->where('tahun_anggaran', $activeYear);

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

        // Filter by Ketua Tim for ketua_tim role
        if ($request->user()->isKetuaTim()) {
            $query->where('ketua_tim_user_id', $request->user()->id);
        }

        $kegiatans = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Kegiatan/Index', [
            'kegiatans' => $kegiatans,
            'filters' => $request->only(['search', 'status']),
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
            'periodeAlokasi.alokasiPetugas.petugas',
        ]);

        // Flatten periodeAlokasi->alokasiPetugas for backward compatibility
        $alokasi = $kegiatan->periodeAlokasi->flatMap(function ($periode) {
            return $periode->alokasiPetugas->map(function ($alok) use ($periode) {
                $alok->bulan = (int) $periode->bulan;
                $alok->tahun = $periode->tahun;
                $alok->jenis_kegiatan = $periode->jenis_kegiatan;
                $alok->status = $periode->status;

                return $alok;
            });
        });

        $kegiatan->alokasi = $alokasi;
        unset($kegiatan->periodeAlokasi);

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

        // Transform pagu_anggaran to anggaran (database column name)
        if (isset($data['pagu_anggaran'])) {
            $data['anggaran'] = $data['pagu_anggaran'];
            unset($data['pagu_anggaran']);
        }

        // Ketua Tim can validate kegiatan (check before updating)
        if ($request->user()->isKetuaTim() && $request->filled('validate')) {
            $data['status'] = 'divalidasi';
            $data['tanggal_validasi'] = now();
        }

        // Check if tanggal kegiatan is being changed
        $oldTanggalMulai = $kegiatan->tanggal_mulai;
        $oldTanggalSelesai = $kegiatan->tanggal_selesai;
        $newTanggalMulai = isset($data['tanggal_mulai']) ? \Carbon\Carbon::parse($data['tanggal_mulai']) : $oldTanggalMulai;
        $newTanggalSelesai = isset($data['tanggal_selesai']) ? \Carbon\Carbon::parse($data['tanggal_selesai']) : $oldTanggalSelesai;
        $tanggalChanged = $oldTanggalMulai != $newTanggalMulai || $oldTanggalSelesai != $newTanggalSelesai;

        if ($tanggalChanged) {
            // Load existing periode alokasi
            $existingPeriodes = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();

            // Check if any periode is outside the new date range
            $invalidPeriodes = [];
            foreach ($existingPeriodes as $periode) {
                // Create date from periode (first day of the month)
                $periodeTanggal = \Carbon\Carbon::createFromDate($periode->tahun, (int) $periode->bulan, 1);

                // Compare with start and end of month ranges (use copy to avoid mutating original)
                $rangeStart = $newTanggalMulai->copy()->startOfMonth();
                $rangeEnd = $newTanggalSelesai->copy()->endOfMonth();

                if ($periodeTanggal->lt($rangeStart) || $periodeTanggal->gt($rangeEnd)) {
                    // Get month name in Indonesian
                    $bulanInt = (int) $periode->bulan;
                    $namabulan = \Carbon\Carbon::create()->month($bulanInt)->translatedFormat('F');

                    $invalidPeriodes[] = sprintf('%s %d', $namabulan, $periode->tahun);
                }
            }

            if (! empty($invalidPeriodes)) {
                return back()->withErrors([
                    'tanggal_mulai' => sprintf(
                        'Tidak dapat mengubah tanggal kegiatan karena terdapat periode alokasi di luar rentang tanggal baru: %s. Hapus atau ubah periode tersebut terlebih dahulu.',
                        implode(', ', $invalidPeriodes)
                    ),
                ])->withInput();
            }
        }

        // Check if anggaran (pagu) is being changed
        $oldPagu = (float) ($kegiatan->anggaran ?? 0);
        $newPagu = (float) ($data['anggaran'] ?? 0);
        $paguChanged = $oldPagu != $newPagu;

        if ($paguChanged) {
            // Load all alokasi for this kegiatan
            $kegiatan->load(['periodeAlokasi' => function ($query) {
                $query->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                    ->with('alokasiPetugas');
            }]);

            // Calculate total honor already allocated
            $totalHonorAlokasi = $kegiatan->periodeAlokasi->sum(function ($periode) {
                return $periode->alokasiPetugas->sum('total_honor');
            });

            // Check if new pagu is smaller than total allocated honor
            if ($newPagu < $totalHonorAlokasi) {

                return back()->withErrors([
                    'pagu_anggaran' => sprintf(
                        'Pagu anggaran tidak boleh lebih kecil dari total honor yang sudah dialokasikan (Rp %s). Total honor saat ini: Rp %s',
                        number_format($newPagu, 0, ',', '.'),
                        number_format($totalHonorAlokasi, 0, ',', '.')
                    ),
                ])->withInput();
            }
        }

        // Update kegiatan with all validated data
        $kegiatan->update($data);

        // If pagu changed, recalculate all periode sisa_pagu
        if ($paguChanged) {
            // Recalculate sisa_pagu for all periods sequentially (January to December)
            $periodes = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
                ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->with('alokasiPetugas')
                ->get();

            $currentSisaPagu = $newPagu;

            foreach ($periodes as $periode) {
                $periodeTotalHonor = $periode->alokasiPetugas->sum('total_honor');
                $currentSisaPagu = $currentSisaPagu - $periodeTotalHonor;

                $periode->update(['sisa_pagu' => $currentSisaPagu]);
            }

            return redirect()->route('kegiatan.index')
                ->with('success', 'Kegiatan dan sisa pagu periode berhasil diperbarui.');
        }

        // Normal update (no pagu change)

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
