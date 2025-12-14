<?php

namespace App\Http\Controllers;

use App\Models\Sbml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SbmlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $sbmls = Sbml::orderBy('tahun_anggaran', 'desc')
            ->orderBy('jenis_kegiatan', 'asc')
            ->orderBy('status_kepegawaian', 'asc')
            ->orderBy('jenis_penugasan', 'asc')
            ->paginate(20);

        return Inertia::render('Sbml/Index', [
            'sbmls' => $sbmls,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Sbml/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'entries' => ['required', 'array', 'size:15'],
            'entries.*.jenis_kegiatan' => ['required', 'in:sensus,survei'],
            'entries.*.status_kepegawaian' => ['required', 'in:organik,non_organik'],
            'entries.*.jenis_penugasan' => ['required', 'in:pcl_ppl,pml,pengolahan,pengawas_pengolahan'],
            'entries.*.honor_max' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        // Check if year already exists
        $exists = Sbml::where('tahun_anggaran', $validated['tahun_anggaran'])->exists();

        if ($exists) {
            return back()->withErrors([
                'tahun_anggaran' => 'SBML untuk tahun '.$validated['tahun_anggaran'].' sudah ada.',
            ])->withInput();
        }

        // Create all 12 entries
        foreach ($validated['entries'] as $entry) {
            Sbml::create([
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'jenis_kegiatan' => $entry['jenis_kegiatan'],
                'status_kepegawaian' => $entry['status_kepegawaian'],
                'jenis_penugasan' => $entry['jenis_penugasan'],
                'honor_max' => $entry['honor_max'],
                'keterangan' => $validated['keterangan'],
                'status' => $validated['status'],
            ]);
        }

        return redirect()->route('sbml.index')
            ->with('success', 'SBML untuk tahun '.$validated['tahun_anggaran'].' berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $tahun): Response
    {
        // Get all SBML entries for the specified year
        $sbmlEntries = Sbml::where('tahun_anggaran', $tahun)
            ->orderByRaw("FIELD(jenis_kegiatan, 'survei', 'sensus')")
            ->orderByRaw("FIELD(status_kepegawaian, 'non_organik', 'organik')")
            ->orderByRaw("FIELD(jenis_penugasan, 'pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan')")
            ->get();

        if ($sbmlEntries->isEmpty()) {
            abort(404, 'SBML untuk tahun '.$tahun.' tidak ditemukan.');
        }

        // Get status and keterangan from the first entry (same for all entries in a year)
        $firstEntry = $sbmlEntries->first();

        return Inertia::render('Sbml/Show', [
            'tahun' => $tahun,
            'sbmlEntries' => $sbmlEntries,
            'status' => $firstEntry->status,
            'keterangan' => $firstEntry->keterangan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $tahun): Response
    {
        // Get all SBML entries for the same year
        $entries = Sbml::where('tahun_anggaran', $tahun)
            ->orderByRaw("FIELD(jenis_kegiatan, 'survei', 'sensus')")
            ->orderByRaw("FIELD(status_kepegawaian, 'non_organik', 'organik')")
            ->orderByRaw("FIELD(jenis_penugasan, 'pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan')")
            ->get();

        if ($entries->isEmpty()) {
            abort(404, 'SBML untuk tahun '.$tahun.' tidak ditemukan.');
        }

        // Get first entry for common data
        $firstEntry = $entries->first();

        return Inertia::render('Sbml/Edit', [
            'entries' => $entries->map(function ($e) {
                return [
                    'id' => $e->id,
                    'hashed_id' => $e->hashed_id,
                    'jenis_kegiatan' => $e->jenis_kegiatan,
                    'status_kepegawaian' => $e->status_kepegawaian,
                    'jenis_penugasan' => $e->jenis_penugasan,
                    'honor_max' => $e->honor_max,
                ];
            }),
            'tahun' => $tahun,
            'status' => $firstEntry->status,
            'keterangan' => $firstEntry->keterangan,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $tahun): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['required', 'array', 'size:15'],
            'entries.*.id' => ['required', 'exists:sbml,id'],
            'entries.*.honor_max' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        // Update all entries for this year
        foreach ($validated['entries'] as $entryData) {
            $entry = Sbml::find($entryData['id']);
            $entry->update([
                'honor_max' => $entryData['honor_max'],
                'keterangan' => $validated['keterangan'],
                'status' => $validated['status'],
            ]);
        }

        return redirect()->route('sbml.index')
            ->with('success', 'SBML untuk tahun '.$tahun.' berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $tahun): RedirectResponse
    {
        $deleted = Sbml::where('tahun_anggaran', $tahun)->delete();

        if ($deleted === 0) {
            return redirect()->route('sbml.index')
                ->with('error', 'SBML untuk tahun '.$tahun.' tidak ditemukan.');
        }

        return redirect()->route('sbml.index')
            ->with('success', 'SBML untuk tahun '.$tahun.' berhasil dihapus.');
    }

    /**
     * Remove all SBML entries for a specific year
     */
    public function destroyYear(int $tahun): RedirectResponse
    {
        $deleted = Sbml::where('tahun_anggaran', $tahun)->delete();

        if ($deleted === 0) {
            return redirect()->route('sbml.index')
                ->with('error', 'SBML untuk tahun '.$tahun.' tidak ditemukan.');
        }

        return redirect()->route('sbml.index')
            ->with('success', 'SBML untuk tahun '.$tahun.' berhasil dihapus.');
    }
}
