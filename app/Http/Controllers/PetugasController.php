<?php

namespace App\Http\Controllers;

use App\Exports\PetugasTemplateExport;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StorePetugasRequest;
use App\Http\Requests\UpdatePetugasRequest;
use App\Imports\PetugasImport;
use App\Models\ActivityLog;
use App\Models\Petugas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

class PetugasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $query = Petugas::query()->select('petugas.*');

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

        // Filter by jenis petugas - ignore if 'all'
        if (! empty($validated['jenis_petugas']) && $validated['jenis_petugas'] !== 'all') {
            $query->where('jenis_petugas', $validated['jenis_petugas']);
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
    public function show(string $petuga): Response
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::findOrFail($id);
        $petugas->load(['alokasi.periodeAlokasi.kegiatan.rateHonors.satuan']);

        // Transform alokasi to include bulan, tahun, jenis_kegiatan from periode
        $petugas->alokasi->each(function ($alok) {
            $periode = $alok->periodeAlokasi;
            $alok->bulan = (int) $periode->bulan;
            $alok->tahun = $periode->tahun;
            $alok->jenis_kegiatan = $periode->jenis_kegiatan;
            $alok->status = $periode->status;
            $alok->kegiatan = $periode->kegiatan;

            // Find the appropriate rate_honor based on petugas type and peran
            $rateHonor = $periode->kegiatan->rateHonors->first(function ($rate) use ($alok) {
                return $rate->status_kepegawaian === $alok->status_kepegawaian
                    && $rate->jenis_penugasan === $alok->peran;
            });

            if ($rateHonor) {
                $alok->rate_honor = [
                    'posisi' => $this->getPositionLabel($alok->peran),
                    'rate' => $alok->jumlah_satuan > 0 ? $alok->total_honor / $alok->jumlah_satuan : 0,
                    'satuan' => [
                        'nama' => $rateHonor->satuan->nama ?? '-',
                    ],
                ];
            } else {
                // Fallback if rate honor not found
                $alok->rate_honor = [
                    'posisi' => $this->getPositionLabel($alok->peran),
                    'rate' => $alok->jumlah_satuan > 0 ? $alok->total_honor / $alok->jumlah_satuan : 0,
                    'satuan' => ['nama' => '-'],
                ];
            }

            unset($alok->periodeAlokasi);
        });

        return Inertia::render('Petugas/Show', [
            'petugas' => $petugas,
        ]);
    }

    /**
     * Get position label from jenis_penugasan
     */
    private function getPositionLabel(string $jenisPenugasan): string
    {
        return match ($jenisPenugasan) {
            'pcl_ppl' => 'PCL/PPL',
            'pml' => 'PML',
            'pengolahan' => 'Pengolahan',
            'pengawas_pengolahan' => 'Pengawas Pengolahan',
            default => $jenisPenugasan,
        };
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $petuga): Response
    {
        $id = Hashids::decode($petuga)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $petugas = Petugas::findOrFail($id);

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

        $petugas = Petugas::findOrFail($id);
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

        $petugas = Petugas::findOrFail($id);
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
            $errors = $import->getErrors();

            if (count($errors) > 0) {
                try {
                    ActivityLog::log(
                        'Import Mitra',
                        'mitra',
                        "Import mitra selesai dengan peringatan: {$successCount} berhasil, ".count($errors).' gagal',
                        'warning',
                        ['success_count' => $successCount, 'error_count' => count($errors)]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
                }

                return redirect()->route('petugas.index')
                    ->with('warning', "Import selesai. {$successCount} data berhasil diimport. ".count($errors).' data gagal: '.implode(', ', array_slice($errors, 0, 3)));
            }

            try {
                ActivityLog::log(
                    'Import Mitra',
                    'mitra',
                    "Berhasil import {$successCount} data mitra",
                    'success',
                    ['success_count' => $successCount]
                );
            } catch (\Exception $e) {
                Log::warning('Failed to log activity', ['error' => $e->getMessage()]);
            }

            return redirect()->route('petugas.index')
                ->with('success', "Import berhasil! {$successCount} petugas telah ditambahkan.");
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
}
