<?php

namespace App\Http\Controllers;

use App\Exports\PetugasExistingExport;
use App\Exports\PetugasTemplateExport;
use App\Http\Requests\BatchUpdatePetugasRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StorePetugasRequest;
use App\Http\Requests\UpdatePetugasRequest;
use App\Imports\PetugasImport;
use App\Imports\PetugasPreviewImport;
use App\Models\ActivityLog;
use App\Models\Petugas;
use App\Services\PetugasImportProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;
use App\Http\Controllers\Concerns\PetugasDetailSupport;

class PetugasController extends Controller
{
    use PetugasDetailSupport;
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $query = Petugas::query()
            ->select('petugas.*');

        // Search
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status - ignore if 'all'
        if (! empty($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        // Filter by tahun bergabung
        if (! empty($validated['tahun'])) {
            $query->where('tahun_bergabung', (int) $validated['tahun']);
        }

        // Load ALL data for client-side filtering, sorting, and pagination
        $petugas = $query->latest()->get();

        // Encrypt sensitive data
        $encryptedData = encryptData($petugas);
        $totalData = $petugas->count();

        return Inertia::render('Petugas/Index', [
            'petugas' => [
                'encrypted' => $encryptedData,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $totalData,
                    'total' => $totalData,
                    'from' => $totalData > 0 ? 1 : 0,
                    'to' => $totalData,
                ],
                'links' => [],
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
        return Inertia::render('Petugas/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePetugasRequest $request): RedirectResponse
    {

        try {
            // Log 2: Validated data
            $validated = $request->validated();

            $petugas = Petugas::create($validated);

            // Activity Log
            try {
                ActivityLog::log(
                    'Tambah Mitra',
                    'mitra',
                    "Berhasil menambahkan mitra baru: {$petugas->nama} (NIK: {$petugas->nik})",
                    'success',
                    ['petugas_id' => $petugas->id, 'nama' => $petugas->nama, 'nik' => $petugas->nik]
                );
                Log::info('✅ [PETUGAS STORE] Activity log recorded');
            } catch (\Exception $e) {
                Log::warning('⚠️ [PETUGAS STORE] Failed to log activity', ['error' => $e->getMessage()]);
            }

            return redirect()->route('petugas.index')
                ->with([
                    'success' => 'Data petugas baru sudah berhasil disimpan ke sistem.',
                ]);

        } catch (QueryException $e) {
            // Activity Log for error
            try {
                ActivityLog::log(
                    'Tambah Mitra - ERROR',
                    'mitra',
                    'GAGAL menambahkan mitra: Database error - '.$e->getMessage(),
                    'error',
                    ['request_data' => $request->all(), 'error' => $e->getMessage()]
                );
            } catch (\Exception $logError) {
                Log::error('Failed to log database error to activity log', ['error' => $logError->getMessage()]);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan data: '.$e->getMessage()]);

        } catch (\Exception $e) {

            // Activity Log for error
            try {
                ActivityLog::log(
                    'Tambah Mitra - ERROR',
                    'mitra',
                    'GAGAL menambahkan mitra: '.$e->getMessage(),
                    'error',
                    ['request_data' => $request->all(), 'error' => $e->getMessage()]
                );
            } catch (\Exception $logError) {
                Log::error('Failed to log error to activity log', ['error' => $logError->getMessage()]);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */


    /**
     * Get position label from jenis_penugasan
     */


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $petuga): Response
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->findOrFail($id);

        $data = $petugas->toEditArray();

        return Inertia::render('Petugas/Edit', [
            'petugas' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePetugasRequest $request, string $petuga): RedirectResponse
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->findOrFail($id);
        $petugas->update($request->validated());

        try {
            ActivityLog::log(
                'Ubah Data Mitra',
                'mitra',
                "Berhasil mengubah data mitra: {$petugas->nama} (NIK: {$petugas->nik})",
                'success',
                ['petugas_id' => $petugas->id, 'nama' => $petugas->nama]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
        }

        return redirect()->route('petugas.index')
            ->with('success', 'Perubahan data petugas sudah berhasil disimpan.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $petuga): RedirectResponse
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::query()
            ->where('jenis_petugas', 'non-organik')
            ->findOrFail($id);
        $petugasNama = $petugas->nama;
        $petugasNik = $petugas->nik;
        $petugasId = $petugas->id;
        $petugas->delete();

        try {
            ActivityLog::log(
                'Hapus Mitra',
                'mitra',
                "Berhasil menghapus data mitra: {$petugasNama} (NIK: {$petugasNik})",
                'success',
                ['petugas_id' => $petugasId, 'nama' => $petugasNama, 'nik' => $petugasNik]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
        }

        return redirect()->route('petugas.index')
            ->with('success', 'Data petugas sudah berhasil dihapus dari sistem.');
    }

    /**
     * Download template Excel untuk import petugas.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new PetugasTemplateExport, 'template_petugas.xlsx');
    }

    /**
     * Download existing petugas data for collective updates.
     */
    public function downloadExisting(): BinaryFileResponse
    {
        return Excel::download(new PetugasExistingExport, 'data_existing_petugas.xlsx');
    }

    /**
     * Preview petugas import data from Excel without persisting to database.
     */
    public function importPreview(Request $request, PetugasImportProcessor $processor): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berupa Excel (.xlsx, .xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ]);

        $import = new PetugasPreviewImport;
        Excel::import($import, $validated['file']);

        $result = $processor->process($import->rows(), persist: false);

        return response()->json([
            'rows' => $result['rows'],
            'errors' => $result['errors'],
            'summary' => [
                'total_rows' => $result['total_rows'],
                'success_count' => $result['success_count'],
                'created_count' => $result['created_count'],
                'updated_count' => $result['updated_count'],
                'skipped_count' => $result['skipped_count'],
            ],
        ]);
    }

    /**
     * Import petugas dari file Excel.
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
            $import = new PetugasImport;
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $createdCount = $import->getCreatedCount();
            $updatedCount = $import->getUpdatedCount();
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                try {
                    ActivityLog::log(
                        'Import Mitra',
                        'mitra',
                        "Import mitra selesai dengan peringatan: {$createdCount} baru, {$updatedCount} diperbarui, ".count($errors).' gagal',
                        'warning',
                        ['success_count' => $successCount, 'created_count' => $createdCount, 'updated_count' => $updatedCount, 'error_count' => count($errors)]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
                }

                return redirect()->route('petugas.index')
                    ->with('warning', "Import selesai. {$createdCount} data baru, {$updatedCount} data diperbarui, ".count($errors).' data gagal: '.implode(', ', array_slice($errors, 0, 3)));
            }

            try {
                ActivityLog::log(
                    'Import Mitra',
                    'mitra',
                    "Berhasil import mitra: {$createdCount} data baru, {$updatedCount} data diperbarui",
                    'success',
                    ['success_count' => $successCount, 'created_count' => $createdCount, 'updated_count' => $updatedCount]
                );
            } catch (\Exception $e) {
                Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
            }

            return redirect()->route('petugas.index')
                ->with('success', "Import berhasil! {$createdCount} petugas ditambahkan dan {$updatedCount} petugas diperbarui.");
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return redirect()->route('petugas.index')
                ->with('error', 'Validasi gagal: '.implode(' | ', array_slice($errorMessages, 0, 5)));
        } catch (\Exception $e) {
            try {
                ActivityLog::logError(
                    'Import Mitra',
                    'mitra',
                    'Gagal import mitra: '.$e->getMessage(),
                    ['error' => $e->getMessage()]
                );
            } catch (\Exception $logErr) {
                Log::warning('Failed to log error', ['error' => $logErr->getMessage()]);
            }

            return redirect()->route('petugas.index')
                ->with('error', 'Gagal mengimport data: '.$e->getMessage());
        }
    }

    /**
     * Batch update multiple petugas.
     */
    public function batchUpdate(BatchUpdatePetugasRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $updated = 0;

        foreach ($validated['petugas'] as $item) {
            $id = Hashids::decode($item['id'])[0] ?? null;
            if (! $id) {
                continue;
            }

            $petugas = Petugas::query()
                ->where('jenis_petugas', 'non-organik')
                ->find($id);
            if (! $petugas) {
                continue;
            }

            $petugas->update([
                'nama' => $item['nama'],
                'telepon' => $item['telepon'],
                'pendidikan' => $item['pendidikan'],
                'jenis_kelamin' => $item['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $item['tanggal_lahir'] ?? null,
                'kecamatan' => $item['kecamatan'] ?? null,
                'desa_kelurahan' => $item['desa_kelurahan'] ?? null,
                'alamat' => $item['alamat'],
            ]);
            $updated++;
        }

        try {
            ActivityLog::log(
                'Batch Edit Mitra',
                'mitra',
                "Berhasil mengubah {$updated} data mitra secara batch.",
                'success',
                ['count' => $updated]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
        }

        return redirect()->route('petugas.index')
            ->with('success', "Berhasil memperbarui {$updated} data petugas.");
    }
}
