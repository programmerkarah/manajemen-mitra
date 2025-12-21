<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\Dipa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DipaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $query = Dipa::query();

        // Search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nomor_dipa', 'like', "%{$search}%")
                    ->orWhere('tahun', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (! empty($validated['status'])) {
            $isActive = $validated['status'] === 'aktif';
            $query->where('is_active', $isActive);
        }

        // Filter by tahun
        if (! empty($validated['tahun'])) {
            $query->where('tahun', (int) $validated['tahun']);
        }

        $dipaList = $query->orderBy('tahun', 'desc')
            ->orderBy('nomor_dipa', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Get unique years for filter
        $tahunOptions = Dipa::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        return Inertia::render('Dipa/Index', [
            'dipaList' => $dipaList,
            'tahunOptions' => $tahunOptions,
            'filters' => $request->only(['search', 'status', 'tahun']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear + 5, $currentYear - 2);

        return Inertia::render('Dipa/Create', [
            'tahunOptions' => $tahunOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_dipa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dipas', 'nomor_dipa')
                    ->where('tahun', $request->tahun)
                    ->whereNull('deleted_at'),
            ],
            'tahun' => 'required|integer|min:2000|max:2100',
            'tanggal_dipa' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'nomor_dipa.required' => 'Nomor DIPA wajib diisi.',
            'nomor_dipa.unique' => 'Nomor DIPA untuk tahun ini sudah ada.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tanggal_dipa.required' => 'Tanggal DIPA wajib diisi.',
        ]);

        Dipa::create($validated);

        return redirect()->route('dipa.index')
            ->with('success', 'DIPA berhasil ditambahkan.');
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
    public function edit(Dipa $dipa): Response
    {
        // Generate tahun options (current year - 2 to current year + 5)
        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear + 5, $currentYear - 2);

        return Inertia::render('Dipa/Edit', [
            'dipa' => $dipa,
            'tahunOptions' => $tahunOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dipa $dipa): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_dipa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dipas', 'nomor_dipa')
                    ->where('tahun', $request->tahun)
                    ->ignore($dipa->id)
                    ->whereNull('deleted_at'),
            ],
            'tahun' => 'required|integer|min:2000|max:2100',
            'tanggal_dipa' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'nomor_dipa.required' => 'Nomor DIPA wajib diisi.',
            'nomor_dipa.unique' => 'Nomor DIPA untuk tahun ini sudah ada.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tanggal_dipa.required' => 'Tanggal DIPA wajib diisi.',
        ]);

        $dipa->update($validated);

        return redirect()->route('dipa.index')
            ->with('success', 'DIPA berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dipa $dipa): RedirectResponse
    {
        $dipa->delete();

        return redirect()->route('dipa.index')
            ->with('success', 'DIPA berhasil dihapus.');
    }
}
