<?php

namespace App\Http\Controllers;

use App\Exports\MitraTemplateExport;
use App\Http\Requests\StoreMitraRequest;
use App\Http\Requests\UpdateMitraRequest;
use App\Imports\MitraImport;
use App\Models\Mitra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MitraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Mitra::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tahun bergabung
        if ($request->filled('tahun')) {
            $query->where('tahun_bergabung', $request->tahun);
        }

        $mitras = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Mitra/Index', [
            'mitras' => $mitras,
            'filters' => $request->only(['search', 'status', 'tahun']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Mitra/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMitraRequest $request): RedirectResponse
    {
        Mitra::create($request->validated());

        return redirect()->route('mitra.index')
            ->with('success', 'Mitra berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitra $mitra): Response
    {
        $mitra->load(['alokasi.kegiatan', 'alokasi.rateHonor.satuan']);

        return Inertia::render('Mitra/Show', [
            'mitra' => $mitra,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitra $mitra): Response
    {
        return Inertia::render('Mitra/Edit', [
            'mitra' => $mitra,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMitraRequest $request, Mitra $mitra): RedirectResponse
    {
        $mitra->update($request->validated());

        return redirect()->route('mitra.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitra $mitra): RedirectResponse
    {
        $mitra->delete();

        return redirect()->route('mitra.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }

    /**
     * Download template Excel untuk import mitra.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new MitraTemplateExport, 'template_mitra.xlsx');
    }

    /**
     * Import mitra dari file Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ], [
            'file.required' => 'File wajib diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ]);

        try {
            $import = new MitraImport;
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                return redirect()->route('mitra.index')
                    ->with('warning', "Import selesai. {$successCount} data berhasil diimport. ".count($errors).' data gagal: '.implode(', ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('mitra.index')
                ->with('success', "Import berhasil! {$successCount} mitra telah ditambahkan.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return redirect()->route('mitra.index')
                ->with('error', 'Validasi gagal: '.implode(' | ', array_slice($errorMessages, 0, 5)));
        } catch (\Exception $e) {
            return redirect()->route('mitra.index')
                ->with('error', 'Gagal mengimport data: '.$e->getMessage());
        }
    }
}
