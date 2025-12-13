<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\Kegiatan;
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
        $query = Kegiatan::query()->with('penanggungJawab');

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

        // Filter by PJ for PJ role
        if ($request->user()->isPJ()) {
            $query->where('pj_user_id', $request->user()->id);
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
        $pjUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'pj'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        return Inertia::render('Kegiatan/Create', [
            'pjUsers' => $pjUsers,
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
    public function show(Kegiatan $kegiatan): Response
    {
        $kegiatan->load([
            'penanggungJawab',
            'alokasi.mitra',
            'alokasi.rateHonor.satuan',
        ]);

        return Inertia::render('Kegiatan/Show', [
            'kegiatan' => $kegiatan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kegiatan $kegiatan): Response
    {
        $pjUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'pj'))
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get();

        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear - 2, $currentYear + 5);

        return Inertia::render('Kegiatan/Edit', [
            'kegiatan' => $kegiatan,
            'pjUsers' => $pjUsers,
            'tahunOptions' => $tahunOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        $data = $request->validated();

        // PJ can validate kegiatan
        if ($request->user()->isPj() && $request->filled('validate')) {
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
    public function destroy(Kegiatan $kegiatan): RedirectResponse
    {
        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }
}
