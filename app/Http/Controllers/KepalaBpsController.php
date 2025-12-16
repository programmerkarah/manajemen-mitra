<?php

namespace App\Http\Controllers;

use App\Models\KepalaBps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KepalaBpsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = KepalaBps::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'aktif';
            $query->where('is_active', $isActive);
        }

        $kepalaBpsList = $query->orderBy('is_active', 'desc')
            ->orderBy('periode_mulai', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('KepalaBps/Index', [
            'kepalaBpsList' => $kepalaBpsList,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('KepalaBps/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'jabatan' => 'required|string|max:255',
            'periode_mulai' => 'nullable|date',
            'periode_selesai' => 'nullable|date|after_or_equal:periode_mulai',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama Kepala BPS wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
        ]);

        KepalaBps::create($validated);

        return redirect()->route('kepala-bps.index')
            ->with('success', 'Kepala BPS berhasil ditambahkan.');
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
    public function edit(KepalaBps $kepalaBp): Response
    {
        return Inertia::render('KepalaBps/Edit', [
            'kepalaBps' => $kepalaBp,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KepalaBps $kepalaBp): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'jabatan' => 'required|string|max:255',
            'periode_mulai' => 'nullable|date',
            'periode_selesai' => 'nullable|date|after_or_equal:periode_mulai',
            'is_active' => 'boolean',
        ], [
            'nama.required' => 'Nama Kepala BPS wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
        ]);

        $kepalaBp->update($validated);

        return redirect()->route('kepala-bps.index')
            ->with('success', 'Kepala BPS berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KepalaBps $kepalaBp): RedirectResponse
    {
        $kepalaBp->delete();

        return redirect()->route('kepala-bps.index')
            ->with('success', 'Kepala BPS berhasil dihapus.');
    }
}
