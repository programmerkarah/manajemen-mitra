<?php

namespace App\Http\Controllers;

use App\Exports\PetugasTemplateExport;
use App\Http\Requests\StorePetugasRequest;
use App\Http\Requests\UpdatePetugasRequest;
use App\Imports\PetugasImport;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PetugasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Petugas::query();

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

        // Filter by jenis petugas
        if ($request->filled('jenis_petugas')) {
            $query->where('jenis_petugas', $request->jenis_petugas);
        }

        $petugas = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Petugas/Index', [
            'petugas' => $petugas,
            'filters' => $request->only(['search', 'status', 'tahun', 'jenis_petugas']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Petugas/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePetugasRequest $request): RedirectResponse
    {
        Petugas::create($request->validated());

        return redirect()->route('petugas.index')
            ->with('success', 'Petugas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Petugas $petugas): Response
    {
        $petugas->load(['alokasi.periodeAlokasi.kegiatan']);

        // Transform alokasi to include bulan, tahun, jenis_kegiatan from periode
        $petugas->alokasi->each(function ($alok) {
            $periode = $alok->periodeAlokasi;
            $alok->bulan = (int) $periode->bulan;
            $alok->tahun = $periode->tahun;
            $alok->jenis_kegiatan = $periode->jenis_kegiatan;
            $alok->status = $periode->status;
            $alok->kegiatan = $periode->kegiatan;
            unset($alok->periodeAlokasi);
        });

        return Inertia::render('Petugas/Show', [
            'petugas' => $petugas,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Petugas $petugas): Response
    {
        return Inertia::render('Petugas/Edit', [
            'petugas' => $petugas,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePetugasRequest $request, Petugas $petugas): RedirectResponse
    {
        $petugas->update($request->validated());

        return redirect()->route('petugas.index')
            ->with('success', 'Petugas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Petugas $petugas): RedirectResponse
    {
        $petugas->delete();

        return redirect()->route('petugas.index')
            ->with('success', 'Petugas berhasil dihapus.');
    }

    /**
     * Download template Excel untuk import petugas.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new PetugasTemplateExport, 'template_petugas.xlsx');
    }

    /**
     * Import petugas dari file Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        // Debug: log request data
        Log::info('Import request received', [
            'has_file' => $request->hasFile('file'),
            'file_info' => $request->hasFile('file') ? [
                'name' => $request->file('file')->getClientOriginalName(),
                'size' => $request->file('file')->getSize(),
                'mime' => $request->file('file')->getMimeType(),
            ] : null,
        ]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ], [
            'file.required' => 'File wajib diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ]);

        try {
            $import = new PetugasImport;
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                return redirect()->route('petugas.index')
                    ->with('warning', "Import selesai. {$successCount} data berhasil diimport. ".count($errors).' data gagal: '.implode(', ', array_slice($errors, 0, 3)));
            }

            return redirect()->route('petugas.index')
                ->with('success', "Import berhasil! {$successCount} petugas telah ditambahkan.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return redirect()->route('petugas.index')
                ->with('error', 'Validasi gagal: '.implode(' | ', array_slice($errorMessages, 0, 5)));
        } catch (\Exception $e) {
            return redirect()->route('petugas.index')
                ->with('error', 'Gagal mengimport data: '.$e->getMessage());
        }
    }
}
