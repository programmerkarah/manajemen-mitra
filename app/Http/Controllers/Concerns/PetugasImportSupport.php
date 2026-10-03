<?php

namespace App\Http\Controllers\Concerns;

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

trait PetugasImportSupport
{
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new PetugasTemplateExport, 'template_petugas.xlsx');
    }

    public function downloadExisting(): BinaryFileResponse
    {
        return Excel::download(new PetugasExistingExport, 'data_existing_petugas.xlsx');
    }

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
