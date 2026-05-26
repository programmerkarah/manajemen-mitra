<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Models\ActivityLog;
use App\Models\DasarHukum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DasarHukumController extends Controller
{
    public function index(FilterRequest $request): Response
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? 'all';

        // Load ALL data for client-side filtering, sorting, and pagination
        $dasarHukum = DasarHukum::query()
            ->orderBy('tahun', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Encrypt sensitive data
        $encryptedData = encryptData($dasarHukum);
        $totalData = $dasarHukum->count();

        return Inertia::render('DasarHukum/Index', [
            'dasarHukum' => [
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

    public function create(): Response
    {
        return Inertia::render('DasarHukum/Create', [
            'kategoriOptions' => [
                ['value' => 'undang_undang', 'label' => 'Undang-Undang'],
                ['value' => 'peraturan_pemerintah', 'label' => 'Peraturan Pemerintah'],
                ['value' => 'peraturan_presiden', 'label' => 'Peraturan Presiden'],
                ['value' => 'peraturan_menteri_badan', 'label' => 'Peraturan Menteri/Badan'],
                ['value' => 'keputusan_menteri_kepala_badan', 'label' => 'Keputusan Menteri/Kepala Badan'],
                ['value' => 'peraturan_kepala_badan', 'label' => 'Peraturan Kepala Badan (BPS)'],
            ],
            'dasarHukumList' => DasarHukum::query()
                ->where('jenis', 'pertama')
                ->select(['id', 'kategori', 'instansi', 'nomor', 'tentang', 'tahun', 'nomor_ln', 'tahun_ln', 'nomor_tln', 'nomor_bn', 'tahun_bn'])
                ->withCount('perubahan')
                ->orderBy('kategori')
                ->orderBy('tahun')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|in:undang_undang,peraturan_pemerintah,peraturan_presiden,peraturan_menteri_badan,keputusan_menteri_kepala_badan,peraturan_kepala_badan',
            'instansi' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $kategoriDenganInstansi = ['peraturan_menteri_badan', 'keputusan_menteri_kepala_badan'];
                    if (in_array($request->kategori, $kategoriDenganInstansi) && empty($value)) {
                        $fail('Nama instansi wajib diisi untuk kategori ini.');
                    }
                },
            ],
            'nomor' => 'required|string|max:255',
            'tentang' => 'required|string',
            'tahun' => 'required|integer|min:1900|max:2100',
            'nomor_ln' => 'nullable|string|max:50',
            'tahun_ln' => 'nullable|integer|min:1900|max:2100|required_with:nomor_ln',
            'nomor_tln' => 'nullable|string|max:50',
            'nomor_bn' => 'nullable|string|max:50',
            'tahun_bn' => 'nullable|integer|min:1900|max:2100|required_with:nomor_bn',
            'jenis' => 'required|in:pertama,perubahan',
            'induk_id' => 'nullable|exists:dasar_hukum,id|required_if:jenis,perubahan',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validated['kategori'] === 'peraturan_kepala_badan') {
            $validated['instansi'] = 'Badan Pusat Statistik';
        }

        $dasarHukum = DasarHukum::create($validated);

        ActivityLog::log(
            'Tambah Dasar Hukum',
            'dasar_hukum',
            "Berhasil menambahkan dasar hukum: {$dasarHukum->kategori} Nomor {$dasarHukum->nomor} Tahun {$dasarHukum->tahun}",
            'success',
            ['dasar_hukum_id' => $dasarHukum->id, 'kategori' => $dasarHukum->kategori, 'nomor' => $dasarHukum->nomor]
        );

        return redirect()->route('dasar-hukum.index')
            ->with('success', 'Data dasar hukum sudah berhasil disimpan ke sistem.');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $editId = null;
        $encrypted = $request->input('encrypted');

        if ($encrypted) {
            $decrypted = decryptData($encrypted);
            if ($decrypted && isset($decrypted['id'])) {
                $editId = (int) $decrypted['id'];
            }
        }

        if ($editId === null) {
            $editId = (int) $request->session()->get('dasar_hukum_edit_id');
        }

        if (! $editId) {
            return redirect()->route('dasar-hukum.index')
                ->with('error', 'Sesi edit tidak ditemukan. Silakan pilih data untuk diedit kembali.');
        }

        $request->session()->put('dasar_hukum_edit_id', $editId);

        $dasarHukum = DasarHukum::findOrFail($editId);

        return Inertia::render('DasarHukum/Edit', [
            'dasarHukum' => $dasarHukum,
            'kategoriOptions' => [
                ['value' => 'undang_undang', 'label' => 'Undang-Undang'],
                ['value' => 'peraturan_pemerintah', 'label' => 'Peraturan Pemerintah'],
                ['value' => 'peraturan_presiden', 'label' => 'Peraturan Presiden'],
                ['value' => 'peraturan_menteri_badan', 'label' => 'Peraturan Menteri/Badan'],
                ['value' => 'keputusan_menteri_kepala_badan', 'label' => 'Keputusan Menteri/Kepala Badan'],
                ['value' => 'peraturan_kepala_badan', 'label' => 'Peraturan Kepala Badan (BPS)'],
            ],
            'dasarHukumList' => DasarHukum::query()
                ->where('jenis', 'pertama')
                ->where('id', '!=', $dasarHukum->id)
                ->select(['id', 'kategori', 'instansi', 'nomor', 'tentang', 'tahun', 'nomor_ln', 'tahun_ln', 'nomor_tln', 'nomor_bn', 'tahun_bn'])
                ->withCount('perubahan')
                ->orderBy('kategori')
                ->orderBy('tahun')
                ->get(),
        ]);
    }

    public function update(Request $request, DasarHukum $dasarHukum): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|in:undang_undang,peraturan_pemerintah,peraturan_presiden,peraturan_menteri_badan,keputusan_menteri_kepala_badan,peraturan_kepala_badan',
            'instansi' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $kategoriDenganInstansi = ['peraturan_menteri_badan', 'keputusan_menteri_kepala_badan'];
                    if (in_array($request->kategori, $kategoriDenganInstansi) && empty($value)) {
                        $fail('Nama instansi wajib diisi untuk kategori ini.');
                    }
                },
            ],
            'nomor' => 'required|string|max:255',
            'tentang' => 'required|string',
            'tahun' => 'required|integer|min:1900|max:2100',
            'nomor_ln' => 'nullable|string|max:50',
            'tahun_ln' => 'nullable|integer|min:1900|max:2100|required_with:nomor_ln',
            'nomor_tln' => 'nullable|string|max:50',
            'nomor_bn' => 'nullable|string|max:50',
            'tahun_bn' => 'nullable|integer|min:1900|max:2100|required_with:nomor_bn',
            'jenis' => 'required|in:pertama,perubahan',
            'induk_id' => 'nullable|exists:dasar_hukum,id|required_if:jenis,perubahan',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validated['kategori'] === 'peraturan_kepala_badan') {
            $validated['instansi'] = 'Badan Pusat Statistik';
        }

        $dasarHukum->update($validated);

        ActivityLog::log(
            'Ubah Dasar Hukum',
            'dasar_hukum',
            "Berhasil mengubah dasar hukum: {$dasarHukum->kategori} Nomor {$dasarHukum->nomor} Tahun {$dasarHukum->tahun}",
            'success',
            ['dasar_hukum_id' => $dasarHukum->id, 'kategori' => $dasarHukum->kategori, 'nomor' => $dasarHukum->nomor]
        );

        return redirect()->route('dasar-hukum.index')
            ->with('success', 'Perubahan data dasar hukum sudah berhasil disimpan.');
    }

    public function destroy(DasarHukum $dasarHukum): RedirectResponse
    {
        ActivityLog::log(
            'Hapus Dasar Hukum',
            'dasar_hukum',
            "Berhasil menghapus dasar hukum: {$dasarHukum->kategori} Nomor {$dasarHukum->nomor} Tahun {$dasarHukum->tahun}",
            'success',
            ['dasar_hukum_id' => $dasarHukum->id, 'kategori' => $dasarHukum->kategori, 'nomor' => $dasarHukum->nomor]
        );

        $dasarHukum->delete();

        return redirect()->route('dasar-hukum.index')
            ->with('success', 'Data dasar hukum sudah berhasil dihapus dari sistem.');
    }
}
