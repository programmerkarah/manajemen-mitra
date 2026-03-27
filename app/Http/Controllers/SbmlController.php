<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Sbml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SbmlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        // Get grouped count by year with status
        $yearGroups = Sbml::selectRaw('tahun_anggaran, MAX(status) as status, COUNT(*) as count')
            ->groupBy('tahun_anggaran')
            ->orderBy('tahun_anggaran', 'desc')
            ->get()
            ->map(function ($group) {
                return [
                    'tahun_anggaran' => (int) $group->tahun_anggaran,
                    'status' => $group->status,
                    'count' => (int) $group->count,
                ];
            });

        return Inertia::render('Sbml/Index', [
            'year_groups' => $yearGroups,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $currentYear = (int) date('Y');

        // Get unique years from SBML data
        $tahunOptions = Sbml::select('tahun_anggaran')
            ->distinct()
            ->orderBy('tahun_anggaran', 'desc')
            ->pluck('tahun_anggaran')
            ->map(fn ($t) => (int) $t)
            ->toArray();

        // Always include current year and next year (for preparation)
        if (! in_array($currentYear, $tahunOptions)) {
            $tahunOptions[] = $currentYear;
        }
        if (! in_array($currentYear + 1, $tahunOptions)) {
            $tahunOptions[] = $currentYear + 1;
        }

        // Sort descending
        rsort($tahunOptions);

        return Inertia::render('Sbml/Create', [
            'tahun_options' => $tahunOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'entries' => ['required', 'array', 'size:18'],
            'entries.*.jenis_kegiatan' => ['required', 'in:sensus,survei'],
            'entries.*.status_kepegawaian' => ['required', 'in:organik,non_organik'],
            'entries.*.jenis_penugasan' => ['required', 'in:pcl_ppl,pml,pengolahan,pengawas_pengolahan,koseka'],
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

        ActivityLog::log(
            'Tambah SBML',
            'sbml',
            "Berhasil menambahkan SBML untuk tahun {$validated['tahun_anggaran']}",
            'success',
            ['tahun_anggaran' => $validated['tahun_anggaran'], 'total_entries' => count($validated['entries'])]
        );

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
            'entries' => ['required', 'array', 'size:18'],
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

        ActivityLog::log(
            'Ubah SBML',
            'sbml',
            "Berhasil mengubah data SBML untuk tahun {$tahun}",
            'success',
            ['tahun_anggaran' => $tahun, 'total_entries' => count($validated['entries'])]
        );

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

        ActivityLog::log(
            'Hapus SBML',
            'sbml',
            "Berhasil menghapus SBML untuk tahun {$tahun} ({$deleted} entries)",
            'success',
            ['tahun_anggaran' => $tahun, 'deleted_count' => $deleted]
        );

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

        ActivityLog::log(
            'Hapus SBML Tahun',
            'sbml',
            "Berhasil menghapus semua data SBML untuk tahun {$tahun} ({$deleted} entries)",
            'success',
            ['tahun_anggaran' => $tahun, 'deleted_count' => $deleted]
        );

        return redirect()->route('sbml.index')
            ->with('success', 'SBML untuk tahun '.$tahun.' berhasil dihapus.');
    }

    /**
     * Download SBML template for import
     */
    public function exportTemplate(int $tahun, string $type = 'create'): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SbmlTemplateExport($tahun, $type),
            "SBML-{$tahun}-template-{$type}.xlsx"
        );
    }

    /**
     * Import SBML data from Excel
     */
    public function import(Request $request, int $tahun): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'file.required' => 'File harus diupload',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV',
        ]);

        try {
            $import = new \App\Imports\SbmlImport($tahun);
            \Maatwebsite\Excel\Facades\Excel::import($import, $validated['file']);

            ActivityLog::log(
                'Import SBML',
                'sbml',
                "Berhasil mengimport SBML untuk tahun {$tahun} ({$import->getSuccessCount()} entries)",
                'success',
                ['tahun_anggaran' => $tahun, 'imported_count' => $import->getSuccessCount()]
            );

            return redirect()->route('sbml.show', $tahun)
                ->with('success', "Berhasil mengimport {$import->getSuccessCount()} data SBML");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessage = 'Gagal mengimport file. Error pada baris: ';
            foreach ($failures as $failure) {
                $errorMessage .= $failure->row().', ';
            }

            return back()->withErrors(['file' => rtrim($errorMessage, ', ')]);
        } catch (\Exception $e) {
            Log::error('SBML Import Error', ['error' => $e->getMessage()]);

            return back()->withErrors(['file' => 'Gagal mengimport file: '.$e->getMessage()]);
        }
    }
}
