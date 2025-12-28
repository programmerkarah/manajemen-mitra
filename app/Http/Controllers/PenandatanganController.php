<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\Penandatangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenandatanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $query = Penandatangan::query();

        // Search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Filter by jenis
        if (! empty($validated['jenis'])) {
            $query->where('jenis_penandatangan', $validated['jenis']);
        }

        // Filter by status
        if (! empty($validated['status'])) {
            $isActive = $validated['status'] === 'aktif';
            $query->where('is_active', $isActive);
        }

        $penandatanganList = $query->orderBy('is_active', 'desc')
            ->orderBy('periode_mulai', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Encrypt sensitive data
        $penandatanganData = $penandatanganList->items();
        $encryptedData = encryptData($penandatanganData);

        return Inertia::render('Penandatangan/Index', [
            'PenandatanganList' => [
                'encrypted' => $encryptedData,
                'meta' => [
                    'current_page' => $penandatanganList->currentPage(),
                    'last_page' => $penandatanganList->lastPage(),
                    'per_page' => $penandatanganList->perPage(),
                    'total' => $penandatanganList->total(),
                    'from' => $penandatanganList->firstItem(),
                    'to' => $penandatanganList->lastItem(),
                ],
                'links' => $penandatanganList->linkCollection()->toArray(),
            ],
            'filters' => [
                'encrypted' => encryptFilters($validated),
                'decrypted' => $validated,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Penandatangan/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'jenis_penandatangan' => 'required|in:kepala,ppk',
            'jabatan' => 'required|string|max:255',
            'periode_mulai' => 'nullable|date',
            'periode_selesai' => 'nullable|date|after_or_equal:periode_mulai',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama penandatangan wajib diisi.',
            'jenis_penandatangan.required' => 'Jenis penandatangan wajib dipilih.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
        ]);

        Penandatangan::create($validated);

        return redirect()->route('penandatangan.index')
            ->with('success', 'Penandatangan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Penandatangan $penandatangan): Response
    {
        return Inertia::render('Penandatangan/Edit', [
            'Penandatangan' => $penandatangan,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Penandatangan $penandatangan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'jenis_penandatangan' => 'required|in:kepala,ppk',
            'jabatan' => 'required|string|max:255',
            'periode_mulai' => 'nullable|date',
            'periode_selesai' => 'nullable|date|after_or_equal:periode_mulai',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama penandatangan wajib diisi.',
            'jenis_penandatangan.required' => 'Jenis penandatangan wajib dipilih.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
        ]);

        $penandatangan->update($validated);

        return redirect()->route('penandatangan.index')
            ->with('success', 'Penandatangan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Penandatangan $penandatangan): RedirectResponse
    {
        $penandatangan->delete();

        return redirect()->route('penandatangan.index')
            ->with('success', 'Penandatangan berhasil dihapus.');
    }
}
