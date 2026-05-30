<?php

namespace App\Http\Controllers;

use App\Models\MasterFrameSampel;
use App\Models\MasterUnitSampel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SampleMasterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('MasterSampel/Index', [
            'frames' => MasterFrameSampel::query()->orderBy('nama')->get(),
            'units' => MasterUnitSampel::query()->orderBy('nama')->get(),
        ]);
    }

    public function storeFrame(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50', 'unique:master_frame_sampel,kode'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        MasterFrameSampel::create([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Master frame sampel berhasil ditambahkan.');
    }

    public function updateFrame(Request $request, MasterFrameSampel $frame): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50', 'unique:master_frame_sampel,kode,'.$frame->id],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $frame->update([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Master frame sampel berhasil diperbarui.');
    }

    public function destroyFrame(MasterFrameSampel $frame): RedirectResponse
    {
        $frame->delete();

        return back()->with('success', 'Master frame sampel berhasil dihapus.');
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50', 'unique:master_unit_sampel,kode'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        MasterUnitSampel::create([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Master unit sampel berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, MasterUnitSampel $unit): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50', 'unique:master_unit_sampel,kode,'.$unit->id],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit->update([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Master unit sampel berhasil diperbarui.');
    }

    public function destroyUnit(MasterUnitSampel $unit): RedirectResponse
    {
        $unit->delete();

        return back()->with('success', 'Master unit sampel berhasil dihapus.');
    }
}
