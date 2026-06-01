<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\KegiatanFrameSampel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KegiatanFrameSampelController extends Controller
{
    private function authorizeKegiatanAccess(Kegiatan $kegiatan): void
    {
        $effectiveUser = effectiveUser(request());

        if ($effectiveUser->hasActiveRole('ketua_tim') && ! ($kegiatan->ketua_tim_user_id === $effectiveUser->id || $kegiatan->pj_lainnya_id === $effectiveUser->id)) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola frame sampel kegiatan ini.');
        }
    }

    public function overview(): Response
    {
        $effectiveUser = effectiveUser(request());

        $query = Kegiatan::query()
            ->withCount('kegiatanFrameSampel');

        if ($effectiveUser->hasActiveRole('ketua_tim') && ! $effectiveUser->hasActiveRole('admin')) {
            $query->where(function ($q) use ($effectiveUser): void {
                $q->where('ketua_tim_user_id', $effectiveUser->id)
                    ->orWhere('pj_lainnya_id', $effectiveUser->id);
            });
        }

        $kegiatans = $query->latest()->get([
            'id', 'hashed_id', 'nama_kegiatan', 'kode_kegiatan', 'tahun_anggaran',
            'ketua_tim_user_id', 'pj_lainnya_id',
        ]);

        return Inertia::render('FrameSampel/Index', [
            'kegiatans' => $kegiatans,
        ]);
    }

    public function index(Kegiatan $kegiatan): Response
    {
        $this->authorizeKegiatanAccess($kegiatan);

        $kegiatan->load([
            'frameSampelListing:id,nama,kode',
            'frameSampelPencacahan:id,nama,kode',
            'kegiatanFrameSampel.frameSampel:id,nama,kode',
        ]);

        return Inertia::render('Kegiatan/FrameSampel', [
            'kegiatan' => $kegiatan,
            'frames' => $kegiatan->kegiatanFrameSampel,
            'unitSampelPencacahanItems' => $kegiatan->unitSampelPencacahanItems(),
            'unitSampelListingItems' => $kegiatan->unitSampelListingItems(),
        ]);
    }

    public function store(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizeKegiatanAccess($kegiatan);

        $data = $request->validate([
            'tahapan' => ['required', 'in:listing,pencacahan'],
            'nama_frame' => ['nullable', 'string', 'max:255'],
            'kode_kecamatan' => ['nullable', 'string', 'max:20'],
            'kode_desa' => ['nullable', 'string', 'max:20'],
            'kode_sls' => ['nullable', 'string', 'max:20'],
            'kode_sub_sls' => ['nullable', 'string', 'max:20'],
            'kode_segmen' => ['nullable', 'string', 'max:20'],
            'target_unit_sampel' => ['required', 'array', 'min:1'],
            'target_unit_sampel.*' => ['integer', 'min:0'],
        ]);

        if ($data['tahapan'] === 'listing' && ! $kegiatan->has_listing_updating) {
            throw ValidationException::withMessages([
                'tahapan' => 'Tahapan listing tidak aktif untuk kegiatan ini.',
            ]);
        }

        if (array_sum($data['target_unit_sampel']) <= 0) {
            throw ValidationException::withMessages([
                'target_unit_sampel' => 'Total target unit sampel harus lebih dari 0.',
            ]);
        }

        $frameSampelId = $data['tahapan'] === 'listing'
            ? $kegiatan->frame_sampel_listing_id
            : $kegiatan->frame_sampel_pencacahan_id;

        if (! $frameSampelId) {
            return back()->withErrors([
                'frame' => 'Frame sampel untuk tahapan ini belum diatur pada data kegiatan.',
            ]);
        }

        KegiatanFrameSampel::create([
            'kegiatan_id' => $kegiatan->id,
            'frame_sampel_id' => $frameSampelId,
            ...$data,
        ]);

        return back()->with('success', 'Daftar frame sampel kegiatan berhasil ditambahkan.');
    }

    public function update(Request $request, Kegiatan $kegiatan, KegiatanFrameSampel $frame): RedirectResponse
    {
        $this->authorizeKegiatanAccess($kegiatan);

        if ((int) $frame->kegiatan_id !== (int) $kegiatan->id) {
            abort(404);
        }

        if ($frame->tahapan === 'listing' && ! $kegiatan->has_listing_updating) {
            throw ValidationException::withMessages([
                'tahapan' => 'Tahapan listing tidak aktif untuk kegiatan ini.',
            ]);
        }

        $data = $request->validate([
            'nama_frame' => ['nullable', 'string', 'max:255'],
            'kode_kecamatan' => ['nullable', 'string', 'max:20'],
            'kode_desa' => ['nullable', 'string', 'max:20'],
            'kode_sls' => ['nullable', 'string', 'max:20'],
            'kode_sub_sls' => ['nullable', 'string', 'max:20'],
            'kode_segmen' => ['nullable', 'string', 'max:20'],
            'target_unit_sampel' => ['required', 'array', 'min:1'],
            'target_unit_sampel.*' => ['integer', 'min:0'],
        ]);

        if (array_sum($data['target_unit_sampel']) <= 0) {
            throw ValidationException::withMessages([
                'target_unit_sampel' => 'Total target unit sampel harus lebih dari 0.',
            ]);
        }

        $frame->update($data);

        return back()->with('success', 'Daftar frame sampel kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan, KegiatanFrameSampel $frame): RedirectResponse
    {
        $this->authorizeKegiatanAccess($kegiatan);

        if ((int) $frame->kegiatan_id !== (int) $kegiatan->id) {
            abort(404);
        }

        $frame->delete();

        return back()->with('success', 'Daftar frame sampel kegiatan berhasil dihapus.');
    }
}
